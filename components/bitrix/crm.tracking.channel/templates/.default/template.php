<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Crm\Tracking\Provider;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Extension::load([
	'ui.icons',
	'ui.sidepanel-content',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.feedback.form',
]);

$name = htmlspecialcharsbx($arResult['ROW']['NAME']);
$iconClass = htmlspecialcharsbx($arResult['ROW']['ICON_CLASS']);

$containerId = 'crm-tracking-channel-pool';
$feedbackParams = Json::encode(Provider::getFeedbackParameters());

Toolbar::deleteFavoriteStar();
Toolbar::addButton([
	"color" => Color::LIGHT_BORDER,
	"click" => new JsCode(
		"BX.UI.Feedback.Form.open({$feedbackParams});"
	),
	"text" => Provider::getFeedbackButtonTitle(),
]);
?>

<div class="ui-slider-section ui-slider-section-icon-center">

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
