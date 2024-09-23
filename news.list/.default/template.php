<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(false);
?>
<div id="barba-wrapper">
    <?if($arParams["DISPLAY_TOP_PAGER"]):?>
        <?=$arResult["NAV_STRING"]?><br />
    <?endif;?>
    <div class="article-list">
    <?foreach($arResult["ITEMS"] as $arItem):?>
        <a class="article-item article-list__item" href="<?=$arItem["DETAIL_PAGE_URL"]?>"
           data-anim="anim-3" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
            <?
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
            ?>
            <div class="article-item__background">
                <img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"/>
            </div>
            <div class="article-item__wrapper">
                <div class="article-item__title"><?echo $arItem["NAME"]?></div>
                <div class="article-item__content"><?echo $arItem["PREVIEW_TEXT"];?></div>
            </div>
        </a>
    <?endforeach;?>
    </div>
    <?if($arParams["DISPLAY_BOTTOM_PAGER"]):?>
        <br /><?=$arResult["NAV_STRING"]?>
    <?endif;?>
</div>
