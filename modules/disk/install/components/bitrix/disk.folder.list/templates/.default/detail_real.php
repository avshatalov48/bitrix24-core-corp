<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var $APPLICATION CAllMain */
/** @var $arResult array */
CJSCore::Init(array('socnetlogdest'));

function wdPrintAccessTab(array $arResult, $printButtons = true)
{
	return '
	<div class="bx-webdav-invite-access">
		<form onsubmit="BX.PreventDefault();" action="' . $arResult['URL_TO_SHARE_FOLDER'] .'" name="webdav-invite-share" id="webdav-invite-share" method="POST">
		<div class="bx-webdav-invite-access-title">' . GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TITLE_SHARING'). ' "' . $arResult['OBJECT']['NAME']  .'"</div>
		<div class="bx-webdav-invite-cont">
			<div class="bx-webdav-invite-whom">
				<div class="bx-webdav-invite-whom-l">' . GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_DESTINATION'). '</div>
				<div id="feed-add-post-destination-container-post" class="bx-webdav-invite-whom-r">
					<span id="feed-add-post-destination-item"></span>
					<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">
						<input autocomplete="off" type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input"/>
					</span>
					<a href="#" class="feed-add-destination-link" id="bx-destination-tag">' . GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_DESTINATION_1"). '</a>
				</div>
			</div>
			<textarea name="invite_description" id="inviteDescription" class="bx-webdav-invite-textar" placeholder="' .  GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_PLACEHOLDER') . '"></textarea>
			<div class="bx-webdav-invite-checkbox-wrap">
				<input name="can_edit" type="checkbox" class="bx-webdav-invite-checkbox" id="canEdit" checked="checked"/><label for="canEdit" class="bx-webdav-invite-label">' .  GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_CAN_EDIT'). '</label>
			</div>
		</div>
		' . ($printButtons? wdPrintAccessTabButtons($arResult) : '') . '
	</div>
';
}

function wdPrintAccessTabButtons(array $arResult)
{
	return '
	<div class="bx-webdav-invite-footer">
		<a style="display: none;" id="bx-webdav-invite-link-access" href="javascript:void(0)" onclick="wdShareToUser(' . $arResult['OBJECT']['ID'] . ');" class="webform-button webform-button-create"><span class="webform-button-left"></span><span class="webform-button-text">' .  GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_BTN_OPEN_ACCESS'). '</span><span class="webform-button-right"></span></a><a href="javascript:void(0)" onclick="wdCloseCurrentPopup();" class="webform-button"><span class="webform-button-left"></span><span class="webform-button-text">' .  GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_BTN_CANCEL'). '</span><span class="webform-button-right"></span></a>
	</div>
	';
}
?>
<script type="text/javascript">
	var BXSocNetLogDestinationFormName = '<?=randString(6)?>';
	BX.loadCSS('/bitrix/components/bitrix/webdav.invite/templates/.default/style.css');
</script>
<? if(empty($arResult['CONNECTED_USERS_CAN_EDITED_COUNT']) && empty($arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT']) && empty($arResult['DISCONNECTED_USERS_COUNT'])) {?>
	<?= wdPrintAccessTab($arResult) ?>
<? } else {?>
<div class="bx-webdav-invite-tabs-wrap" id="bx-webdav-invite-tabs">
	<div class="bx-webdav-invite-tabs">
		<span onclick="wdChangeTab(this, 'bx-webdav-invite-show-access-list')" class="bx-webdav-invite-tab bx-webdav-invite-tab-active"><?= GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_HEADER_ACCESS') ?></span><span id="wd-tab-access-add" onclick="wdChangeTab(this, 'bx-webdav-invite-show-access-add')" class="bx-webdav-invite-tab"><?= GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_HEADER_MANAGE_ACCESS') ?></span>
	</div>
	<div id="bx-webdav-invite-show-access-list" class="bx-webdav-invite-tabs-cont">
		<span class="bx-webdav-invite-users bx-webdav-invite-owner">
			<span class="bx-webdav-invite-us-avatar"><?
				if(!empty($arResult['OWNER']['PHOTO_SRC']))
				{
					?><img height="21" width="21" src="<?= $arResult['OWNER']['PHOTO_SRC'] ?>" alt="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"/><?
				}
			?></span>
			<a class="bx-webdav-invite-us-name" href="<?= $arResult['OWNER']['HREF']; ?>" target="_blank" title="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"><?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?></a><span class="bx-webdav-invite-us-descript"><?= GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_OWNER_USER') ?></span>
		</span>
		<? if($arResult['CONNECTED_USERS_CAN_EDITED_COUNT'] > 0){ ?>
		<div class="bx-webdav-invite-users-list" style="height: auto;">
			<div onclick="diskOpenUsersList(<?= $arResult['OBJECT']['ID'] ?>, this, 'can_edit', '<?= $arResult['URL_TO_SHOW_USER_LIST'] ?>');" class="bx-webdav-invite-users-title">
				<span class="bx-webdav-invite-users-title-text"><?=
					\Bitrix\Disk\Ui\Text::getNumericCase(
						$arResult['CONNECTED_USERS_CAN_EDITED_COUNT'],
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_1', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_21', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_2_4', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CAN_EDIT_COUNT_5_20', array('#COUNT#' => $arResult['CONNECTED_USERS_CAN_EDITED_COUNT']))
					);
				?></span>
				<span class="bx-webdav-invite-users-arrow"></span>
			</div>
			<div class="bx-webdav-invite-users-block"></div>
		</div>
		<? } ?>
		<? if($arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'] > 0){ ?>
		<div class="bx-webdav-invite-users-list" style="height: auto;">
			<div onclick="diskOpenUsersList(<?= $arResult['OBJECT']['ID'] ?>, this, 'cannot_edit', '<?= $arResult['URL_TO_SHOW_USER_LIST'] ?>');" class="bx-webdav-invite-users-title">
				<span class="bx-webdav-invite-users-title-text"><?=
					\Bitrix\Disk\Ui\Text::getNumericCase(
						$arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'],
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_1', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_21', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_2_4', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_USAGE_CANNOT_EDIT_COUNT_5_20', array('#COUNT#' => $arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT']))
					);
				?></span>
				<span class="bx-webdav-invite-users-arrow"></span>
			</div>
			<div class="bx-webdav-invite-users-block"></div>
		</div>
		<? } ?>
		<? if($arResult['DISCONNECTED_USERS_COUNT'] > 0){ ?>
		<div class="bx-webdav-invite-users-list" style="height: auto;">
			<div onclick="diskOpenUsersList(<?= $arResult['OBJECT']['ID'] ?>, this, 'disconnect', '<?= $arResult['URL_TO_SHOW_USER_LIST'] ?>');" class="bx-webdav-invite-users-title">
				<span class="bx-webdav-invite-users-title-text"><?=
					\Bitrix\Disk\Ui\Text::getNumericCase(
						$arResult['DISCONNECTED_USERS_COUNT'],
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_1', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_21', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_2_4', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT'])),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_NOT_USAGE_COUNT_5_20', array('#COUNT#' => $arResult['DISCONNECTED_USERS_COUNT']))
					);
				?></span>
				<span class="bx-webdav-invite-users-arrow"></span>
			</div>
			<div class="bx-webdav-invite-users-block"></div>
		</div>
		<? } ?>
		<div class="bx-webdav-invite-footer-link-block">
			<a onclick="diskUnshareAllUsers(<?= $arResult['OBJECT']['ID'] ?>, '<?= $arResult['URL_TO_UNSHARE_ALL_USERS'] ?>');" class="bx-webdav-invite-footer-link" href="javascript:void(0)"><?= GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_BTN_DIE_ACCESS'); ?></a>
		</div>
	</div>
	<div id="bx-webdav-invite-show-access-add" class="bx-webdav-invite-tabs-cont" style="display: none;">
		<?= wdPrintAccessTab($arResult, false) ?>
	</div>
	<?= wdPrintAccessTabButtons($arResult) ?>
</div>
<? } ?>

<script type="text/javascript">
BX.ready(function(){
	<? if(empty($arResult['CONNECTED_USERS_CAN_EDITED_COUNT']) && empty($arResult['CONNECTED_USERS_CANNOT_EDITED_COUNT']) && empty($arResult['DISCONNECTED_USERS_COUNT'])){ ?>
	wdChangeTab(null, 'bx-webdav-invite-show-access-add');
	<? } ?>
	if(window.location.href.match(/[#]share/) && BX('wd-tab-access-add'))
	{
		BX.fireEvent(BX('wd-tab-access-add'), 'click');
	}

});
</script>

<script>
	BX.message({
		'BX_FPD_LINK_1':'<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_DESTINATION_1")?>',
		'BX_FPD_LINK_2':'<?=GetMessageJS("DISK_FOLDER_LIST_INVITE_MODAL_DESTINATION_2")?>'
	});

	var socBPDest = {
			department : <?=(empty($arResult["FEED_DESTINATION"]['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['DEPARTMENT']))?>,
			departmentRelation : {},
			relation : {}
		};

	<?if(empty($arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION']))
	{
		?>
		for(var iid in socBPDest.department)
		{
			var p = socBPDest.department[iid]['parent'];
			if (!socBPDest.relation[p])
				socBPDest.relation[p] = [];
			socBPDest.relation[p][socBPDest.relation[p].length] = iid;
		}
		function makeDepartmentTree(id, relation)
		{
			var arRelations = {};
			if (relation[id])
			{
				for (var x in relation[id])
				{
					var relId = relation[id][x];
					var arItems = [];
					if (relation[relId] && relation[relId].length > 0)
						arItems = makeDepartmentTree(relId, relation);

					arRelations[relId] = {
						id: relId,
						type: 'category',
						items: arItems
					};
				}
			}

			return arRelations;
		}
		socBPDest.departmentRelation = makeDepartmentTree('DR0', socBPDest.relation);
		<?
	}
	else
	{
		?>socBPDest.departmentRelation = <?=CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['DEPARTMENT_RELATION'])?>;<?
	}
	?>
</script>

	<script type="text/javascript">
		var BXSocNetLogDestinationFormName = '<?=randString(6)?>';
		BXSocNetLogDestinationDisableBackspace = null;
		BX.SocNetLogDestination.init({
			'name' : BXSocNetLogDestinationFormName,
			'searchInput' : BX('feed-add-post-destination-input'),
			'extranetUser' :  false,
			'departmentSelectDisable' :  true,
			'bindMainPopup' : { 'node' : BX('feed-add-post-destination-container-post'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
			'bindSearchPopup' : { 'node' : BX('feed-add-post-destination-container-post'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
			'callback' : {
				'select' : BXWebdavPostSelectCallback,
				'unSelect' : BXWebdavPostUnSelectCallback,
				'openDialog' : BXWebdavPostOpenDialogCallback,
				'closeDialog' : BXWebdavPostCloseDialogCallback,
				'openSearch' : BXWebdavPostOpenDialogCallback,
				'closeSearch' : BXWebdavPostCloseSearchCallback
			},
			'items' : {
				'users' : <?=(empty($arResult["FEED_DESTINATION"]['USERS'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['USERS']))?>,
				'groups' : {},
				'sonetgroups' : <?=(empty($arResult["FEED_DESTINATION"]['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['SONETGROUPS']))?>,
				'department' : socBPDest.department,
				'departmentRelation' : socBPDest.departmentRelation
			},
			'itemsLast' : {
				'users' : <?=(empty($arResult["FEED_DESTINATION"]['LAST']['USERS'])? '{}': CUtil::PhpToJSObject($arResult["FEED_DESTINATION"]['LAST']['USERS']))?>,
				'sonetgroups' : {},
				'department' : {},
				'groups' : {}
			},
			'itemsSelected' : {}
		});
		BX.bind(BX('feed-add-post-destination-input'), 'keyup', BXWebdavPostSearch);
		BX.bind(BX('feed-add-post-destination-input'), 'keydown', BXWebdavPostSearchBefore);
		BX.bind(BX('feed-add-post-destination-container-post'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);BX.PreventDefault(e); });
	</script>