<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Uri;

$taskUrlTemplate = ($arParams['MENU_GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams['PATH_TO_USER_TASKS_TASK']);
$taskTemplateUrlTemplate = $arParams['PATH_TO_USER_TASKS_TEMPLATES'];
$taskTemplateUrlTemplateAction = $arParams['PATH_TO_USER_TASKS_TEMPLATES_ACTION'];

$createButtonClass = 'ui-btn-split tasks-interface-filter-btn-add';
$createButtonClass .= ($arResult['IS_SCRUM_PROJECT'] ? ' ui-btn-light-border ui-btn-themes' : ' ui-btn-success');
$createButtonUri = new Uri(
	CComponentEngine::makePathFromTemplate(
		$taskUrlTemplate,
		[
			'action' => 'edit',
			'task_id' => 0,
			'user_id' => $arParams['USER_ID'],
			'group_id' => $arParams['MENU_GROUP_ID'],
		]
	)
);
if (isset($arParams['SCOPE']) && $arParams['SCOPE'] !== '')
{
	$createButtonUri->addParams(['SCOPE' => $arParams['SCOPE']]);
}
?>

<div class="<?= $createButtonClass ?>">
	<a class="ui-btn-main" id="tasks-buttonAdd" href="<?= $createButtonUri->getUri() ?>"><?= GetMessage('TASKS_BTN_ADD_TASK') ?></a>
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

					var submenu = this.getSubMenu();
					submenu.removeMenuItem("loading");

					BX.ajax.runComponentAction('bitrix:tasks.templates.list', 'getList', {
						mode: 'class',
						data: {
							select: ['ID', 'TITLE'],
							order: {ID: 'DESC'},
							filter: {ZOMBIE: 'N'}
						}
					}).then(
						function(response)
						{
							this.subMenuLoaded = true;

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
							if (response.data.length > 0)
							{
								BX.Tasks.each(response.data, function(item, k)
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
						}.bind(this),
						function(response)
						{
							this.subMenuLoaded = true;

							this.addSubMenu([
								{text: '<?=GetMessageJS('TASKS_AJAX_ERROR_LOAD_TEMPLATES')?>'},
							]);

							this.showSubMenu();
						}.bind(this)
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
