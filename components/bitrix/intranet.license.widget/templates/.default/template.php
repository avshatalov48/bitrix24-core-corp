<?php
/**
 * @param $this CBitrixComponentTemplate
 * @global $APPLICATION \CMain
 * @var $arResult array
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.button', 'ui.vue', 'ui.feedback.form', 'ui.fonts.opensans']);
$class = ['ui-btn ui-btn-round ui-btn-themes license-btn'];
$title = $arResult['isDemo'] ? Loc::getMessage('INTRANET_LICENSE_WIDGET_TITLE_DEMO')
	: Loc::getMessage('INTRANET_LICENSE_WIDGET_TITLE');
/**
 * @var Main\Type\Date $expireDate
 */
$expireDate = $arResult['expireDate'];
$expirationLevel = 0;
if (!$expireDate)
{
	$class = ['license-btn-alert-border', ''];
	$title = Loc::getMessage('INTRANET_LICENSE_WIDGET_TITLE_ERROR');
}
else
{
	$daysLeft = (new Main\Type\DateTime())->getDiff($expireDate);
	$daysLeft = ($daysLeft->invert ? (-1) : 1) *  $daysLeft->days;
	$expirationLevel = $daysLeft < (-14) ? 4 : (
		$daysLeft <= 0 ? 3 : ($daysLeft <= 14  ? 2 : ($daysLeft < 30 ? 1 : 0))
	);

	$class[] = $arResult['isDemo'] ? 'ui-btn-icon-demo' : 'ui-btn-icon-tariff';
	if ($expirationLevel <= 0)
	{
		$class[] = $arResult['isDemo'] ? 'ui-btn-icon-demo' : 'ui-btn-icon-tariff';
		$class[] = 'license-btn-blue-border';
	}
	else
	{
		$title = $arResult['isDemo'] ? Loc::getMessage('INTRANET_LICENSE_WIDGET_BUY') : Loc::getMessage('INTRANET_LICENSE_WIDGET_PROLONG');
		if ($expirationLevel <= 1)
		{
			$class[] = 'license-btn-orange';
		}
		else
		{
			$class[] = 'license-btn-alert-border';
			$class[] = $expirationLevel >= 3 ? 'ui-btn-icon-low-battery' :
				'license-btn-animate license-btn-animate-forward';
		}
	}
}
$class = implode(' ', $class);
$title = $expirationLevel.$title;
$APPLICATION->SetPageProperty('HeaderClass', 'intranet-header--with-controls');

$frame = $this->createFrame()->begin();
if ($expireDate)
{
?>
<span data-id="licenseWidgetWrapper">
	<button class="<?=$class?>">
		<?=$title?>
	</button>
</span>
<script>
	BX.message(<?=CUtil::phpToJsObject(Loc::loadLanguageFile(__FILE__))?>);
	BX.ready(function () {
		BX.Intranet.LicenseWidget = new BX.Intranet.LicenseWidget({
			wrapper: document.querySelector("[data-id='licenseWidgetWrapper']"),
			isDemo: '<?=$arResult['isDemo'] ? 'Y' : 'N'?>',
			expirationLevel: <?=$expirationLevel?>,
		});
		console.log('$expirationLevel: ', <?=$expirationLevel?>);
	});
</script>
	<?php
}
else
{
?>
<span data-id="licenseWidgetWrapper">
	<button class="<?=$class?>">
		<?=$title?>
	</button>
</span>
	<?
}

$frame->beginStub(); ?>
<button class="<?=$class?>">
	<?=$title?>
</button>
<?php $frame->end(); ?>
