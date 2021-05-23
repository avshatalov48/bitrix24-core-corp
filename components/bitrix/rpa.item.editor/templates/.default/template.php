<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div><?=htmlspecialcharsbx($error->getMessage());?></div>
		<?php
	}

	return;
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
	"bitrix:ui.form",
	"",
	$arResult['formParams']
);

?>
<script>
	BX.ready(function()
	{
		(new BX.Rpa.ItemEditorComponent('<?=CUtil::JSEscape($arResult['formParams']['GUID']);?>', <?=CUtil::PhpToJSObject($arResult['jsParams']);?>)).init();
	});
</script>