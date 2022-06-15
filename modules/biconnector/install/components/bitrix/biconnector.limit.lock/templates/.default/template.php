<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

?>
<script>
	if (BX.getClass("BX.BIConnector.LicenseInfoPopup"))
	{
		BX.BIConnector.LicenseInfoPopup.init(<?=CUtil::PhpToJSObject($arResult['JS_PARAMS'])?>);
		BX.BIConnector.LicenseInfoPopup.show();
	}
</script>
