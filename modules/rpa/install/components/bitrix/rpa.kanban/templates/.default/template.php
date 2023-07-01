<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$bodyClass = false; //$APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-background");

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

\Bitrix\Main\UI\Extension::load([
	'rpa.kanban',
	'bp_user_selector'
]);

/** @see \Bitrix\Rpa\Components\Base::addToolbar() */
$this->getComponent()->addToolbar($this);
\Bitrix\Rpa\Driver::getInstance()->getBitrix24Manager()->addFeedbackButtonToToolbar('kanban');

//load Bizproc Automation API
$APPLICATION->includeComponent(
	'bitrix:bizproc.automation',
	'',
	[
		'API_MODE' => 'Y',
		'DOCUMENT_TYPE' => $arResult['documentType'],
	]
);
?>

<div id="rpa-kanban" class="rpa-kanban"></div>
<script>
BX.ready(function()
{
	var kanban = <?=CUtil::PhpToJSObject($arResult['kanban'], false, false, true);?>;
	kanban.renderTo = document.getElementById('rpa-kanban');
	kanban.itemType = 'BX.Rpa.Kanban.Item';
	kanban.columnType = 'BX.Rpa.Kanban.Column';
	kanban.bgColor = 'transparent';
	var grid = new BX.Rpa.Kanban.Grid(kanban);
	grid.draw();
	<?='BX.message('.\CUtil::PhpToJSObject($arResult['messages']).');'?>
});
</script>
