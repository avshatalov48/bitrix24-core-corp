<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Web\Json;

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.common',
	'ui.design-tokens',
	'ui.entity-editor',
]);

?>
<div id="intranet-profile-settings-popup"  style="margin: 20px;">
	<div class="ui-title-1"><?=Loc::getMessage("INTRANET_USER_PROFILE_SETTINGS_TITLE")?></div>
	<form name="profileFieldsSettingsForm" id="profileFieldsSettingsForm" method="post">
		<div class="ui-text-1"><?=Loc::getMessage("INTRANET_USER_PROFILE_SETTINGS_FIELDS_VIEW")?></div>
		<div
			data-name="fieldsView"
			data-items='<?=Json::encode($arResult["SettingsFieldsAll"]);?>'
			data-params='<?=Json::encode(['isMulti'=>true]);?>'
			data-value='<?=Json::encode($arResult["SettingsFieldsView"]);?>'
			id="FieldsView" class="main-ui-control main-ui-multi-select">
			<span class="main-ui-square-container">
				<?
				if (!empty($arResult["SettingsFieldsView"]))
				{
					foreach ($arResult["SettingsFieldsView"] as $item)
					{
						$field = [
							"NAME" => $item["NAME"],
							"VALUE" => $item["VALUE"]
						];
						?>
						<span class="main-ui-square"
							  data-item='<?=Json::encode($field);?>'>
							<span class="main-ui-square-item"><?=htmlspecialcharsbx($item["NAME"])?></span>
							<span class="main-ui-item-icon main-ui-square-delete"></span>
						</span>
						<?
					}
				}
				?>
			</span>
			<span class="main-ui-square-search">
				<input type="text" tabindex="2" class="main-ui-square-search-item">
			</span>
			<span class="main-ui-hide main-ui-control-value-delete">
				<span class="main-ui-control-value-delete-item"></span>
			</span>
		</div>

		<div class="ui-text-1" style="margin-top: 25px;"><?=Loc::getMessage("INTRANET_USER_PROFILE_SETTINGS_FIELDS_EDIT")?></div>
		<div
			 data-name="fieldsEdit"
			 data-items='<?= \Bitrix\Main\Web\Json::encode($arResult["SettingsFieldsAll"]);?>'
			 data-params='<?= \Bitrix\Main\Web\Json::encode(['isMulti'=>true]);?>'
			 data-value='<?= \Bitrix\Main\Web\Json::encode($arResult["SettingsFieldsEdit"]);?>'
			 id="FieldsEdit" class="main-ui-control main-ui-multi-select">
			<span class="main-ui-square-container">
				<?
				if (!empty($arResult["SettingsFieldsEdit"]))
				{
					foreach ($arResult["SettingsFieldsEdit"] as $item)
					{
						$field = [
							"NAME" => $item["NAME"],
							"VALUE" => $item["VALUE"]
						];
						?>
						<span class="main-ui-square"
							  data-item='<?=Json::encode($field);?>'>
							<span class="main-ui-square-item"><?=htmlspecialcharsbx($item["NAME"])?></span>
							<span class="main-ui-item-icon main-ui-square-delete"></span>
						</span>
						<?
					}
				}
				?>
			</span>
			<span class="main-ui-square-search">
				<input type="text" tabindex="2" class="main-ui-square-search-item">
			</span>
			<span class="main-ui-hide main-ui-control-value-delete">
				<span class="main-ui-control-value-delete-item"></span>
			</span>
		</div>

		<div class="ui-entity-wrap crm-section-control-active"><div class="ui-entity-section ui-entity-section-control">
			<a href="javascript:void(0)" class="ui-btn ui-btn-success" data-role="fieldsSaveBtn"><?=Loc::getMessage("INTRANET_USER_PROFILE_SETTINGS_SAVE")?></a>
			<a href="javascript:void(0)" class="ui-btn ui-btn-link" data-role="fieldsCloseBtn"><?=Loc::getMessage("INTRANET_USER_PROFILE_SETTINGS_CANCEL")?></a>
			<div class="ui-entity-section-control-error-block" style="max-height: 0px;"></div></div>
		</div>
	</form>
	<div style="height: 65px;"></div>
</div>

<script>
	BX.ready(function () {
		BX.Intranet.UserProfile.Settings.init({
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			componentName: '<?=$this->getComponent()->getName()?>'
		});
	});
</script>