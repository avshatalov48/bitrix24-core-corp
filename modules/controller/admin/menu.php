<?php
/** @var CUser $USER */

if (!CModule::IncludeModule('controller'))
{
	return false;
}

IncludeModuleLangFile(__FILE__);

$aMenu = [
	'parent_menu' => 'global_menu_services',
	'section' => 'controller',
	'sort' => 100,
	'text' => GetMessage('CTRLR_MENU_NAME'),
	'title' => GetMessage('CTRLR_MENU_TITLE'),
	'icon' => 'controller_menu_icon',
	'page_icon' => 'controller_page_icon',
	'items_id' => 'menu_controller',
	'more_url' => [],
	'items' => []
];

if ($USER->CanDoOperation('controller_member_view'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_SITE_NAME'),
		'url' => 'controller_member_admin.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [
			'controller_member_edit.php?lang=' . LANGUAGE_ID,
			'controller_member_history.php?lang=' . LANGUAGE_ID,
		],
		'items_id' => 'menu_controller_member_',
		'title' => GetMessage('CTRLR_MENU_SITE_TITLE'),
	];
}

if ($USER->CanDoOperation('controller_group_view'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_GROUP_NAME'),
		'url' => 'controller_group_admin.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [
			'controller_group_edit.php?lang=' . LANGUAGE_ID,
		],
		'items_id' => 'menu_controller_group',
		'title' => GetMessage('CTRLR_MENU_GROUP_TYPE'),
	];
}

if ($USER->CanDoOperation('controller_task_view'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_TASK_NAME'),
		'url' => 'controller_task.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [
			'controller_task.php?lang=' . LANGUAGE_ID,
		],
		'items_id' => 'menu_controller_task',
		'title' => GetMessage('CTRLR_MENU_TASK_TITLE'),
	];
}

if ($USER->CanDoOperation('controller_log_view'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_LOG_NAME'),
		'url' => 'controller_log_admin.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [],
		'items_id' => 'menu_controller_log',
		'title' => GetMessage('CTRLR_MENU_LOG_TITLE'),
	];
}

if ($USER->CanDoOperation('controller_member_updates_run') && ControllerIsSharedMode())
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_UPD_NAME'),
		'url' => 'controller_update.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [],
		'title' => GetMessage('CTRLR_MENU_UPD_TYPE'),
	];
}

if ($USER->CanDoOperation('controller_run_command'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_RUN_NAME'),
		'url' => 'controller_run_command.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [],
		'title' => GetMessage('CTRLR_MENU_RUN_TITLE'),
	];
}

if ($USER->CanDoOperation('controller_upload_file'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_UPLOAD_NAME'),
		'url' => 'controller_upload_file.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [],
		'title' => GetMessage('CTRLR_MENU_UPLOAD_TITLE'),
	];
}

if ($USER->CanDoOperation('controller_counters_view'))
{
	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_COUNTERS'),
		'url' => 'controller_counter_admin.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => [
			'controller_counter_edit.php?lang=' . LANGUAGE_ID,
		],
		'items_id' => 'menu_controller_counter',
		'title' => GetMessage('CTRLR_MENU_COUNTERS_TITLE'),
		'items' => [
			[
				'text' => GetMessage('CTRLR_MENU_COUNTERS_HISTORY'),
				'url' => 'controller_counter_history.php?lang=' . LANGUAGE_ID,
				'module_id' => 'controller',
				'more_url' => [
					'controller_counter_history.php',
				],
				'items_id' => 'menu_controller_counter_history',
				'title' => GetMessage('CTRLR_MENU_COUNTERS_HISTORY_TITLE'),
			]
		],
	];
}

if ($USER->CanDoOperation('controller_auth_view'))
{
	$more_url = [
		'controller_group_map.php',
	];

	$items = [];
	if ($USER->CanDoOperation('controller_auth_log_view'))
	{
		$items[] = [
			'text' => GetMessage('CTRLR_MENU_AUTH_LOG'),
			'url' => 'controller_auth_log.php?lang=' . LANGUAGE_ID,
			'module_id' => 'controller',
			'items_id' => 'menu_controller_auth_log',
			'title' => GetMessage('CTRLR_MENU_AUTH_LOG_TITLE'),
		];
	}
	else
	{
		$more_url[] = 'controller_auth_log.php';
	}

	$aMenu['items'][] = [
		'text' => GetMessage('CTRLR_MENU_AUTH'),
		'url' => 'controller_auth.php?lang=' . LANGUAGE_ID,
		'module_id' => 'controller',
		'more_url' => $more_url,
		'items_id' => 'menu_controller_auth',
		'title' => '',
		'items' => $items,
	];
}

if ($aMenu['items'])
{
	return $aMenu;
}
else
{
	return false;
}
