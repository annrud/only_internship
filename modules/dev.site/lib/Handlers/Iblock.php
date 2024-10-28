<?php

namespace Dev\Site\Handlers;

use CIBlock;
use CIBlockElement;
use CIBlockSection;

class Iblock
{
    /**
     * Добавление или обновление элемента инфоблока LOG
     */
    public static function addLog(&$arFields): void
    {
        $iblockId = $arFields['IBLOCK_ID'];
        $iblockSectionId = $arFields['IBLOCK_SECTION'][0];
        $elementId = $arFields['ID'];
        $elementName = $arFields['NAME'];
        $userId = $arFields['CREATED_BY'];

        $iblockLog = CIBlock::GetList([], ['CODE' => 'LOG'])->Fetch();
        if ($iblockId == $iblockLog['ID']) {
            return;
        }

        $element = CIBlockElement::GetByID($elementId)->Fetch();
        $date = $element['TIMESTAMP_X'] ?? date('d.m.Y H:i:s');

        $iblock = CIBlock::GetByID($iblockId)->Fetch();

        $elementPreview = self::generateElementPreview($iblockSectionId, $iblock['NAME'], $elementName);

        $sectionLogId = $iblockSectionId
            ? self::getOrCreateSectionLog($iblockLog['ID'], $iblockSectionId)
            : self::getOrCreateSectionLogMain($iblockLog['ID'], $iblockId);

        $elementLog= new CIBlockElement;
        $arLoadArray = [
            'MODIFIED_BY' => $userId,
            'IBLOCK_ID' => $iblockLog['ID'],
            'NAME' => $elementId,
            'CODE' => $elementId,
            'ACTIVE' => 'Y',
            'ACTIVE_FROM' => $date,
            'IBLOCK_SECTION_ID' => $sectionLogId,
            'PREVIEW_TEXT' => $elementPreview
        ];
        $arFilter = [
            'IBLOCK_ID' => $iblockLog['ID'],
            'NAME' => $elementId
        ];
        $elementDesired = CIBlockElement::GetList([], $arFilter, false, false, ['ID'])->Fetch();

        if ($elementDesired) {
            if ($elementLog->Update($elementDesired['ID'], $arLoadArray)) {
                echo 'Элемент успешно обновлен.';
            } else {
                echo 'Ошибка при обновлении элемента: ' . $elementLog->LAST_ERROR;
            }
        } else {
            if ($elementLog->Add($arLoadArray)) {
                echo 'Элемент успешно добавлен.';
            } else {
                echo 'Ошибка при добавлении элемента: ' . $elementLog->LAST_ERROR;
            }
        }
    }
    /**
     * Поиск или создание раздела в инфоблоке LOG
     */
    private static function getOrCreateSectionLog($iblockLogId, $sectionId)
    {
        $section = CIBlockSection::GetByID($sectionId)->Fetch();
        $sectionLog = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockLogId, 'CODE' => $section['CODE']],
            false,
            ['ID']
        )->Fetch();

        if ($sectionLog) {
            return $sectionLog['ID'];
        }
        $sectionLog = new CIBlockSection;
        $arFields = [
            'IBLOCK_ID' => $iblockLogId,
            'NAME' => $section['NAME'],
            'CODE' => $section['CODE'],
            'IBLOCK_SECTION_ID' => $section['IBLOCK_SECTION_ID']
                ? self::getOrCreateSectionLog($iblockLogId, $section['IBLOCK_SECTION_ID'])
                : self::getOrCreateSectionLogMain($iblockLogId, $section['IBLOCK_ID']),
            'ACTIVE' => 'Y'
        ];

        return $sectionLog->Add($arFields);
    }
    /**
     * Поиск или создание главного раздела в инфоблоке LOG
     */
    private static function getOrCreateSectionLogMain($iblockLogId, $iblockId)
    {
        $iblockMain = CIBlock::GetByID($iblockId)->Fetch();
        $iblockLogMain = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockLogId, 'CODE' => $iblockMain['CODE']],
            false,
            ['ID']
        )->Fetch();
        if ($iblockLogMain) {
            return $iblockLogMain['ID'];
        }
        $sectionLog = new CIBlockSection;
        $arFields = [
            'IBLOCK_ID' => $iblockLogId,
            'NAME' => $iblockMain['NAME'],
            'CODE' => $iblockMain['CODE'],
            'ACTIVE' => 'Y'
        ];

        return $sectionLog->Add($arFields);
    }
    /**
     * Наполнение массива наименованиями разделов
     */
    private static function getSectionNames($sectionId, &$result): void
    {
        if (!$sectionId) {
            return;
        }
        $section = CIBlockSection::GetByID($sectionId)->Fetch();
        if (!$section) {
            return;
        }
        array_unshift($result, $section['NAME']);
        self::getSectionNames($section['IBLOCK_SECTION_ID'], $result);
    }

    /**
     * Генерация строки для "Описание для анонса"
     */
    private static function generateElementPreview($iblockSectionId, $iblockName, $elementName): string
    {
        $sectionNames = [];

        if (!empty($iblockSectionId)) {
            self::getSectionNames($iblockSectionId, $sectionNames);
        }

        return implode('->', array_filter([
            $iblockName,
            $sectionNames ? implode('->', $sectionNames) : '',
            $elementName,
        ]));
    }
}
