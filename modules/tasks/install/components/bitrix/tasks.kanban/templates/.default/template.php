<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Text\HtmlFilter;

\Bitrix\Main\Loader::includeModule('ui');
/** intranet-settings-support */
if (($arResult['IS_TOOL_AVAILABLE'] ?? null) === false)
{
	$APPLICATION->IncludeComponent("bitrix:tasks.error", "limit", [
		'LIMIT_CODE' => RestrictionUrl::TASK_LIMIT_OFF_SLIDER_URL,
		'SOURCE' => 'kanban',
	]);

	return;
}

if (!empty($arResult['ERRORS']))
{
	ShowError(implode("\n", $arResult['ERRORS']));
	return;
}

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Integration\Network\MemberSelector;
use Bitrix\Tasks\Integration\Socialnetwork\Context\Context;
use Bitrix\Tasks\Kanban\Sort\Item\MenuItem;
use Bitrix\Tasks\Kanban\Sort\Menu;
use Bitrix\Tasks\UI\Filter;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\UI\ScopeDictionary;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var array $arResult */
/** @var array $arParams */
/** @var Application $APPLICATION */
/** @var CBitrixComponent $component */
/** @var $isBitrix24Template */

Loc::loadMessages(__FILE__);

$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');

$isIFrame = isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y';

$data = $arResult['DATA'];

if ($arParams['TIMELINE_MODE'] === 'Y')
{
	$type = 'TL';
	$scope = ScopeDictionary::SCOPE_TASKS_KANBAN_TIMELINE;
}
else if ($arParams['PERSONAL'] === 'Y')
{
	$type = 'P';
	$scope = ScopeDictionary::SCOPE_TASKS_KANBAN_PERSONAL;
}
else
{
	$type = 'K';
	$scope = ScopeDictionary::SCOPE_TASKS_KANBAN;
}

$demoAccess = $arParams['PERSONAL'] !== 'Y'
	&& CJSCore::IsExtRegistered('intranet_notify_dialog')
	&& Loader::includeModule('im');

$emptyKanban = (int)$arParams['GROUP_ID'] === 0 && $arParams['PERSONAL'] !== 'Y';

$clientDate = date(Date::convertFormatToPhp(FORMAT_DATE), (time() + CTimeZone::GetOffset()));
$clientTime = date(Date::convertFormatToPhp(FORMAT_DATETIME), (time() + CTimeZone::GetOffset()));

// js extension reg
CJSCore::Init([
	'task_kanban',
	'intranet_notify_dialog',
]);
Extension::load([
	'tasks.kanban-sort',
	'ui.notification',
	'ui.dialogs.messagebox',
	'ui.counter',
	'ui.label',
	'ui.tour',
	'pull.queuemanager',
	'ui.avatar',
]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$collabClass = $arResult['IS_COLLAB'] ? 'sn-collab-tasks__wrapper' : '';

if ($arResult['IS_COLLAB'])
{
	Toolbar::deleteFavoriteStar();
	$this->SetViewTarget('in_pagetitle') ?>

	<div class="sn-collab-icon__wrapper">
		<div id="sn-collab-icon-<?=HtmlFilter::encode($arParams["GROUP_ID"])?>" class="sn-collab-icon__hexagon-bg"></div>
	</div>
	<div class="sn-collab__subtitle"><?=HtmlFilter::encode($arResult["COLLAB_NAME"])?></div>

	<?php $this->EndViewTarget();
}


if (!$emptyKanban)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass',
		($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background no-hidden' . ' ' . $collabClass);
}

$workMode = StagesTable::getWorkMode();
$isTimeline = ($workMode === StagesTable::WORK_MODE_TIMELINE);
$isMyPlan = ($workMode === StagesTable::WORK_MODE_USER);
$isSprintView = ($workMode == StagesTable::WORK_MODE_ACTIVE_SPRINT);

$viewMode = '';
if ($isMyPlan)
{
	$viewMode = 'myPlan';
}
elseif ($isTimeline)
{
	$viewMode = 'timeline';
}

$order = $arResult['NEW_TASKS_ORDER'] ?? 'actual';
$styleAccepted = 'menu-popup-item-accept';
$styleNone = 'menu-popup-item-none';
$menuId = 'popupMenuOptions';

if (isset($arParams['INCLUDE_INTERFACE_HEADER']) && $arParams['INCLUDE_INTERFACE_HEADER'] === 'Y')
{
	$filterInstance = \Bitrix\Tasks\Helper\Filter::getInstance($arParams["USER_ID"], $arParams["GROUP_ID"])
		->setGanttMode(false);

	$filter = $filterInstance->getFilters();
	$presets = $filterInstance->getAllPresets();
	$gridID = $filterInstance->getId();

	if ($isBitrix24Template)
	{
		$this->SetViewTarget('inside_pagetitle');
	}

	$showViewMode = (
		(
			isset($arParams['KANBAN_SHOW_VIEW_MODE'])
			&& $arParams['KANBAN_SHOW_VIEW_MODE'] === 'Y'
		)
		|| $isMyPlan
		|| $isTimeline
		|| !($workMode === StagesTable::WORK_MODE_GROUP && $arParams['GROUP_ID'] > 0)
	);

	$group = Bitrix\Socialnetwork\Item\Workgroup::getById($arParams['GROUP_ID']);
	if ($group && $group->isScrumProject())
	{
		$showViewMode = ($isSprintView ? false : $showViewMode);
	}
	$menu = new Menu($menuId, $order);
	$sortDesc = (new MenuItem())
		->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().selectCustomOrder)')
		->setType(MenuItem::TYPE_SUB)
		->setOrder(MenuItem::SORT_DESC)
		->setClassName($order === MenuItem::SORT_DESC ? $styleAccepted : $styleNone)
		->setHtml(
			'<span data-id=\'kanban-sort-desc\'>' . Loc::getMessage('TASKS_KANBAN_SORT_DESC') . '</span>'
		);

	$sortAsc = (new MenuItem())
		->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().selectCustomOrder)')
		->setType(MenuItem::TYPE_SUB)
		->setOrder(MenuItem::SORT_ASC)
		->setClassName($order === MenuItem::SORT_ASC ? $styleAccepted : $styleNone)
		->setHtml(
			'<span data-id=\'kanban-sort-asc\'>' . Loc::getMessage('TASKS_KANBAN_SORT_ASC') . '</span>'
		);

	$tasksTitle = (new MenuItem())
		->setType(MenuItem::TYPE_SUB)
		->setHtml('<b>' . Loc::getMessage('TASKS_KANBAN_NEW_TASKS_SORT_TITLE') . '</b>');

	$sortTitle = (new MenuItem())
		->setHtml('<b>' . Loc::getMessage('TASKS_KANBAN_SORT_TITLE') . '</b>');

	$sortActual = (new MenuItem())
		->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().disableCustomSort)')
		->setOrder(MenuItem::SORT_ACTUAL)
		->setClassName($order === MenuItem::SORT_ACTUAL ? $styleAccepted : $styleNone)
		->setHtml(
			Loc::getMessage('TASKS_KANBAN_SORT_ACTUAL')
			. '<span class=\"menu-popup-item-sort-field-label\">'
			. Loc::getMessage("TASKS_KANBAN_SORT_ACTUAL_RECOMMENDED_LABEL")
			. '</span>'
		);

	$sortMy = (new MenuItem())
		->setOnClick('BX.delegate(BX.Tasks.KanbanSort.getInstance().enableCustomSort)')
		->setClassName($menu->isCustomSort() ? $styleAccepted : $styleNone)
		->addItem($tasksTitle)
		->addItem($sortDesc)
		->addItem($sortAsc)
		->setHtml(Loc::getMessage('TASKS_KANBAN_SORT_MY_SORT'));

	$menu
		->addItem($sortTitle)
		->addItem($sortActual)
		->addItem($sortMy)
		->addItem($tasksTitle)
		->addItem($sortDesc)
		->addItem($sortAsc);

	$popupSortItems = $menu->toArray();

if ($arResult['CONTEXT'] !== Context::getSpaces())
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.header',
		'',
		[
			'FILTER_ID' => $gridID,
			'GRID_ID' => $gridID,
			'FILTER' => $filter,
			'PRESETS' => $presets,
			'USER_ID' => $arParams['USER_ID'] ?? null,
			'GROUP_ID' => $arParams['GROUP_ID'] ?? null,
			'SPRINT_ID' => $arParams['SPRINT_ID'] ?? null,
			'SPRINT_SELECTED' => $arParams['SPRINT_SELECTED'] ?? null,
			'MENU_GROUP_ID' =>
				!array_key_exists('GROUP_ID_FORCED', $arParams)
				|| !$arParams['GROUP_ID_FORCED']
				|| (isset($arParams['PERSONAL']) && $arParams['PERSONAL'] === 'Y')
					? $arParams['GROUP_ID']
					: 0,

			'SHOW_VIEW_MODE' => ($showViewMode ? 'Y' : 'N'),
			'SHOW_FILTER' => ($isSprintView && $group->isScrumProject() ? 'N' : 'Y'),
			'USE_AJAX_ROLE_FILTER' => $arParams['PERSONAL'] == 'Y' ? 'Y' : 'N',
			'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'] ?? null,
			'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'] ?? null,
			'MARK_SECTION_PROJECTS' => $arParams['MARK_SECTION_PROJECTS'] ?? null,
			'PROJECT_VIEW' => $arParams['PROJECT_VIEW'] ?? null,
			'PATH_TO_USER_TASKS' => $arParams['~PATH_TO_USER_TASKS'] ?? null,
			'PATH_TO_USER_TASKS_TASK' => $arParams['~PATH_TO_USER_TASKS_TASK'] ?? null,
			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['~PATH_TO_USER_TASKS_TEMPLATES'] ?? null,
			'PATH_TO_USER_TASKS_VIEW' => $arParams['PATH_TO_USER_TASKS_VIEW'] ?? '',
			'PATH_TO_USER_TASKS_REPORT' => $arParams['PATH_TO_USER_TASKS_REPORT'] ?? '',
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] ?? '',
			'PATH_TO_GROUP_TASKS_TASK' => $arParams['~PATH_TO_GROUP_TASKS_TASK'] ?? null,
			'PATH_TO_GROUP_TASKS' => $arParams['~PATH_TO_GROUP_TASKS'] ?? null,
			'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP'] ?? '',
			'PATH_TO_GROUP_TASKS_VIEW' => $arParams['PATH_TO_GROUP_TASKS_VIEW'] ?? '',
			'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'] ?? '',
			'PATH_TO_USER_PROFILE' => $arParams['~PATH_TO_USER_PROFILE'] ?? null,
			'PATH_TO_MESSAGES_CHAT' => $arParams['PATH_TO_MESSAGES_CHAT'] ?? '',
			'PATH_TO_VIDEO_CALL' => $arParams['PATH_TO_VIDEO_CALL'] ?? '',
			'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT'] ?? '',
			'USE_GROUP_SELECTOR' =>
				(isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0)
				&& (!array_key_exists('GROUP_ID_FORCED', $arParams) || !$arParams['GROUP_ID_FORCED'])
				|| (isset($arParams['PERSONAL']) && $arParams['PERSONAL'] === 'Y')
					? 'N' : 'Y',
			'USE_EXPORT' => 'N',
			'SHOW_QUICK_FORM' => 'N',

			'POPUP_MENU_ITEMS' =>
				(
					isset($arParams['PERSONAL'])
					&& $arParams['PERSONAL'] == 'Y'
					&& isset($arResult['ACCESS_SORT_PERMS'])
					&& $arResult['ACCESS_SORT_PERMS']
				)
				|| (
					isset($arParams['PERSONAL'])
					&& $arParams['PERSONAL'] !== 'Y'
					&& !$emptyKanban
					&& !(
						isset($arParams['SPRINT_SELECTED'])
						&& $arParams['SPRINT_SELECTED'] === 'Y'
						&& (
							!array_key_exists('SPRINT_ID', $arParams)
							|| !$arParams['SPRINT_ID']
						)
					)
				)
					? $popupSortItems
					: [],
			'DEFAULT_ROLEID' => $arParams['DEFAULT_ROLEID'] ?? null,

			'SCOPE' => $scope,
			'CONTEXT' => $arResult['CONTEXT'] ?? null,
		],
		$component,
		['HIDE_ICONS' => true]
	);
}

	if ($isBitrix24Template)
	{
		$this->EndViewTarget();
	}
}
else
{
	$gridID = Filter\Task::getFilterId();
}
?>

<div id="task_kanban">
	<?php
	if ($emptyKanban):?>
		<div class="tasks-kanban-start">
			<div class="tasks-kanban-start-wrapper">
				<div class="tasks-kanban-start-title">
					<?= Loc::getMessage('KANBAN_WO_GROUP_1'); ?>
				</div>
				<div class="tasks-kanban-start-icon"></div>

				<?php
				if (\Bitrix\Socialnetwork\Helper\Workgroup::canCreate()):
					?>
					<div class="tasks-kanban-start-title-sm">
						<?= Loc::getMessage('KANBAN_WO_GROUP_2'); ?>
					</div>
					<a href="/company/personal/user/<?= $arParams['USER_ID'] ?>/groups/create/" <?php
					?>class="webform-button webform-button-blue tasks-kanban-start-button js-id-add-project"><?php
						?><?= Loc::getMessage('KANBAN_WO_GROUP_BUTTON'); ?><?php
						?></a>
				<?php
				endif;
				?>

			</div>
		</div>
		<style type="text/css">
			#counter_panel_container {
				display: none;
			}
		</style>
		<?php
		return;
	endif; ?>
</div>

<script>
	BX.Tasks.KanbanComponent.defaultPresetId = '<?=$arResult['DEFAULT_PRESET_KEY']?>';
	var ajaxHandlerPath = "<?= $this->GetComponent()->getPath()?>/ajax.php";

	var ajaxParams = {
		USER_ID: "<?= $arParams['USER_ID']?>",
		GROUP_ID: "<?= $arParams['GROUP_ID']?>",
		GROUP_ID_FORCED: <?= (int)$arParams['GROUP_ID_FORCED']?>,
		PERSONAL: "<?= $arParams['PERSONAL']?>",
		TIMELINE_MODE: "<?= $arParams['TIMELINE_MODE']?>",
		SPRINT_ID: <?= $arParams['SPRINT_ID'] > 0 ? $arParams['SPRINT_ID'] : -1;?>,
	};
	BX.Tasks.KanbanComponent.openedCustomSort = '<?= $order === 'asc' || $order === 'desc'?>';

	BX.ready(function() {
		BX.Tasks.KanbanAjaxComponent.Parameters = new BX.Tasks.KanbanAjaxComponent({
			ajaxComponentPath: ajaxHandlerPath,
			ajaxComponentParams: ajaxParams,
		});
	});
	var Kanban;

	(function() {

		'use strict';

		Kanban = new BX.Tasks.Kanban.Grid({
			renderTo: BX('task_kanban'),
			//multiSelect: true,
			itemType: 'BX.Tasks.Kanban.Item',
			columnType: 'BX.Tasks.Kanban.Column',
			canAddColumn: <?= $demoAccess
				? 'true'
				: (($arResult['ACCESS_CONFIG_PERMS']
					&& $arParams['TIMELINE_MODE']
					== 'N') ? 'true' : 'false')?>,
			canEditColumn: <?= $demoAccess
				? 'true'
				: (($arResult['ACCESS_CONFIG_PERMS']
					&& $arParams['TIMELINE_MODE']
					== 'N') ? 'true' : 'false')?>,
			canRemoveColumn: <?= ($arResult['ACCESS_CONFIG_PERMS'] && $arParams['TIMELINE_MODE'] == 'N') ? 'true'
				: 'false'?>,
			canSortColumn: <?= ($arResult['ACCESS_SORT_PERMS'] && $arParams['TIMELINE_MODE'] == 'N') ? 'true'
				: 'false'?>,
			canAddItem: <?= $arResult['ACCESS_CREATE_PERMS'] ? 'true' : 'false'?>,
			canSortItem: <?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>,
			bgColor: <?= (SITE_TEMPLATE_ID === 'bitrix24' ? '"transparent"' : 'null')?>,
			addItemTitleText: "<?= Loc::getMessage('KANBAN_QUICK_TASK');?>",
			addDraftItemInfo: "<?= Loc::getMessage('KANBAN_QUICK_TASK_ITEM_INFO');?>",
			columns: <?= CUtil::PhpToJSObject($data['columns'], false, false, true)?>,
			items: <?= CUtil::PhpToJSObject($data['items'], false, false, true)?>,
			data: {
				kanbanType: "<?= htmlspecialcharsbx($type);?>",
				ajaxHandlerPath: ajaxHandlerPath,
				groupId: '<?=$arParams['GROUP_ID']?>',
				pathToTask: "<?= CUtil::JSEscape(str_replace('#action#', 'view', $arParams['~PATH_TO_TASKS_TASK']))?>",
				pathToGroupTask: "<?= CUtil::JSEscape(str_replace(['#group_id#', '#action#'],
					[(int)$arParams['GROUP_ID'], 'view'], $arParams['~PATH_TO_GROUP_TASKS_TASK']))?>",
				pathToTaskCreate: "<?= CUtil::JSEscape(str_replace('#action#', 'edit',
					$arParams['~PATH_TO_TASKS_TASK']))?>",
				pathToUser: "<?= CUtil::JSEscape($arParams['~PATH_TO_USER_PROFILE'])?>",
				addItemInSlider: <?= $arResult['MANDATORY_EXISTS'] ? 'true' : 'false'?>,
				params: ajaxParams,
				gridId: "<?= CUtil::JSEscape($gridID)?>",
				newTaskOrder: "<?= $arResult['NEW_TASKS_ORDER']?>",
				setClientDate: <?= $arResult['NEED_SET_CLIENT_DATE'] ? 'true' : 'false'?>,
				clientDate: '<?=CUtil::JSEscape($clientDate)?>',
				clientTime: '<?=CUtil::JSEscape($clientTime)?>',
				rights: {
					canAddColumn: <?= ($arResult['ACCESS_CONFIG_PERMS'] && $arParams['TIMELINE_MODE'] == 'N') ? 'true'
						: 'false'?>,
					canEditColumn: <?= ($arResult['ACCESS_CONFIG_PERMS'] && $arParams['TIMELINE_MODE'] == 'N') ? 'true'
						: 'false'?>,
					canRemoveColumn: <?= ($arResult['ACCESS_CONFIG_PERMS'] && $arParams['TIMELINE_MODE'] == 'N')
						? 'true' : 'false'?>,
					canSortColumn: <?= ($arResult['ACCESS_SORT_PERMS'] && $arParams['TIMELINE_MODE'] == 'N') ? 'true'
						: 'false'?>,
					canAddItem: <?= $arResult['ACCESS_CREATE_PERMS'] ? 'true' : 'false'?>,
					canSortItem: <?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>,
				},
				admins: <?= CUtil::PhpToJSObject(array_values($arResult['ADMINS']))?>,
				customSectionsFields: <?= CUtil::phpToJSObject($arResult['POPUP_FIELDS_SECTIONS']);?>,
			},
			messages: {
				ITEM_TITLE_PLACEHOLDER: "<?= CUtil::JSEscape(Loc::getMessage('KANBAN_ITEM_TITLE_PLACEHOLDER'))?>",
				COLUMN_TITLE_PLACEHOLDER: "<?= CUtil::JSEscape(Loc::getMessage('KANBAN_COLUMN_TITLE_PLACEHOLDER'))?>",
			},
			ownerId: <?= (int)$arParams["USER_ID"] ?>,
			groupId: <?= (int)$arParams['GROUP_ID'] ?>,
			isSprintView: '<?= ($isSprintView ? 'Y' : 'N') ?>',
			isCollab: '<?= $arResult['IS_COLLAB'] ? 'Y' : 'N' ?>',
			networkEnabled: <?= MemberSelector::isNetworkEnabled() ? "true"
				: "false"; ?>,
		});

		Kanban.draw();

		BX.Tasks.KanbanComponent.onReady();

		function runTours()
		{
			BX.removeCustomEvent(Kanban, 'Kanban.Grid:onRender', runTours);
			BX.Tasks.KanbanComponent.TourGuideControllerInstance = new BX.Tasks.KanbanComponent.TourGuideController(<?=
				Json::encode([
					'userId' => $arParams['USER_ID'],
					'groupId' => (int)$arParams['GROUP_ID'],
					'tours' => $arResult['TOURS'],
					'viewMode' => $viewMode,
				])
				?>);
		}

		BX.addCustomEvent(Kanban, 'Kanban.Grid:onRender', runTours);
	})();

</script>

<?php
// select views
if (!empty($arResult['VIEWS']))
{
	require 'initial.php';
}

// converter tasks in my plan
if ($arResult['MP_CONVERTER'] > 0)
{
	require 'converter.php';
}

// demo popup

$show = false;
$popupsShowed = \CUserOptions::getOption(
	'tasks',
	'kanban_demo_showed',
	[]
);

if ($type === 'P' && !in_array('P', $popupsShowed, true))
{
	$show = true;
}
elseif ($type === 'K' && !in_array('K', $popupsShowed, true))
{
	$show = true;
}

$show = ($isSprintView ? false : $show);

if ($show)
{
	if ($type === 'P')
	{
		if (in_array(LANGUAGE_ID, ['ru', 'ua', 'by', 'kz'], true))
		{
			$popupUrlId = '5630723';
		}
		elseif (in_array(LANGUAGE_ID, ['la', 'es'], true))
		{
			$popupUrlId = '5637971';
		}
		elseif (LANGUAGE_ID === 'de')
		{
			$popupUrlId = '5638585';
		}
		else
		{
			$popupUrlId = '5637775';
		}
	}
	elseif ($type === 'K')
	{
		if (in_array(LANGUAGE_ID, ['ru', 'ua', 'by', 'kz'], true))
		{
			$popupUrlId = '5630349';
		}
		elseif (in_array(LANGUAGE_ID, ['la', 'es'], true))
		{
			$popupUrlId = '5637971';
		}
		elseif (LANGUAGE_ID === 'de')
		{
			$popupUrlId = '5638577';
		}
		else
		{
			$popupUrlId = '5637765';
		}
	}

	if (in_array(LANGUAGE_ID, ['ru', 'ua', 'by', 'kz'], true))
	{
		$popupDomain = 'ru';
	}
	elseif (in_array(LANGUAGE_ID, ['la', 'es'], true))
	{
		$popupDomain = 'es';
	}
	elseif (LANGUAGE_ID === 'de')
	{
		$popupDomain = 'de';
	}
	else
	{
		$popupDomain = 'com';
	}

	CJSCore::Init(['helper']);
	$this->addExternalCss($this->getFolder() . '/popup/style.css');
	$this->addExternalJs($this->getFolder() . '/popup/script.js');
	?>
	<div class="tasks-kanban-popup" id="kanban-popup" <?php
	?>data-close="<?= Loc::getMessage('KANBAN_POPUP_CLOSE'); ?>" <?php
		 ?>data-ajax="<?= CUtil::JSEscape($this->getFolder() . '/popup/ajax.php') ?>" <?php
		 ?>data-type="<?= $type ?>"<?php
	?>>
		<div class="tasks-kanban-popup-title"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TITLE'); ?></div>
		<?php if($arResult['IS_COLLAB'] && $type !== 'P'): ?>
			<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_COLLAB_' . $type . '_TEXT_1'); ?></div>
			<img src="<?= $this->getFolder() ?>/popup/kanban_img.png" alt="" class="tasks-kanban-popup-img">
			<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_COLLAB_' . $type . '_TEXT_2'); ?></div>
			<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_COLLAB_' . $type . '_TEXT_3'); ?></div>
		<?else:?>
		<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_1'); ?></div>
		<img src="<?= $this->getFolder() ?>/popup/kanban_img.png" alt="" class="tasks-kanban-popup-img">
		<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_2'); ?></div>
		<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_3'); ?></div>
		<div class="tasks-kanban-popup-text tasks-kanban-popup-text-italic"><?= Loc::getMessage('KANBAN_POPUP_'
				. $type
				. '_TEXT_4'); ?></div>
		<?php endif;?>
		<a href="https://helpdesk.bitrix24.<?= $popupDomain ?>/open/<?= $popupUrlId ?>/" target="_blank"
		   data-helpId="<?= $popupUrlId ?>"<?php
		if (SITE_TEMPLATE_ID === 'bitrix24')
		{
			?> id="kanban-readmore"<?php
		} ?> class="tasks-kanban-popup-text-redmore">
			<?= Loc::getMessage('KANBAN_POPUP_DETAIL'); ?>
		</a>
	</div>
	<?php
}

CJSCore::Init("spotlight");
?>
<script>
	BX.ready(function() {
		if (<?=(int)$show?> === 1)
		{
			showPopup();
		}
		BX.Tasks.KanbanComponent.filterId = '<?=$gridID?>';

		<?php if ($arResult['IS_COLLAB']): ?>
			const collabImagePath = "<?=$arResult["COLLAB_IMAGE"]?>" || null;
			const collabName = "<?=HtmlFilter::encode($arResult["COLLAB_NAME"])?>";
			const groupId = "<?=HtmlFilter::encode($arParams["GROUP_ID"])?>";
			const avatar = new BX.UI.AvatarHexagonGuest({
				size: 42,
				userName: collabName.toUpperCase(),
				baseColor: '#19CC45',
				userpicPath: collabImagePath,
			});
			avatar.renderTo(BX('sn-collab-icon-' + groupId));
		<?php endif; ?>
	});

	BX.message({
		TASKS_CLOSE_PAGE_CONFIRM: '<?=GetMessageJS('TASKS_CLOSE_PAGE_CONFIRM')?>',
	});
</script>
