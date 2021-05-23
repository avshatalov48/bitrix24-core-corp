<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var array $arResult
 */

\Bitrix\Main\UI\Extension::load(['ui.notification']);
$messages = \Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);
?>
<script>
	BX.message(<?=CUtil::PhpToJSObject($messages)?>);
	BX.ready(function()
	{
		new BX.Crm.Order.Import.Instagram.Observer({
			hasNewMedia: !!'<?=$arResult['HAS_NEW_MEDIA']?>',
			pathToImport: '<?=CUtil::JSEscape($arResult['PATH_TO_IMPORT'])?>',
		});
	});
</script>