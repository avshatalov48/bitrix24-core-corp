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

if (!empty($arResult['showFieldsToSetWarning'])):
	\Bitrix\Main\UI\Extension::load("ui.alerts");
?>
	<div style="padding: 5px">
		<div class="ui-alert ui-alert-xs ui-alert-warning ui-alert-icon-warning">
			<span class="ui-alert-message"><?=htmlspecialcharsbx($arResult['showFieldsToSetWarning'])?></span>
		</div>
	</div>
<?php endif;?>
<script>
	BX.ready(function()
	{
		(new BX.Rpa.TaskFieldsComponent(
			'<?=CUtil::JSEscape($arResult['formParams']['GUID']);?>',
			<?=CUtil::PhpToJSObject($arResult['jsParams']);?>)
		).init();
	});
</script>