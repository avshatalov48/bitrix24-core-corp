<?php

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CMain $APPLICATION */

$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24" || SITE_TEMPLATE_ID === 'air';
$isV2Form = \Bitrix\Tasks\V2\FormV2Feature::isOn('miniform');

Loader::includeModule('ui');
Extension::load([
	"ui.entity-selector",
	"ui.buttons",
	"ui.buttons.icons",
	"popup",
	"ui.fonts.opensans",
	"ui.dialogs.checkbox-list",
	'ui.tour',
	'ui.design-tokens',
	'spotlight',
	'ui.system.menu',
]);

if ($isV2Form)
{
	Extension::load('tasks.v2.application.task-card');
}

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

if ($isBitrix24Template)
{
	if (array_key_exists('PROJECT_VIEW', $arParams) && $arParams['PROJECT_VIEW'] === 'Y')
	{
		include(__DIR__.'/project_selector.php');
	}
}

if (isset($arParams['FILTER']) && is_array($arParams['FILTER']))
{
	include(__DIR__ . '/filter_selector.php');
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$relationToId = $request->get('relationToId');

$showCreateButton = (int)$arParams['MENU_GROUP_ID'] === 0 || $arParams['SHOW_CREATE_TASK_BUTTON'] !== 'N';
if (!$relationToId && $showCreateButton)
{
	include(__DIR__ . '/create_button.php');
}

if ($relationToId)
{
	include(__DIR__ . '/add_relation_button.php');
}

include(__DIR__.'/filter.php');

if ($arParams['USE_GROUP_SELECTOR'] === 'Y' && $arParams['PROJECT_VIEW'] !== 'Y')
{
	include(__DIR__.'/group_selector.php');
}

if ($arResult['SPRINT'])
{
	include(__DIR__.'/sprint_selector.php');
}

$showPopupMenu = $arParams['SHOW_USER_SORT'] === 'Y'
	|| $arParams['USE_GROUP_BY_SUBTASKS'] === 'Y'
	|| $arParams['USE_GROUP_BY_GROUPS'] === 'Y'
	|| $arParams['USE_EXPORT'] == 'Y'
	|| !empty($arParams['POPUP_MENU_ITEMS'])
;

if ($showPopupMenu)
{
	include(__DIR__.'/popup_menu.php');
}

if (!$relationToId && $arParams["SHOW_QUICK_FORM_BUTTON"] !== "N")
{
	include(__DIR__.'/quick_form.php');
}
?>
<script>
	BX.ready(() => {
		BX.message({
			TASKS_ALL_ROLES: '<?= GetMessageJS('TASKS_ALL_ROLES') ?>',
			TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE: '<?= GetMessageJS('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE') ?>',
			TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT: '<?= GetMessageJS('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT_V2') ?> ',
		});

		const groupId = <?= !empty($arParams['GROUP_ID']) ? (int)$arParams['GROUP_ID'] : 'null' ?>;
		const analytics = {
			context: '<?= CUtil::JSEscape($arResult['CREATE_BUTTON_ANALYTICS']['sectionType']) ?>',
			additionalContext: '<?= CUtil::JSEscape($arResult['CREATE_BUTTON_ANALYTICS']['viewState']) ?>',
			element: '<?= \Bitrix\Tasks\Helper\Analytics::ELEMENT['create_button'] ?>',
		};

		new BX.Tasks.TasksInterfaceFilter({
			filterId: '<?= CUtil::JSEscape($arParams['FILTER_ID']) ?>',
			createNode: document.getElementById('tasks-buttonAdd'),
			roles: {
				...<?= Json::encode($arResult['roles']) ?>,
				button: document.getElementById('tasks-buttonRoles'),
				analytics,
				groupId,
			},
			showPresetTourGuide: <?= $arResult['showPresetTourGuide'] ? 'true' : 'false' ?>,
			isV2Form: <?= $isV2Form ? 'true' : 'false' ?>,
			groupId,
			analytics,
		});
	})
</script>
