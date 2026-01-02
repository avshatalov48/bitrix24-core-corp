<?php

use Bitrix\Main\Page\Asset;

$assetRoot = '/bitrix/js/tasks/';
$langRoot = BX_ROOT . '/modules/tasks/lang/' . LANGUAGE_ID . '/';

$assets = [
	// basic asset, contains widely used phrases and js-stuff required everywhere
	// also contains media kit asset, contains sprites and common CSS used in components
	/*
	 * When doing redesign, implement conditionally added css, like
	 * 'css' => [['condition' => function(){return *some condition*;}, 'file' => '/bitrix/js/tasks/css/media.css']];
	 */
	[
		'code' => 'tasks',
		'js' => [
			$assetRoot . 'tasks.js',
		],
		'css' => [
			$assetRoot . 'css/media.css',
		],
		'lang' => $langRoot . 'include.php',
		'rel' => ['ui.design-tokens'],
		'bundle_js' => 'tasks',
	],
	// util asset, contains fx functions, helper functions, and so on
	[
		'code' => 'tasks_util',
		'js' => [
			$assetRoot . 'util.js',
		],
		'bundle_js' => 'tasks',
	],
	// oop asset contains a basic class for making js oop emulation work
	[
		'code' => 'tasks_util_base',
		'js' => [
			$assetRoot . 'util/base.js',
		],
		'css' => [
			$assetRoot . 'css/media.css',
		],
		'rel' => ['core', 'ui.design-tokens'],
		'lang' => $langRoot . 'include.php',
	],
	// widget asset, allows creating widget-based js-controls
	[
		'code' => 'tasks_util_widget',
		'js' => [
			$assetRoot . 'util/widget.js',
		],
		'rel' => ['tasks_util_base'],
	],
	// components asset, contains logic for components
	[
		'code' => 'tasks_component',
		'js' => [
			$assetRoot . 'component.js',
		],
		'rel' => ['tasks_util_widget', 'tasks_util_query'],
	],
	// asset that imports an item accumulator
	[
		'code' => 'tasks_util_datacollection',
		'js' => [
			$assetRoot . 'util/datacollection.js',
		],
		'rel' => ['tasks_util_base'],
	],
	// asset that implements client-side interface for common ajax api
	[
		'code' => 'tasks_util_query',
		'js' => [
			$assetRoot . 'util/query.js',
		],
		'rel' => ['tasks_util_base', 'ajax'],
		'lang' => $langRoot . '/include/assets/query.php',
	],
	// asset that implements an interface for page rounting
	[
		'code' => 'tasks_util_router',
		'js' => [
			$assetRoot . 'util/router.js',
		],
		'rel' => ['tasks_util_base'],
	],
	// asset that implements templating mechanism
	[
		'code' => 'tasks_util_template',
		'js' => [
			$assetRoot . 'util/template.js',
		],
	],
	// asset that imports datepicker widget
	[
		'code' => 'tasks_util_datepicker',
		'js' => [
			$assetRoot . 'util/datepicker.js',
		],
		'rel' => ['tasks_util_widget', 'date'],
	],
	// asset that imports a util for implementing drag-n-drop
	[
		'code' => 'tasks_util_draganddrop',
		'js' => [
			$assetRoot . 'util/draganddrop.js',
		],
		'rel' => ['tasks_util_base', 'tasks_util', 'dnd'],
	],
	// asset that imports a list rendering control (abstract)
	[
		'code' => 'tasks_util_itemset',
		'js' => [
			$assetRoot . 'util/itemset.js',
		],
		'rel' => ['tasks_util_widget', 'tasks_util_datacollection'],
	],
	// asset that imports a family of scroll pane controls
	[
		'code' => 'tasks_util_scrollpane',
		'js' => [
			$assetRoot . 'util/scrollpane.js',
		],
		'rel' => ['tasks_util_widget', 'tasks_util_template', 'popup'],
	],
	// asset that imports a family of selector controls
	[
		'code' => 'tasks_util_selector',
		'js' => [
			$assetRoot . 'util/selector.js',
		],
		'rel' => ['tasks_util_widget', 'tasks_util_scrollpane', 'tasks_util_datacollection'],
	],
	// asset that imports a list rendering control different implementations
	[
		'code' => 'tasks_itemsetpicker',
		'js' => [
			$assetRoot . 'itemsetpicker.js',
		],
		'rel' => ['tasks_util_itemset', 'tasks_integration_socialnetwork'],
	],
	// asset that imports js-api for interacting with user day plan
	[
		'code' => 'tasks_dayplan',
		'js' => [
			$assetRoot . 'dayplan.js',
		],
		'rel' => ['tasks_ui_base', 'tasks_util_query'],
	],
	// asset that implements some integration with "socialnetwork" module
	[
		'code' => 'tasks_integration_socialnetwork',
		'js' => [
			$assetRoot . 'integration/socialnetwork.js',
		],
		'rel' => ['tasks_util', 'tasks_util_query', 'tasks_util_widget', 'socnetlogdest', 'tasks_itemsetpicker'],
	],
	// shared js parts
	[
		'code' => 'tasks_shared_form_projectplan',
		'js' => [
			$assetRoot . 'shared/form/projectplan.js',
		],
		'rel' => ['tasks_util_widget', 'tasks_util_datepicker'],
	],

	// assets for implementing gantt js api
	[
		'code' => 'task_date',
		'js' => [
			$assetRoot . 'task-date.js',
		],
	],
	[
		'code' => 'task_calendar',
		'js' => [
			$assetRoot . 'task-calendar.js',
		],
		'rel' => ['task_date'],
	],
	[
		'code' => 'task_timeline',
		'js' => [
			$assetRoot . 'scheduler/util.js',
			$assetRoot . 'scheduler/timeline.js',
			$assetRoot . 'scheduler/printer.js',
			$assetRoot . 'scheduler/print-settings.js',
		],
		'lang' => $langRoot . 'scheduler/timeline.php',
		'css' => [
			$assetRoot . 'css/gantt.css',
			$assetRoot . 'scheduler/css/print-settings.css',
		],
		'rel' => ['ui.design-tokens', 'task_date', 'task_calendar', 'date', 'ui.alerts'],
		'bundle_js' => 'tasks_timeline',
		'bundle_css' => 'tasks_gantt',
	],
	[
		'code' => 'task_scheduler',
		'js' => [
			$assetRoot . 'scheduler/tree.js',
			$assetRoot . 'scheduler/scheduler.js',
		],
		'css' => [
			$assetRoot . 'scheduler/css/scheduler.css',
		],
		'rel' => ['ui.design-tokens', 'task_timeline'],
		'bundle_js' => 'tasks_scheduler',
		'bundle_css' => 'tasks_scheduler',
	],
	[
		'code' => 'gantt',
		'js' => [
			$assetRoot . 'gantt.js',
		],
		'css' => [
			$assetRoot . 'css/gantt.css',
		],
		'rel' => [
			'ui.design-tokens',
			'popup',
			'date',
			'task_info_popup',
			'task_calendar',
			'task_date',
			'dnd',
			'task_scheduler',
		],
		'lang' => $langRoot . 'gantt.php',
		'bundle_js' => 'tasks_gantt',
		'bundle_css' => 'tasks_gantt',
	],

	[
		'code' => 'task_kanban',
		'js' => [
			$assetRoot . 'kanban/actions.js',
			$assetRoot . 'kanban/grid.js',
			$assetRoot . 'kanban/item.js',
			$assetRoot . 'kanban/column.js',
		],
		'css' => [
			$assetRoot . 'kanban/css/kanban.css',
		],
		'rel' => [
			'ui.design-tokens',
			'ui.fonts.opensans',
			'kanban',
			'ajax',
			'color_picker',
			'date',
			'tasks_integration_socialnetwork',
		],
		'lang' => $langRoot . 'kanban.php',
		'bundle_js' => 'tasks_kanban',
		'bundle_css' => 'tasks_kanban',
	],

	[
		'code' => 'task_graph_circle',
		'js' => [
			$assetRoot . 'graph/circle.js',
		],
		'bundle_js' => 'task_graph_circle',
	],

	[
		'code' => 'task_kanban_timeline',
		'js' => [
			$assetRoot . 'kanban/timeline/grid.js',
			$assetRoot . 'kanban/timeline/item.js',
		],
		'rel' => ['task_kanban'],
		'bundle_js' => 'task_kanban_timeline',
	],

	// deprecated assets
	[
		'code' => 'task_info_popup',
		'js' => [
			$assetRoot . 'task-info-popup.js',
		],
		'css' => [
			$assetRoot . 'css/task-info-popup.css',
		],
		'rel' => ['ui.design-tokens', 'popup', 'tasks_util'],
		'lang' => $langRoot . 'task-info-popup.php',
	],
	[
		'code' => 'task_popups',
		'js' => [
			$assetRoot . 'task-popups.js',
		],
		'css' => [
			$assetRoot . 'css/task-popups.css',
		],
		'rel' => ['ui.design-tokens', 'popup'],
		'lang' => $langRoot . 'task-popups.php',
	],
	[
		'code' => 'CJSTask',
		'js' => [
			$assetRoot . 'cjstask.js',
		],
		'rel' => ['ajax'],
		'bundle_js' => 'tasks',
	],
	[
		'code' => 'taskQuickPopups',
		'js' => [
			$assetRoot . 'task-quick-popups.js',
		],
		'rel' => ['popup', 'ajax', 'CJSTask'],
	],
	[
		'code' => 'tasks_style_legacy',
		'css' => [
			$assetRoot . 'css/tasks.css',
		],
		'rel' => ['ui.design-tokens'],
	],
];

Asset::getInstance()->addCssKernelInfo('tasks', [
	$assetRoot . 'css/core_planner_handler.css',
]);

Asset::getInstance()->addJsKernelInfo('tasks', [
	$assetRoot . 'core_planner_handler.js',
	$assetRoot . 'task-iframe-popup.js',
]);

foreach ($assets as $asset)
{
	CJSCore::registerExt(
		$asset['code'],
		$asset
	);
}
