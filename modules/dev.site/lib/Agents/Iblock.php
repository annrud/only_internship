<?php

namespace Dev\Site\Agents;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CIBlock;
use CIBlockElement;

class Iblock
{
    /**
     * @throws LoaderException
     */
    public static function clearOldLogs(): string
    {
        if (Loader::includeModule('iblock'))
        {
            $iblock = CIBlock::GetList([], ['CODE' => 'LOG'])->Fetch();
            if (!$iblock) {
                return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
            }
            $arSelect = ['ID', 'IBLOCK_SECTION_ID'];
            $arFilter = ['IBLOCK_ID' => $iblock['ID']];
            $arOrder = ['TIMESTAMP_X' => 'DESC'];
            $elements = CIBlockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
            $i = 0;
            while ($element = $elements->GetNext()) {
                if (++$i > 10) {
                    CIBlockElement::Delete($element['ID']);
                }
            }
        }

        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
