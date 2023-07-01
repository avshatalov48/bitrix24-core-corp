<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

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
