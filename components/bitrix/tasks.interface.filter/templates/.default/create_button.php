<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Uri;

$pathToTaskTemplatesList = CComponentEngine::makePathFromTemplate(
	$arParams['PATH_TO_USER_TASKS_TEMPLATES'],
	['user_id' => $arResult['USER_ID']] // pass current user here because we need our templates list
);
$createButtonUri = new Uri(
	CComponentEngine::makePathFromTemplate(
		($arParams['MENU_GROUP_ID'] > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams['PATH_TO_USER_TASKS_TASK']),
		[
			'action' => 'edit',
			'task_id' => 0,
			'user_id' => $arParams['USER_ID'], // pass page's owner here to make him responsible automatically
			'group_id' => $arParams['MENU_GROUP_ID'],
		]
	)
);
if (isset($arParams['SCOPE']) && $arParams['SCOPE'] !== '')
{
	$createButtonUri->addParams(['SCOPE' => $arParams['SCOPE']]);
}
if ($arParams['MENU_GROUP_ID'] > 0)
{
	$createButtonUri->addParams(['GROUP_ID' => $arParams['MENU_GROUP_ID']]);
}
if ((int)$arParams['USER_ID'] !== (int)$arResult['USER_ID'])
{
	$createButtonUri->addParams(['RESPONSIBLE_ID' => $arParams['USER_ID']]);
}

$createButtonClass = 'ui-btn-split tasks-interface-filter-btn-add';
$createButtonClass .= ($arResult['IS_SCRUM_PROJECT'] ? ' ui-btn-light-border ui-btn-themes' : ' ui-btn-success');
?>

<div class="<?= $createButtonClass ?>">
	<a class="ui-btn-main" id="tasks-buttonAdd" href="<?= $createButtonUri->getUri() ?>">
		<?= GetMessage('TASKS_BTN_CREATE_TASK') ?>
	</a>
	<span id="tasks-popupMenuAdd" class="ui-btn-extra"></span>
</div>

<script type="text/javascript">
	(function() {
		function getMenuItems()
		{
			return [
				{
					tabId: 'popupMenuAdd',
					text: '<?= GetMessageJS('TASKS_BTN_ADD_TASK_BY_TASK') ?>',
					href: '<?= $createButtonUri->getUri() ?>',
					onclick : function() {
						this.close();
					}
				},
				{
					tabId: 'popupMenuAdd',
					text: '<?= GetMessageJS('TASKS_BTN_CREATE_TASK_BY_TEMPLATE') ?>',
					href: '',
					className: 'menu-popup-no-icon menu-popup-item-submenu',
					cacheable: true,
					items: [
						{
							id: 'loading',
							text: '<?= GetMessageJS('TASKS_AJAX_LOAD_TEMPLATES') ?>'
						}
					],
					events: {
						onSubMenuShow: function() {
							if (this.isSubMenuLoaded)
							{
								return;
							}

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
									this.isSubMenuLoaded = true;
									if (response.data.length > 0)
									{
										BX.Tasks.each(response.data, function(item, k) {
											this.getSubMenu().addMenuItem({
												text: BX.util.htmlspecialchars(item.TITLE),
												href: '<?= $createButtonUri->getUri() ?>' + '&TEMPLATE=' + item.ID,
												onclick : function() {
													this.getParentMenuWindow().close();
												}
											});
										}.bind(this));
									}
									else
									{
										this.getSubMenu().addMenuItem({
											text: '<?= GetMessageJS('TASKS_AJAX_EMPTY_TEMPLATES') ?>'
										});
									}
									this.getSubMenu().removeMenuItem('loading');
								}.bind(this),
								function()
								{
									this.isSubMenuLoaded = true;
									this.getSubMenu().addMenuItem({
										text: '<?= GetMessageJS('TASKS_AJAX_ERROR_LOAD_TEMPLATES') ?>'
									});
									this.getSubMenu().removeMenuItem('loading');
								}.bind(this)
							);
						}
					}
				},
				{
					tabId: 'popupMenuAdd',
					delimiter: true
				},
				{
					tabId: 'popupMenuAdd',
					text: '<?= GetMessageJS('TASKS_BTN_LIST_TASK_TEMPLATE') ?>',
					href: '<?= $pathToTaskTemplatesList ?>',
					target: '_top'
				}
			];
		}

		var createButtonExtra = BX('tasks-popupMenuAdd');
		var menu = BX.Main.MenuManager.create({
			id: 'popupMenuAdd',
			bindElement: createButtonExtra,
			items: getMenuItems(),
			closeByEsc: true,
			offsetLeft: createButtonExtra.getBoundingClientRect().width / 2,
			angle: true
		});

		BX.bind(createButtonExtra, 'click', function() {
			menu.popupWindow.show();
		});
	})();
</script>