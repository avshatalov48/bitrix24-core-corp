<?php
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
$messages = Loc::loadLanguageFile(__FILE__);

CJSCore::Init([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.viewer',
	'ui.avatar',
	'disk.viewer.actions',
	'disk.viewer.document-item',
	'disk.viewer.board-item',
	'disk.document',
	'disk_information_popups',
	'sidepanel',
	'ui.buttons',
	'disk.model.sharing-item',
	'disk.model.external-link.input',
	'disk.model.external-link.description',
	'disk.model.external-link.settings',
]);

$sortBpLog = false;
if(isset($_GET['log_sort']))
{
	$sortBpLog = true;
}

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-all-paddings no-background');


$jsTemplates = new Main\IO\Directory($documentRoot.$templateFolder.'/js-templates');
/** @var Main\IO\File $jsTemplate */
foreach ($jsTemplates->getChildren() as $jsTemplate)
{
	include($jsTemplate->getPath());
}
?>

<div class="disk-detail">
	<div class="disk-detail-content">

		<div class="disk-detail-file">
			<div class="disk-detail-preview disk-detail-preview-with-border">
				<? if($arResult['FILE']['IS_IMAGE']) { ?>
					<div class="disk-detail-preview-image" data-role="disk-detail-preview-image">
						<a class="disk-detail-preview-link" href="<?= $arResult['FILE']['SHOW_FILE_URL'] ?>" target="_blank"><img src="<?= $arResult['FILE']['SHOW_PREVIEW_URL'] ?>" alt="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>" title="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>"></a>
					</div>
				<?  } elseif($arResult['FILE']['SHOW_PREVIEW_IMAGE_URL']) { ?>
					<div class="bx-shared-preview-document-image <?= $arResult['FILE']['ICON_CLASS'] ?>" style="position: relative">
						<img src="<?=$arResult['FILE']['SHOW_PREVIEW_IMAGE_URL'];?>" alt="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>" title="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>">
						<div class="bx-file-icon-label"></div>
					</div>
				<?  } elseif($arResult['FILE']['IS_VIDEO']) { ?>
					<?= $arResult['FILE']['VIEWER'] ?>
				<?  } else { ?>
					<div class="bx-file-icon-container-big <?= $arResult['FILE']['ICON_CLASS'] ?>">
						<div class="bx-file-icon-cover">
							<div class="bx-file-icon-corner"></div>
							<div class="bx-file-icon-corner-fix"></div>
							<div class="bx-file-icon-images"></div>
						</div>
						<div class="bx-file-icon-label"></div>
						<? if($arResult['FILE']['LOCK']['IS_LOCKED']){ ?>
							<div id="lock-anchor-created-<?= $component->getComponentId() ?>" class="disk-locked-document-block-icon-big js-disk-locked-document-tooltip"></div>
						<? } ?>
					</div>
				<?  } ?>
			</div>

			<script>
				BX.ready(
					function()
					{
						var filePreviewBlock = document.querySelector('[data-role="disk-detail-preview-image"]');
						var filePreviewBlockParent;

						if(BX.type.isDomNode(filePreviewBlock))
						{
							filePreviewBlockParent = filePreviewBlock.parentNode;
							filePreviewBlock.offsetWidth <= filePreviewBlockParent.offsetWidth - 25 ?
								filePreviewBlockParent.classList.add('disk-detail-preview-with-border') :
								filePreviewBlockParent.classList.remove('disk-detail-preview-with-border')
						}
					}
				)
			</script>
			<div class="disk-detail-file-info">
				<span class="disk-detail-file-name"><?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?></span>
				<table class="disk-detail-table">
					<tr>
						<td class="disk-detail-table-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_SIZE') ?>:</td>
						<td class="disk-detail-table-value"><?= CFile::formatSize($arResult['FILE']['SIZE']) ?></td>
					</tr>
					<tr>
						<td class="disk-detail-table-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_UPDATE_TIME') ?>:</td>
						<td class="disk-detail-table-value"><?= $arResult['FILE']['UPDATE_TIME'] ?></td>
					</tr>
					<? if(!empty($arResult['EXTERNAL_LINK']['ID'])): ?>
					<tr>
						<td class="disk-detail-table-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_DOWNLOAD_COUNT_BY_EXT_LINK') ?>:</td>
						<td class="disk-detail-table-value"><?= $arResult['EXTERNAL_LINK']['DOWNLOAD_COUNT'] ?></td>
					</tr>
					<? endif; ?>
					<? if($arResult['FILE']['IS_DELETED'])
					{
						?>
						<tr>
							<td class="disk-detail-table-param">
								<?= Loc::getMessage('DISK_FILE_VIEW_FILE_DELETE_TIME') ?>:
							</td>
							<td class="disk-detail-table-value">
								<?= $arResult['FILE']['DELETE_TIME'] ?>
							</td>
						</tr>
						<?
					}
					?>
				</table>

				<div class="disk-detail-sidebar-owner-title"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_OWNER') ?>:</div>
				<div class="disk-detail-sidebar-owner">
					<?= $arResult['FILE']['CREATE_USER']['AVA_HTML'] ?>
					<div class="disk-detail-sidebar-owner-name">
						<a class="disk-detail-sidebar-owner-link" target="_top" href="<?= $arResult['FILE']['CREATE_USER']['LINK'] ?>"><?= htmlspecialcharsbx($arResult['FILE']['CREATE_USER']['NAME']) ?></a>
						<div class="disk-detail-sidebar-owner-position"><?= htmlspecialcharsbx($arResult['FILE']['CREATE_USER']['WORK_POSITION']) ?></div>
					</div>
				</div>
			</div>
		</div>

		<? if(!empty($arResult['USE_IN_ENTITIES'])){?>
		<div class="disk-file-info">
			<div class="disk-file-info-title"><?= Loc::getMessage('DISK_FILE_VIEW_USAGE') ?></div>
			<? foreach($arResult['ENTITIES'] as $entity){?>
				<div class="disk-file-info-item">
					<div class="disk-file-info-item-title">
					<?
						if(!empty($entity['DETAIL_URL']))
						{
							echo "<a class=\"disk-file-info-item-link\" target='_blank' href=\"{$entity['DETAIL_URL']}\">" . htmlspecialcharsbx($entity['TITLE']) . "</a>";
						}
						elseif(!empty($entity['JS_OPEN_DETAIL']))
						{
							echo "<a class=\"disk-file-info-item-link\" href=\"javascript:{$entity['JS_OPEN_DETAIL']}\">" . htmlspecialcharsbx($entity['TITLE']) . "</a>";
						}
						else
						{
							echo htmlspecialcharsbx($entity['TITLE']);
						}
					?>
					</div>
					<div class="disk-file-info-item-title"><?= htmlspecialcharsbx($entity['DESCRIPTION']) ?></div>
					<div class="disk-file-info-users">
						<div class="disk-file-info-users-title"><?= Loc::getMessage('DISK_FILE_VIEW_ENTITY_MEMBERS') ?></div>
						<? foreach($entity['MEMBERS'] as $member){?>
						<div class="disk-file-info-user">
							<div class="disk-file-info-user-avatar" <?= (!empty($member['AVATAR_SRC'])? "style=\"background-image: url('" . Uri::urnEncode($member['AVATAR_SRC']) . "');\"" : '') ?>></div>
							<? if(empty($member['LINK'])) {?>
							<div class="disk-file-info-user-name"><?= htmlspecialcharsbx($member['NAME']) ?></div>
							<? } else { ?>
							<a class="disk-file-info-user-name" target="_top" href="<?= $member['LINK'] ?>"><?= htmlspecialcharsbx($member['NAME']) ?></a>
							<? } ?>
						</div>
						<? } ?>
					</div>
				</div>
			<? }?>
		</div>
		<? } else { ?>
			<div class="disk-file-info">
				<div class="disk-file-info-title"><?= Loc::getMessage('DISK_FILE_VIEW_USAGE') ?></div>
				<div class="disk-file-info-item">
					<span class="disk-file-info-item-not-used"><?= Loc::getMessage('DISK_FILE_VIEW_NOT_USED_1') ?></span>
				</div>
			</div>
		<? } ?>
	</div>
	<div class="disk-detail-sidebar">

		<div class="disk-detail-sidebar-section">
			<div class="disk-detail-sidebar-editor">
				<? if ($arResult['FILE']['IS_DELETED'] && $arResult['CAN_RESTORE'])
				{
					?>
					<span class="disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-restore">
						<?= Loc::getMessage('DISK_FILE_VIEW_FILE_RESTORE') ?>
					</span>
					<?
				}
				?>
				<span class="disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-show" id="bx-disk-filepage-filename" <?= $arResult['FILE']['VIEWER_ATTRIBUTES'] ?>><?= Loc::getMessage("DISK_FILE_VIEW_FILE_RUN_VIEWER") ?></span>
				<? if (!$arResult['FILE']['IS_DELETED'] && !empty($arResult['CAN_UPDATE']) && (!$arResult['FILE']['LOCK']['IS_LOCKED'] || $arResult['FILE']['LOCK']['IS_LOCKED_BY_SELF']) && $arResult['FILE']['IS_EDITABLE'])
				{
					?><a class="disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-edit" href="#edit" onclick="top.BX.UI.Viewer.Instance.runActionByNode(BX('bx-disk-filepage-filename'), 'edit', {
							modalWindow: BX.Disk.openBlankDocumentPopup()
						}); event.preventDefault(); return false;"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_EDIT') ?></a><?
				}?>
				<a class="disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-download" href="<?= $arResult['FILE']['DOWNLOAD_URL'] ?>"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_DOWNLOAD') ?></a>
				<? if (!$arResult['FILE']['IS_DELETED'] && !empty($arResult['CAN_UPDATE']) && (!$arResult['FILE']['LOCK']['IS_LOCKED'] || $arResult['FILE']['LOCK']['IS_LOCKED_BY_SELF']))
				{
					?>
					<a id="bx-disk-file-upload-btn" class="disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-update" href="javascript:void(0);">
						<?= Loc::getMessage('DISK_FILE_VIEW_FILE_UPLOAD_VERSION') ?>
					</a>
					<?
				}
				?>
				<? if(!empty($arResult['CAN_DELETE']))
				{
					?>
					<div class="disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-remove">
						<?= Loc::getMessage('DISK_FILE_VIEW_DELETE') ?>
					</div>
					<?
				}
				?>
				<div class="disk-detail-sidebar-link-more js-disk-file-more-actions"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_MORE_ACTIONS') ?></div>
			</div>
		</div>

		<?php if(!$arResult['FILE']['IS_DELETED'] && $arResult['EXTERNAL_LINK']['ENABLED']){ ?>
		<div class="disk-detail-sidebar-section">
			<div class="disk-detail-sidebar-public-link">
				<div class="disk-detail-sidebar-public-link-copy-link" id="disk-detail-sidebar-public-link-copy-link" for="bx-disk-sidebar-shared-inner-link-input" title="<?= Loc::getMessage('DISK_FILE_VIEW_INTERNAL_LINK_COPY_HINT') ?>"><?= Loc::getMessage('DISK_FILE_VIEW_INTERNAL_LINK') ?></div>
				<input class="bx-disk-sidebar-shared-inner-link-input" type="text" value="<?= $arResult['FILE']['SHOW_FILE_ABSOLUTE_URL'] ?>" id="bx-disk-sidebar-shared-inner-link-input">
			</div>
			<div data-entity="external-link-place"></div>
		</div>
		<?php } ?>

		<? if(!$arResult['FILE']['IS_DELETED'])
		{
			?>
			<div class="disk-detail-sidebar-section">
				<div class="disk-detail-sidebar-user-access-title disk-detail-sidebar-owner-title-double">
					<div class="disk-detail-sidebar-user-access-title-text"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_SHARING') ?>:</div>
					<?php if($arResult['CAN_SHARE'] && $arResult['CAN_CHANGE_RIGHTS']) { ?>
					<div class="disk-detail-sidebar-user-access-title-add" id="disk-file-add-sharing"><?= Loc::getMessage('DISK_FILE_ADD_SHARING') ?></div>
					<?php } ?>
				</div>
				<div class="disk-detail-sidebar-user-access-section"></div>
			</div>
			<?
		}
		?>
		<? if($arResult['SHOW_USER_FIELDS'])
		{
			?>
			<div class="disk-detail-sidebar-section">
				<div class="disk-detail-sidebar-user-access-title">
					<div class="disk-detail-sidebar-user-access-title-text"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_USER_FIELDS') ?>:</div>
					<?php if($arResult['CAN_UPDATE']) { ?>
					<div class="disk-detail-sidebar-user-access-title-add" id="disk-edit-uf"><?= Loc::getMessage('DISK_FILE_VIEW_LINK_EDIT_USER_FIELDS_SIDEBAR') ?></div>
					<?php } ?>
				</div>
				<?php include 'uf_sidebar.php'; ?>
			</div>
			<?
		}
		?>
	</div>
</div>

<? if($arParams['STATUS_BIZPROC']&&!empty($arResult['WORKFLOW_TEMPLATES'])) { ?>
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
							<?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_LABEL_START') ?>
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
								<input type="hidden" value="edit" name="autoExecute" />
							</td>
						</tr>
						<?CBPDocument::StartWorkflowParametersShow($workflowTemplate['ID'], $workflowTemplate['PARAMETERS'], 'formAutoloadBizProc', false);
					}else { ?>
						<tr>
							<td class="bx-disk-form-bizproc-start-td-name-bizproc" colspan="2">
								<input type="hidden" value="1" name="checkBp" />
								<input type="hidden" value="edit" name="autoExecute" />
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
<?
$APPLICATION->IncludeComponent(
	'bitrix:disk.file.upload',
	'',
	array(
		'STORAGE' => $arResult['STORAGE'],
		'FILE_ID' => $arResult['FILE']['ID'],
		'CID' => 'FolderList',
		'INPUT_CONTAINER' => 'BX("bx-disk-file-upload-btn")',
		'DROP_ZONE' => 'BX("bx-disk-file-upload-btn")'
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
<script>
BX(function () {
	BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FileViewClass({
		componentName: '<?= $this->getComponent()->getName() ?>',
		selectorId: 'disk-file-add-sharing',
		object: {
			id: <?= $arResult['FILE']['ID'] ?>,
			name: '<?= CUtil::JSEscape($arResult['FILE']['NAME']) ?>',
			isDeleted: <?= $arResult['FILE']['IS_DELETED']? 'true' : 'false' ?>,
			isHistoryBlocked: <?= $arResult['HISTORY_BLOCKED_BY_FEATURE']? 'true' : 'false' ?>,
			hasUf: <?= $arResult['SHOW_USER_FIELDS']? 'true' : 'false' ?>,
			hasBp: <?= $arParams['STATUS_BIZPROC']? 'true' : 'false' ?>
		},
		canDelete: <?= $arResult['CAN_DELETE']? 'true' : 'false' ?>,
		urls: {
			trashcanList: '<?= CUtil::JSUrlEscape($arResult['PATH_TO_TRASHCAN_LIST']) ?>',
			fileHistory: '<?= CUtil::JSUrlEscape($arResult['PATH_TO_FILE_HISTORY']) ?>',
			fileShowBp: '?action=showBp',
			fileShowUf: '?action=showUserField',
			fileEditUf: '?action=editUserField'
		},
		layout: {
			sidebarUfValuesSection: BX('disk-uf-sidebar-values'),
			editUf: BX('disk-edit-uf'),
			moreActionsButton: BX(document.querySelector('.js-disk-file-more-actions')),
			restoreButton: BX(document.querySelector('.disk-detail-sidebar-editor-item-restore')),
			deleteButton: BX(document.querySelector('.disk-detail-sidebar-editor-item-remove')),
			addSharingButton: BX('disk-file-add-sharing'),
			externalLink: BX(document.querySelector('[data-entity="external-link-place"]'))
		},
		externalLinkInfo: {
			<? if (isset($arResult['EXTERNAL_LINK']['ID'])) { ?>
				id: <?= $arResult['EXTERNAL_LINK']['ID']?>,
				objectId: <?= $arResult['EXTERNAL_LINK']['OBJECT_ID']?>,
				link: '<?= $arResult['EXTERNAL_LINK']['LINK']?>',
				hasPassword: <?= $arResult['EXTERNAL_LINK']['HAS_PASSWORD']? 'true' : 'false'?>,
				hasDeathTime: <?= $arResult['EXTERNAL_LINK']['HAS_DEATH_TIME']? 'true' : 'false'?>,
				deathTimeTimestamp: <?= $arResult['EXTERNAL_LINK']['DEATH_TIME_TIMESTAMP']?: 'null'?>,
				<? if ($arResult['EXTERNAL_LINK']['DEATH_TIME']) { ?>
				deathTime: '<?= $arResult['EXTERNAL_LINK']['DEATH_TIME'] ?>'
				<? } ?>
			<? } ?>
		},
		uf: {
			editButton: BX('bx-disk-link-edit-uf-values'),
			contentContainer: BX('bx-disk-file-uf-props-content')
		}
	});

	if('<?= $sortBpLog ?>')
	{
		BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'].fixUrlForSort();
	}
});

BX.message(<?=Main\Web\Json::encode($messages)?>);
</script>
<?
global $USER;
if(
	\Bitrix\Disk\Integration\Bitrix24Manager::isEnabled()
)
{
	?>
	<div id="bx-bitrix24-business-tools-info" style="display: none; width: 600px; margin: 9px;">
		<? $APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array()); ?>
	</div>

	<script>
	BX.message({
		disk_restriction: <?= (!\Bitrix\Disk\Integration\Bitrix24Manager::checkAccessEnabled('disk', $USER->getId())? 'true' : 'false') ?>
	});
	</script>

<?
}

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.selector",
	".default",
	[
		'API_VERSION' => 2,
		'ID' => 'disk-file-add-sharing',
		'BIND_ID' => 'disk-file-add-sharing',
		'ITEMS_SELECTED' => [],
		'CALLBACK' => [
			'select' => "BX.Disk['FileViewClass_{$component->getComponentId()}'].onSelectSelectorItem.bind(BX.Disk['FileViewClass_{$component->getComponentId()}'])",
			'unSelect' => "BX.Disk['FileViewClass_{$component->getComponentId()}'].onUnSelectSelectorItem.bind(BX.Disk['FileViewClass_{$component->getComponentId()}'])",
			'openDialog' => 'function(){}',
			'closeDialog' => 'function(){}',
		],
		'OPTIONS' => [
			'eventInit' => 'Disk.FileView:onShowSharingEntities',
			'eventOpen' => 'Disk.FileView:openSelector',
			'useContainer' => 'Y',
			'lazyLoad' => 'Y',
			'context' => 'DISK_SHARING',
			'contextCode' => 'U',
			'useSearch' => 'Y',
			'useClientDatabase' => 'Y',
			'allowEmailInvitation' => 'N',
			'enableAll' => 'N',
			'enableDepartments' => 'Y',
			'enableSonetgroups' => 'Y',
			'departmentSelectDisable' => 'N',
			'allowAddUser' => 'N',
			'allowAddCrmContact' => 'N',
			'allowAddSocNetGroup' => 'N',
			'allowSearchEmailUsers' => 'N',
			'allowSearchCrmEmailUsers' => 'N',
			'allowSearchNetworkUsers' => 'N',
			'useNewCallback' => 'Y',
		]
	],
	false,
	["HIDE_ICONS" => "Y"]
);
