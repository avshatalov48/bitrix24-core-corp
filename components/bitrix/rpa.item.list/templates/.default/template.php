<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var RpaItemListComponent $component */
$component = $this->getComponent();

if($component->getErrors())
{
	foreach($component->getErrors() as $error)
	{
		?>
		<div><?=$error->getMessage();?></div>
		<?php
	}

	return;
}
?>

<?php
$component->addToolbar($this);
\Bitrix\Rpa\Driver::getInstance()->getBitrix24Manager()->addFeedbackButtonToToolbar('list');
?>

<div class="rpa-wrapper">
	<?php
	$APPLICATION->IncludeComponent(
		"bitrix:main.ui.grid",
		"",
		$arResult['GRID']
	);
	?>
</div>
<script>
BX.ready(function()
{
	(new BX.Rpa.ItemsListComponent(<?=CUtil::PhpToJSObject($arResult['jsParams'])?>));
});
</script>