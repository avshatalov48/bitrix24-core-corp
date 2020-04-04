<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CAllMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");
Extension::load(['ui.icons', 'ui.alerts', 'ui.forms', 'sidepanel',]);

$name = htmlspecialcharsbx($arResult['ROW']['NAME']);
$iconClass = htmlspecialcharsbx($arResult['ROW']['ICON_CLASS']);

$containerId = 'crm-tracking-order';
?>

<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-analytics-source-block-wrap">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>

	<form method="post">
		<?=bitrix_sessid_post();?>

		<div class="crm-analytics-source-block crm-analytics-source-block-desc">
			<span class="crm-analytics-source-icon <?=$iconClass?>">
				<i></i>
			</span>

			<div class="crm-analytics-source-section">
				<div class="crm-analytics-source-header">
					<?if ($arResult['ROW']['CONFIGURED']):?>
						<?=Loc::getMessage('CRM_TRACKING_ORDER_CONNECTED', ['%name%' => $name])?>
					<?else:?>
						<?=Loc::getMessage('CRM_TRACKING_ORDER_CONNECT', ['%name%' => $name])?>
					<?endif;?>
				</div>
				<div class="crm-analytics-source-desc">
					<span class="crm-analytics-source-desc-text">
						<?=Loc::getMessage('CRM_TRACKING_ORDER_DESC', ['%name%' => $name])?>
					</span>
				</div>
			</div>
		</div>

		<div class="crm-analytics-source-block">
			<div class="crm-analytics-source-header">
				1. <?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_CONNECT_SITE')?>
			</div>
			<div class="crm-analytics-source-desc">
				<span class="crm-analytics-source-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_CONNECT_SITE_DESC')?>
				</span>
			</div>
		</div>
		<div class="crm-analytics-source-block">
			<div class="crm-analytics-source-header">
				2. <?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_SELECT_FIELD')?>
			</div>
			<div class="crm-analytics-source-desc">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select name="FIELD_CODE" class="ui-ctl-element">
						<option value=""><?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_SELECT_FIELD_NONE')?></option>
						<?foreach ($arResult['FIELDS'] as $field):?>
						<option value="<?=htmlspecialcharsbx($field['id'])?>" <?=($arResult['FIELD_CODE'] == $field['id'] ? 'selected' : '')?>>
							<?=htmlspecialcharsbx($field['name'])?>
						</option>
						<?endforeach;?>
					</select>
				</div>
			</div>
			<br>
			<div class="crm-analytics-source-desc">
				<span class="crm-analytics-source-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_SELECT_FIELD_DESC')?>
				</span>
			</div>
		</div>
		<div class="crm-analytics-source-block">
			<div class="crm-analytics-source-header">
				3. <?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE')?>
			</div>
			<div class="crm-analytics-source-desc">
				<span class="crm-analytics-source-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_DESC')?>
					<a href="javascript: top.BX.Helper?top.BX.Helper.show('redirect=detail&code=9257317'):null;">
						<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_DESC_LINK')?>
					</a>
				</span>
				<br>
				<br>
				<span class="crm-analytics-source-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_EXAMPLE')?>:
				</span>
				<br>
				<div class="ui-alert ui-alert-primary ui-alert-xs" style="font-size: 11px;">
					<span class="ui-alert-message">
						<?
						$textId = strtoupper(str_replace(
							' ',
							'_',
							Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_EXAMPLE_ID')
						));
						$textSum = strtoupper(str_replace(
							' ',
							'_',
							Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_EXAMPLE_SUM')
						));
						echo htmlspecialcharsbx('<script>')
							. "(window.b24order=window.b24order||[]).push({id: \"$textId\", sum: \"$textSum\"});"
							. htmlspecialcharsbx('</script>');
						?>
					</span>
				</div>
			</div>
		</div>

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => [
				'save',
				'cancel' => $arParams['PATH_TO_LIST']
			]
		]);?>

	</form>

</div>
