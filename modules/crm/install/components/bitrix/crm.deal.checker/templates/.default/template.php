<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if ($arResult['showPopup'])
{
	$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '');
	?>

	<script>
		BX.ready(function () {
			BX.UI.InfoHelper.show('limit_crm_robots');
		});
	</script>

	<?php
}