<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

Extension::load("ui.buttons");
Extension::load("ui.buttons.icons");

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */
$containerId = 'bx-crm-exclusion-import';
?>
<script>
	BX.ready(function () {
		BX.Crm.Exclusion.Import.init(<?=Json::encode(array(
			'containerId' => $containerId,
			'signedParameters' => $this->getComponent()->getSignedParameters(),
			'componentName' => $this->getComponent()->getName(),
			'pathToList' => $arParams['PATH_TO_LIST'],
			'mess' => array()
		))?>);
	});
</script>

<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-exclusion-import-wrap">
	<?=bitrix_sessid_post()?>

	<div class="crm-exclusion-import-list-box">
		<div class="crm-exclusion-import-list-caption"><?=Loc::getMessage('CRM_EXCLUSION_IMPORT_RECIPIENTS')?></div>
		<textarea data-role="text-list" class="crm-exclusion-import-list-textarea"></textarea>

		<div data-role="loader" class="crm-exclusion-import-loader" style="display: none;">
			<div class="crm-exclusion-import-overlay"></div>
			<div class="crm-exclusion-import-progress-box">
				<div class="crm-exclusion-import-progress-inner">
					<div class="crm-exclusion-import-progress">
						<span class="crm-exclusion-import-progress-name"><?=Loc::getMessage('CRM_EXCLUSION_IMPORT_LOADING')?>:</span>
						<span data-role="process" class="crm-exclusion-import-progress-number">0</span>
						<span class="crm-exclusion-import-progress-percent">%</span>
					</div>
					<div class="crm-exclusion-import-progress-bar">
						<div data-role="indicator" class="crm-exclusion-import-progress-bar-item"></div>
					</div>
				</div>
			</div>
		</div>
		
	</div>


	<div class="crm-exclusion-import-informer">
		<div class="crm-exclusion-import-informer-text"><?=Loc::getMessage('CRM_EXCLUSION_IMPORT_FORMAT_DESC')?></div>
	</div>

	<?if ($arParams['CAN_EDIT']):?>
	<div class="crm-exclusion-footer-buttons crm-exclusion-footer-buttons-fixed"></div>
	<div class="webform-buttons crm-exclusion-footer-fixed">
		<div class="crm-exclusion-footer-container">
			<button
				data-role="panel-button-save"
				name="save"
				class="ui-btn ui-btn-success"
			>
				<?=Loc::getMessage('CRM_EXCLUSION_IMPORT_BUTTON_LOAD')?>
			</button>

			<a
				data-role="panel-button-cancel"
				href="<?=htmlspecialcharsbx($arParams['PATH_TO_LIST'])?>"
				class="ui-btn ui-btn-link"
			>
				<?=Loc::getMessage('CRM_EXCLUSION_IMPORT_BUTTON_CANCEL')?>
			</a>
		</div>
	</div>
	<?endif;?>
</div>