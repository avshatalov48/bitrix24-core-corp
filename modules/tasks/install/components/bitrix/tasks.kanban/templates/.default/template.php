<?php
use \Bitrix\Tasks\Kanban;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!empty($arResult['ERRORS']))
{
	ShowError(implode("\n", $arResult['ERRORS']));
	return;
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Tasks\UI\Filter;

Loc::loadMessages(__FILE__);

$data = $arResult['DATA'];

$demoAccess = $arParams['PERSONAL'] != 'Y' &&
			\CJSCore::IsExtRegistered('intranet_notify_dialog') &&
			\Bitrix\Main\Loader::includeModule('im');

$emptyKanban = $arParams['GROUP_ID'] == 0 &&
				$arParams['PERSONAL'] != 'Y';

\CJSCore::Init(array('task_kanban', 'intranet_notify_dialog'));

if (!$emptyKanban)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '') . 'no-all-paddings no-background');
}

if (isset($arParams['INCLUDE_INTERFACE_HEADER']) && $arParams['INCLUDE_INTERFACE_HEADER'] == 'Y')
{
	$filterInstance = \Bitrix\Tasks\Helper\Filter::getInstance($arParams["USER_ID"], $arParams["GROUP_ID"]);

	$filter = $filterInstance->getFilters();
	$presets = $filterInstance->getPresets();
	$gridID = $filterInstance->getId();

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.interface.header',
		'',
		array(
			'FILTER_ID' => $gridID,
			'GRID_ID' => $gridID,

			'FILTER' => $filter,
			'PRESETS' => $presets,

			'USER_ID' => $arParams['USER_ID'],
			'GROUP_ID' => $arParams['GROUP_ID'],
			'MENU_GROUP_ID' => !$arParams['GROUP_ID_FORCED'] || $arParams['PERSONAL'] == 'Y'
								? $arParams['GROUP_ID'] : 0,

			'SHOW_VIEW_MODE' => $arParams['KANBAN_SHOW_VIEW_MODE'] == 'Y'
								|| Kanban\StagesTable::getWorkMode() == Kanban\StagesTable::WORK_MODE_USER
								|| !(Kanban\StagesTable::getWorkMode() == Kanban\StagesTable::WORK_MODE_GROUP
								   && $arParams['GROUP_ID'] > 0) ? 'Y' : 'N',
			'MARK_SECTION_KANBAN' =>  Kanban\StagesTable::getWorkMode() == Kanban\StagesTable::WORK_MODE_GROUP ? 'Y' : 'N',
			'USE_AJAX_ROLE_FILTER' =>  $arParams['PERSONAL'] == 'Y' ? 'Y' : 'N',

			'MARK_ACTIVE_ROLE' => $arParams['MARK_ACTIVE_ROLE'],
			'MARK_SECTION_ALL' => $arParams['MARK_SECTION_ALL'],
//			'MARK_SPECIAL_PRESET' => $arParams['MARK_SPECIAL_PRESET'],

			'PATH_TO_USER_TASKS' => $arParams['~PATH_TO_USER_TASKS'],
			'PATH_TO_USER_TASKS_TASK' => $arParams['~PATH_TO_USER_TASKS_TASK'],
			'PATH_TO_USER_TASKS_TEMPLATES' => $arParams['~PATH_TO_USER_TASKS_TEMPLATES'],
			'PATH_TO_USER_TASKS_VIEW' =>
				isset($arParams['PATH_TO_USER_TASKS_VIEW'])
				? $arParams['PATH_TO_USER_TASKS_VIEW'] : '',
			'PATH_TO_USER_TASKS_REPORT' =>
				isset($arParams['PATH_TO_USER_TASKS_REPORT'])
				? $arParams['PATH_TO_USER_TASKS_REPORT'] : '',
			'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' =>
				isset($arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'])
				? $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'] : '',

			'PATH_TO_GROUP_TASKS_TASK' => $arParams['~PATH_TO_GROUP_TASKS_TASK'],
			'PATH_TO_GROUP_TASKS' => $arParams['~PATH_TO_GROUP_TASKS'],
			'PATH_TO_GROUP' =>
				isset($arParams['PATH_TO_GROUP'])
				? $arParams['PATH_TO_GROUP'] : '',
			'PATH_TO_GROUP_TASKS_VIEW' =>
				isset($arParams['PATH_TO_GROUP_TASKS_VIEW'])
				? $arParams['PATH_TO_GROUP_TASKS_VIEW'] : '',
			'PATH_TO_GROUP_TASKS_REPORT' =>
				isset($arParams['PATH_TO_GROUP_TASKS_REPORT'])
				? $arParams['PATH_TO_GROUP_TASKS_REPORT'] : '',

			'PATH_TO_USER_PROFILE' => $arParams['~PATH_TO_USER_PROFILE'],
			'PATH_TO_MESSAGES_CHAT' =>
				isset($arParams['PATH_TO_MESSAGES_CHAT'])
				? $arParams['PATH_TO_MESSAGES_CHAT'] : '',
			'PATH_TO_VIDEO_CALL' =>
				isset($arParams['PATH_TO_VIDEO_CALL'])
				? $arParams['PATH_TO_VIDEO_CALL'] : '',
			'PATH_TO_CONPANY_DEPARTMENT' =>
				isset($arParams['PATH_TO_CONPANY_DEPARTMENT'])
				? $arParams['PATH_TO_CONPANY_DEPARTMENT'] : '',

			'USE_GROUP_SELECTOR' => $arParams['GROUP_ID'] > 0 && !$arParams['GROUP_ID_FORCED'] || $arParams['PERSONAL'] == 'Y'
									? 'N' : 'Y',
			'USE_EXPORT'=>'N',
			'SHOW_QUICK_FORM'=>'N',


			'POPUP_MENU_ITEMS' =>
				$arParams['PERSONAL'] == 'Y' && $arResult['ACCESS_SORT_PERMS']
				|| $arParams['PERSONAL'] != 'Y' && !$emptyKanban
				? array(
					array(
						'tabId' => 'popupMenuOptions',
						'text' => '<b>' . Loc::getMessage('KANBAN_SORT_TITLE') . '</b>'
					),
					array(
						'tabId' => 'popupMenuOptions',
						'text' => Loc::getMessage('KANBAN_SORT_DESC'),
						'className' => 'menu-popup-item-none',
						'onclick' => 'BX.delegate(BX.Tasks.KanbanComponent.ClickSort)',
						'params' => '{order: "desc"}'
					),
					array(
						'tabId' => 'popupMenuOptions',
						'text' => Loc::getMessage('KANBAN_SORT_ASC'),
						'className' => 'menu-popup-item-none',
						'onclick' => 'BX.delegate(BX.Tasks.KanbanComponent.ClickSort)',
						'params' => '{order: "asc"}'
					),
				)
				: array(
					//
				),
			'DEFAULT_ROLEID'=>$arParams['DEFAULT_ROLEID']
		),
		$component,
		array('HIDE_ICONS' => true)
	);
}
else
{
	$gridID = Filter\Task::getFilterId();
}
?>

<div id="task_kanban">
<?if ($emptyKanban):?>
	<div class="tasks-kanban-start">
		<div class="tasks-kanban-start-wrapper">
			<div class="tasks-kanban-start-title">
				<?= Loc::getMessage('KANBAN_WO_GROUP_1');?>
			</div>
			<div class="tasks-kanban-start-icon"></div>
			<div class="tasks-kanban-start-title-sm">
				<?= Loc::getMessage('KANBAN_WO_GROUP_2');?>
			</div>
			<a href="<?=htmlspecialcharsbx(str_replace('#user_id#', $arResult['CURRENT_USER_ID'], $arParams['PATH_TO_USER_PROFILE']))?>groups/create/" class="webform-button webform-button-blue tasks-kanban-start-button"><?
				?><?= Loc::getMessage('KANBAN_WO_GROUP_BUTTON');?><?
			?></a>
		</div>
	</div>
	<style type="text/css">#counter_panel_container {display: none;}</style>
	<?
	return;
endif;?>
</div>

<script type="text/javascript">
	BX.Tasks.KanbanComponent.defaultPresetId = '<?=$arResult['DEFAULT_PRESET_KEY']?>';
	var ajaxHandlerPath = "<?= $this->GetComponent()->getPath()?>/ajax.php";

	var ajaxParams = {
		USER_ID: "<?= $arParams['USER_ID']?>",
		GROUP_ID: "<?= $arParams['GROUP_ID']?>",
		GROUP_ID_FORCED: <?= (int)$arParams['GROUP_ID_FORCED']?>,
		PERSONAL: "<?= $arParams['PERSONAL']?>"
	};

	var Kanban;

	(function() {

		"use strict";

		Kanban = new BX.Tasks.Kanban.Grid({
			renderTo: BX("task_kanban"),
			itemType: "BX.Tasks.Kanban.Item",
			columnType: "BX.Tasks.Kanban.Column",
			canAddColumn: <?= $demoAccess ? 'true' : ($arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false')?>,
			canEditColumn: <?= $demoAccess ? 'true' : ($arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false')?>,
			canRemoveColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
			canSortColumn: <?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>,
			canAddItem: <?= $arResult['ACCESS_CREATE_PERMS'] ? 'true' : 'false'?>,
			canSortItem: <?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>,
			bgColor: <?= (SITE_TEMPLATE_ID === 'bitrix24' ? '"transparent"' : 'null')?>,
			columns: <?= \CUtil::PhpToJSObject($data['columns'], false, false, true)?>,
			items: <?= \CUtil::PhpToJSObject($data['items'], false, false, true)?>,
			data: {
				ajaxHandlerPath: ajaxHandlerPath,
				pathToTask: "<?= \CUtil::JSEscape(str_replace('#action#', 'view', $arParams['~PATH_TO_TASKS_TASK']))?>",
				pathToTaskCreate: "<?= \CUtil::JSEscape(str_replace('#action#', 'edit', $arParams['~PATH_TO_TASKS_TASK']))?>",
				pathToUser: "<?= \CUtil::JSEscape($arParams['~PATH_TO_USER_PROFILE'])?>",
				addItemInSlider: <?= $arResult['MANDATORY_EXISTS'] ? 'true' : 'false'?>,
				params: ajaxParams,
				gridId: "<?= \CUtil::JSEscape($gridID)?>",
				newTaskOrder: "<?= $arResult['NEW_TASKS_ORDER']?>",
				rights: {
					canAddColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
					canEditColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
					canRemoveColumn: <?= $arResult['ACCESS_CONFIG_PERMS'] ? 'true' : 'false'?>,
					canSortColumn: <?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>,
					canAddItem: <?= $arResult['ACCESS_CREATE_PERMS'] ? 'true' : 'false'?>,
					canSortItem: <?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>
				},
				admins: <?= \CUtil::PhpToJSObject(array_values($arResult['ADMINS']))?>
			},
			messages: {
				ITEM_TITLE_PLACEHOLDER: "<?= \CUtil::JSEscape(Loc::getMessage('KANBAN_ITEM_TITLE_PLACEHOLDER'))?>",
				COLUMN_TITLE_PLACEHOLDER: "<?= \CUtil::JSEscape(Loc::getMessage('KANBAN_COLUMN_TITLE_PLACEHOLDER'))?>"
			}
		});

		Kanban.draw();

		BX.Tasks.KanbanComponent.SetSort(
			<?= $arResult['ACCESS_SORT_PERMS'] ? 'true' : 'false'?>,
			"<?= $arResult['NEW_TASKS_ORDER']?>"
		);

		BX.Tasks.KanbanComponent.onReady();
	})();

</script>

<?
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
$type = $arParams['PERSONAL'] == 'Y' ? 'P' : 'K';
$popupsShowed = \CUserOptions::getOption(
	'tasks',
	'kanban_demo_showed',
	array()
);

if ($type == 'P' && !in_array('P', $popupsShowed))
{
	$show = true;
}
elseif ($type == 'K' && !in_array('K', $popupsShowed))
{
	$show = true;
}

if ($show)
{
	if ($type == 'P')
	{
		if (in_array(LANGUAGE_ID, array('ru', 'ua', 'by', 'kz')))
		{
			$popupUrlId = '5630723';
		}
		elseif (in_array(LANGUAGE_ID, array('la', 'es')))
		{
			$popupUrlId = '5637971';
		}
		elseif (in_array(LANGUAGE_ID, array('de')))
		{
			$popupUrlId = '5638585';
		}
		else
		{
			$popupUrlId = '5637775';
		}
	}
	elseif ($type == 'K')
	{
		if (in_array(LANGUAGE_ID, array('ru', 'ua', 'by', 'kz')))
		{
			$popupUrlId = '5630349';
		}
		elseif (in_array(LANGUAGE_ID, array('la', 'es')))
		{
			$popupUrlId = '5637971';
		}
		elseif (in_array(LANGUAGE_ID, array('de')))
		{
			$popupUrlId = '5638577';
		}
		else
		{
			$popupUrlId = '5637765';
		}
	}

	if (in_array(LANGUAGE_ID, array('ru', 'ua', 'by', 'kz')))
	{
		$popupDomain = 'ru';
	}
	elseif (in_array(LANGUAGE_ID, array('la', 'es')))
	{
		$popupDomain = 'es';
	}
	elseif (in_array(LANGUAGE_ID, array('de')))
	{
		$popupDomain = 'de';
	}
	else
	{
		$popupDomain = 'com';
	}

	\CJSCore::Init(array('helper'));
	$this->addExternalCss($this->getFolder() . '/popup/style.css');
	$this->addExternalJs($this->getFolder() . '/popup/script.js');
	?>
	<div class="tasks-kanban-popup" id="kanban-popup" <?
		?>data-close="<?= Loc::getMessage('KANBAN_POPUP_CLOSE');?>" <?
		?>data-ajax="<?= \CUtil::JSEscape($this->getFolder() . '/popup/ajax.php')?>" <?
		?>data-type="<?= $type?>"<?
	?>>
		<div class="tasks-kanban-popup-title"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TITLE');?></div>
		<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_1');?></div>
		<img src="<?= $this->getFolder()?>/popup/kanban_img.png" alt="" class="tasks-kanban-popup-img" alt="">
		<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_2');?></div>
		<div class="tasks-kanban-popup-text"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_3');?></div>
		<div class="tasks-kanban-popup-text tasks-kanban-popup-text-italic"><?= Loc::getMessage('KANBAN_POPUP_' . $type . '_TEXT_4');?></div>
		<a href="https://helpdesk.bitrix24.<?= $popupDomain?>/open/<?= $popupUrlId?>/" target="_blank" data-helpId="<?= $popupUrlId?>"<?if (SITE_TEMPLATE_ID == 'bitrix24'){?> id="kanban-readmore"<?}?> class="tasks-kanban-popup-text-redmore">
			<?= Loc::getMessage('KANBAN_POPUP_DETAIL');?>
		</a>
	</div>
	<?
}



//Remove this code after 01.11.2017
//if (\Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker::getInstance()->shouldShowHint()):
	CJSCore::Init("spotlight");
	?>
	<script>
		BX.ready(function() {

			BX.Tasks.KanbanComponent.filterId = '<?=$gridID?>';

//			var node = BX("tasks_group_selector");
//			var left = node.offsetWidth - 4;
//			var spotlight = new BX.SpotLight({
//				renderTo: node,
//				top: node.offsetHeight / 2,
//				left: left,
//				content: "<?//=GetMessageJS("KANBAN_SPOTLIGHT_GROUPS")?>//"
//			});
//
//			var onresize = function() {
//
//				if (spotlight.container && spotlight.getRenderTo())
//				{
//					var pos = BX.pos(spotlight.getRenderTo());
//					spotlight.container.style.left = pos.left + spotlight.left + "px";
//					spotlight.container.style.top = pos.top + spotlight.top + "px";
//				}
//			};
//
//			BX.bind(window, "resize", onresize);
//			BX.addCustomEvent("onFrameDataProcessed", onresize);
//
//			spotlight.show();
		});

		BX.message({
			TASKS_CLOSE_PAGE_CONFIRM: '<?=GetMessageJS('TASKS_CLOSE_PAGE_CONFIRM')?>'
		});
	</script>
<?//endif?>
