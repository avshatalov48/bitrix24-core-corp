<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use Bitrix\UI;
\Bitrix\Main\Loader::requireModule('ui');
\Bitrix\Main\UI\Extension::load([
	'ui.dialogs.messagebox',
]);

if ($arResult['error'])
{
	foreach ($arResult['error'] as $error)
	{
		ShowError($error);
	}
}
?>

<form action="<?=$arResult["URL"]["DELETE"]?>" method="post" id="form_delete_<?=$arResult["CONNECTOR"]?>">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
	<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>

<? if ($arResult['ACTIVE_STATUS']): ?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-universal"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_HEADER')?>
				</div>
				<div class="imconnector-field-box-content">
					<p><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DESCRIPTION')?></p>
					<p>
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DESCRIPTION_1')?>
						<a href="<?=UI\Util::getArticleUrlByCode('13655934')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DETAILS')?></a>
					</p>
				</div>
				<div class="ui-btn-container">
					<button class="ui-btn ui-btn-light-border"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
					</button>
				</div>
			</div>
		</div>
	</div>
<? else: ?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-universal"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_HEADER')?>
				</div>
				<div class="imconnector-field-box-content">
					<p><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DESCRIPTION')?></p>
					<p>
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DESCRIPTION_1')?>
						<a href="<?=UI\Util::getArticleUrlByCode('13655934')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DETAILS')?></a>
					</p>
				</div>
				<form action="<?= $arResult["URL"]["SIMPLE_FORM"] ?>" method="post">
					<input type="hidden" name="<?= $arResult["CONNECTOR"] ?>_form" value="true">
					<input type="hidden" name="<?= $arResult["CONNECTOR"] ?>_active" value="true">
					<?= bitrix_sessid_post(); ?>
					<div class="ui-btn-container">
						<button class="ui-btn ui-btn-light-border"
								type="button"
								onclick="BX.ImConnector.Notifications.onConnectButtonClick(this);"
								value="<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>">
							<?= Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT') ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
<? endif ?>
