<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

use Bitrix\UI;

use Bitrix\ImConnector\Connector;

Loader::requireModule('ui');
Extension::load([
	'ui.design-tokens',
	'ui.dialogs.messagebox',
	'ui.buttons',
]);

$iconCode = Connector::getIconByConnector($arResult['CONNECTOR']);

if (!empty($arResult['error']))
{
	foreach ($arResult['error'] as $error)
	{
		ShowError($error);
	}
}
?>

<form action="<?=$arResult['URL']['DELETE']?>" method="post" id="form_delete_<?=$arResult['CONNECTOR']?>">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
	<input type="hidden" name="<?=$arResult['CONNECTOR']?>_del" value="Y">
	<?=bitrix_sessid_post();?>
</form>

<?if (!empty($arResult['ACTIVE_STATUS'])):?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box">
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_HEADER')?>
				</div>
				<div class="imconnector-field-box-content">
					<p><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DESCRIPTION')?></p>
					<p>
						<a href="<?=UI\Util::getArticleUrlByCode('13655934')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DETAILS_MSGVER_2')?></a>
					</p>
				</div>
				<div class="ui-btn-container">
					<button class="ui-btn ui-btn-light-border"
							onclick="popupShow(<?=CUtil::PhpToJSObject($arResult['CONNECTOR'])?>)">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_DISABLE')?>
					</button>
				</div>
			</div>
		</div>
	</div>
<?else:?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section imconnector-field-section-social imconnector-field-section-info">
			<div class="imconnector-field-box">
				<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
			</div>
			<div class="imconnector-field-box" data-role="more-info">
				<div class="imconnector-field-main-subtitle imconnector-field-section-main-subtitle">
					<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_TITLE')?>
				</div>
				<div class="imconnector-field-box-content">

					<div class="imconnector-field-box-content-text-light">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_SUBTITLE') ?>
					</div>

					<ul class="imconnector-field-box-content-text-items">
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_LIST_ITEM_1') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_LIST_ITEM_2') ?></li>
						<li class="imconnector-field-box-content-text-item"><?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_LIST_ITEM_3') ?></li>
					</ul>

					<div class="imconnector-field-box-content-subtitle">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_SECOND_TITLE') ?>
					</div>

					<div class="imconnector-field-box-content-text">
						<?= Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_SECOND_DESCRIPTION') ?>
					</div>

					<div class="imconnector-field-box-content-text-light">
						<a href="<?=UI\Util::getArticleUrlByCode('13655934')?>"><?=Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_INDEX_DETAILS_MSGVER_2')?></a>
					</div>

					<div class="imconnector-field-box-content-btn">
						<form action="<?=$arResult['URL']['SIMPLE_FORM']?>" method="post" class="ui-btn-container">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_form" value="true">
							<input type="hidden" name="<?=$arResult['CONNECTOR']?>_active" value="true">
							<?=bitrix_sessid_post()?>
							<button class="ui-btn ui-btn-lg ui-btn-success ui-btn-round"
									type="button"
									onclick="BX.ImConnector.Notifications.onConnectButtonClick(this);"
									value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>">
								<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_TO_CONNECT')?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
<?endif;
