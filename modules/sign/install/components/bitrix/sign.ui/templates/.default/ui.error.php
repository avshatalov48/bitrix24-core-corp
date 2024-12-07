<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load("ui.alerts");

/** @var array $arResult */

if ($arResult['ERRORS'])
{
	echo '<div class="ui-alert ui-alert-danger"><span class="ui-alert-message">
		<script >
			BX.ready(function() {
				BX.onCustomEvent("BX.Sign:Error");
			});
		</script>
	';

	foreach ($arResult['ERRORS'] as $error)
	{
		echo $error->getMessage() . '<br/>';
	}

	echo '</span></div>';
}
