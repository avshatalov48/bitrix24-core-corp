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
	'ui.design-tokens',
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

$suffixDomId = $this->getComponent()->getId();

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
	$arResult['ATTACHMENTS'] = (($arResult['ATTACHMENTS'] ?? null) ?: []);
	?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">
		<?php $helper->displayWarnings();?>

		<div id="checklistArea_<?=$suffixDomId?>"></div>
		<?php if ($arResult['COMMON_ACTION']['CAN_ADD']):?>
			<div class="tasks-checklist-list-actions">
				<a id="addCheckList_<?=$suffixDomId?>" class="tasks-checklist-item-add-btn"><?=Loc::getMessage('TASKS_CHECKLIST_TEMPLATE_ADD_CHECKLIST')?></a>
			</div>
		<?php endif?>
	</div>

	<script>
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

			var checklistNode = document.getElementById('checklistArea_' + '<?=$suffixDomId?>');
			if (!checklistNode)
			{
				checklistNode = top.document.getElementById('checklistArea_' + '<?=$suffixDomId?>');
			}

			var renderTo = {
				renderTo: checklistNode
			};
			var data = Object.assign(renderTo, <?=Json::encode([
				'userId' => $arResult['USER_ID'],
				'entityId' => $arResult['ENTITY_ID'],
				'entityType' => $arResult['ENTITY_TYPE'],
				'items' => $arResult['DATA']['TREE_ARRAY'],
				'prefix' => $arResult['INPUT_PREFIX'],
				'userPath' => $arResult['PATH_TO_USER_PROFILE'],
				'converted' => $arResult['CONVERTED'],
				'isNetworkEnabled' => $arResult['IS_NETWORK_ENABLED'],
				'showCompleteAllButton' => $arResult['SHOW_COMPLETE_ALL_BUTTON'],
				'collapseOnCompleteAll' => $arResult['COLLAPSE_ON_COMPLETE_ALL'],
				'ajaxActions' => $arResult['AJAX_ACTIONS'],
				'attachments' => $arResult['ATTACHMENTS'],
				'options' => $arResult['USER_OPTIONS'],
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
				'suffixDomId' => $suffixDomId,
			])?>);

			BX.Tasks.CheckListInstance = new BX.Tasks.CheckList(data);
		});
	</script>

	<?php $helper->initializeExtension();?>

<?php endif?>