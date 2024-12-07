<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'loader',
	'main.polyfill.closest',
	'main.polyfill.matches',
	'tasks.checklist',
	'ui.alerts',
	'ui.icons.disk',
	'ui.forms',
	'ui.progressbar',
	'ui.progressround',
	'ui.fonts.opensans',
]);

Loc::loadMessages(__FILE__);

if (Loader::includeModule('disk'))
{
	Extension::load([
		'mobile_uploader',
		'disk.document',
	]);
}
?>

<div id="checklistArea"></div>
<?php if ($arResult['COMMON_ACTION']['CAN_ADD'] && $arResult['CONVERTED']):?>
	<div class="mobile-task-checklist-add-new">
		<div class="mobile-task-checklist-add-new-text" id="addCheckList"><?=Loc::getMessage('TASKS_CHECKLIST_MOBILE_TEMPLATE_ADD_CHECKLIST')?></div>
	</div>
<?php endif?>

<script>
	BX.ready(function()
	{
		BX.message({
			TASKS_CHECKLIST_MOBILE_TEMPLATE_ADD_CHECKLIST: '<?=GetMessageJS('TASKS_CHECKLIST_MOBILE_TEMPLATE_ADD_CHECKLIST')?>',
			TASKS_CHECKLIST_MOBILE_COMPONENT_JS_NEW_CHECKLIST_TITLE: '<?=GetMessageJS('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_NEW_CHECKLIST_TITLE')?>',
			TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_1: '<?=GetMessageJS('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_1')?>',
			TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_2: '<?=GetMessageJS('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_2')?>',
			TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_3: '<?=GetMessageJS('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_3')?>',
			TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_4: '<?=GetMessageJS('TASKS_CHECKLIST_MOBILE_COMPONENT_JS_CHECKLIST_NOT_CONVERTED_MESSAGE_PART_4')?>'
		});

		BX.addCustomEvent(window, 'onBXMessageNotFound', function(message) {
			if (message === 'DISK_TMPLT_THUMB' || message === 'DISK_TMPLT_THUMB2')
			{
				var obj = {};
				obj[message] = '';

				BX.message(obj);
			}
		});

		var renderTo = {
			renderTo: BX('checklistArea')
		};
		var data = Object.assign(renderTo, <?=Json::encode([
			'userId' => $arResult['USER_ID'],
			'entityId' => $arResult['ENTITY_ID'],
			'entityType' => $arResult['ENTITY_TYPE'],
			'mode' => $arResult['MODE'],
			'items' => $arResult['DATA']['TREE_ARRAY'],
			'prefix' => $arResult['INPUT_PREFIX'],
			'taskGuid' => $arResult['TASK_GUID'],
			'userPath' => $arResult['PATH_TO_USER_PROFILE'],
			'converted' => $arResult['CONVERTED'],
			'showCompleteAllButton' => $arResult['SHOW_COMPLETE_ALL_BUTTON'],
			'collapseOnCompleteAll' => $arResult['COLLAPSE_ON_COMPLETE_ALL'],
			'ajaxActions' => $arResult['AJAX_ACTIONS'],
			'options' => $arResult['USER_OPTIONS'],
			'diskFolderId' => $arResult['DISK_FOLDER_ID'],
			'commonAction' => [
				'canAdd' => $arResult['COMMON_ACTION']['CAN_ADD'],
				'canDrag' => $arResult['COMMON_ACTION']['CAN_REORDER'],
				'canAddAccomplice' => $arResult['COMMON_ACTION']['CAN_ADD_ACCOMPLICE'],
			],
			'diskUrls' => [
				'urlSelect' => '/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID='.SITE_ID,
				'urlRenameFile' => '/bitrix/tools/disk/uf.php?action=renameFile',
				'urlDeleteFile' => '/bitrix/tools/disk/uf.php?action=deleteFile',
				'urlUpload' => '/bitrix/tools/disk/uf.php?action=uploadFile&ncc=1',
			],
		])?>);

		BX.Mobile.Tasks.CheckListInstance = new BX.Mobile.Tasks.CheckList(data);
	});
</script>