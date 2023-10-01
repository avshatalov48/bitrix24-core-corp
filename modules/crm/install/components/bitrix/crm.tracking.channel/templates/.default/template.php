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
	'ui.sidepanel-content',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$name = htmlspecialcharsbx($arResult['ROW']['NAME']);
$iconClass = htmlspecialcharsbx($arResult['ROW']['ICON_CLASS']);

$containerId = 'crm-tracking-channel-pool';
?>

<div class="ui-slider-section ui-slider-section-icon-center">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:ui.feedback.form',
		'',
		\Bitrix\Crm\Tracking\Provider::getFeedbackParameters()
	);
	?>

	<span class="ui-slider-icon <?=$iconClass?>">
		<i></i>
	</span>

	<div class="ui-slider-content-box">
		<div class="ui-slider-heading-3">
			<?=Loc::getMessage('CRM_TRACKING_CHANNEL_CONNECTED', ['%name%' => $name])?>
		</div>
		<p class="ui-slider-paragraph-2"><?=Loc::getMessage('CRM_TRACKING_CHANNEL_AUTO_DESC', ['%name%' => $name])?></p>
	</div>

	<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
		'BUTTONS' => ['close' => $arParams['PATH_TO_LIST']]
	]);?>
</div>
