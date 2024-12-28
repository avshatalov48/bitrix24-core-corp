<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main;
use Bitrix\Main\Grid;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Grid\Task;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Text\HtmlFilter;

Extension::load(["ui.notification", "ui.icons", "ui.avatar",]);
$isExtranetUser = \Bitrix\Tasks\Integration\Extranet\User::isExtranet();
if (Main\ModuleManager::isModuleInstalled('rest') && !$isExtranetUser)
{
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'menu',
		[
			'PLACEMENT' => 'TASK_LIST_CONTEXT_MENU',
			'PLACEMENT_OPTIONS' => [],
			//'INTERFACE_EVENT' => 'onCrmLeadListInterfaceInit',
			'MENU_EVENT_MODULE' => 'tasks',
			'MENU_EVENT' => 'onTasksBuildContextMenu',
		],
		null,
		['HIDE_ICONS' => 'Y']
	);
}

CJSCore::Init(['tasks_util_query', 'task_popups']);

//region TITLE
if ($arParams['PROJECT_VIEW'] === 'Y')
{
	$title = $shortTitle = Loc::getMessage('TASKS_TITLE_PROJECT');
}
elseif ($arParams['GROUP_ID'] > 0)
{
	$shortTitle = Loc::getMessage('TASKS_TITLE_GROUP_TASKS');
	$title = $shortTitle;

	if (
		Main\Loader::includeModule('socialnetwork')
		&& method_exists(Bitrix\Socialnetwork\ComponentHelper::class, 'getWorkgroupPageTitle')
	)
	{
		$title = \Bitrix\Socialnetwork\ComponentHelper::getWorkgroupPageTitle([
			'WORKGROUP_ID' => $arParams['GROUP_ID'],
			'TITLE' => $shortTitle
		]);
	}
}
elseif ((int)$arParams['USER_ID'] === User::getId())
{
	$title = $shortTitle = Loc::getMessage('TASKS_TITLE_MY');
}
else
{
	$shortTitle = Loc::getMessage('TASKS_TITLE');
	$title = CUser::FormatName($arParams['NAME_TEMPLATE'], $arResult['USER'], true, false).": ".$shortTitle;
}

if ($arResult["IS_COLLAB"])
{
	Toolbar::deleteFavoriteStar();
	$shortTitle = Loc::getMessage('TASKS_TITLE');

	$this->SetViewTarget('in_pagetitle') ?>

	<div class="sn-collab-icon__wrapper">
		<div id="sn-collab-icon-<?=HtmlFilter::encode($arResult["OWNER_ID"])?>" class="sn-collab-icon__hexagon-bg"></div>
	</div>
	<div class="sn-collab__subtitle"><?=HtmlFilter::encode($arResult["COLLAB_NAME"])?></div>
	<?php $this->EndViewTarget();
}

$APPLICATION->SetPageProperty('title', $title);
$APPLICATION->SetTitle($shortTitle);

if (isset($arParams['SET_NAVCHAIN']) && $arParams['SET_NAVCHAIN'] !== 'N')
{
	$APPLICATION->AddChainItem(Loc::getMessage('TASKS_TITLE'));
}

//endregion TITLE

if (!function_exists('formatDateFieldsForOutput'))
{
	/**
	 * @param $row
	 * @throws Main\ObjectException
	 */
	function formatDateFieldsForOutput(&$row): void
	{
		$dateFields = array_filter(
			CTasks::getFieldsInfo(),
			static function ($item) {
				return ($item['type'] === 'datetime' ? $item : null);
			}
		);

		$localOffset = (new \DateTime())->getOffset();
		$userOffset = CTimeZone::GetOffset(null, true);
		$offset = $localOffset + $userOffset;

		foreach ($dateFields as $fieldName => $fieldData)
		{
			if (isset($row[$fieldName]) && is_string($row[$fieldName]) && $row[$fieldName])
			{
				$date = new DateTime($row[$fieldName]);
				if ($date)
				{
					$newOffset = ($offset > 0? '+' : '') . UI::formatTimeAmount($offset, 'HH:MI');
					$row[$fieldName] = mb_substr($date->format('c'), 0, -6).$newOffset;
				}
			}
		}
	}
}

$request = Bitrix\Main\Context::getCurrent()?->getRequest();
if ($request->get('my_tasks_column') === 'Y')
{
	$arParams['FLOW_MY_TASKS'] = 'Y';
	$arParams['demoSuffix'] = FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';
}

if ($request->get('show_counters_toolbar') === 'N')
{
	$arParams['SHOW_COUNTERS_TOOLBAR'] = 'N';
}

$grid = (new Bitrix\Tasks\Grid\Task\Grid($arResult['LIST'], $arParams))
	->setScope($arParams['CONTEXT'] ?? null);

$arResult['HEADERS'] = $grid->prepareHeaders();
$arResult['TEMPLATE_DATA'] = [
	'EXTENSION_ID' => 'tasks_task_list_component_ext_'.md5($this->GetFolder()),
];
$arResult['ROWS'] = [];
$arResult['EXPORT_LIST'] = $arResult['LIST'];

if (!empty($arResult['LIST']))
{
	$users = [];
	$groups = [];

	foreach ($arResult['LIST'] as $row)
	{
		$users[] = $row['CREATED_BY'];
		$users[] = $row['RESPONSIBLE_ID'];

		if ($arResult['GROUP_BY_PROJECT'] && ($groupId = (int)$row['GROUP_ID']))
		{
			$groups[$groupId] = $groupId;
		}
	}

	$groups = SocialNetwork\Group::getData($groups, ['TYPE'], ['WITH_CHAT']);
	$preparedRows = $grid->prepareRows();
	$prevGroupId = $arResult['LAST_GROUP_ID'];

	foreach ($arResult['LIST'] as $key => $row)
	{
		$taskId = (int)$row['ID'];
		$groupId = (int)$row['GROUP_ID'];

		if ($arResult['GROUP_BY_PROJECT'] && $groupId !== $prevGroupId)
		{
			$groupName = htmlspecialcharsbx($groups[$groupId]['NAME']);
			$groupType = $groups[$groupId]['TYPE'] ?? null;
			$groupChatId = $groups[$groupId]['CHAT_ID'] ?? 0;
			$groupUrl = SocialNetwork\Collab\Url\UrlManager::getUrlByType($groupId, $groupType, ['chatId' => $groupChatId]);

			$actionCreateTask = SocialNetwork\Group::ACTION_CREATE_TASKS;
			$actionEditTask = SocialNetwork\Group::ACTION_EDIT_TASKS;

			$arResult['ROWS'][] = [
				'id' => "group_{$groupId}",
				'group_id' => $groupId,
				'parent_id' => 0,
				'has_child' => true,
				'not_count' => true,
				'draggable' => false,
				'custom' => "<div class='tasks-grid-wrapper'><a href='{$groupUrl}' class='tasks-grid-group-link'>{$groupName}</a></div>",
				'attrs' => [
					'data-type' => 'group',
					'data-group-id' => $groupId,
					'data-can-create-tasks' => (SocialNetwork\Group::can($groupId, $actionCreateTask) ? 'true' : 'false'),
					'data-can-edit-tasks' => (SocialNetwork\Group::can($groupId, $actionEditTask) ? 'true' : 'false'),
				],
			];
		}

		$preparedRow = $preparedRows[$key];

		$parentId = $row['PARENT_ID'] ?? 0;
		$arResult['ROWS'][] = [
			'id' => $taskId,
			'has_child' => array_key_exists($taskId, $arResult['SUB_TASK_COUNTERS']),
			'parent_id' => (Grid\Context::isInternalRequest() ? $parentId : 0),
			'parent_group_id' => $groupId,
			'columns' => $preparedRow['content'],
			'actions' => $preparedRow['actions'],
			'cellActions' => $preparedRow['cellActions'],
			'counters' => $preparedRow['counters'],
			'attrs' => [
				'data-type' => 'task',
				'data-group-id' => $groupId,
				'data-can-edit' => ($row['ACTION']['EDIT'] === true ? 'true' : 'false'),
			],
		];

		formatDateFieldsForOutput($arResult['LIST'][$key]);

		$prevGroupId = $groupId;
	}

	$arResult['LAST_GROUP_ID'] = $prevGroupId;
}

$disabledActions = [];
if (
	isset($arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'])
	&& $arResult['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'] === 'FAVORITE'
	&& isset($arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'])
	&& $arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ADVANCED_FILTER'
)
{
	$disabledActions = [Task\GroupAction::ACTION_ADD_FAVORITE];
}

$arResult['LIST'] = Bitrix\Main\Engine\Response\Converter::toJson()->process($arResult['LIST']);
$arResult['GROUP_ACTIONS'] = (new Task\GroupAction())->prepareGroupActions($arParams['GRID_ID'], $disabledActions);

if ($arResult["IS_COLLAB"]): ?>
<script>
	BX.ready(() => {
		const collabImagePath = "<?=$arResult["COLLAB_IMAGE"] ?>" || null;
		const collabName = "<?=HtmlFilter::encode($arResult["COLLAB_NAME"])?>";
		const ownerId = "<?=HtmlFilter::encode($arResult["OWNER_ID"])?>";
		const avatar = new BX.UI.AvatarHexagonGuest({
			size: 42,
			userName: collabName.toUpperCase(),
			baseColor: '#19CC45',
			userpicPath: collabImagePath,
		});
		avatar.renderTo(BX('sn-collab-icon-' + ownerId));
	});
</script>
<?php endif;
