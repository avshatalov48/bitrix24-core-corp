<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
?>

<div class="imconnector-field-main-subtitle">
	<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECTED' . $arResult['LOC_REGION_POSTFIX'])?>
</div>
<div class="imconnector-field-box-content">
	<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CHANGE_ANY_TIME')?>
</div>
<div class="ui-btn-container">
	<a href="<?=$arResult['URL']['SIMPLE_FORM']?>" class="ui-btn ui-btn-primary show-preloader-button">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_CHANGE_SETTING')?>
	</a>
	<button class="ui-btn ui-btn-light-border"
			onclick="
				const form = document.getElementById('form_delete_<?=$arResult['CONNECTOR']?>');
				if (form) {
				const input = form.querySelector('input[name=<?=$arResult['CONNECTOR']?>_del]');
					if (input) {
						input.name = '<?=$arResult['CONNECTOR']?>_delall';
					}
				}
				popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>);
			"
	>
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_DISABLE_CONNECTOR')?>
	</button>
</div>
