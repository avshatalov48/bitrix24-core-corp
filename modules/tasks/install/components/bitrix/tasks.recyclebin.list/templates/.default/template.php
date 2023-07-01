<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load('ui.design-tokens');

Loc::loadMessages(__FILE__);
?>

<div class="tasks-rec-list__toolbar">
	<div class="tasks-rec-list__toolbar-item --float-left"><?= GetMessage('TASKS_RECYCLEBIN_FILE_LIFETIME'); ?></div>
</div>

<?php $APPLICATION->IncludeComponent(
	'bitrix:tasks.interface.topmenu',
	'',
	[
		'USER_ID' => $arParams['USER_ID'],

		'GROUP_ID'           => ($arParams['MENU_GROUP_ID'] ?? null),
		'SECTION_URL_PREFIX' => '',

		'MARK_RECYCLEBIN' => 'Y',

		'PATH_TO_GROUP_TASKS'        => $arParams['PATH_TO_GROUP_TASKS'],
		'PATH_TO_GROUP_TASKS_TASK'   => $arParams['PATH_TO_GROUP_TASKS_TASK'],
		'PATH_TO_GROUP_TASKS_VIEW'   => $arParams['PATH_TO_GROUP_TASKS_VIEW'],
		'PATH_TO_GROUP_TASKS_REPORT' => $arParams['PATH_TO_GROUP_TASKS_REPORT'],

		'PATH_TO_USER_TASKS'                   => $arParams['PATH_TO_USER_TASKS'],
		'PATH_TO_USER_TASKS_TASK'              => $arParams['PATH_TO_USER_TASKS_TASK'],
		'PATH_TO_USER_TASKS_VIEW'              => $arParams['PATH_TO_USER_TASKS_VIEW'],
		'PATH_TO_USER_TASKS_REPORT'            => $arParams['PATH_TO_USER_TASKS_REPORT'],
		'PATH_TO_USER_TASKS_TEMPLATES'         => $arParams['PATH_TO_USER_TASKS_TEMPLATES'],
		'PATH_TO_USER_TASKS_PROJECTS_OVERVIEW' => $arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],

		'PATH_TO_CONPANY_DEPARTMENT' => $arParams['PATH_TO_CONPANY_DEPARTMENT']
	],
	$component,
	['HIDE_ICONS' => true]
);
?>

<?php if (\Bitrix\Tasks\Integration\Recyclebin\ConvertAgent::isProceed()): ?>
	<?php \CJSCore::Init(array('update_stepper')); ?>
	<div class="main-stepper-block">
		<div class="main-stepper main-stepper-show" >
			<div class="main-stepper-info"><?= GetMessage("TASKS_RECYCLEBIN_CONVERT_DATA"); ?></div>
			<div class="main-stepper-inner">
				<div class="main-stepper-bar">
					<div class="main-stepper-bar-line" style="width:0%;"></div>
				</div>
				<div class="main-stepper-error-text"></div>
			</div>
		</div>
	</div>
<?php else: ?>
	<?php $APPLICATION->IncludeComponent(
		'bitrix:recyclebin.list',
		".default",
		[
			"MODULE_ID"            => "tasks",
			"USER_ID"              => \Bitrix\Tasks\Util\User::getId(),
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
		],
		$component,
		["HIDE_ICONS" => "Y"]
	); ?>
<?php endif; ?>