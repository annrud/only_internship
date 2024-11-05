<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
    Bitrix\Iblock,
    Bitrix\Main\SystemException;

class CListElementsComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        if (empty($arParams["IBLOCK_TYPE"]) || !is_string($arParams["IBLOCK_TYPE"])) {
            ShowError("Тип инфоблока не задан или неверен");
            return [];
        }
        if (!empty($arParams["IBLOCK_ID"]) && !is_numeric($arParams["IBLOCK_ID"])) {
            ShowError("ID инфоблока должен быть числом");
            return [];
        }
        $arParams["DETAIL_URL"] = isset($arParams["DETAIL_URL"]) && is_string($arParams["DETAIL_URL"]) ? $arParams["DETAIL_URL"] : '';
        $arParams["PREVIEW_TRUNCATE_LEN"] = isset($arParams["PREVIEW_TRUNCATE_LEN"]) && is_numeric($arParams["PREVIEW_TRUNCATE_LEN"])
            ? (int)$arParams["PREVIEW_TRUNCATE_LEN"]
            : 0;
        foreach ([
                     "SET_META_KEYWORDS",
                     "SET_META_DESCRIPTION",
                     "SET_LAST_MODIFIED",
                     "INCLUDE_IBLOCK_INTO_CHAIN",
                     "ADD_SECTIONS_CHAIN",
                     "HIDE_LINK_WHEN_NO_DETAIL",
                     "STRICT_SECTION_CHECK",
                     "CACHE_FILTER",
                     "CACHE_GROUPS"
                 ] as $param) {
            $arParams[$param] = $arParams[$param] === "Y" ? "Y" : "N";
        }
        $arParams["NEWS_COUNT"] = max(1, (int)($arParams["NEWS_COUNT"] ?? 20));
        $arParams["INCLUDE_SUBSECTIONS"] = $arParams["INCLUDE_SUBSECTIONS"] !== 'N';
        $arParams['DISPLAY_TOP_PAGER'] = $arParams['DISPLAY_TOP_PAGER'] == 'Y';
        $arParams['DISPLAY_BOTTOM_PAGER'] = $arParams['DISPLAY_BOTTOM_PAGER'] == 'Y';

        $arParams["PARENT_SECTION_CODE"] = isset($arParams["PARENT_SECTION_CODE"]) && is_string($arParams["PARENT_SECTION_CODE"])
            ? $arParams["PARENT_SECTION_CODE"]
            : '';
        return $arParams;
    }

    public function executeComponent(): void
    {
        try {
            $this->checkModules();
            $iblockIds = $this->getIblockIds();

            if (!empty($iblockIds)) {
                $this->getItems($iblockIds);
            } else {
                $this->arResult['ITEMS'] = [];
            }

            $this->includeComponentTemplate();
        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
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
    }

    protected function getIblockIds(): array
    {
        if (!empty($this->arParams['IBLOCK_IDS'])) {
            return $this->arParams['IBLOCK_IDS'];
        }

        $iblockIds = [];

        if ($this->arParams['IBLOCK_TYPES']) {
            $list = Iblock\IblockTable::getList([
                'filter' => ['IBLOCK_TYPE_ID' => $this->arParams['IBLOCK_TYPES'], 'ACTIVE' => 'Y'],
            ]);
            while ($iblock = $list->fetch()) {
                $iblockIds[] = $iblock['ID'];
                $this->arResult['ITEMS'][$iblock['ID']] = $iblock;
            }
        }

        return $iblockIds;
    }

    protected function getItems($iblockIds): void
    {
        $arNavParams = [
            'nPageSize' => $this->arParams["NEWS_COUNT"],
            'iNumPage' => $_GET['PAGEN_1'] ?? 1,
        ];

        $res = CIBlockElement::GetList(
            ['IBLOCK_ID' => 'ASC', 'SORT' => 'ASC'],
            ['IBLOCK_ID' => $iblockIds, 'ACTIVE' => 'Y'],
            false,
            $arNavParams,
            ['*', 'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL']
        );

        while ($item = $res->fetch()) {
            $item['DETAIL_PAGE_URL'] = CIBlock::ReplaceDetailUrl($item['DETAIL_PAGE_URL'], $item, false, 'E');
            $this->arResult['ITEMS'][$item['IBLOCK_ID']]['ITEMS'][] = $item;
        }

        $this->arResult["NAV_STRING"] = $res->GetPageNavStringEx(
            $navComponentObject,
            $this->arParams["PAGER_TITLE"] ?? '',
            $this->arParams["PAGER_TEMPLATE"] ?? '',
            $this->arParams["PAGER_SHOW_ALWAYS"] === 'Y',
            $this,
            []
        );
    }
}
