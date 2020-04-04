<?php
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
if(!empty($arResult['OWNER']['IS_GROUP']))
{
	$ownerTitle = GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_OWNER_GROUP');
}
elseif(!empty($arResult['OWNER']['IS_COMMON']))
{
	$ownerTitle = GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_OWNER_USER_COMMON_SECTION');
}
else
{
	$ownerTitle = GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_OWNER_USER');
}
?>

<div class="bx-webdav-invite-access bx-webdav-invite-access-wide">
	<div class="bx-webdav-invite-access-title"><?= GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TITLE_USAGE_SHARING'); ?> "<?= $arResult['OBJECT']['NAME'] ?>"</div>
	<div id="bx-webdav-invite-show-access-list" class="bx-webdav-invite-tabs-cont">
		<span class="bx-webdav-invite-users bx-webdav-invite-owner">
			<span class="bx-webdav-invite-us-avatar"><?
				if(!empty($arResult['OWNER']['PHOTO_SRC']))
				{
					?><img height="21" width="21" src="<?= $arResult['OWNER']['PHOTO_SRC'] ?>" alt="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>"/><?
				}
			?></span>
			<a class="bx-webdav-invite-us-name" href="<?= $arResult['OWNER']['HREF']; ?>" target="_blank" title="<?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?>" ><?= htmlspecialcharsbx($arResult['OWNER']['FORMATTED_NAME']); ?></a><span class="bx-webdav-invite-us-descript"><?= $ownerTitle ?></span>
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
	</div>
	<div class="bx-webdav-invite-footer">
		<a href="javascript:void(0)" onclick="diskOpenConfirmDetach(<?= $arResult['OBJECT']['ID'] ?>, '<?= $arResult['URL_TO_DETACH_OBJECT'] ?>');" class="webform-button"><span class="webform-button-left"></span><span class="webform-button-text"><?= GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_BTN_DIE_SELF_ACCESS'); ?></span><span class="webform-button-right"></span></a>
	</div>
</div>
