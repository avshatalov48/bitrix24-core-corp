<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var \CBitrixComponent $component */

//todo Remove this shame when it becomes clear what to do with the dod side panel.
?>

<div style="display: none;">
	<?php
		$APPLICATION->includeComponent(
			'bitrix:tasks.widget.checklist.new',
			'',
			[
				'ENTITY_ID' => 1,
				'ENTITY_TYPE' => 'SCRUM_ITEM',
			],
			$component,
			['HIDE_ICONS' => 'Y']
		);
		$APPLICATION->includeComponent(
			'bitrix:ui.button.panel',
			'',
			[],
			$component,
			['HIDE_ICONS' => 'Y']
		);
	?>
</div>