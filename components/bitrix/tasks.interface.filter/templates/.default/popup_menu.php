<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Helper\Filter;
use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;
\Bitrix\Main\UI\Extension::load([
	'ui.stepprocessing',
]);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>

<button id="tasks-popupMenuOptions" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting webform-cogwheel"></button>


<script>
	(function()
	{
		var menuItemsOptions = [];
		var columnId = '<?= $arParams['SORT_FIELD'] ?? "" ?>';
		var columnDir = '<?= $arParams['SORT_FIELD_DIR'] ?? "" ?>';

		BX.addCustomEvent('BX.Main.grid:sort', function(column, grid) {
			if (BX.type.isNotEmptyString(column.sort_by))
			{
				columnId = column.sort_by;
			}
			if (BX.type.isNotEmptyString(column.sort_order))
			{
				columnDir = column.sort_order;
			}
		});

		<?php if($arParams['USE_GROUP_BY_SUBTASKS'] == 'Y'):?>
		<?php
		$instance = CTaskListState::getInstance($arParams['USER_ID']);
		$state = $instance->getState();
		$submodes = $state['SUBMODES'];
		$groupBySubTasks = $submodes['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] == 'Y';
		?>
		var groupBySubTasks = ('<?=$groupBySubTasks?>' === '1');
		menuItemsOptions.push({
			id: 'groupBySubTasks',
			tabId: "popupMenuOptions",
			text: '<?=$submodes['VIEW_SUBMODE_WITH_SUBTASKS']['TITLE']?>',
			className: (groupBySubTasks ? 'menu-popup-item-accept' : 'menu-popup-item-none'),
			onclick: function(event, item) {
				BX.ajax.runComponentAction('bitrix:tasks.interface.filter', 'toggleGroupByTasks', {
					mode: 'class',
					data: {
						userId: <?= $arParams['USER_ID'] ?>
					}
				}).then(
					function(response)
					{
						if (
							response.status
							&& response.status === 'success'
						)
						{
							if (BX.hasClass(item.layout.item, "menu-popup-item-accept"))
							{
								BX.removeClass(item.layout.item, "menu-popup-item-accept");
								BX.addClass(item.layout.item, "menu-popup-item-none");
							}
							else
							{
								BX.addClass(item.layout.item, "menu-popup-item-accept");
								BX.removeClass(item.layout.item, "menu-popup-item-none");
							}

							if (BX.Main.gridManager)
							{
								groupBySubTasks = !groupBySubTasks;
								var gridInstance = BX.Main.gridManager.getById('<?=$arParams['GRID_ID']?>').instance;
								gridInstance.reloadTable();
								BX.onCustomEvent('BX.Tasks.Filter.group', [gridInstance, 'groupBySubTasks', groupBySubTasks]);
							}
							else
							{
								window.location.reload();
							}
						}
					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			}
		});
		<?php endif?>

		<?php if($arParams['USE_GROUP_BY_GROUPS'] == 'Y'):?>
		<?php
		$instance = CTaskListState::getInstance($arParams['USER_ID']);
		$state = $instance->getState();
		$submodes = $state['SUBMODES'];
		$groupByGroups = $submodes['VIEW_SUBMODE_WITH_GROUPS']['SELECTED'] == 'Y';
		?>
		var groupByGroups = '<?=$groupByGroups?>' === '1';
		menuItemsOptions.push({
			id: 'groupByGroups',
			tabId: "popupMenuOptions",
			text: '<?=$submodes['VIEW_SUBMODE_WITH_GROUPS']['TITLE']?>',
			className: (groupByGroups ? 'menu-popup-item-accept' : 'menu-popup-item-none'),
			onclick: function(event, item) {
				BX.ajax.runComponentAction('bitrix:tasks.interface.filter', 'toggleGroupByGroups', {
					mode: 'class',
					data: {
						userId: <?= $arParams['USER_ID'] ?>
					}
				}).then(
					function(response)
					{
						if (
							response.status
							&& response.status === 'success'
						)
						{
							if (BX.hasClass(item.layout.item, "menu-popup-item-accept"))
							{
								BX.removeClass(item.layout.item, "menu-popup-item-accept");
								BX.addClass(item.layout.item, "menu-popup-item-none");
							}
							else
							{
								BX.addClass(item.layout.item, "menu-popup-item-accept");
								BX.removeClass(item.layout.item, "menu-popup-item-none");
							}

							if (BX.Main.gridManager)
							{
								groupByGroups = !groupByGroups;
								var gridInstance = BX.Main.gridManager.getById('<?=$arParams['GRID_ID']?>').instance;
								gridInstance.reloadTable();
								BX.onCustomEvent('BX.Tasks.Filter.group', [gridInstance, 'groupByGroups', groupByGroups]);
							}
							else
							{
								window.location.reload();
							}
						}
					}.bind(this),
					function(response)
					{

					}.bind(this)
				);
			}
		});
		<?php endif?>

		<?php if($arParams['SHOW_USER_SORT'] == 'Y'):?>

		menuItemsOptions.push({
			tabId: "popupMenuOptions",
			delimiter: true
		});

		<?php
		$sortFields = \Bitrix\Tasks\Ui\Controls\Column::getFieldsWithMessages('TASKS_BTN_SORT_');
		?>

		function onMenuItemClick(selectedItemType, selectedItem, menuItems)
		{
			if (selectedItemType === 'field')
			{
				columnId = selectedItem.value;
			}
			else if (selectedItemType === 'dir')
			{
				columnDir = selectedItem.value;
			}

			BX.Tasks.SortManager.setSort(columnId, columnDir, '<?=$arParams['GRID_ID']?>');

			menuItems.forEach(function(item) {
				handleStyles(item, columnId, columnDir);
			});
		}

		function handleStyles(item, columnId, columnDir)
		{
			var itemAccept = 'menu-popup-item-accept';
			var itemSortField = 'menu-popup-item-sort-field';
			var itemSortDir = 'menu-popup-item-sort-dir';
			var itemDisplayNone = 'menu-popup-item-display-none';

			if (item.className != undefined && item.className.indexOf(itemSortField) >= 0)
			{
				if (columnId === item.value)
				{
					BX.addClass(item.getLayout().item, itemAccept);
				}
				else
				{
					BX.removeClass(item.getLayout().item, itemAccept);
				}
			}

			if (columnId === 'SORTING')
			{
				if (
					item.getId() === "delimiterDir"
					|| (item.className != undefined && item.className.indexOf(itemSortDir) >= 0)
				)
				{
					item.getLayout().item.classList.add(itemDisplayNone);
				}
			}
			else
			{
				BX.removeClass(item.getLayout().item, itemDisplayNone);
			}

			if (item.className != undefined && item.className.indexOf(itemSortDir) >= 0)
			{
				if (columnDir === item.value)
				{
					BX.addClass(item.getLayout().item, itemAccept);
				}
				else
				{
					BX.removeClass(item.getLayout().item, itemAccept);
				}
			}
		}

		menuItemsOptions.push({
			id: "popupMenuOptionsSort",
			text: '<?=GetMessageJS('TASKS_USER_SORT')?>',
			className: "menu-popup-item-none menu-popup-sort",
			events: {
				onSubMenuShow: function()
				{
					this.getSubMenu().getMenuItems().forEach(function(item)
					{
						handleStyles(item, columnId, columnDir);
					});
				}
			},
			items: [
				<?php foreach($sortFields as $sortField => $langTitle):?>
				{
					html: '<?=GetMessageJS($langTitle)?><?php if($sortField === "ACTIVITY_DATE"):?><span class="menu-popup-item-sort-field-label"><?=GetMessageJS('TASKS_BTN_SORT_RECOMMENDED_LABEL')?></span><?php endif;?>',
					value: '<?=$sortField?>',
					className: "menu-popup-item-sort-field menu-popup-item-none",
					onclick: function(event, menuItem)
					{
						onMenuItemClick('field', menuItem, this.menuItems);
					}
				},
				<?php if($sortField === 'SORTING' || $sortField === 'ACTIVITY_DATE'):?>
				{
					tabId: "popupMenuOptions",
					delimiter: true
				},
				<?php endif?>
				<?php endforeach?>
				{
					tabId: "popupMenuOptions",
					id: 'delimiterDir',
					delimiter: true
				},
				{
					tabId: "popupMenuOptions",
					className: "menu-popup-item-sort-dir menu-popup-item-accept",
					text: "<?=GetMessageJS('TASKS_BTN_SORT_DIR_ASC')?>",
					value: 'asc',
					onclick: function(event, menuItem)
					{
						onMenuItemClick('dir', menuItem, this.menuItems);
					}
				},
				{
					tabId: "popupMenuOptions",
					className: "menu-popup-item-sort-dir menu-popup-item-none",
					text: "<?=GetMessageJS('TASKS_BTN_SORT_DIR_DESC')?>",
					value: 'desc',
					onclick: function(event, menuItem)
					{
						onMenuItemClick('dir', menuItem, this.menuItems);
					}
				}
			]
		});
		<?php endif?>

		<?php if($arParams['USE_EXPORT'] == 'Y'):?>

		menuItemsOptions.push({
			tabId: "popupMenuOptions",
			delimiter: true
		});

		<?php if(TaskAccessController::can($arResult['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_IMPORT)): ?>
		menuItemsOptions.push({
			tabId: "popupMenuOptions",
			text: '<?=GetMessageJS('TASKS_BTN_IMPORT')?>',
			className: "menu-popup-item-none",
			items: [
				{
					tabId: "popupMenuOptions",
					text: '<?=GetMessageJS('TASKS_BTN_IMPORT_CSV')?>',
					className: "tasks-interface-filter-icon-excel",
					href: '<?= '/company/personal/user/'.$arResult['USER_ID'].'/tasks/import/'?>'
				}
			]
		});
		<?php endif; ?>

		<?php if(TaskAccessController::can($arResult['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_EXPORT)):?>

		var baloonShowed = false;
		var baloonLifeTime = 5000;

		var onClickExport = function(exportType = ''){
			BX.UI.StepProcessing.ProcessManager.get('EXPORT_EXCEL_PARAMS').showDialog();
		};

		menuItemsOptions.push({
			tabId: "popupMenuOptions",
			text: '<?=GetMessageJS('TASKS_BTN_EXPORT_TO_EXCEL')?>',
			className: 'menu-popup-item-none',
			onclick: function(){
				onClickExport();
			}
		});

		<?php endif; ?>

		<?php if(
		TaskAccessController::can($arResult['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_EXPORT)
		&& TaskAccessController::can($arResult['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_IMPORT)
		): ?>
		menuItemsOptions.push({
			tabId: "popupMenuOptions",
			text: '<?=GetMessageJS('TASKS_BTN_SYNC')?>',
			className: 'menu-popup-item-none',
			items: [
				{
					tabId: "popupMenuOptions",
					text: '<?=GetMessageJS('TASKS_BTN_SYNC_OUTLOOK')?>',
					className: "tasks-interface-filter-icon-outlook",
					onclick: function(event, item)
					{
						<?=CIntranetUtils::GetStsSyncURL(array('LINK_URL' => $arParams['PATH_TO_TASKS'] ?? ''), 'tasks')?>
					}
				}
			]
		});
		<?php endif; ?>
		<?php endif?>

		<?if (!empty($arParams['POPUP_MENU_ITEMS'])):?>
		<?foreach ($arParams['POPUP_MENU_ITEMS'] as $menuItem):?>
		menuItemsOptions.push({
			tabId: "<?= isset($menuItem['tabId']) ? $menuItem['tabId'] : ''?>",
			text: "<?= isset($menuItem['text']) ? $menuItem['text'] : ''?>",
			html: "<?= isset($menuItem['html']) ? $menuItem['html'] : ''?>",
			href: "<?= isset($menuItem['href']) ? $menuItem['href'] : ''?>",
			className: "<?= isset($menuItem['className']) ? $menuItem['className'] : ''?>",
			onclick: <?= isset($menuItem['onclick']) ? $menuItem['onclick'] : '""'?>,
			params: <?= isset($menuItem['params']) ? $menuItem['params'] : '{}'?>
		});
		<?endforeach;?>
		<?endif;?>

		<?php
		$viewKanbanFieldsPopup = [
			\Bitrix\Tasks\UI\ScopeDictionary::SCOPE_TASKS_KANBAN,
			\Bitrix\Tasks\UI\ScopeDictionary::SCOPE_TASKS_KANBAN_TIMELINE,
			\Bitrix\Tasks\UI\ScopeDictionary::SCOPE_TASKS_KANBAN_PERSONAL,
		];
		if (in_array($arParams['SCOPE'], $viewKanbanFieldsPopup)): ?>
		menuItemsOptions.push({
			tabId: "popupMenuOptions",
			text: '<?=GetMessageJS('TASKS_BTN_KANBAN_POPUP_TITLE_CONFIGURE_VIEW')?>',
			className: "menu-popup-item-none menu-popup-no-icon",
			onclick: function(event, item) {
				BX.Event.EventEmitter.emit('tasks-kanban-settings-fields-view');
			},
		})
		<?php endif;?>

		var buttonRect = BX("tasks-popupMenuOptions").getBoundingClientRect();
		var menu = new BX.Main.Menu({
			id: "popupMenuOptions",
			bindElement: BX("tasks-popupMenuOptions"),
			items: menuItemsOptions,
			closeByEsc: true,
			offsetLeft: buttonRect.width / 2,
			angle: true
		});

		BX.bind(BX("tasks-popupMenuOptions"), "click", BX.delegate(function() {
			if (BX.data(BX("tasks-popupMenuOptions"), "disabled") !== true)
			{
				menu.show();
			}
		}, this));
	})();
</script>
