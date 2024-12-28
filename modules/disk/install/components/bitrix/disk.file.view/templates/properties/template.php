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
	'ui.avatar',
	'disk',
	'disk_information_popups',
	'sidepanel',
	'ui.buttons',
	'disk.model.sharing-item',
	'disk.model.external-link.input',
	'disk.model.external-link.description',
	'disk.model.external-link.settings',
]);

$uniqueIdForUiSelector = 'disk-file-add-sharing' . $arResult['FILE']['ID'] . time();
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
<div class="disk-detail-properties">
	<div class="disk-detail-properties-title"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_PROPERTIES') ?></div>
	<div class="disk-detail-properties-block">
		<div class="disk-detail-properties-file-info">
			<span class="disk-detail-properties-file-name"><?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?></span>
			<table class="disk-detail-properties-table">
				<tr>
					<td class="disk-detail-properties-table-value"><?= CFile::formatSize($arResult['FILE']['SIZE']) ?></td>
				</tr>
				<tr>
					<td class="disk-detail-properties-table-value"><?= $arResult['FILE']['UPDATE_TIME'] ?></td>
				</tr>
				<? if($arResult['FILE']['IS_DELETED'])
				{
					?>
					<tr>
						<td class="disk-detail-properties-table-param">
							<?= Loc::getMessage('DISK_FILE_VIEW_FILE_DELETE_TIME') ?>:
						</td>
						<td class="disk-detail-properties-table-value">
							<?= $arResult['FILE']['DELETE_TIME'] ?>
						</td>
					</tr>
					<?
				}
				?>
				<? if(!empty($arResult['EXTERNAL_LINK']['ID'])): ?>
				<tr>
					<td class="disk-detail-properties-table-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_DOWNLOAD_COUNT_BY_EXT_LINK') ?>:</td>
					<td class="disk-detail-properties-table-value"><?= $arResult['EXTERNAL_LINK']['DOWNLOAD_COUNT'] ?></td>
				</tr>
				<? endif; ?>
			</table>
			<div class="disk-detail-properties-owner">
				<?= $arResult['FILE']['CREATE_USER']['AVA_HTML'] ?>
				<div class="disk-detail-properties-owner-name">
					<a class="disk-detail-properties-owner-link" target="_top" href="<?= $arResult['FILE']['CREATE_USER']['LINK'] ?>"><?= htmlspecialcharsbx($arResult['FILE']['CREATE_USER']['NAME']) ?></a>
					<div class="disk-detail-properties-owner-position"><?= htmlspecialcharsbx($arResult['FILE']['CREATE_USER']['WORK_POSITION']) ?></div>
				</div>
			</div>
		</div>
	</div>

	<?php if(!$arResult['FILE']['IS_DELETED'] && $arResult['EXTERNAL_LINK']['ENABLED']){ ?>
	<div class="disk-detail-properties-block">
		<div class="disk-detail-properties-section-title">
			<div class="disk-detail-properties-section-title-text"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_FILE_LINKS') ?></div>
		</div>
		<div class="disk-detail-properties-public-link">
			<div class="disk-detail-properties-public-link-copy-link" id="disk-detail-sidebar-public-link-copy-link" for="bx-disk-sidebar-shared-inner-link-input" title="<?= Loc::getMessage('DISK_FILE_VIEW_INTERNAL_LINK_COPY_HINT') ?>"><?= Loc::getMessage('DISK_FILE_VIEW_INTERNAL_LINK') ?></div>
			<input class="bx-disk-sidebar-shared-inner-link-input" type="text" value="<?= $arResult['FILE']['SHOW_FILE_ABSOLUTE_URL'] ?>" id="bx-disk-sidebar-shared-inner-link-input">
		</div>
		<div data-entity="external-link-place"></div>
	</div>
	<?php } ?>

	<? if(!$arResult['FILE']['IS_DELETED'])
	{
		?>
	<div class="disk-detail-properties-block">
		<div class="disk-detail-properties-section-title disk-detail-properties-section-title-double">
			<div class="disk-detail-properties-section-title-text"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_SHARING') ?></div>
			<?php if($arResult['CAN_SHARE'] && $arResult['CAN_CHANGE_RIGHTS']) { ?>
				<div class="disk-detail-properties-section-title-action" id="disk-file-add-sharing"><?= Loc::getMessage('DISK_FILE_ADD_SHARING') ?></div>
			<?php } ?>
		</div>
		<div class="disk-detail-properties-user-access-section disk-detail-sidebar-user-access-section"></div>
	</div>
		<?
	}
	?>

	<? if($arResult['SHOW_USER_FIELDS'])
	{
		?>
	<div class="disk-detail-properties-block">
		<div class="disk-detail-properties-section-title">
			<div class="disk-detail-properties-section-title-text"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_USER_FIELDS') ?>:</div>
			<?php if($arResult['CAN_UPDATE']) { ?>
				<div class="disk-detail-properties-section-title-action" id="disk-edit-uf"><?= Loc::getMessage('DISK_FILE_VIEW_LINK_EDIT_USER_FIELDS_SIDEBAR') ?></div>
			<?php } ?>
		</div>
		<?php include 'uf_sidebar.php'; ?>
	</div>
		<?
	}
	?>

	<? if(!empty($arResult['USE_IN_ENTITIES'])){?>
	<div class="disk-detail-properties-block">
		<div class="disk-detail-properties-section-title">
			<div class="disk-detail-properties-section-title-text"><?= Loc::getMessage('DISK_FILE_VIEW_USAGE') ?></div>
		</div>
		<? foreach($arResult['ENTITIES'] as $entity){?>
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
		<? }?>
	</div>
	<? } ?>
</div>
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
	var location = "<?= $arResult['PATH_TO_FILE_VIEW'] ?>";
	BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FileViewClass({
		componentName: '<?= $this->getComponent()->getName() ?>',
		selectorId: '<?= $uniqueIdForUiSelector ?>',
		object: {
			id: <?= $arResult['FILE']['ID'] ?>,
			name: '<?= CUtil::JSEscape($arResult['FILE']['NAME']) ?>',
			isDeleted: <?= $arResult['FILE']['IS_DELETED']? 'true' : 'false' ?>,
			hasUf: <?= $arResult['SHOW_USER_FIELDS']? 'true' : 'false' ?>,
			hasBp: <?= $arParams['STATUS_BIZPROC']? 'true' : 'false' ?>
		},
		canDelete: <?= $arResult['CAN_DELETE']? 'true' : 'false' ?>,
		urls: {
			trashcanList: '<?= CUtil::JSUrlEscape($arResult['PATH_TO_TRASHCAN_LIST']) ?>',
			fileHistory: '<?= CUtil::JSUrlEscape($arResult['PATH_TO_FILE_HISTORY']) ?>',
			fileShowBp: BX.util.add_url_param(location, {action: 'showBp'}),
			fileShowUf: BX.util.add_url_param(location, {action: 'showUserField'}),
			fileEditUf: BX.util.add_url_param(location, {action: 'editUserField'})
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
			<? if ($arResult['EXTERNAL_LINK']['ID']) { ?>
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
		'ID' => $uniqueIdForUiSelector,
		'BIND_ID' => 'disk-file-add-sharing',
		'ITEMS_SELECTED' => [],
		'CALLBACK' => [
			'select' => "BX.Disk['FileViewClass_{$component->getComponentId()}'].onSelectSelectorItem.bind(BX.Disk['FileViewClass_{$component->getComponentId()}'])",
			'unSelect' => "BX.Disk['FileViewClass_{$component->getComponentId()}'].onUnSelectSelectorItem.bind(BX.Disk['FileViewClass_{$component->getComponentId()}'])",
			'openDialog' => "BX.Disk['FileViewClass_{$component->getComponentId()}'].onOpenSelector.bind(BX.Disk['FileViewClass_{$component->getComponentId()}'])",
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
