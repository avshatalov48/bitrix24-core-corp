<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

Extension::load([
	'ui.icons',
	'ui.alerts',
	'ui.forms',
	'sidepanel',
	'ui.sidepanel-content',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

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

		<div class="ui-slider-section ui-slider-section-icon-center">
			<span class="ui-slider-icon <?=$iconClass?>">
				<i></i>
			</span>

			<div class="ui-slider-content-box">
				<div class="ui-slider-heading-3">
					<?if ($arResult['ROW']['CONFIGURED']):?>
						<?=Loc::getMessage('CRM_TRACKING_ORDER_CONNECTED', ['%name%' => $name])?>
					<?else:?>
						<?=Loc::getMessage('CRM_TRACKING_ORDER_CONNECT', ['%name%' => $name])?>
					<?endif;?>
				</div>
				<p class="ui-slider-paragraph-2"><?=Loc::getMessage('CRM_TRACKING_ORDER_DESC', ['%name%' => $name])?></p>
			</div>
		</div>

		<div class="ui-slider-section">
			<div class="ui-slider-heading-3">
				1. <?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_CONNECT_SITE')?>
			</div>
			<p class="ui-slider-paragraph-2"><?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_CONNECT_SITE_DESC')?></p>
		</div>
		<div class="ui-slider-section">
			<div class="ui-slider-heading-3">
				2. <?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_SELECT_FIELD')?>
			</div>
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
			<br>
			<p class="ui-slider-paragraph-2"><?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_SELECT_FIELD_DESC')?></p>
		</div>
		<div class="ui-slider-section">
			<div class="ui-slider-heading-3">
				3. <?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE')?>
			</div>
			<p class="ui-slider-paragraph-2">
				<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_DESC')?>
				<a href="javascript: top.BX.Helper?top.BX.Helper.show('redirect=detail&code=9257317'):null;">
					<?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_DESC_LINK')?>
				</a>
			</p>
			<p class="ui-slider-paragraph-2"><?=Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_EXAMPLE')?>:</p>
			<div class="ui-alert ui-alert-primary ui-alert-xs" style="font-size: 11px;">
					<span class="ui-alert-message">
						<?
						$textId = mb_strtoupper(str_replace(
													' ',
													'_',
													Loc::getMessage('CRM_TRACKING_ORDER_STEP_INSTALL_CODE_EXAMPLE_ID')
												));
						$textSum = mb_strtoupper(str_replace(
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

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => [
				'save',
				'cancel' => $arParams['PATH_TO_LIST']
			]
		]);?>

	</form>

</div>
