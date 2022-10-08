<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @global \CAllMain $APPLICATION */
/** @global \CAllDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$containerId = 'crm-tracking-entity-details';
?>
<div id="<?=htmlspecialcharsbx($containerId)?>"
	class="crm-tracking-entity-details-banner"
	style="display: none;"
>
	<div class="crm-tracking-entity-details-inner">
		<div class="crm-tracking-entity-details-title">
			<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_TITLE')?>
		</div>
		<div class="crm-tracking-entity-details-content">
			<div class="crm-tracking-entity-details-text-block">
				<span class="crm-tracking-entity-details-subtitle">
					<?=Loc::getMessage(
						'CRM_TRACKING_ENTITY_DETAILS_HEAD',
						[
							'%tagStart%' => '<b>',
							'%tagEnd%' => '</b>',
						]
					)?>
				</span>
				<span class="crm-tracking-entity-details-text">
					<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_DESC')?>
				</span>
			</div>
			<div class="crm-tracking-entity-details-btn">
				<button
					data-role="tracking/banner/btn/setup"
					class="ui-btn ui-btn-sm ui-btn-light-border crm-tracking-entity-details-btn-connect"
				>
					<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_BTN_SETUP')?>
				</button>
				<button type="button" data-role="tracking/banner/btn/hide"
					class="ui-btn ui-btn-sm ui-btn-link crm-tracking-entity-details-btn-hide"
				>
					<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_BTN_HIDE')?>
				</button>
			</div>
		</div>
		<div class="crm-tracking-entity-details-close"
			data-role="tracking/banner/btn/hide"
			title="<?=Loc::getMessage('CRM_TRACKING_ENTITY_DETAILS_BTN_HIDE')?>"
		></div>
	</div>

	<script>
		BX.ready(function () {
			BX.Crm.TrackingEntityDetails.init(<?=Json::encode([
				'containerId' => $containerId,
				'userOptionName' => $arResult['USER_OPTION_NAME'],
				'userOptionKeyName' => $arResult['USER_OPTION_KEY_NAME'],
			])?>);
		});
	</script>
</div>
<?