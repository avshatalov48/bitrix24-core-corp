<?php

use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons\Split\Button as SplitButton;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Uri;

/**
 * @var array $arResult
 * @var array $arParams
 */

$isCollab = $arResult['IS_COLLAB'];
$groupId = (int)$arParams['MENU_GROUP_ID'];
$currentUserId = (int)$arResult['USER_ID'];
$targetUserId = (int)$arParams['USER_ID'];
$isV2Form = \Bitrix\Tasks\V2\FormV2Feature::isOn('miniform') || \Bitrix\Tasks\V2\FormV2Feature::isOn('', $groupId);

$pathToTaskTemplatesList = CComponentEngine::makePathFromTemplate(
	$arParams['PATH_TO_USER_TASKS_TEMPLATES'],
	['user_id' => $currentUserId] // pass current user here because we need our templates list
);
$createButtonUri = new Uri(
	CComponentEngine::makePathFromTemplate(
		($groupId > 0 ? $arParams['PATH_TO_GROUP_TASKS_TASK'] : $arParams['PATH_TO_USER_TASKS_TASK']),
		[
			'action' => 'edit',
			'task_id' => 0,
			'user_id' => $targetUserId, // pass page's owner here to make him responsible automatically
			'group_id' => $groupId,
		]
	)
);
if (isset($arParams['SCOPE']) && $arParams['SCOPE'] !== '')
{
	$createButtonUri->addParams(['SCOPE' => $arParams['SCOPE']]);
}
if ($groupId > 0)
{
	$createButtonUri->addParams(['GROUP_ID' => $groupId]);
}
if ($currentUserId !== $targetUserId)
{
	$createButtonUri->addParams(['RESPONSIBLE_ID' => $arParams['USER_ID']]);
}

$analytics = \Bitrix\Tasks\Helper\Analytics::getInstance($currentUserId);

$createButtonUri->addParams([
	'ta_sec' => $arResult['CREATE_BUTTON_ANALYTICS']['sectionType'] ?? '',
	'ta_sub' => $arResult['CREATE_BUTTON_ANALYTICS']['viewState'] ?? '',
	'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['create_button'],
	'p1' => $analytics->getIsDemoParameter(),
	'p2' => $analytics->getUserTypeParameter(),
]);

if ($isCollab)
{
	$createButtonUri->addParams([
		'p4' => $analytics->getCollabParameter($groupId),
	]);
}

$mainButton = $isV2Form ? [] : [
	'link' => $createButtonUri->getUri(),
	'tag' =>  Buttons\Tag::LINK,
];

$splitButton = new SplitButton([
	'text' => Loc::getMessage('TASKS_BTN_CREATE_TASK'),
	'mainButton' => $mainButton,
	'menuButton' => [
		'icon' => Buttons\Icon::SETTING,
	],
]);
$splitButton->addAttribute('id', 'tasks-buttonAdd');
$splitButton->getMenuButton()->addAttribute('id', 'tasks-popupMenuAdd');
Toolbar::addButton($splitButton, ButtonLocation::AFTER_TITLE);

if (!$arResult['IS_SCRUM_PROJECT'])
{
	$rolesButton = (new Buttons\Button())
		->setText($arResult['roles']['selectedRoleName'] ?? Loc::getMessage('TASKS_ALL_ROLES'))
		->setCollapsedIcon(Buttons\Icon::TWO_PERSONS)
		->setCounter($arResult['roles']['totalCounter'])
		->setStyle(Buttons\AirButtonStyle::OUTLINE)
		->setDropdown()
		->addAttribute('id', 'tasks-buttonRoles')
	;
	Toolbar::addButton($rolesButton, ButtonLocation::AFTER_TITLE);
}

?>

<script>
	(function() {
		function getMenuItems()
		{
			const menuItems = [{
				tabId: 'popupMenuAdd',
				text: '<?= GetMessageJS('TASKS_BTN_ADD_TASK_BY_TASK') ?>',
				href: '<?= $createButtonUri->getUri() ?>',
				onclick : function() {
					this.close();
				},
			}];

			const isTemplatesAvailable = <?=CUtil::PhpToJSObject($arResult['IS_TEMPLATES_AVAILABLE'])?>;
			if (isTemplatesAvailable)
			{
				menuItems.push(
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
					},
				);
			}

			return menuItems;
		}

		const createButtonExtra = BX('tasks-popupMenuAdd');
		const menu = BX.Main.MenuManager.create({
			id: 'popupMenuAdd',
			bindElement: createButtonExtra,
			items: getMenuItems(),
			closeByEsc: true,
			offsetLeft: createButtonExtra.getBoundingClientRect().width / 2,
			angle: true
		});

		BX.bind(createButtonExtra, 'click', () => {
			menu.popupWindow.show();
		});
	})();
</script>
