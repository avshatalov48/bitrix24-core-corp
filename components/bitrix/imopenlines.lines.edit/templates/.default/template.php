<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\Imopenlines\Limit;

/**
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 */

Loc::loadMessages(__FILE__);

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'ui.alerts',
	'ui.hint',
	'ui.entity-selector',
	'ui.buttons',
	'ui.forms',
	'socnetlogdest',
	'sidepanel',
]);
if (Loader::includeModule('bitrix24'))
{
	$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '', []);
	\CBitrix24::initLicenseInfoPopupJS();
}

$APPLICATION->SetTitle($arResult['PAGE_TITLE']);

$isBitrix24Template = (SITE_TEMPLATE_ID == 'bitrix24');
if($isBitrix24Template)
{
	$bodyClass = $APPLICATION->getPageProperty('BodyClass');
	$APPLICATION->setPageProperty("BodyClass", ($bodyClass ? $bodyClass . " " : "") . "no-all-paddings no-background");
}
?>
<script>
	BX.ready(function(){
		BX.OpenLinesConfigEdit.init(
			{
				isSuccessSendForm: <?=CUtil::PhpToJSObject($arResult['IS_SUCCESS_SEND_FORM'])?>
			}
		);
	});
	BX.message({
		'IMOL_CONFIG_EDIT_POPUP_LIMITED_TITLE_DEFAULT': '<?=GetMessageJS('IMOL_CONFIG_EDIT_POPUP_LIMITED_TITLE_DEFAULT')?>',
		'IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_CUSTOMER_RATE': '<?=Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_CUSTOMER_RATE?>',
		'IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_WORKHOUR_SETTING': '<?=Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_WORKHOUR_SETTING?>',
		'IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_MESSAGE_TO_ALL': '<?=Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_MESSAGE_TO_ALL?>',
		'IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_QUICK_ANSWERS': '<?=Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_QUICK_ANSWERS?>',
		'IMOL_CONFIG_EDIT_NO_ANSWER_RULE_FORM': '<?=GetMessageJS('IMOL_CONFIG_EDIT_NO_ANSWER_RULE_FORM')?>',
		'IMOL_CONFIG_EDIT_NO_ANSWER_RULE_TEXT': '<?=GetMessageJS('IMOL_CONFIG_EDIT_NO_ANSWER_RULE_TEXT')?>',
		'IMOL_CONFIG_EDIT_NO_ANSWER_RULE_QUEUE': '<?=GetMessageJS('IMOL_CONFIG_EDIT_NO_ANSWER_RULE_QUEUE')?>',
		'IMOL_CONFIG_EDIT_NO_ANSWER_RULE_NONE': '<?=GetMessageJS('IMOL_CONFIG_EDIT_NO_ANSWER_RULE_NONE')?>',
		'IMOL_CONFIG_EDIT_QUEUE_TIME': '<?=GetMessageJS('IMOL_CONFIG_EDIT_QUEUE_TIME_NEW')?>',
		'IMOL_CONFIG_EDIT_NA_TIME_NEW': '<?=GetMessageJS('IMOL_CONFIG_EDIT_NA_TIME_NEW')?>',
		'IMOL_CONFIG_EDIT_POPUP_LIMITED_ACTIVE': '<?=GetMessageJS('IMOL_CONFIG_EDIT_POPUP_LIMITED_ACTIVE')?>',
		'IMOL_CONFIG_EDIT_LIMIT_QUEUE_ALL': '<?=!Limit::canUseQueueAll()?'Y':'N'?>',
	});
</script>
<?
if (!$arResult['IFRAME'])
{
	?>
	<div class="imopenlines-page-menu-sidebar">
		<?$APPLICATION->ShowViewContent('left-panel');?>
	</div>
	<?
}

$APPLICATION->IncludeComponent('bitrix:ui.sidepanel.wrappermenu', '', [
	'ITEMS' => $arResult['CONFIG_MENU'],
	'TITLE' => Loc::getMessage('IMOL_CONFIG_CONFIG'),
	'RELOAD_PAGE_AFTER_SAVE' => true
]);
?>
<div id="imopenlines-field-container" <?if(!$arResult['IFRAME']){?>class="imopenlines-page-field-container"<?}?>>
	<form action="<?=$arResult['ACTION_URI']?>"
		  method="POST"
		  id="imol_config_edit_form"
	<?if ($arResult['IFRAME']):?>class="imopenlines-form-settings-wrap"<?endif;?>>
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="CONFIG_ID" id="imol_config_id" value="<?=$arResult['CONFIG']['ID']?>" />
		<input type="hidden" name="form" value="imopenlines_edit_form" />
		<input type="hidden" name="action" value="apply" id="imol_config_edit_form_action" />
		<input type="hidden" name="PAGE" value="<?=$arResult['PAGE']?>" id="imol_config_current_page">
		<?php foreach ($arResult['CONFIG_MENU'] as $key => $menuItem): ?>
				<?php if ((new \Bitrix\Main\IO\File(__DIR__ . '/' . $menuItem['PAGE']))->isExists()): ?>
					<div data-imol-page="<?=$key?>" class="<?php if($key === $arResult['PAGE']): ?>imopenlines-page-show<?php else: ?>imopenlines-page-hide invisible<?php endif; ?>">
					<?php include $menuItem['PAGE']; ?>
					<div data-imol-title="<?=$menuItem['NAME']?>" class="invisible"></div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>

		<?
		if ($arResult['CAN_EDIT'])
		{
			$APPLICATION->IncludeComponent(
				'bitrix:ui.button.panel',
				'',
				[
					'BUTTONS' => $arResult['PANEL_BUTTONS'],
					'ALIGN' => 'center'
				],
				false
			);
		}
		?>
	</form>
</div>
