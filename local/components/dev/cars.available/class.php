<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();


use Bitrix\Main\Loader,
    Bitrix\Main\SystemException,
    Bitrix\Main\Localization\Loc,
    Bitrix\Highloadblock as HL;

class CListCarAvailable extends CBitrixComponent
{
    private $iblockCarsId;
    private $iblockTripsId;
    public function executeComponent(): void
    {
        try {
            $this->checkModules();
            $this->initializeIblockIds();
            $this->getResult();
        }
        catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }
    public function onIncludeComponentLang(): void
    {
        Loc::loadMessages(__FILE__);
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     * @throws SystemException
     */
    protected function checkModules(): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException(GetMessage('IBLOCK_MODULE_NOT_INSTALLED'));
        }
        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException(GetMessage('HLBLOCK_MODULE_NOT_INSTALLED'));
        }
    }

    /**
     * @throws SystemException
     */
    private function initializeIblockIds(): void
    {
        $this->iblockCarsId = $this->getIblockIdByCode('CARS');
        $this->iblockTripsId = $this->getIblockIdByCode('TRIPS');
    }

    /**
     * @throws SystemException
     */
    private function getIblockIdByCode(string $code): int
    {
        $iblock = CIBlock::GetList([], ['CODE' => $code])->Fetch();
        if (!$iblock) {
            throw new SystemException("IBlock with code '{$code}' not found");
        }
        return (int)$iblock['ID'];
    }

    /**
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    protected function getResult(): void
    {
        global $USER;
        $userId = $USER->GetID();
        $user = CUser::GetByID($userId)->Fetch();
        $jobPositionId = $user['UF_JOB_POSITION'] ?? null;;

        if ($jobPositionId) {
            $comfortCategories = $this->getUserCategories($jobPositionId);
            $carsIdsSuitable= $this->getCarsIdsSuitable($comfortCategories);
            $carIdsAvailable = $this->getCarsIdsAvailable($carsIdsSuitable);
            $this->fetchAvailableCars($carIdsAvailable);
        }
    }

    protected function fetchAvailableCars($carIdsAvailable): void
    {
        $elements = [];
        $rows = CIBlockElement::GetList(
            [],
            ['ID' => $carIdsAvailable,]
        );

        while ($row = $rows->Fetch()) {
            $row['PROPERTIES'] = [];
            $elements[$row['ID']] =& $row;
            unset($row);
        }
        CIBlockElement::GetPropertyValuesArray($elements, $this->iblockCarsId, ['IBLOCK_ID' => $this->iblockCarsId]);

        foreach ($elements as &$element) {
            $driver = CUser::GetByID($element['PROPERTIES']['DRIVER']['VALUE'])->Fetch();
            $this->arResult['CARS'][] = [
                'MODEL' => $element['NAME'],
                'CATEGORY_COMFORT' => $element['PROPERTIES']['CATEGORY_COMFORT']['VALUE'],
                'DRIVER' => $driver['LAST_NAME'] . ' ' . $driver['NAME'],
            ];
        }
    }

    protected function getUserCategories(int $jobPositionId): array
    {
        $hlblock = HL\HighloadBlockTable::getList(['filter' => ['=NAME' => 'JobPosition'], 'limit' => 1,])->Fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entityDataClass = $entity->getDataClass();
        $result = $entityDataClass::getList([
            'select' => ['UF_COMFORT_CATEGORIES'],
            'filter' => ['ID' => $jobPositionId]
        ])->Fetch();
        $comfortCategories = [];
        if ($result) {
            $comfortCategoryIds = $result['UF_COMFORT_CATEGORIES'];

            if (!empty($comfortCategoryIds) && is_array($comfortCategoryIds)) {
                $enumQuery = \CUserFieldEnum::GetList([], ['ID' => $comfortCategoryIds]);

                while ($enum = $enumQuery->Fetch()) {
                    $comfortCategories[] = $enum['VALUE'];
                }
            }
        }
        return $comfortCategories;
    }

    protected function getCarsIdsSuitable($comfortCategories): array
    {
        $carsIdsSuitable = [];
        $propertyCars = CIBlockProperty::GetList([], ['IBLOCK_ID' => $this->iblockCarsId, 'CODE' => 'CATEGORY_COMFORT'])->Fetch();
        $propertyCarsId = $propertyCars['ID'];
        $result = CIBlockPropertyEnum::GetList(
            [],
            ['PROPERTY_ID' => $propertyCarsId, 'VALUE' => $comfortCategories]
        );
        $enumIds = [];
        while ($enum = $result->Fetch()) {
            $enumIds[] = $enum['ID'];
        }
        $resultByProperty = CIBlockElement::GetList(
            [],
            ['PROPERTY_' . $propertyCarsId=>$enumIds],
            false,
            false,
            ['ID', 'IBLOCK_ID']
        );
        while ($element = $resultByProperty->Fetch()) {
            $carsIdsSuitable[] = $element['ID'];
        }
        return $carsIdsSuitable;
    }

    protected function getCarsIdsAvailable($carsIdsAvailableByCategory): array
    {
        $carsIdsNotAvailableByTime = $this->filterCarsInTrips($this->iblockTripsId);

        return array_diff($carsIdsAvailableByCategory, $carsIdsNotAvailableByTime);
    }

    protected function filterCarsInTrips($iblockTripsId): array
    {
        global $DB;
        $start_date = $_GET['start_date'] ?? '';
        $finish_date = $_GET['finish_date'] ?? '';
        $sql = "
        SELECT
            FPV0.VALUE as CarId,
            STR_TO_DATE(FPV1.VALUE, '%Y-%m-%d %H:%i:%s') as Sb,
            STR_TO_DATE(FPV2.VALUE, '%Y-%m-%d %H:%i:%s') as Fb,
            STR_TO_DATE('" . $start_date . "', '%Y-%m-%d %H:%i:%s') as Sa,
            STR_TO_DATE('" . $finish_date . "', '%Y-%m-%d %H:%i:%s') as Fa
        FROM
            b_iblock_element BE
            INNER JOIN b_iblock_property FP0 ON FP0.CODE = 'CAR'
            INNER JOIN b_iblock_property FP1 ON FP1.CODE = 'START_DATE'
            INNER JOIN b_iblock_property FP2 ON FP2.CODE = 'FINISH_DATE'
            INNER JOIN b_iblock_element_property FPV0
                ON FPV0.IBLOCK_PROPERTY_ID = FP0.ID AND FPV0.IBLOCK_ELEMENT_ID = BE.ID
            INNER JOIN b_iblock_element_property FPV1
                ON FPV1.IBLOCK_PROPERTY_ID = FP1.ID AND FPV1.IBLOCK_ELEMENT_ID = BE.ID
            INNER JOIN b_iblock_element_property FPV2
                ON FPV2.IBLOCK_PROPERTY_ID = FP2.ID AND FPV2.IBLOCK_ELEMENT_ID = BE.ID
        WHERE BE.IBLOCK_ID = '" . $iblockTripsId . "'
        HAVING (Sa <= Fb AND Fa >= Sb) OR (Sa >= Sb AND Fa <= Fb);
        ";
        $result = $DB->Query($sql);
        $carsIdsOccupied = [];

        while ($row = $result->Fetch()) {
            $carsIdsOccupied[] = $row['CarId'];
        }
        return $carsIdsOccupied;
    }
}
