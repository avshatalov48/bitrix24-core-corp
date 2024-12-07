<?php

use \Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui',
	'ui.forms',
	'ui.buttons',
	'ui.icons',
	'ui.common',
	'ui.alerts',
	'ui.switcher',
	'salescenter.manager',
]);

$formId = 'salescenter-delivery-service-installation-form';
$saveButtonId = 'sc-deliveryservice-installation-save-button';

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . ' no-all-paddings no-background');
$this->setViewTarget('inside_pagetitle_below', 100);
?>

<div class="salescenter-main-header-feedback-container">
	<?Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance()->renderFeedbackDeliveryOfferButton();?>
</div>

<?if ($arResult['edit']):?>
	<div style="margin-left: 20px;" class="salescenter-main-header-switcher-container">
		<span id="salescenter-delivery-wizard-active"></span>
	</div>
<?endif;?>

<?php $this->endViewTarget(); ?>

<?
$dirPath = __DIR__ . '/' . strtolower($arResult['code']);

if (in_array($arResult['code'], $arResult['knownHandlerCodes']) && Bitrix\Main\IO\Directory::isDirectoryExists($dirPath)):?>
	<form id="<?=$formId?>">
		<input type="hidden" name="code" value="<?=htmlspecialcharsbx($arResult['code'])?>" />
		<input type="hidden" id="delivery_service_activity" name="ACTIVE" value="<?=$arResult['edit'] ? $arResult['service']['ACTIVE'] : 'Y'?>" />
		<?if ($arResult['edit']):?>
			<input type="hidden" name="id" value="<?=(int)$arResult['service']['ID']?>" />
		<?endif;?>
		<div class="salescenter-delivery-installation-wrap">
			<?include($dirPath . '/header.php');?>
		</div>

		<div class="ui-alert ui-alert-danger" style="display: none;">
			<span id="salescenter-delivery-error" class="ui-alert-message"></span>
		</div>

		<div class="salescenter-delivery-installation-wrap">
			<?include($dirPath . '/fields.php');?>
			<div class="salescenter-delivery-install-section" style="padding-bottom: 68px">
				<div class="salescenter-delivery-install-content-block">
					<label for="" class="ui-ctl-label-text">
						<?=Loc::getMessage('DELIVERY_SERVICE_NAME')?>
					</label>
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w75" style="margin-bottom: 17px;">
						<input required value="<?=htmlspecialcharsbx(($arResult['edit'] ? $arResult['service']['NAME'] : ($arResult['handler'] ? $arResult['handler']->getName() : '')))?>" placeholder="<?=Loc::getMessage('DELIVERY_SERVICE_NAME_PLACEHOLDER')?>" type="text" name="NAME" class="ui-ctl-element">
					</div>
				</div>
			</div>
		</div>

		<?
		$buttons = [
			[
				'TYPE' => 'save',
				'ID' => $saveButtonId,
			],
			[
				'TYPE' => 'cancel',
			],
		];
		if ($arResult['edit'])
		{
			$buttons[] = [
				'TYPE' => 'remove',
				'ONCLICK' => 'BX.Salescenter.DeliveryInstallation.Wizard.delete(event);',
			];
		}

		$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => $buttons,
			'ALIGN' => 'center'
		]);?>
	</form>
<?endif;?>

<script>
	BX.ready(function () {
		BX.Salescenter.DeliveryInstallation.Wizard.init(<?=\Bitrix\Main\Web\Json::encode(
			[
				'id' => $arResult['edit'] ? (int)$arResult['service']['ID'] : null,
				'code' => $arResult['code'],
				'formId' => $formId,
				'saveButtonId' => $saveButtonId,
				'confirmDeleteMessage' => Loc::getMessage('DELIVERY_SERVICE_DELETE_CONFIRM'),
				'errorMessageId' => 'salescenter-delivery-error',
			]
		)?>);

		<?if ($arResult['edit']):?>
			let inputNode = document.getElementById('delivery_service_activity');

			new BX.UI.Switcher({
				color: 'green',
				node: document.getElementById('salescenter-delivery-wizard-active'),
				checked: <?=($arResult['service']['ACTIVE'] == 'Y' ? 'true' : 'false')?>,
				handlers: {
					unchecked: () => {inputNode.value = 'Y';},
					checked: () => {inputNode.value = 'N';},
				}
			});
		<?endif;?>
	});
</script>
