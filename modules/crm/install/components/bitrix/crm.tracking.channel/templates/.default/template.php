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

Extension::load('ui.icons');

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

	<span class="crm-analytics-source-icon <?=$iconClass?>">
		<i></i>
	</span>

	<div class="crm-analytics-source-section">
		<div class="crm-analytics-source-header">
			<?=Loc::getMessage('CRM_TRACKING_CHANNEL_CONNECTED', ['%name%' => $name])?>
		</div>
		<div class="crm-analytics-source-desc">
				<span class="crm-analytics-source-desc-text">
					<?=Loc::getMessage('CRM_TRACKING_CHANNEL_AUTO_DESC', ['%name%' => $name])?>
				</span>
		</div>
	</div>

	<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
		'BUTTONS' => ['close' => $arParams['PATH_TO_LIST']]
	]);?>
</div>