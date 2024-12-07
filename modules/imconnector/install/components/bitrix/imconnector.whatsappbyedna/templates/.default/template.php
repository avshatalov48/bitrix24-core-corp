<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

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
/** $arResult["CONNECTION_STATUS"]; */
/** $arResult["REGISTER_STATUS"]; */
/** $arResult["ERROR_STATUS"]; */
/** $arResult["SAVE_STATUS"]; */

Loc::loadMessages(__FILE__);

if ($arParams['INDIVIDUAL_USE'] !== 'Y')
{
	$this->addExternalCss('/bitrix/components/bitrix/imconnector.settings/templates/.default/style.css');
	$this->addExternalJs('/bitrix/components/bitrix/imconnector.settings/templates/.default/script.js');
	\Bitrix\Main\UI\Extension::load('ui.buttons');
	\Bitrix\Main\UI\Extension::load('ui.hint');
	\Bitrix\ImConnector\Connector::initIconCss();
}

$placeholder = ' placeholder="'.Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_PLACEHOLDER').'"';

$iconCode = \Bitrix\ImConnector\Connector::getIconByConnector($arResult['CONNECTOR']);
?>
	<script type="text/javascript">
		BX.ready(function() {
			BX.UI.Hint.init(BX('imconnector-field-box'));
		});

		BX.message({
			'IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_TITLE': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_TITLE')?>',
			'IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_DESCRIPTION': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_DESCRIPTION')?>',
			'IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_BUTTON_OK': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_BUTTON_OK')?>',
			'IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_BUTTON_CANCEL': '<?=GetMessageJS('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONFIRM_BUTTON_CANCEL')?>'
		});
	</script>
	<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
		<?=bitrix_sessid_post()?>
	</form>

<?php if (empty($arResult['PAGE'])): ?>
	<?php include 'page.php'; ?>
<?php else: ?>
	<?php include 'continue.php'; ?>
<?php endif; ?>