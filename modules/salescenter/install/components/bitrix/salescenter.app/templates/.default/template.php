<?php

use Bitrix\SalesCenter\Integration\Bitrix24Manager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load(['salescenter.app', 'ui.common', 'currency']);

if (\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('IFRAME') == 'Y')
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background');
}

$this->SetViewTarget('pagetitle');
?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<?php
		Bitrix24Manager::getInstance()->renderFeedbackButton();
		?>
	</div>
<?
$this->EndViewTarget();

// todo a bit later
//Bitrix24Manager::getInstance()->addFeedbackButtonToToolbar();

if (!empty($arResult['CURRENCIES']))
{
	?>
	<script>
		BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'])?>);
	</script>
	<?
}
?>
	<div id="salescenter-app-root"></div>
	<?php
	if($arResult['isPaymentsLimitReached'])
	{
		?>
		<div id="salescenter-payment-limit-container" style="display: none;">
			<?php
			$APPLICATION->includeComponent('bitrix:salescenter.feature', '', ['FEATURE' => 'salescenterPaymentsLimit']);
			?>
		</div>
		<?php
	}
	?>
	<script>
		BX.ready(function()
		{
			var options = <?=CUtil::PhpToJSObject($arResult)?>;
			new BX.Salescenter.App(options);
			BX.Salescenter.Manager.init(options);
		});
	</script>
<?