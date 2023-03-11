<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arResult */

if (
	\Bitrix\Tasks\Integration\Bizproc\Document\Task::isProjectTask($arResult['DOCUMENT_TYPE'])
	|| \Bitrix\Tasks\Integration\Bizproc\Document\Task::isScrumProjectTask($arResult['DOCUMENT_TYPE'])
)
{
	$titleView = GetMessage('TASKS_AUTOMATION_CMP_TITLE_VIEW');
	$titleEdit = GetMessage('TASKS_AUTOMATION_CMP_TITLE_TASK_EDIT');
}
elseif (\Bitrix\Tasks\Integration\Bizproc\Document\Task::isPlanTask($arResult['DOCUMENT_TYPE']))
{
	$titleView = GetMessage('TASKS_AUTOMATION_CMP_TITLE_VIEW_PLAN_1');
	$titleEdit = GetMessage('TASKS_AUTOMATION_CMP_TITLE_TASK_EDIT_PLAN_1');
}
else
{
	$titleView = GetMessage('TASKS_AUTOMATION_CMP_TITLE_VIEW_STATUSES');
	$titleEdit = GetMessage('TASKS_AUTOMATION_CMP_TITLE_TASK_EDIT_STATUSES');
}

if ($arResult['TASK_CAPTION'])
{
	$titleView = GetMessage('TASKS_AUTOMATION_CMP_TITLE_TASK_VIEW', array('#TITLE#' => $arResult['TASK_CAPTION']));
}

global $APPLICATION;
\CUtil::initJSCore('tasks_integration_socialnetwork');
?>
<div class="tasks-automation">
	<?php $APPLICATION->IncludeComponent('bitrix:bizproc.automation', '', [
			'DOCUMENT_TYPE' => ['tasks', \Bitrix\Tasks\Integration\Bizproc\Document\Task::class, $arResult['DOCUMENT_TYPE']],
			'DOCUMENT_ID' => $arResult['TASK_ID'] ?: null,
			'TITLE_VIEW' => $titleView,
			'TITLE_EDIT' => $titleEdit,
			'MARKETPLACE_ROBOT_CATEGORY' => 'tasks_bots',
			'MARKETPLACE_TRIGGER_PLACEMENT' => 'TASKS_ROBOT_TRIGGERS',
			'MESSAGES' => [
				'BIZPROC_AUTOMATION_CMP_TRIGGER_HELP_2' => GetMessage('TASKS_AUTOMATION_CMP_TRIGGER_HELP_TIP_2'),
				'BIZPROC_AUTOMATION_CMP_ROBOT_HELP' => GetMessage('TASKS_AUTOMATION_CMP_ROBOT_HELP_TIP'),
				'BIZPROC_AUTOMATION_CMP_ROBOT_HELP_ARTICLE_ID' => '8233939',
			],
			'IS_TEMPLATES_SCHEME_SUPPORTED' => true,
			'CATEGORY_SELECTOR' => ['TEXT' => $arResult['GROUPS_SELECTOR']['CAPTION']],
	], $this); ?>
</div>
<script>
	BX.ready(function()
	{
		BX.message(<?= \Bitrix\Main\Web\Json::encode(\Bitrix\Main\Localization\Loc::loadLanguageFile(__file__)) ?>);

		var viewType = '<?=CUtil::JSEscape($arResult['VIEW_TYPE'])?>';

		var selectorNode = document.querySelector('[data-role="category-selector"]');

		if (viewType === 'plan')
		{
			selectorNode.textContent = '<?=GetMessageJS('TASKS_AUTOMATION_CMP_SELECTOR_ITEM_PLAN_1')?>';
		}
		else if (viewType === 'personal')
		{
			selectorNode.textContent = '<?=GetMessageJS('TASKS_AUTOMATION_CMP_SELECTOR_ITEM_PERSONAL')?>';
		}

		var menu = null;
		var groups = <?=\Bitrix\Main\Web\Json::encode($arResult['GROUPS_SELECTOR']['GROUPS'])?>;
		var currentGroupId = <?= (int)$arResult['PROJECT_ID']?>;

		BX.bind(selectorNode, 'click', function(event)
		{
			if (menu === null)
			{
				var projectMenuItems = [];

				var clickHandler = function (e, item)
				{
					menu.close();
					if (item.id === currentGroupId && viewType === 'project')
					{
						return;
					}

					// top.BX.onCustomEvent(top.window, 'BX.Kanban.ChangeGroup', [item.id, currentGroupId]);

					selectorNode.innerHTML = item.text;
					window.location.href = BX.util.add_url_param(window.location.href, {project_id: item.id, view: 'project'});
				};

				// fill menu array
				for (var i = 0, c = groups.length; i < c; i++)
				{
					projectMenuItems.push({
						id: parseInt(groups[i]["id"]),
						text: BX.util.htmlspecialchars(groups[i]["text"]),
						class: 'menu-popup-item-none',
						onclick: BX.delegate(clickHandler, this)
					});

				}
				//select new group
				if (groups.length > 0)
				{
					projectMenuItems.push({delimiter: true});
					projectMenuItems.push({
						id: "new",
						text: '<?=GetMessageJS('TASKS_AUTOMATION_CMP_CHOOSE_GROUP')?>',
						onclick: function (event, item)
						{
							menu.getPopupWindow().setAutoHide(false);
							var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
								scope: item.getContainer(),
								id: "group-selector",
								mode: "group",
								query: false,
								useSearch: true,
								useAdd: false,
								parent: this,
								popupOffsetTop: 5,
								popupOffsetLeft: 40
							});

							selector.bindEvent("item-selected", function (data)
							{
								clickHandler(null, {
									id: data.id,
									text: data.nameFormatted.length > 50
										? data.nameFormatted.substring(0, 50) + "..."
										: data.nameFormatted
								});
								selector.close();
							});

							selector.bindEvent("close", function (data)
							{
								menu.getPopupWindow().setAutoHide(true);
							});

							selector.open();
						}
					});
				}
				// create menu
				menu = BX.PopupMenu.create(
					'tasks-automation-view-selector-' + BX.util.getRandomString(),
					selectorNode,
					[
						{
							text: '<?=GetMessageJS('TASKS_AUTOMATION_CMP_SELECTOR_ITEM_PROJECTS')?>',
							items: projectMenuItems
						},
						{
							text: '<?=GetMessageJS('TASKS_AUTOMATION_CMP_SELECTOR_ITEM_PLAN_1')?>',
							onclick: function(e, item)
							{
								menu.close();
								selectorNode.textContent = item.text;
								window.location.href = BX.util.add_url_param(window.location.href, {view: 'plan'});
							}
						},
						{
							text: '<?=GetMessageJS('TASKS_AUTOMATION_CMP_SELECTOR_ITEM_PERSONAL')?>',
							onclick: function(e, item)
							{
								menu.close();
								selectorNode.textContent = item.text;
								window.location.href = BX.util.add_url_param(window.location.href, {view: 'personal'});
							}
						}
					],
					{
						autoHide: true,
						closeByEsc: true
					}
				);
			}
			menu.popupWindow.show();
		});
	});
</script>