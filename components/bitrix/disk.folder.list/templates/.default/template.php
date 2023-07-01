<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var $this CBitrixComponentTemplate */
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var BaseComponent $component */

use Bitrix\Disk\Integration\Bitrix24Manager;
use Bitrix\Disk\Integration\BizProcManager;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Grid\FolderListOptions;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\Page\Asset;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsHandler;
use Bitrix\UI\Buttons\Tag;

?>

<?
$documentRoot = Application::getDocumentRoot();
$isBitrix24Template = (SITE_TEMPLATE_ID === 'bitrix24');
$isInIframe = Main\Context::getCurrent()->getRequest()->get('IFRAME') === 'Y';


CJSCore::Init(array(
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.viewer',
	'disk',
	'disk_page',
	'disk_folder_tree',
	'disk_information_popups',
	'socnetlogdest',
	'access',
	'bp_starter',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.tilegrid',
	'ui.icons',
	'ui.actionpanel',
	'sidepanel',
	'tooltip',
	'update_stepper',
	'disk_queue',
	'disk.model.item',
	'disk.model.external-link.settings',
	'disk.model.external-link.input',
	'disk.model.external-link.description',
	'disk.document',
	'disk.viewer.document-item',
	'disk.viewer.actions',
	'clipboard',
));

Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.grid/templates/.default/bitrix/main.interface.grid/.default/style.css');
Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.interface.toolbar/templates/.default/style.css');

$bodyClasses = 'pagetitle-toolbar-field-view no-hidden no-all-paddings';
if ($arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_TILE)
{
	$bodyClasses .= ' no-background';
}

$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, $bodyClasses)));

$jsTemplates = new Main\IO\Directory($documentRoot.$templateFolder.'/js-templates');
/** @var Main\IO\File $jsTemplate */
foreach ($jsTemplates->getChildren() as $jsTemplate)
{
	include($jsTemplate->getPath());
}

include('process.php');

Toolbar::addFilter([
   'GRID_ID' => $arResult['GRID']['ID'],
   'FILTER_ID' => $arResult['FILTER']['FILTER_ID'],
   'FILTER' => $arResult['FILTER']['FILTER'],
   'FILTER_PRESETS' => $arResult['FILTER']['FILTER_PRESETS'],
   'ENABLE_LIVE_SEARCH' => $arResult['FILTER']['ENABLE_LIVE_SEARCH'],
   'ENABLE_LABEL' => $arResult['FILTER']['ENABLE_LABEL'],
   'RESET_TO_DEFAULT_MODE' => $arResult['FILTER']['RESET_TO_DEFAULT_MODE'],
]);
Toolbar::setTitleMinWidth(158);

$uri = new Main\Web\Uri(Bitrix\Main\Context::getCurrent()->getRequest()->getRequestUri());

$uriToTileM = (clone $uri);
$uriToTileM->addParams(['viewMode' => FolderListOptions::VIEW_MODE_TILE, 'viewSize' => FolderListOptions::VIEW_TILE_SIZE_M, ]);

$uriToTileXL = (clone $uri);
$uriToTileXL->addParams(['viewMode' => FolderListOptions::VIEW_MODE_TILE, 'viewSize' => FolderListOptions::VIEW_TILE_SIZE_XL, ]);

$uriToGrid = (clone $uri);
$uriToGrid->addParams(['viewMode' => FolderListOptions::VIEW_MODE_GRID,]);

$jsSettingsDropdown = $jsSortFields = array();

if(!empty($arResult['STORAGE']['CAN_CHANGE_RIGHTS_ON_STORAGE']))
{
	$onclickRightsOnStorage = Bitrix24Manager::filterJsAction('disk_folder_sharing', "BX.Disk['FolderListClass_{$component->getComponentId()}'].showRightsOnStorage();");
	$jsSettingsDropdown[] = array(
		'text' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_CHANGE_RIGHTS'),
		'title' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_CHANGE_RIGHTS'),
		'href' => "javascript: {$onclickRightsOnStorage}; BX.PopupMenu.destroy('settings_disk');",
	);
}
if(!empty($arResult['STORAGE']['CAN_CHANGE_SETTINGS_ON_STORAGE']) && $arResult['STORAGE']['CAN_CHANGE_SETTINGS_ON_BIZPROC_EXCEPT_USER'] && BizProcManager::isAvailable())
{
	$jsSettingsDropdown[] = array(
		'text' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_BIZPROC_SETTINGS'),
		'title' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_BIZPROC_SETTINGS'),
		'href' => "javascript:BX.Disk['FolderListClass_{$component->getComponentId()}'].showSettingsOnBizproc(); BX.PopupMenu.destroy('settings_disk');",
	);
}
if(!empty($arResult['STORAGE']['CAN_CHANGE_SETTINGS_ON_BIZPROC']) && $arResult['STORAGE']['CAN_CHANGE_SETTINGS_ON_BIZPROC_EXCEPT_USER'] && $arResult['STORAGE']['SHOW_BIZPROC'])
{
	$jsSettingsDropdown[] = array(
		'text' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_BIZPROC'),
		'title' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_BIZPROC'),
		'href' => "javascript:BX.Disk['FolderListClass_{$component->getComponentId()}'].openSlider('{$arParams["PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN"]}'); BX.PopupMenu.destroy('settings_disk');",
	);
}
$linkOnNetworkDrive = CUtil::JSescape($arResult['STORAGE']['NETWORK_DRIVE_LINK']);
$jsSettingsDropdown[] = array(
	'text' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE'),
	'title' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE'),
	'href' => "javascript:BX.Disk['FolderListClass_{$component->getComponentId()}'].showNetworkDriveConnect({
		link: '{$linkOnNetworkDrive}'
	}); BX.PopupMenu.destroy('settings_disk');",
);
$jsSettingsDropdown[] = array(
	'text' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_SETTINGS_DOCS'),
	'title' => Loc::getMessage('DISK_FOLDER_LIST_PAGE_TITLE_SETTINGS_DOCS'),
	'href' => "javascript:BX.Disk['FolderListClass_{$component->getComponentId()}'].openWindowForSelectDocumentService({}); BX.PopupMenu.destroy('settings_disk');",
);
if (!empty($arResult["PATH_TO_DISK_VOLUME"]))
{
	$jsSettingsDropdown[] = array(
		'text' => Loc::getMessage('DISK_FOLDER_LIST_VOLUME_PURIFY'),
		'title' => Loc::getMessage('DISK_FOLDER_LIST_VOLUME_PURIFY'),
		'href' => $arResult["PATH_TO_DISK_VOLUME"],
		'target' => $isInIframe? '_blank' : '',
	);
}
$currentPage = $APPLICATION->GetCurPageParam('', array($arResult['GRID']["SORT_VARS"]["order"], $arResult['GRID']["SORT_VARS"]["by"]));
foreach($arResult['GRID']['COLUMN_FOR_SORTING'] as $name => $column)
{
	$jsSortFields[] = [
		'field' => $name,
		'label' => $column['LABEL'],
	];
}

$byColumn = key($arResult['GRID']['SORT']);
$direction = $arResult['GRID']['SORT'][$byColumn];
$inverseDirection = mb_strtolower($direction) == 'desc'? 'asc' : 'desc';
$sortLabel = $arResult['GRID']['COLUMN_FOR_SORTING'][$byColumn]['LABEL'];
$isMixSorting = $arResult['GRID']['SORT_MODE'] === FolderListOptions::SORT_MODE_MIX;

if (!empty($arResult['STORAGE']['FOR_SOCNET_GROUP']))
{
	$connectBtn = new Button([
		"color" => Color::LIGHT_BORDER,
    	"icon" => Icon::DISK,
		"click" => new JsHandler(
			"BX.Disk.FolderListClass_{$component->getComponentId()}.onClickManageConnectButton",
			"BX.Disk.FolderListClass_{$component->getComponentId()}"
		),
		"text" => Loc::getMessage('DISK_FOLDER_LIST_LABEL_CONNECT_DISK'),
    ]);

	if ($arResult['STORAGE']['CONNECTED_SOCNET_GROUP_OBJECT_ID'])
	{
		$connectBtn->setText(Loc::getMessage('DISK_FOLDER_LIST_LABEL_ALREADY_CONNECT_DISK'));
		$connectBtn->addClass(Icon::DONE);
		$connectBtn->removeClass(Icon::DISK);
	}

    Toolbar::addButton($connectBtn);
}

if (empty($arResult['IS_TRASH_MODE']))
{
	$trashBtn = new Button([
		"color" => Color::LIGHT_BORDER,
		"tag" => Tag::LINK,
		"className" => 'js-disk-trashcan-button',
		"dataset" => ['toolbar-collapsed-icon' => Icon::REMOVE],
		"link" => $arResult['PATH_TO_USER_TRASHCAN_LIST'],
		"text" => Loc::getMessage('DISK_FOLDER_LIST_GO_TO_TRASH'),
    ]);
	if ($isInIframe)
	{
		$trashBtn->addAttribute("target", "_blank");
	}

    Toolbar::addButton($trashBtn);
}


Toolbar::addButton([
	"className" => 'js-disk-settings-button',
	"color" => Color::LIGHT_BORDER,
	"icon" => Icon::SETTING,
]);

if (empty($arResult['IS_TRASH_MODE']))
{
	$filterJsAction = $arResult['STORAGE']['BLOCK_ADD_BUTTONS'] ? Bitrix24Manager::filterJsAction('disk_common_storage', '') : '';
	$addBtn = new Button([
        "color" => Color::PRIMARY,
		"className" => $filterJsAction? '' : 'js-disk-add-button',
		"click" => new JsCode($filterJsAction),
		"text" => Loc::getMessage('DISK_FOLDER_LIST_TITLE_ADD_COMPLEX'),
    ]);
	$addBtn->setDropdown();

    Toolbar::addButton($addBtn);
}
else
{
    Toolbar::addButton([
        "color" => Color::PRIMARY,
		"click" => new JsHandler(
			"BX.Disk.FolderListClass_{$component->getComponentId()}.openConfirmEmptyTrash",
			"BX.Disk.FolderListClass_{$component->getComponentId()}"
		),
		"text" => Loc::getMessage('DISK_FOLDER_LIST_TITLE_EMPTY_TRASH'),
    ]);
}
?>

<? $isBitrix24Template && $this->setViewTarget('below_pagetitle'); ?>
<div class="disk-folder-list-toolbar" id="disk-folder-list-toolbar">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.breadcrumbs',
		'',
		array(
			'STORAGE_ID' => $arResult['STORAGE']['ID'],
			'BREADCRUMBS_ROOT' => $arResult['BREADCRUMBS_ROOT'],
			'BREADCRUMBS' => $arResult['BREADCRUMBS'],
			'ENABLE_DROPDOWN' => !$arResult['IS_TRASH_MODE'],
			//'ENABLE_SHORT_MODE' => true,
		)
	);
	?>
	<div class="disk-folder-list-config">
    <? if (!empty($arResult['ENABLED_TRASHCAN_TTL'])): ?>
		<div class="disk-folder-list-trashcan-info">
			<span class="disk-folder-list-trashcan-info-text"><?= Loc::getMessage('DISK_FOLDER_LIST_TRASHCAN_TTL_NOTICE', ['#TTL_DAY#' => $arResult['TRASHCAN_TTL']]) ?></span>
		</div>
    <? endif; ?>
		<div class="disk-folder-list-sorting">
			<span class="disk-folder-list-sorting-text" data-role="disk-folder-list-sorting"><?= $sortLabel ?></span>
		</div>
		<div class="disk-folder-list-view">
			<a href="?<?= $uriToGrid->getQuery() ?>" class="disk-folder-list-view-item disk-folder-list-view-item-lines <?= ($arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_GRID? 'disk-folder-list-view-item-active' : '') ?>"></a>
			<a href="?<?= $uriToTileM->getQuery() ?>" class="disk-folder-list-view-item disk-folder-list-view-item-grid js-disk-change-view <?= ($arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_TILE && $arResult['GRID']['VIEW_SIZE'] === FolderListOptions::VIEW_TILE_SIZE_M? 'disk-folder-list-view-item-active' : '') ?>" data-view-tile-size="<?= FolderListOptions::VIEW_TILE_SIZE_M ?>"></a>
			<a href="?<?= $uriToTileXL->getQuery() ?>" class="disk-folder-list-view-item disk-folder-list-view-item-grid-tile js-disk-change-view <?= ($arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_TILE && $arResult['GRID']['VIEW_SIZE'] === FolderListOptions::VIEW_TILE_SIZE_XL? 'disk-folder-list-view-item-active' : '') ?>" data-view-tile-size="<?= FolderListOptions::VIEW_TILE_SIZE_XL ?>"></a>
		</div>
	</div>
</div>
<? $isBitrix24Template && $this->endViewTarget(); ?>

<? if($arResult['STATUS_BIZPROC'] && $arResult['WORKFLOW_TEMPLATES']) { ?>
	<div style="display:none;">
		<form id="parametersFormBp">
		<div id="divStartBizProc" class="bx-disk-form-bizproc-start-div">
			<table class="bx-disk-form-bizproc-start-table">
				<col class="bx-disk-col-table-left">
				<col class="bx-disk-col-table-right">
				<? if(!empty($arResult['WORKFLOW_TEMPLATES'])) {
					if($arResult['BIZPROC_PARAMETERS']) {?>
						<tr>
							<td class="bx-disk-form-bizproc-start-td-title" colspan="2">
								<?= Loc::getMessage('DISK_FOLDER_LIST_LABEL_START_BIZPROC') ?>
							</td>
						</tr>
						<tr id="errorTr">
							<td id="errorTd" class="bx-disk-form-bizproc-start-td-error" colspan="2">

							</td>
						</tr>
					<? }
					foreach($arResult['WORKFLOW_TEMPLATES'] as $workflowTemplate)
					{
						if(!empty($workflowTemplate['PARAMETERS'])) { ?>
							<tr>
								<td class="bx-disk-form-bizproc-start-td-name-bizproc" colspan="2">
									<?= $workflowTemplate['NAME'] ?>
									<input type="hidden" value="1" name="checkBp" />
									<input type="hidden" value="create" name="autoExecute" />
								</td>
							</tr>
						<?CBPDocument::StartWorkflowParametersShow($workflowTemplate['ID'], $workflowTemplate['PARAMETERS'], 'formAutoloadBizProc', false);
						}else { ?>
							<tr>
								<td class="bx-disk-form-bizproc-start-td-name-bizproc" colspan="2">
									<input type="hidden" value="1" name="checkBp" />
									<input type="hidden" value="create" name="autoExecute" />
								</td>
							</tr>
						<? }
					}
				}
				?>
			</table>
		</div>
		</form>
	</div>
<? } ?>

<script type="text/javascript">
BX.message({
	DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_DIE_ACCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_DIE_ACCESS")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_DIE_ACCESS_SUCCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_DIE_ACCESS_SUCCESS")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_ACCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_ACCESS")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_ACCESS_SUCCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TAB_PROCESS_ACCESS_SUCCESS")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TITLE_DIE_SELF_ACCESS_SIMPLE: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TITLE_DIE_SELF_ACCESS_SIMPLE")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TAB_COMMON_SHARED_SECTION_PROCESS_DIE_ACCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TAB_COMMON_SHARED_SECTION_PROCESS_DIE_ACCESS")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TAB_COMMON_SHARED_SECTION_PROCESS_DIE_ACCESS_SUCCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TAB_COMMON_SHARED_SECTION_PROCESS_DIE_ACCESS_SUCCESS")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TITLE_DIE_ALL_ACCESS_SIMPLE: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TITLE_DIE_ALL_ACCESS_SIMPLE")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_TITLE_DIE_ALL_ACCESS_DESCR: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_TITLE_DIE_ALL_ACCESS_DESCR")?>',
	DISK_FOLDER_LIST_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE_CANCEL: '<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_BTN_DIE_SELF_ACCESS_SIMPLE_CANCEL")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_FILE_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_FILE_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_FOLDER_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_DELETED_FILE_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_DELETED_FILE_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_DELETED_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_DELETED_FOLDER_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_FOLDER_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_FILE_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_FILE_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_GROUP_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DESTROY_GROUP_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DESTROY_GROUP_CONFIRM")?>',
	DISK_FOLDER_LIST_TRASH_DESTROY_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DESTROY_BUTTON")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_EMPTY_TRASH: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EMPTY_TRASH")?>',
	DISK_FOLDER_LIST_TRASH_EMPTY_TRASH_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_EMPTY_TRASH_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_EMPTY_TRASH_TITLE: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EMPTY_TRASH_TITLE")?>',
	DISK_FOLDER_LIST_TRASH_EMPTY_TRASH_DESCRIPTION: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_EMPTY_TRASH_DESCRIPTION")?>',
	DISK_FOLDER_LIST_TRASH_CANCEL_DELETE_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_CANCEL_DELETE_BUTTON")?>',
	DISK_FOLDER_LIST_TRASH_DELETE_TITLE: '<?=GetMessageJS("DISK_FOLDER_LIST_TRASH_DELETE_TITLE")?>',
	DISK_FOLDER_LIST_DETACH_FILE_TITLE: '<?= GetMessageJS('DISK_FOLDER_LIST_DETACH_FILE_TITLE') ?>',
	DISK_FOLDER_LIST_DETACH_FOLDER_TITLE: '<?= GetMessageJS('DISK_FOLDER_LIST_DETACH_FOLDER_TITLE') ?>',
	DISK_FOLDER_LIST_DETACH_FOLDER_CONFIRM: '<?= GetMessageJS('DISK_FOLDER_LIST_DETACH_FOLDER_CONFIRM') ?>',
	DISK_FOLDER_LIST_DETACH_FILE_CONFIRM: '<?= GetMessageJS('DISK_FOLDER_LIST_DETACH_FILE_CONFIRM') ?>',
	DISK_FOLDER_LIST_DETACH_BUTTON: '<?= GetMessageJS('DISK_FOLDER_LIST_DETACH_BUTTON') ?>',
	DISK_FOLDER_LIST_UNSHARE_SECTION_CONFIRM: '<?=GetMessageJS("DISK_FOLDER_LIST_UNSHARE_SECTION_CONFIRM")?>',
	DISK_FOLDER_LIST_SUCCESS_CONNECT_TO_DISK_FOLDER: '<?=GetMessageJS("DISK_FOLDER_LIST_SUCCESS_CONNECT_TO_DISK_FOLDER")?>',
	DISK_FOLDER_LIST_SUCCESS_CONNECT_TO_DISK_FILE: '<?=GetMessageJS("DISK_FOLDER_LIST_SUCCESS_CONNECT_TO_DISK_FILE")?>',
	DISK_FOLDER_LIST_SUCCESS_LOCKED_FILE: '<?=GetMessageJS("DISK_FOLDER_LIST_SUCCESS_LOCKED_FILE")?>',
	DISK_FOLDER_LIST_SUCCESS_UNLOCKED_FILE: '<?=GetMessageJS("DISK_FOLDER_LIST_SUCCESS_UNLOCKED_FILE")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_GET_EXT_LINK: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_GET_EXT_LINK")?>',
	DISK_FOLDER_LIST_DETAIL_SHARE_INFO_OWNER: '<?=GetMessageJS("DISK_FOLDER_LIST_DETAIL_SHARE_INFO_OWNER")?>',
	DISK_FOLDER_LIST_DETAIL_SHARE_INFO_HAVE_ACCESS: '<?=GetMessageJS("DISK_FOLDER_LIST_DETAIL_SHARE_INFO_HAVE_ACCESS")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_TREE: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_TREE")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_MOVE_TO: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_MOVE_TO")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_COPY_TO: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_COPY_TO")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_MANY_COPY_TO: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_MANY_COPY_TO")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_MANY_MOVE_TO: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_MANY_MOVE_TO")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_MANY_COPY_TO_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_MANY_COPY_TO_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_MANY_DOWNLOAD_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_MANY_DOWNLOAD_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_MANY_DELETE_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_MANY_DELETE_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_MOVE_TO_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_MOVE_TO_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_MODAL_COPY_TO_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_MODAL_COPY_TO_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_GRID_TOOLBAR_COPY_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_GRID_TOOLBAR_COPY_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_GRID_TOOLBAR_MOVE_BUTTON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_GRID_TOOLBAR_MOVE_BUTTON")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_INT_LINK: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_INT_LINK")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_LINK: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_LINK")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_PARAMS: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_PARAMS_2")?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USE_DEATH_TIME: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USE_DEATH_TIME")?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USE_PASSWORD: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USE_PASSWORD")?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_INPUT_PASSWORD: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EXT_PARAMS_INPUT_PASSWORD")?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_TIME_MIN: '<?= GetMessageJS('DISK_FOLDER_LIST_TITLE_EXT_PARAMS_TIME_MIN') ?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_TIME_HOUR: '<?= GetMessageJS('DISK_FOLDER_LIST_TITLE_EXT_PARAMS_TIME_HOUR') ?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_TIME_DAY: '<?= GetMessageJS('DISK_FOLDER_LIST_TITLE_EXT_PARAMS_TIME_DAY') ?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_LINK_ON: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_LINK_ON")?>',
	DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_LINK_OFF: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_SIDEBAR_EXT_LINK_OFF")?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USED_DEATH_TIME: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USED_DEATH_TIME")?>',
	DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USED_PASSWORD: '<?=GetMessageJS("DISK_FOLDER_LIST_TITLE_EXT_PARAMS_USED_PASSWORD")?>',
	DISK_FOLDER_LIST_SELECTED_OBJECT_1: '<?= GetMessageJS('DISK_FOLDER_LIST_SELECTED_OBJECT_1') ?>',
	DISK_FOLDER_LIST_SELECTED_OBJECT_21: '<?= GetMessageJS('DISK_FOLDER_LIST_SELECTED_OBJECT_21') ?>',
	DISK_FOLDER_LIST_SELECTED_OBJECT_2_4: '<?= GetMessageJS('DISK_FOLDER_LIST_SELECTED_OBJECT_2_4') ?>',
	DISK_FOLDER_LIST_SELECTED_OBJECT_5_20: '<?= GetMessageJS('DISK_FOLDER_LIST_SELECTED_OBJECT_5_20') ?>',
	DISK_FOLDER_LIST_RIGHTS_TITLE_MODAL_WITH_NAME: '<?= GetMessageJS('DISK_FOLDER_LIST_RIGHTS_TITLE_MODAL_WITH_NAME') ?>',
	DISK_FOLDER_LIST_BIZPROC_TITLE_MODAL: '<?= GetMessageJS('DISK_FOLDER_LIST_BIZPROC_TITLE_MODAL') ?>',
	DISK_FOLDER_LIST_BIZPROC_LABEL: '<?= GetMessageJS('DISK_FOLDER_LIST_BIZPROC_LABEL') ?>',
	DISK_FOLDER_LIST_SHARING_TITLE_MODAL_3: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_TITLE_MODAL_3') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_RIGHTS_FOLDER: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_RIGHTS_FOLDER') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_NAME_ALLOW_SHARING_RIGHTS_USER: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ALLOW_SHARING_RIGHTS_USER') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_RIGHT_READ: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_RIGHT_READ') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_RIGHT_EDIT: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_RIGHT_EDIT') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_RIGHT_FULL: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_RIGHT_FULL') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_TOOLTIP_SHARING: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_TOOLTIP_SHARING') ?>',
	DISK_FOLDER_LIST_SHARING_LABEL_OWNER: '<?= GetMessageJS('DISK_FOLDER_LIST_SHARING_LABEL_OWNER') ?>',
	DISK_FOLDER_LIST_BTN_CLOSE: '<?= GetMessageJS('DISK_FOLDER_LIST_BTN_CLOSE') ?>',
	DISK_FOLDER_LIST_BTN_SAVE: '<?= GetMessageJS('DISK_FOLDER_LIST_BTN_SAVE') ?>',
	DISK_FOLDER_LIST_ACT_COPY_INTERNAL_LINK: '<?=GetMessageJS("DISK_FOLDER_LIST_ACT_COPY_INTERNAL_LINK")?>',
	DISK_FOLDER_LIST_ACT_COPIED_INTERNAL_LINK: '<?=GetMessageJS("DISK_FOLDER_LIST_ACT_COPIED_INTERNAL_LINK")?>',
	DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE: '<?=GetMessageJS("DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE")?>',
	DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE_DESCR_MODAL: '<?=GetMessageJS("DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE_DESCR_MODAL")?>',
	DISK_FOLDER_LIST_LABEL_NAME_CREATE_FOLDER: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_NAME_CREATE_FOLDER")?>',
	DISK_FOLDER_LIST_LABEL_LIVE_UPDATE_FILE: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_LIVE_UPDATE_FILE")?>',
	DISK_FOLDER_LIST_LABEL_ALREADY_CONNECT_DISK: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_ALREADY_CONNECT_DISK")?>',
	DISK_FOLDER_LIST_LABEL_CONNECT_DISK: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_CONNECT_DISK")?>',
	DISK_FOLDER_LIST_LABEL_DISCONNECTED_DISK: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_DISCONNECTED_DISK")?>',
	DISK_FOLDER_LIST_CREATE_FOLDER_MODAL: '<?=GetMessageJS("DISK_FOLDER_LIST_CREATE_FOLDER_MODAL")?>',
	DISK_FOLDER_LIST_LABEL_SHOW_EXTENDED_RIGHTS: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_SHOW_EXTENDED_RIGHTS")?>',
	DISK_FOLDER_LIST_LABEL_CHANGE_SYSTEM_FOLDERS: '<?=GetMessageJS("DISK_FOLDER_LIST_LABEL_CHANGE_SYSTEM_FOLDERS")?>',
	DISK_FOLDER_LABEL_NAME_CREATE_FOLDER: '<?= GetMessageJS('DISK_FOLDER_LABEL_NAME_CREATE_FOLDER') ?>',
	DISK_FOLDER_TITLE_CREATE_FOLDER: '<?= GetMessageJS('DISK_FOLDER_TITLE_CREATE_FOLDER') ?>',
	DISK_FOLDER_BTN_CREATE_FOLDER: '<?= GetMessageJS('DISK_FOLDER_BTN_CREATE_FOLDER') ?>',
	DISK_FOLDER_MW_CREATE_FILE_TITLE: '<?= GetMessageJS('DISK_FOLDER_MW_CREATE_FILE_TITLE') ?>',
	DISK_FOLDER_MW_CREATE_FILE_TEXT: '<?= GetMessageJS('DISK_FOLDER_MW_CREATE_FILE_TEXT') ?>',
	DISK_FOLDER_MW_CREATE_TYPE_DOC: '<?= GetMessageJS('DISK_FOLDER_MW_CREATE_TYPE_DOC') ?>',
	DISK_FOLDER_MW_CREATE_TYPE_XLS: '<?= GetMessageJS('DISK_FOLDER_MW_CREATE_TYPE_XLS') ?>',
	DISK_FOLDER_MW_CREATE_TYPE_PPT: '<?= GetMessageJS('DISK_FOLDER_MW_CREATE_TYPE_PPT') ?>',
	DISK_FOLDER_LIST_LABEL_SORT_INVERSE_DIRECTION: '<?= GetMessageJS('DISK_FOLDER_LIST_LABEL_SORT_INVERSE_DIRECTION') ?>',
	DISK_FOLDER_LIST_LABEL_SORT_MIX_MODE: '<?= GetMessageJS('DISK_FOLDER_LIST_LABEL_SORT_MIX_MODE') ?>',
	DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM')?>',
	DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM')?>',
	DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM')?>',
	DISK_TRASHCAN_ACT_RESTORE: '<?= GetMessageJS('DISK_TRASHCAN_ACT_RESTORE')?>',
	DISK_TRASHCAN_TRASH_RESTORE_TITLE: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_RESTORE_TITLE')?>',
	DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM')?>',
	DISK_FOLDER_LIST_EMPTY_BLOCK_MESSAGE: '<?= GetMessageJS('DISK_FOLDER_LIST_EMPTY_BLOCK_MESSAGE')?>',
	DISK_FOLDER_LIST_EMPTY_BLOCK_CREATE_FILE: '<?= GetMessageJS('DISK_FOLDER_LIST_EMPTY_BLOCK_CREATE_FILE')?>',
	DISK_FOLDER_LIST_EMPTY_BLOCK_CREATE_FOLDER: '<?= GetMessageJS('DISK_FOLDER_LIST_EMPTY_BLOCK_CREATE_FOLDER')?>',
	DISK_FOLDER_LIST_OK_FILE_MOVED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FILE_MOVED')?>',
	DISK_FOLDER_LIST_OK_FILE_COPIED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FILE_COPIED')?>',
	DISK_FOLDER_LIST_OK_FILE_DELETED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FILE_DELETED')?>',
	DISK_FOLDER_LIST_OK_FILE_SHARE_MODIFIED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FILE_SHARE_MODIFIED')?>',
	DISK_FOLDER_LIST_OK_FOLDER_MOVED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FOLDER_MOVED')?>',
	DISK_FOLDER_LIST_OK_FOLDER_COPIED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FOLDER_COPIED')?>',
	DISK_FOLDER_LIST_OK_FOLDER_DELETED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FOLDER_DELETED')?>',
	DISK_FOLDER_LIST_OK_FILE_RIGHTS_MODIFIED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FILE_RIGHTS_MODIFIED')?>',
	DISK_FOLDER_LIST_OK_FOLDER_RIGHTS_MODIFIED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FOLDER_RIGHTS_MODIFIED')?>',
	DISK_FOLDER_LIST_OK_FOLDER_SHARE_MODIFIED: '<?= GetMessageJS('DISK_FOLDER_LIST_OK_FOLDER_SHARE_MODIFIED')?>',
	DISK_FOLDER_LIST_SEARCH_INDEX_NOTICE_1: '<?= GetMessageJS('DISK_FOLDER_LIST_SEARCH_INDEX_NOTICE_1')?>',
	DISK_TRASHCAN_TRASH_RESTORE_DESCR_MULTIPLE: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_RESTORE_DESCR_MULTIPLE')?>',
	DISK_TRASHCAN_TRASH_RESTORE_SUCCESS: '<?= GetMessageJS('DISK_TRASHCAN_TRASH_RESTORE_SUCCESS')?>',
	DISK_FOLDER_LIST_SEARCH_PROGRESS_LABEL: '<?= GetMessageJS('DISK_FOLDER_LIST_SEARCH_PROGRESS_LABEL')?>'
});
</script>

<?php
echo $isInIframe? "<div id='bx-disk-container' class='bx-disk-container'>" : "";
include('only_grid.php');
echo $isInIframe? "</div>" : "";
?>

<script type="text/javascript">
BX(function () {
	if (BX.getClass('BX.UI.Viewer.Instance.setOptionsByGroup'))
	{
		BX.UI.Viewer.Instance.setOptionsByGroup('<?= $component->getComponentId() ?>', {
			cycleMode: false
		});
	}

	BX.Disk.storePathToUser('<?= CUtil::JSUrlEscape($arParams['PATH_TO_USER']) ?>');
	BX.Disk['FolderListClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FolderListClass({
		showSearchNotice: <?= empty($arResult['SHOW_SEARCH_NOTICE'])? 0 : 1 ?>,
		isTrashMode: <?= empty($arResult['IS_TRASH_MODE'])? 0 : 1 ?>,
		relativePath: '<?= $arResult['RELATIVE_PATH_ENCODED'] ?>',
		layout: {
			fileListContainer: document.querySelector('.bx-disk-interface-filelist'),
			trashCanButton: document.querySelector('.js-disk-trashcan-button'),
			changeViewButtons: document.querySelectorAll('.js-disk-change-view'),
			createItemsButton: document.querySelector('.js-disk-add-button'),
			emptyBlockUploadFileButtonId: 'disk-folder-list-no-data-upload-file',
			emptyBlockCreateFolderButtonId: 'disk-folder-list-no-data-create-folder'
		},
		rootObject: {
			id: <?= $arResult['BREADCRUMBS_ROOT']['ID']?: 'null' ?>,
			canAdd: <?= $arResult['STORAGE']['CAN_ADD']? 1 : 0 ?>,
			name: '<?= CUtil::JSEscape($arResult['BREADCRUMBS_ROOT']['NAME']) ?>'
		},
		currentFolder: {
			id: <?= (int)$arResult['FOLDER']['ID'] ?>,
			name: '<?= CUtil::JSEscape($arResult['FOLDER']['NAME']) ?>',
			canAdd: <?= $arResult['FOLDER']['CAN_ADD']? 1 : 0 ?>
		},
		sortFields: <?= CUtil::PhpToJSObject($jsSortFields) ?>,
		sort: {
			layout: {
				label: document.querySelector('[data-role="disk-folder-list-sorting"]')
			},
			sortBy: '<?= CUtil::JSEscape($byColumn) ?>',
			direction: '<?= CUtil::JSEscape($direction) ?>',
			mix: <?= $isMixSorting? 'true' : 'false' ?>
		},
		storage: {
			id: <?= $arResult['STORAGE']['ID'] ?>,
			link: '<?= CUtil::JSUrlEscape($arResult['STORAGE']['LINK']) ?>',
			trashLink: '<?= CUtil::JSUrlEscape($arResult['STORAGE']['TRASH_LINK']) ?>',
			fileLinkPrefix: '<?= CUtil::JSUrlEscape($arResult['STORAGE']['FILE_LINK_PREFIX']) ?>',
			trashFileLinkPrefix: '<?= CUtil::JSUrlEscape($arResult['STORAGE']['TRASH_FILE_LINK_PREFIX']) ?>',
			<?php if(isset($arParams["PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN"])): ?>
			bpListLink: '<?= CUtil::JSEscape($arParams["PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN"]) ?>',
			<?php endif; ?>
			name: '<?= CUtil::JSEscape($arResult['STORAGE']['NAME']) ?>',
			rootObject: {
				id: <?= $arResult['STORAGE']['ROOT_OBJECT_ID'] ?>
			},
			manage: {
				link: {
					object: {
						id: <?= isset($arResult['STORAGE']['CONNECTED_SOCNET_GROUP_OBJECT_ID'])? $arResult['STORAGE']['CONNECTED_SOCNET_GROUP_OBJECT_ID'] : 'null' ?>
					}
				}
			}
		},
		defaultServiceLabel: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['DEFAULT_SERVICE_LABEL']) ?>",
		createBlankFileUrl: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['CREATE_BLANK_FILE_URL']) ?>",
		renameBlankFileUrl: "<?= CUtil::JSUrlEscape($arResult['CLOUD_DOCUMENT']['RENAME_BLANK_FILE_URL']) ?>",
		getFilesCountAndSize: {
			button: BX('bx-btn-disk-files-number'),
			sizeContainer: BX('bx-disk-files-size-data'),
			countContainer: BX('bx-disk-files-count-data')
		},
		enabledModZip: <?= $arResult['ENABLED_MOD_ZIP']? 'true' : 'false' ?>,
		enabledExternalLink: <?= $arResult['ENABLED_EXTERNAL_LINK']? 'true' : 'false' ?>,
		enabledObjectLock: <?= $arResult['ENABLED_OBJECT_LOCK']? 'true' : 'false' ?>,
		isBitrix24: <?= $arResult['IS_BITRIX24']? 'true' : 'false' ?>,
		gridId: '<?= $arResult['GRID']['ID'] ?>',
		filterId: '<?= $arResult['FILTER']['FILTER_ID'] ?>',
		errors: <?= Bitrix\Main\Web\Json::encode($arResult['ERRORS_IN_GRID_ACTIONS']) ?>,
		information: '<?= CUtil::JSEscape($arResult['GRID_INFORMATION']) ?>',
		filterValueToSkipSearchUnderLinks: {
			SEARCH_IN_CURRENT_FOLDER: 1,
			SHARED: <?= CDiskFolderListComponent::FILTER_SHARED_TO_ME ?>
		}
	});

	var btnSettings = document.querySelector('.js-disk-settings-button');
	var buttonSettingsRect = btnSettings.getBoundingClientRect();
	BX.bind(
		btnSettings,
		'click',
		function(e){
			BX.PreventDefault(e);
			var menu = BX.PopupMenu.getMenuById('settings_disk');
			if(menu && menu.popupWindow)
			{
				if(menu.popupWindow.isShown())
				{
					BX.PopupMenu.destroy('settings_disk');
					return;
				}
			}
			BX.PopupMenu.show(
				'settings_disk',
				btnSettings,
				<?= CUtil::PhpToJSObject($jsSettingsDropdown) ?>,
				{
					autoHide : true,
					offsetTop: 0,
					offsetLeft: buttonSettingsRect.width / 2,
					angle: { offset: 25 },
					events:
					{
						onPopupClose : function(){}
					}
				}
			);
		}
	);

	var menuItemsLists = [];

	menuItemsLists.push({
		className: "menu-popup-no-icon menu-popup-item-upload-file",
		html: "<span id='menuItemsListsUpload'></span><?= CUtil::JSEscape(Loc::getMessage('DISK_FOLDER_LIST_TITLE_UPLOAD_FILE')) ?>",
		onclick: function(){

		}
	});
	menuItemsLists.push({
		text: "<?= CUtil::JSEscape(Loc::getMessage('DISK_FOLDER_LIST_TITLE_ADD_FOLDER')) ?>",
		onclick: function(){
			BX.Disk['FolderListClass_<?= $component->getComponentId() ?>'].createFolder();
		}
	});

	<?
	if (!empty($arResult['DOCUMENT_HANDLERS']))
	{
		foreach ($arResult['DOCUMENT_HANDLERS'] as $handler)
		{ ?>
			menuItemsLists.push({
				text: "<?= CUtil::JSEscape($handler['name']) ?>",
				items: [
					{
						text: "<?=CUtil::JSEscape(Loc::getMessage('DISK_FOLDER_MW_CREATE_TYPE_DOC')) ?>",
						onclick: function(event, popupItem){
							popupItem.getMenuWindow().getParentMenuWindow().close();
							BX.Disk['FolderListClass_<?= $component->getComponentId() ?>'].runCreatingFile('docx', '<?=$handler['code']?>');
						}
					},
					{
						text: "<?=CUtil::JSEscape(Loc::getMessage('DISK_FOLDER_MW_CREATE_TYPE_XLS')) ?>",
						onclick: function(event, popupItem){
							popupItem.getMenuWindow().getParentMenuWindow().close();
							BX.Disk['FolderListClass_<?= $component->getComponentId() ?>'].runCreatingFile('xlsx', '<?=$handler['code']?>');
						}

					},
					{
						text: "<?=CUtil::JSEscape(Loc::getMessage('DISK_FOLDER_MW_CREATE_TYPE_PPT')) ?>",
						onclick: function(event, popupItem){
							popupItem.getMenuWindow().getParentMenuWindow().close();
							BX.Disk['FolderListClass_<?= $component->getComponentId() ?>'].runCreatingFile('pptx', '<?=$handler['code']?>');
						}
					}
				]
			});
		<? }
	?>
	<?
	}
	?>

	<? if (empty($arResult['IS_TRASH_MODE']))
		{
			?>
				var addButton = document.querySelector('.js-disk-add-button');
				if (addButton)
				{
					var buttonRect = addButton.getBoundingClientRect();
					var menu = BX.PopupMenu.create(
						"popupMenuAdd",
						addButton,
						menuItemsLists,
						{
							closeByEsc: true,
							offsetLeft: buttonRect.width / 2,
							angle: true,
							events : {
								onPopupShow : function() { BX.onCustomEvent(window, "onDiskUploadPopupShow", [BX('menuItemsListsUpload').parentNode]); },
								onPopupClose : function() { BX.onCustomEvent(window, "onDiskUploadPopupClose", [BX('menuItemsListsUpload').parentNode]); }
							}
						}
					);

					BX.bind(addButton, "click", function()	{
						if (!BX.hasClass(BX("bx-disk-add-menu"), 'ui-btn-disabled'))
						{
							menu.popupWindow.show();
						}
					});
				}
			<?
		}
	?>

	BX.bind(BX("bx-disk-destroy-btn"), "click", function()	{
		BX.Disk['FolderListClass_<?= $component->getComponentId() ?>'].openConfirmEmptyTrash();
	});
});
</script>

<?
$APPLICATION->IncludeComponent('bitrix:disk.help.network.drive','');

global $USER;
if(
	Bitrix24Manager::isEnabled()
)
{
	?>
	<div id="bx-bitrix24-business-tools-info" style="display: none; width: 600px; margin: 9px;">
		<? $APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array()); ?>
	</div>
	<script type="text/javascript">
	BX.message({
		disk_restriction: <?= (!Bitrix24Manager::checkAccessEnabled('disk', $USER->getId())? 'true' : 'false') ?>
	});
	</script>
	<?
		$APPLICATION->IncludeComponent("bitrix:bitrix24.limit.lock", "");
	?>
<?
}

$APPLICATION->includeComponent("bitrix:spotlight", "", array(
	"ID" => "disk-view-mod2e-feature",
	"JS_OPTIONS" => array(
		"targetElement" => ".disk-folder-list-view",
		"content" => Loc::getMessage("DISK_FOLDER_LIST_SPOTLIGHT_VIEW_MODE"),
		"targetVertex" => "middle-center",
	)
));
?>