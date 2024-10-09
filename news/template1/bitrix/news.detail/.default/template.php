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
$this->setFrameMode(true);
?>
<div class="article-card">
    <div class="article-card__title">
        <?if($arParams["DISPLAY_NAME"]!="N" && $arResult["NAME"]):?>
            <?=$arResult["NAME"]?>
        <?endif;?>
    </div>
    <div class="article-card__date">
        <?if($arParams["DISPLAY_DATE"]!="N" && $arResult["DISPLAY_ACTIVE_FROM"]):?>
            <?
                $dateTime = DateTime::createFromFormat('d.m.Y', $arResult["DISPLAY_ACTIVE_FROM"]);
                $date = strftime('%d %b %Y', $dateTime->getTimestamp());
            ?>
            <span class="news-date-time"><?=$date?></span>
        <?endif;?>
    </div>
    <div class="article-card__content">
        <?if($arParams["DISPLAY_PICTURE"] != "N" && is_array($arResult["DETAIL_PICTURE"])):?>
            <div class="article-card__image sticky">
                <img
                    data-object-fit="cover"
                    src="<?=$arResult["DETAIL_PICTURE"]["SRC"]?>"
                    alt="<?=$arResult["DETAIL_PICTURE"]["ALT"]?>"
                    title="<?=$arResult["DETAIL_PICTURE"]["TITLE"]?>"
                />
            </div>
        <?endif;?>
        <div class="article-card__text">
            <div class="block-content" data-anim="anim-3">
                <?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && ($arResult["FIELDS"]["PREVIEW_TEXT"] ?? '') !== ''):?>
                    <p><?=$arResult["FIELDS"]["PREVIEW_TEXT"];unset($arResult["FIELDS"]["PREVIEW_TEXT"]);?></p>
                <?endif;?>
                <?if($arResult["DETAIL_TEXT"] <> ''):?>
                    <?echo $arResult["DETAIL_TEXT"];?>
                <?else:?>
                    <?echo $arResult["PREVIEW_TEXT"];?>
                <?endif?>
                <?foreach($arResult["FIELDS"] as $code=>$value):
                    if ('PREVIEW_PICTURE' == $code || 'DETAIL_PICTURE' == $code)
                    {
                        ?><?=GetMessage("IBLOCK_FIELD_".$code)?>:&nbsp;<?
                        if (!empty($value) && is_array($value))
                        {
                            ?><img src="<?=$value["SRC"]?>" width="<?=$value["WIDTH"]?>" height="<?=$value["HEIGHT"]?>"><?
                        }
                    }
                    else
                    {
                        ?><?=GetMessage("IBLOCK_FIELD_".$code)?>:&nbsp;<?=$value;?><?
                    }
                    ?>
                <?endforeach;
                foreach($arResult["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>

                    <?=$arProperty["NAME"]?>:&nbsp;
                    <?if(is_array($arProperty["DISPLAY_VALUE"])):?>
                        <?=implode("&nbsp;/&nbsp;", $arProperty["DISPLAY_VALUE"]);?>
                    <?else:?>
                        <?=$arProperty["DISPLAY_VALUE"];?>
                    <?endif?>
                    <br />
                <?endforeach;?>
                <a class="article-card__button" href="<?=$arResult["LIST_PAGE_URL"]?>"><?=GetMessage("T_NEWS_DETAIL_BACK")?></a>
            </div>
        </div>
    </div>
</div>