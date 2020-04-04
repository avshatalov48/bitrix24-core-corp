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
]);

Loc::loadMessages(__FILE__);

if (Loader::includeModule('disk'))
{
	Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
	Extension::load([
		'ajax',
		'core',
		'uploader',
		'disk_external_loader',
		'ui.tooltip',
		'ui.viewer',
		'disk.document',
		'disk.viewer.actions',
	]);
}

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?php $helper->displayFatals();?>
<?php if(!$helper->checkHasFatals()):?>

	<?php
	foreach ($arResult['UF_CHECKLIST_FILES'] as $id => $field)
	{
		ob_start();
		\Bitrix\Tasks\Util\UserField\UI::showView($field, ['TEMPLATE' => 'checklist']);
		$arResult['ATTACHMENTS'][$id] = ob_get_clean();
	}
	$arResult['ATTACHMENTS'] = ($arResult['ATTACHMENTS']?: []);
	?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">
		<?php $helper->displayWarnings();?>

		<div id="checklistArea"></div>
		<?php if ($arResult['COMMON_ACTION']['CAN_ADD']):?>
			<div class="checklist-list-actions">
				<a id="addCheckList" class="checklist-item-add-btn"><?=Loc::getMessage('TASKS_CHECKLIST_TEMPLATE_ADD_CHECKLIST')?></a>
			</div>
		<?php endif?>
	</div>

	<script type="text/javascript">
		BX.ready(function()
		{
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
				'items' => $arResult['DATA']['TREE_ARRAY'],
				'prefix' => $arResult['INPUT_PREFIX'],
				'userPath' => $arResult['PATH_TO_USER_PROFILE'],
				'converted' => $arResult['CONVERTED'],
				'ajaxActions' => $arResult['AJAX_ACTIONS'],
				'attachments' => $arResult['ATTACHMENTS'],
				'options' => [
					'showCompleted' => $arResult['SHOW_COMPLETED'],
				],
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

			BX.Tasks.CheckListInstance = new BX.Tasks.CheckList(data);
		});
	</script>

	<?php $helper->initializeExtension();?>

<?php endif?>