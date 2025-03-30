<?php

/** @var array $arResult */
/** @var \CMain $APPLICATION */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '') . 'no-all-paddings no-hidden');

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

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);

\Bitrix\Main\Loader::includeModule('ui');

echo \Bitrix\Crm\Tour\Permissions\AutomatedSolutionList::getInstance()->build();

echo '<div class="crm-automated-solution-list-wrapper">';
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult['grid']
);
echo '</div>';
?>

<script>
	BX.ready(() => {
		const grid = BX.Main.gridManager.getInstanceById('<?= \CUtil::JSEscape($arResult['grid']['GRID_ID']) ?>');

		BX.Crm.ToolbarComponent.Instance.subscribeAutomatedSolutionUpdatedEvent(() => {
			if (grid)
			{
				grid.reload();
			}
		});
	});
</script>
