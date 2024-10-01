<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>
<?
function renderInput(array $arResult, string $FIELD_SID): void
{
    $arQuestion = $arResult['QUESTIONS'][$FIELD_SID];
    ?>
    <div class="input<?=in_array($arQuestion['STRUCTURE'][0]['FIELD_TYPE'], ['text', 'email']) ? ' contact-form__input' : ''?>">
        <label class="input__label">
            <div class="input__label-text">
                <?=$arQuestion['CAPTION']?>
                <?if ($arQuestion['REQUIRED'] == 'Y'):?>
                    <?=$arResult['REQUIRED_SIGN'];?>
                <?endif;?>
            </div>
            <?if(in_array($arQuestion['STRUCTURE'][0]['FIELD_TYPE'], ['text', 'email'])):?>
                <input type="<?=$arQuestion['STRUCTURE'][0]['FIELD_TYPE']?>" class="input__input" name="form_<?=$arQuestion['STRUCTURE'][0]['FIELD_TYPE']?>_<?=$arQuestion['STRUCTURE'][0]['ID']?>" value="<?=$arQuestion['STRUCTURE'][0]['VALUE']?>"/>
            <?endif;?>
            <?if($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] === 'textarea'):?>
                <textarea class="input__input" name="form_textarea_<?=$arQuestion['STRUCTURE'][0]['ID']?>"><?=$arQuestion['STRUCTURE'][0]['VALUE']?></textarea>
            <?endif;?>
            <?if (is_array($arResult["FORM_ERRORS"]) && array_key_exists($FIELD_SID, $arResult['FORM_ERRORS'])):?>
                <div class="input__notification input__notification--error">
                    <?=htmlspecialcharsbx($arResult['FORM_ERRORS'][$FIELD_SID])?>
                </div>
            <?endif;?>
        </label>
    </div>
    <?
}
?>
<?if ($arResult['isFormErrors'] == 'Y'):?>
    <?=$arResult['FORM_ERRORS_TEXT'];?>
<?endif;?>
<?=$arResult['FORM_NOTE']?>
<?if ($arResult['isFormNote'] != 'Y'):?>
    <div class="contact-form">
        <div class="contact-form__head">
            <?if ($arResult['isFormTitle'] == 'Y'):?>
                <div class="contact-form__head-title"><?=$arResult['FORM_TITLE']?></div>
            <?endif;?>
            <?if ($arResult['isFormDescription'] == 'Y'):?>
                <div class="contact-form__head-text"><?=$arResult['FORM_DESCRIPTION']?></div>
            <?endif;?>
        </div>
        <form class="contact-form__form" action="/feedback/" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="sessid" id="sessid" value="<?=$_SESSION['fixed_session_id']?>" />
            <input type="hidden" name="WEB_FORM_ID" value="<?=$arResult['arForm']['ID']?>" />
            <div class="contact-form__form-inputs">
                <?foreach ($arResult['QUESTIONS'] as $FIELD_SID => $arQuestion):?>
                    <?if ($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'hidden'):?>
                        <?=$arQuestion['HTML_CODE']?>
                    <?endif;?>
                <?endforeach;?>
                <?renderInput($arResult, 'name');?>
                <?renderInput($arResult, 'company');?>
                <?renderInput($arResult, 'email');?>
                <?renderInput($arResult, 'phone');?>
            </div>
            <div class="contact-form__form-message">
                <?renderInput($arResult, 'message');?>
            </div>
            <div class="contact-form__bottom">
                <div class="contact-form__bottom-policy">
                    Нажимая &laquo;Отправить&raquo;, Вы&nbsp;подтверждаете, что
                    ознакомлены, полностью согласны и&nbsp;принимаете условия &laquo;Согласия на&nbsp;обработку персональных
                    данных&raquo;.
                </div>
                <button class="form-button contact-form__bottom-button" data-success="Отправлено" data-error="Ошибка отправки">
                    <div class="form-button__title">
                        <?=htmlspecialcharsbx(trim($arResult['arForm']['BUTTON']) == '' ? GetMessage('FORM_ADD') : $arResult['arForm']['BUTTON']);?>
                    </div>
                </button>
                <?if ($arResult['F_RIGHT'] >= 15):?>
                    <input type="hidden" name="web_form_apply" value="Y" />
                <?endif;?>
            </div>
        </form>
    </div>
<?endif;?>
