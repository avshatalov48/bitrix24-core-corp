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

Extension::load([
	'ui.icons',
	'ui.forms',
	'ui.hint',
	'ui.common',
	'ui.design-tokens',
]);

$name = htmlspecialcharsbx($arResult['ROW']['NAME']);
$iconClass = htmlspecialcharsbx($arResult['ROW']['ICON_CLASS']);

$containerId = 'crm-tracking-channel-pool';
?>

<div class="crm-analytics-source-block crm-analytics-source-block-desc">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>
	<form method="post" id="crm-tracking-settings">
		<?=bitrix_sessid_post();?>
		<div class="crm-analytics-source-section">
			<div class="ui-title-3" style="margin-bottom: 15px;"><?=Loc::getMessage('CRM_TRACKING_SETTINGS_ATTR_WINDOW')?></div>
			<div class="crm-analytics-source-desc">
				<div class="ui-ctl ui-ctl-inline">
					<span style="padding-bottom: 0;" class="ui-ctl-label-text"><?=Loc::getMessage('CRM_TRACKING_SETTINGS_ATTR_WINDOW_SITE')?></span>
				</div>
				<div class="ui-ctl ui-ctl-textbox ui-ctl-inline" style="width: 80px;">
					<input name="ATTR_WINDOW" type="number" class="ui-ctl-element"
						min="1" max="180" step="1"
						value="<?=htmlspecialcharsbx($arResult['DATA']['ATTR_WINDOW'])?>"
					>
				</div>
				<div class="ui-ctl ui-ctl-inline ui-ctl-row">
					<span style="padding-bottom: 0;" class="ui-ctl-label-text">
						<?=Loc::getMessage('CRM_TRACKING_SETTINGS_ATTR_WINDOW_DAYS')?>
					</span>
					<span style="margin: 0 0 0 5px;" class="ui-hint" data-hint="<?=Loc::getMessage('CRM_TRACKING_SETTINGS_ATTR_WINDOW_SITE_HINT')?>"></span>
				</div>

				<label class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
					<input name="ATTR_WINDOW_OFFLINE" type="checkbox" class="ui-ctl-element"
						value="Y"
						<?=($arResult['DATA']['ATTR_WINDOW_OFFLINE'] === 'Y' ? 'checked' : '')?>
					>
					<span class="ui-ctl-label-text">
						<?=Loc::getMessage('CRM_TRACKING_SETTINGS_ATTR_WINDOW_OFFLINE')?>
					</span>
					<span class="ui-hint" style="margin: 0;" data-hint="<?=Loc::getMessage('CRM_TRACKING_SETTINGS_ATTR_WINDOW_OFFLINE_HINT')?>"></span>
				</label>
			</div>

			<br><br>
			<div class="ui-title-3" style="margin-bottom: 10px;">
				<?=Loc::getMessage('CRM_TRACKING_SETTINGS_REF_DOMAIN')?>
			</div>
			<div class="crm-analytics-source-desc">
				<label class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
					<input name="SOCIAL_REF_DOMAIN_USED" type="checkbox" class="ui-ctl-element"
						value="Y"
						<?=($arResult['DATA']['SOCIAL_REF_DOMAIN_USED'] === 'Y' ? 'checked' : '')?>
					>
					<span class="ui-ctl-label-text"><?=Loc::getMessage('CRM_TRACKING_SETTINGS_REF_DOMAIN_USE')?></span>
				</label>
			</div>
		</div>

		<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
			'BUTTONS' => ['save', 'cancel' => $arParams['PATH_TO_LIST']]
		]);?>
	</form>
</div>