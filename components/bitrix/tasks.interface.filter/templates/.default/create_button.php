<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$taskUrlTemplate = ($arParams['MENU_GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams['PATH_TO_USER_TASKS_TASK']);
$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATES'];
$taskTemplateUrlTemplateAction = $arParams['PATH_TO_USER_TASKS_TEMPLATES_ACTION'];
?>


<div style="margin-right: 12px" class="ui-btn-split ui-btn-success tasks-interface-filter-btn-add">
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

<script type="text/javascript">
	(function()
	{
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

		BX.bind(BX("tasks-popupMenuAdd"), "click", BX.delegate(function()
		{
			menu.popupWindow.show();
		}, this));
	})();
</script>
