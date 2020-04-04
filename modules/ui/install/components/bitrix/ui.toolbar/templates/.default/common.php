<?

use Bitrix\UI\Toolbar;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$this->setFrameMode(true);

$toolbarManager = Toolbar\Manager::getInstance();
if($arResult["TOOLBAR_ID"] != "")
{
	$toolbar = $toolbarManager->getToolbarById($arResult["TOOLBAR_ID"]);
}
else
{
	$toolbar = $toolbarManager->getToolbarById(Toolbar\Facade\Toolbar::DEFAULT_ID);
}

$filter = $toolbar->getFilter();
$rightButtons = $toolbar->renderRightButtons();
$filterButtons = $toolbar->renderAfterFilterButtons();
?>

<div id="<?=$arResult["CONTAINER_ID"]?>" class="ui-toolbar">
	<? if (strlen($filter)): ?>
		<div class="ui-toolbar-filter-box"><?=$filter?><?=$filterButtons?></div>
	<? endif; ?>

	<? if (strlen($rightButtons)):?>
		<div class="ui-toolbar-btn-box"><?=$rightButtons?></div>
	<?endif;?>
</div>

<script>
    new BX.UI.Toolbar(<?=\Bitrix\Main\Web\Json::encode([
        "titleMinWidth" => $toolbar->getTitleMinWidth(),
		"titleMaxWidth" => $toolbar->getTitleMaxWidth(),
		"buttonIds" => array_map(function(\Bitrix\UI\Buttons\BaseButton $button){
			return $button->getUniqId();
		}, $toolbar->getButtons()),
    ])?>,
		targetContainer = document.getElementById('<?=$arResult["CONTAINER_ID"]?>')
	);
</script>
