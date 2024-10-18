<?php

use Bitrix\Main\Loader;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}
Loader::includeModule('iblock');

$row = 1;

$iblock = CIBlock::GetList([], ['CODE' => 'VACANCIES'])->GetNext();
$IBLOCK_ID = $iblock['ID'];

$el = new CIBlockElement;
$enum = new CIBlockPropertyEnum;

$propertyTypes = [];
$propertyEnumValues = [];
$propertyEnumValuesLower = [];

$properties = CIBlockProperty::GetList($arFilter=['IBLOCK_ID' => $IBLOCK_ID]);

while ($property = $properties->GetNext()) {
    $propertyTypes[$property['CODE']]=[$property['PROPERTY_TYPE'], $property['ID']];
}

$propertyEnums = CIBlockPropertyEnum::GetList(
    $arFilter=['IBLOCK_ID' => $IBLOCK_ID]
);

while ($propertyEnum = $propertyEnums->Fetch()) {
    $value = trim($propertyEnum['VALUE']);
    $propertyEnumValues[$propertyEnum['PROPERTY_CODE']][$value] = $propertyEnum['ID'];

    $valueLower = mb_strtolower(trim($value), 'UTF-8');
    $propertyEnumValuesLower[$propertyEnum['PROPERTY_CODE']][$valueLower] = $propertyEnum['ID'];
}
$rsElements = CIBlockElement::GetList(
    $arOrder=[],
    $arFilter=['IBLOCK_ID' => $IBLOCK_ID],
    $arGroupBy=false,
    $arNavStartParams=false,
    $arSelectFields=['ID']
);

while ($element = $rsElements->GetNext()) {
    CIBlockElement::Delete($element['ID']);
}

function processSalaryType(&$value, &$PROP, $propertyEnumValuesLower): void {
    if ($value == '-' || $value == '') {
        $value = '';
        $PROP['SALARY_TYPE'] = '';
    } elseif ($value == 'по договоренности') {
        $value = '';
        $PROP['SALARY_TYPE'] = $propertyEnumValuesLower['SALARY_TYPE']['договорная'];
    } else {
        $arSalary = explode(' ', $value);
        if ($arSalary[0] == 'от' || $arSalary[0] == 'до') {
            $PROP['SALARY_TYPE'] = $propertyEnumValuesLower['SALARY_TYPE'][$arSalary[0]];
            array_splice($arSalary, 0, 1);
            $value = implode(' ', $arSalary);
        } else {
            $PROP['SALARY_TYPE'] = $propertyEnumValuesLower['SALARY_TYPE']['='];
        }
    }
}

function processEnumValue(
    &$value,
    $key,
    $enum,
    $IBLOCK_ID,
    $propertyTypes,
    &$propertyEnumValues,
    $propertyEnumValuesLower
): void {
    $valueLower = mb_strtolower(trim($value), 'UTF-8');
    if (array_key_exists($valueLower, $propertyEnumValuesLower[$key])) {
        $value = $propertyEnumValues[$key][$value];
        return;
    }
    foreach ($propertyEnumValues[$key] as $enumValue => $enumId) {
        $enumValueLower = mb_strtolower(trim($enumValue), 'UTF-8');
        if (stripos($enumValueLower, $valueLower) !== false) {
            $value = $enumId;
            return;
        }
    }
    if (!isset($propertyEnumValues[$key][$value])) {
        $PROPERTY_ENUM_ID = $enum->Add([
            'IBLOCK_ID' => $IBLOCK_ID,
            'PROPERTY_ID' => $propertyTypes[$key][1],
            'VALUE' => $value
        ]);
        $propertyEnumValues[$key][$value] = $PROPERTY_ENUM_ID;
    }
    $value = $propertyEnumValues[$key][$value];
}

if (($handle = fopen("vacancy.csv", "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if ($row == 1) {
            $row++;
            continue;
        }
        $row++;
        $PROP = [
            'ACTIVITY' => $data[9],
            'FIELD' => $data[11],
            'OFFICE' => $data[1],
            'LOCATION' => $data[2],
            'REQUIRE' => $data[4],
            'DUTY' => $data[5],
            'CONDITIONS' => $data[6],
            'EMAIL' => $data[12],
            'DATE' => date('d.m.Y'),
            'TYPE' => $data[8],
            'SALARY_TYPE' => '',
            'SALARY_VALUE' => $data[7],
            'SCHEDULE' => $data[10],
        ];

        foreach ($PROP as $key => &$value) {
            $value = str_replace(["\n", "\t"], ' ', $value);
            $value = trim($value);

            if ($key == 'SALARY_VALUE'){
                processSalaryType($value, $PROP, $propertyEnumValuesLower);
                continue;
            }
            if (stripos($value, '•') !== false) {
                $value = str_replace(['.', ';'], '', $value);
                $value = explode('•', $value);
                array_splice($value, 0, 1);
            }
            if ($propertyTypes[$key][0] == 'L' && $value != '') {
                processEnumValue(
                    $value,
                    $key,
                    $enum,
                    $IBLOCK_ID,
                    $propertyTypes,
                    $propertyEnumValues,
                    $propertyEnumValuesLower
                );
            }
        }
        $arLoadProductArray = [
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $data[3],
            "ACTIVE" => end($data) ? 'Y' : 'N',
        ];
        if ($PRODUCT_ID = $el->Add($arFields=$arLoadProductArray)) {
            echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
        } else {
            echo "Error: " . $el->LAST_ERROR . '<br>';
        }
        unset($PROP);
    }
    fclose($handle);
}
