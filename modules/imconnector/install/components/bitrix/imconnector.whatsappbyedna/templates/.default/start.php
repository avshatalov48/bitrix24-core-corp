<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
?>

<div class="imconnector-field-box-content">
	<div class="imconnector-field-box-content-text-light">
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_SUBTITLE' . $arResult['LOC_REGION_POSTFIX']) ?>
	</div>
	<ul class="imconnector-field-box-content-text-items">
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_0') ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_1') ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_2') ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_3') ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_4') ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_5' . $arResult['LOC_REGION_POSTFIX']) ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_6') ?>
		</li>
		<li class="imconnector-field-box-content-text-item">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPBYEDNA_CONNECT_SCREEN_7') ?>
		</li>
	</ul>
</div>
<div class="imconnector-field-box-content-btn">
	<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
		<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
		<?=bitrix_sessid_post()?>
		<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
				type="submit"
				name="<?=$arResult['CONNECTOR']?>_active"
				value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
		</button>
	</form>
</div>