<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('ui');

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";

\Bitrix\Main\UI\Extension::load(["ui.buttons", "ui.buttons.icons", "popup"]);
?>

<?php
if ($isBitrix24Template)
{
	$this->SetViewTarget('in_pagetitle');
}
?>

	<? if ($isBitrix24Template && array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW'] === 'Y'): ?>
		<?php
			$containerID = 'tasks_group_selector';
			if (isset($arResult['GROUPS'][$arParams['GROUP_ID']]))
			{
				$currentGroup = $arResult['GROUPS'][$arParams['GROUP_ID']];
				unset($arResult['GROUPS'][$arParams['GROUP_ID']]);
			}
			else
			{
				$currentGroup = array(
					'id'   => 'wo',
					'text' => \GetMessage('TASKS_BTN_GROUP_WO')
				);
			}
		?>

		<div class="tasks-project-btn-container" id="<?=htmlspecialcharsbx($containerID)?>">
			<div class="tasks-project-btn">
				<div class="tasks-project-btn-image">
					<? if (!empty($currentGroup['image'])): ?>
						<img src="<?= $currentGroup['image'] ?>" width="27" height="27" alt="<?= htmlspecialcharsbx($currentGroup['text']); ?>" />
					<? else: ?>
						<img src="data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2282%22%20height%3D%2282%22%20viewBox%3D%220%200%2082%2082%22%3E%3Cpath%20fill%3D%22%23FFF%22%20fill-rule%3D%22evenodd%22%20d%3D%22M46.03%2033.708s-.475%201.492-.55%201.692c-.172.256-.317.53-.433.816.1%200%201.107.47%201.107.47l3.917%201.227-.056%201.86c-.74.296-1.394.778-1.894%201.4-.19.413-.42.806-.69%201.17%203.568%201.45%205.655%203.573%205.74%205.95.058.422%202.223%208.205%202.347%209.958H72c.014-.072-.5-10.14-.5-10.216%200%200-.946-2.425-2.446-2.672-1.487-.188-2.924-.66-4.233-1.388-.864-.504-1.775-.923-2.72-1.252-.483-.425-.858-.957-1.095-1.555-.5-.623-1.152-1.105-1.894-1.4l-.055-1.86%203.917-1.227s1.01-.47%201.107-.47c-.158-.33-.338-.646-.54-.948-.075-.2-.444-1.554-.444-1.554.572.733%201.242%201.384%201.992%201.933-.667-1.246-1.238-2.542-1.708-3.876-.314-1.233-.532-2.488-.653-3.754-.27-2.353-.69-4.687-1.255-6.987-.406-1.148-1.124-2.16-2.072-2.923-1.403-.974-3.04-1.555-4.742-1.685h-.2c-1.7.13-3.333.712-4.733%201.685-.947.765-1.664%201.777-2.07%202.925-.568%202.3-.987%204.634-1.255%206.987-.103%201.295-.31%202.58-.622%203.84-.47%201.312-1.052%202.58-1.737%203.792.75-.55%201.42-1.202%201.99-1.936zM54.606%2064c0-2.976-3.336-15.56-3.336-15.56%200-1.84-2.4-3.942-7.134-5.166-1.603-.448-3.127-1.142-4.517-2.057-.3-.174-.26-1.78-.26-1.78l-1.524-.237c0-.13-.13-2.057-.13-2.057%201.824-.613%201.636-4.23%201.636-4.23%201.158.645%201.912-2.213%201.912-2.213%201.37-3.976-.682-3.736-.682-3.736.36-2.428.36-4.895%200-7.323-.912-8.053-14.646-5.867-13.018-3.24-4.014-.744-3.1%208.4-3.1%208.4l.87%202.364c-1.71%201.108-.52%202.45-.463%204%20.085%202.28%201.477%201.808%201.477%201.808.086%203.764%201.94%204.26%201.94%204.26.348%202.363.13%201.96.13%201.96l-1.65.2c.022.538-.02%201.075-.13%201.6-1.945.867-2.36%201.375-4.287%202.22-3.726%201.634-7.777%203.76-8.5%206.62C13.117%2052.695%2010.99%2064%2010.99%2064H54.606z%22/%3E%3C/svg%3E" width="27" height="27" alt="<?= htmlspecialcharsbx($currentGroup['text']); ?>" />
					<? endif; ?>
				</div>
				<div class="tasks-project-btn-text"><?= htmlspecialcharsbx($currentGroup['text']); ?></div>
			</div>
		</div>
	<? endif; ?>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<?php
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}
?>

	<?php
	if (!isset($arParams['MENU_GROUP_ID']))
	{
		$arParams['MENU_GROUP_ID'] = $arParams['GROUP_ID'];
	}

	if (isset($arParams['FILTER']) && is_array($arParams['FILTER']))
	{
		$selectors = array();

		foreach ($arParams['FILTER'] as $filterItem)
		{
			if (!(isset($filterItem['type']) &&
				  $filterItem['type'] === 'custom_entity' &&
				  isset($filterItem['selector']) &&
				  is_array($filterItem['selector']))
			)
			{
				continue;
			}

			$selector = $filterItem['selector'];
			$selectorType = isset($selector['TYPE']) ? $selector['TYPE'] : '';
			$selectorData = isset($selector['DATA']) && is_array($selector['DATA']) ? $selector['DATA'] : null;
			$selectorData['MODE'] = $selectorType;
			$selectorData['MULTI'] = $filterItem['params']['multiple'] && $filterItem['params']['multiple'] == 'Y';

			if (!empty($selectorData) && $selectorType == 'user')
			{
				$selectors[] = $selectorData;
			}
			if (!empty($selectorData) && $selectorType == 'group')
			{
				$selectors[] = $selectorData;
			}
		}

		if (!empty($selectors))
		{
			\CUtil::initJSCore(
				array(
					'tasks_integration_socialnetwork'
				)
			);
		}

		if (!empty($selectors))
		{
			?>
			<script type="text/javascript"><?
			foreach ($selectors as $groupSelector)
			{
				$selectorID = $groupSelector['ID'];
				$selectorMode = $groupSelector['MODE'];
				$fieldID = $groupSelector['FIELD_ID'];
				$multi = $groupSelector['MULTI'];
				?>BX.ready(
					function()
					{
						BX.FilterEntitySelector.create(
							"<?= \CUtil::JSEscape($selectorID)?>",
							{
								fieldId: "<?= \CUtil::JSEscape($fieldID)?>",
								mode: "<?= \CUtil::JSEscape($selectorMode)?>",
								multi: <?= $multi ? 'true' : 'false'?>
							}
						);
					}
				);<?
				}
				?></script><?
		}
	}
	?>
	<? if (!$isBitrix24Template): ?>

		<div class="tasks-interface-filter-container">
			<? endif ?>

			<?php
			$taskUrlTemplate = ($arParams['MENU_GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams['PATH_TO_USER_TASKS_TASK']);
			$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATES'];
			$taskTemplateUrlTemplateAction = $arParams['PATH_TO_USER_TASKS_TEMPLATES_ACTION'];
			?>



			<? if ($arParams['MENU_GROUP_ID'] == 0 || $arParams['SHOW_CREATE_TASK_BUTTON'] != 'N'): ?>

				<div style="margin-right: 12px" class="ui-btn-split ui-btn-primary tasks-interface-filter-btn-add">
					<a class="ui-btn-main" id="tasks-buttonAdd"
					   href="<?=CComponentEngine::makePathFromTemplate(
						   $taskUrlTemplate,
						   array(
							   'action'   => 'edit',
							   'task_id'  => 0,
							   'user_id'  => $arParams['USER_ID'],
							   'group_id' => $arParams['MENU_GROUP_ID']
						   )
					   )?>"
					><?=GetMessage('TASKS_BTN_ADD_TASK')?></a>
					<span id="tasks-popupMenuAdd" class="ui-btn-extra"></span>
				</div>

			<? endif; ?>

			<div class="tasks-interface-filter pagetitle-container<?php if (!$isBitrix24Template): ?> pagetitle-container-light<? endif ?> pagetitle-flexible-space">
				<?php
				$filterComponentData = [
					"FILTER_ID" => $arParams["FILTER_ID"],
					"GRID_ID" => $arParams["GRID_ID"],
					"FILTER" => $arParams["FILTER"],
					"FILTER_PRESETS" => $arParams["PRESETS"],
					"ENABLE_LABEL" => true,
					'ENABLE_LIVE_SEARCH' => $arParams['USE_LIVE_SEARCH'] == 'Y',
					'RESET_TO_DEFAULT_MODE' => true,
				];

				if ($arResult['LIMIT_EXCEEDED'])
				{
					$filterComponentData['LIMITS'] = $arResult['LIMITS'];
				}

				$APPLICATION->IncludeComponent(
					"bitrix:main.ui.filter",
					"",
					$filterComponentData,
					$component,
					["HIDE_ICONS" => true]
				); ?>
			</div>

			<? if ($arParams['USE_GROUP_SELECTOR'] == 'Y' && $arParams['PROJECT_VIEW'] !== 'Y'): ?>
				<?
				$containerID = 'tasks_group_selector';
				if (isset($arResult['GROUPS'][$arParams['GROUP_ID']]))
				{
					$currentGroup = $arResult['GROUPS'][$arParams['GROUP_ID']];
					unset($arResult['GROUPS'][$arParams['GROUP_ID']]);
				}
				else
				{
					$currentGroup = array(
						'id'   => 'wo',
						'text' => \GetMessage('TASKS_BTN_GROUP_WO')
					);
				}
				?>

				<div class="pagetitle-container pagetitle-flexible-space">
					<div id="<?=htmlspecialcharsbx($containerID)?>"
						 class="tasks-interface-toolbar-button-container">
						<div class="webform-small-button webform-small-button-transparent webform-small-button-dropdown">
							<span class="webform-small-button-text"
								  id="<?=htmlspecialcharsbx($containerID)?>_text">
									<?=htmlspecialcharsbx($currentGroup['text'])?>
								</span>
							<span class="webform-small-button-icon"></span>
						</div>
					</div>
				</div>
			<? endif; ?>

			<? if ($arParams['USE_GROUP_SELECTOR'] === 'Y' || $arParams['PROJECT_VIEW'] === 'Y'): ?>

				<script type="text/javascript">
					BX.ready(
						function()
						{
							BX.TasksGroupsSelectorInit({
								groupId: <?= intval($arParams['GROUP_ID'])?>,
								selectorId: "<?= \CUtil::JSEscape($containerID)?>",
								buttonAddId: "tasks-buttonAdd",
								pathTaskAdd: "<?= \CUtil::JSEscape(\CComponentEngine::makePathFromTemplate(
									$arParams['MENU_GROUP_ID'] > 0
										? $arParams['PATH_TO_GROUP_TASKS_TASK']
										: $arParams['PATH_TO_USER_TASKS_TASK'],
									array(
										'action'   => 'edit',
										'task_id'  => 0,
										'user_id'  => $arResult['USER_ID'],
										'group_id' => $arParams['MENU_GROUP_ID']
									)
								))?>",
								groups: <?= \CUtil::PhpToJSObject(array_values($arResult['GROUPS']))?>,
								currentGroup: <?= \CUtil::PhpToJSObject($currentGroup)?>,
								groupLimit: <?= intval($arParams['GROUP_SELECTOR_LIMIT'])?>,
								messages: {
									TASKS_BTN_GROUP_WO: "<?= \GetMessageJS('TASKS_BTN_GROUP_WO')?>",
									TASKS_BTN_GROUP_SELECT: "<?= \GetMessageJS('TASKS_BTN_GROUP_SELECT')?>"
								},
								offsetLeft: <?= $arParams['PROJECT_VIEW'] === 'Y' ? 19 : 0 ?>
							});
						}
					);
				</script>

				<? if ($arParams['PROJECT_VIEW'] === 'Y'): ?>
					<script type="text/javascript">
						BX.addCustomEvent(window, 'BX.Tasks.ChangeGroup', function(newId) {
							BX.Tasks.ProjectSelector.reloadProject(newId);
						});
					</script>
				<? endif; ?>

			<? endif; ?>

			<?if ($arResult['SPRINTS'] && isset($arResult['SPRINTS'][$arParams['SPRINT_ID']])):
				$currentSprint = $arResult['SPRINTS'][$arParams['SPRINT_ID']];
				$containerID = 'tasks_sprint_selector';
				?>
				<div class="pagetitle-container pagetitle-flexible-space">
					<div id="<?= $containerID;?>"
						 class="tasks-interface-toolbar-button-container">
						<div class="webform-small-button webform-small-button-transparent webform-small-button-dropdown">
							<span class="webform-small-button-text"
								  id="<?= $containerID;?>_text">
									<?= \htmlspecialcharsbx($currentSprint['START_TIME'])?>
									&mdash;
									<?= \htmlspecialcharsbx($currentSprint['FINISH_TIME'])?>
								</span>
							<span class="webform-small-button-icon"></span>
						</div>
					</div>
				</div>
				<script type="text/javascript">
					BX.ready(function()
					{
						BX.Tasks.SprintSelector(
							<?= $containerID;?>,
							<?= \CUtil::phpToJSObject(array_values($arResult['SPRINTS']));?>,
							{
								sprintId: <?= $arParams['SPRINT_ID'];?>,
								groupId: <?= $arParams['GROUP_ID'];?>
							}
						);
					});
				</script>
			<?endif;?>

			<div class="pagetitle-container pagetitle-align-right-container">

				<?php if ($arParams['SHOW_USER_SORT'] == 'Y' ||
						  $arParams['USE_GROUP_BY_SUBTASKS'] == 'Y' ||
						  $arParams['USE_GROUP_BY_GROUPS'] == 'Y' ||
						  $arParams['USE_EXPORT'] == 'Y' ||
						  !empty($arParams['POPUP_MENU_ITEMS'])
				): ?>
					<button id="tasks-popupMenuOptions" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting webform-cogwheel"></button>
				<?php endif ?>

				<? if ($arParams["SHOW_QUICK_FORM_BUTTON"] != "N"): ?>
					<button class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting ui-btn-icon-task tasks-quick-form-button"
						id="task-quick-form-button"
						title="<?=GetMessage("TASKS_ADD_QUICK_TASK")?>"
					></button>
				<? endif ?>

			</div>

			<? if (!$isBitrix24Template): ?>
		</div>
	<? endif ?>

	<?php if ($arParams['SHOW_USER_SORT'] == 'Y' ||
			  $arParams['USE_GROUP_BY_SUBTASKS'] == 'Y' ||
			  $arParams['USE_GROUP_BY_GROUPS'] == 'Y' ||
			  $arParams['USE_EXPORT'] == 'Y' ||
			  !empty($arParams['POPUP_MENU_ITEMS'])
	): ?>
		<script type="text/javascript">
			(function()
			{
				var menuItemsOptions = [];
				var columnId = '<?=$arParams['SORT_FIELD']?>';
				var columnDir = '<?=$arParams['SORT_FIELD_DIR']?>';

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
				$instance = \CTaskListState::getInstance(\Bitrix\Tasks\Util\User::getId());
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
						var query = new BX.Tasks.Util.Query({autoExec: true});
						query.add('ui.listcontrols.togglegroupbytasks', {}, {}, BX.delegate(function(errors, data) {
							if (!errors.checkHasErrors())
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
						}));
					}
				});
				<?php endif?>

				<?php if($arParams['USE_GROUP_BY_GROUPS'] == 'Y'):?>
				<?php
				$instance = \CTaskListState::getInstance(\Bitrix\Tasks\Util\User::getId());
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
						var query = new BX.Tasks.Util.Query({autoExec: true});
						query.add('ui.listcontrols.togglegroupbygroups', {}, {}, BX.delegate(function(errors, data) {
							if (!errors.checkHasErrors())
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
						}));
					}
				});
				<?php endif?>

				<?php if($arParams['SHOW_USER_SORT'] == 'Y'):?>

				menuItemsOptions.push({
					tabId: "popupMenuOptions",
					delimiter: true
				});

				<?php
				$sortFields = \Bitrix\Tasks\Ui\Controls\Column::getFieldsForSorting();
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
						<?php foreach($sortFields as $sortField):?>
						{
							text: '<?=GetMessageJS('TASKS_BTN_SORT_'.$sortField)?><?php if($sortField === "ACTIVITY_DATE"):?><span class="menu-popup-item-sort-field-label"><?=GetMessageJS('TASKS_BTN_SORT_RECOMMENDED_LABEL')?></span><?php endif;?>',
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

					<?php if(\Bitrix\Tasks\Access\TaskAccessController::can($arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_IMPORT)): ?>
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

					<?php if(\Bitrix\Tasks\Access\TaskAccessController::can($arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_EXPORT)): ?>
					menuItemsOptions.push({
						tabId: "popupMenuOptions",
						text: '<?=GetMessageJS('TASKS_BTN_EXPORT')?>',
						className: "<?=$groupBySubTasks ? 'menu-popup-item-none' : 'menu-popup-item-none'?>",
						items: [
							{
								tabId: "popupMenuOptions",
								text: '<?=GetMessageJS('TASKS_BTN_EXPORT_EXCEL')?>',
								className: "tasks-interface-filter-icon-excel",
								href: '<?=$arParams['PATH_TO_TASKS']?>?EXPORT_AS=EXCEL&ncc=1'
							}
						]
					});
					<?php endif; ?>

					<?php if(
							\Bitrix\Tasks\Access\TaskAccessController::can($arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_EXPORT)
							&& \Bitrix\Tasks\Access\TaskAccessController::can($arParams['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_IMPORT)
					): ?>
					menuItemsOptions.push({
						tabId: "popupMenuOptions",
						text: '<?=GetMessageJS('TASKS_BTN_SYNC')?>',
						className: "<?=$groupBySubTasks ? 'menu-popup-item-none' : 'menu-popup-item-none'?>",
						items: [
							{
								tabId: "popupMenuOptions",
								text: '<?=GetMessageJS('TASKS_BTN_SYNC_OUTLOOK')?>',
								className: "tasks-interface-filter-icon-outlook",
								onclick: function(event, item)
								{
									<?=CIntranetUtils::GetStsSyncURL(array('LINK_URL' => $arParams['PATH_TO_TASKS']), 'tasks')?>
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
						href: "<?= isset($menuItem['href']) ? $menuItem['href'] : ''?>",
						className: "<?= isset($menuItem['className']) ? $menuItem['className'] : ''?>",
						onclick: <?= isset($menuItem['onclick']) ? $menuItem['onclick'] : '""'?>,
						params: <?= isset($menuItem['params']) ? $menuItem['params'] : '{}'?>
					});
					<?endforeach;?>
				<?endif;?>

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
	<?php endif ?>

	<script type="text/javascript">
		(function()
		{
			<?php
			if($arParams['MENU_GROUP_ID'] == 0 || $arParams['SHOW_CREATE_TASK_BUTTON'] != 'N'):
			?>
				var menuItemsLists = [];

				menuItemsLists.push({
					tabId: "popupMenuAdd",
					text: "<?= GetMessageJS('TASKS_BTN_ADD_TASK')?>",
					href: "<?= CComponentEngine::makePathFromTemplate(
						$taskUrlTemplate,
						array(
							'action'   => 'edit',
							'task_id'  => 0,
							'user_id'  => $arParams['USER_ID'],
							'group_id' => $arParams['MENU_GROUP_ID']
						)
					)?>"
				});

				menuItemsLists.push({
					tabId: "popupMenuAdd",
					text: "<?= GetMessageJS('TASKS_BTN_ADD_TASK_BY_TEMPLATE')?>",
					href: "",
					className: "menu-popup-no-icon menu-popup-item-submenu",
					cacheable: true,
					events: {
						onSubMenuShow: function()
						{
							if (this.subMenuLoaded)
							{
								return;
							}

							var query = new BX.Tasks.Util.Query({
								autoExec: true
							});

							var submenu = this.getSubMenu();
							submenu.removeMenuItem("loading");

							query.add(
								'task.template.find',
								{
									parameters: {
										select: ['ID', 'TITLE'],
										order: {ID: 'DESC'},
										filter: {ZOMBIE: 'N'}
									}
								},
								{},
								BX.delegate(function(errors, data)
								{
									this.subMenuLoaded = true;

									if (!errors.checkHasErrors())
									{
										var tasksTemplateUrlTemplate = '<?=CComponentEngine::makePathFromTemplate(
											$taskUrlTemplate,
											array(
												'action' => 'edit',
												'task_id' => 0,
												'user_id' => $arResult['USER_ID'],
												'group_id' => $arParams['MENU_GROUP_ID']
											)
										)?>';

										var subMenu = [];
										if (data.RESULT.DATA.length > 0)
										{
											BX.Tasks.each(data.RESULT.DATA, function(item, k)
											{
												subMenu.push({
													text: BX.util.htmlspecialchars(item.TITLE),
													href: tasksTemplateUrlTemplate + '?TEMPLATE=' + item.ID
												});
											}.bind(this));
										}
										else
										{
											subMenu.push({text: '<?=GetMessageJS('TASKS_AJAX_EMPTY_TEMPLATES')?>'});
										}
										this.addSubMenu(subMenu);
										this.showSubMenu();
									}
									else
									{
										this.addSubMenu([
											{text: '<?=GetMessageJS('TASKS_AJAX_ERROR_LOAD_TEMPLATES')?>'},
										]);

										this.showSubMenu();
									}
								}, this)
							);
						}
					},
					items: [
						{
							id: "loading",
							text: "<?=GetMessageJS('TASKS_AJAX_LOAD_TEMPLATES')?>"
						}
					]
				});

				menuItemsLists.push({
					tabId: "popupMenuAdd",
					delimiter: true
				});

				menuItemsLists.push({
					tabId: "popupMenuAdd",
					text: "<?= GetMessageJS('TASKS_BTN_LIST_TASK_TEMPLATE')?>",
					href: "<?= CComponentEngine::makePathFromTemplate(
						$taskTemplateUrlTemplate,
						array('user_id' => $arResult['USER_ID'])
					)?>",
					target: '_top'
				});


				var buttonRect = BX("tasks-popupMenuAdd").getBoundingClientRect();
				var menu = BX.PopupMenu.create(
					"popupMenuAdd",
					BX("tasks-popupMenuAdd"),
					menuItemsLists,
					{
						closeByEsc: true,
						offsetLeft: buttonRect.width / 2,
						angle: true
					}
				);
			<?php endif?>

			BX.bind(BX("tasks-popupMenuAdd"), "click", BX.delegate(function()
			{
				menu.popupWindow.show();
			}, this));

		})();
	</script>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>
<?php CJSCore::Init("spotlight"); ?>