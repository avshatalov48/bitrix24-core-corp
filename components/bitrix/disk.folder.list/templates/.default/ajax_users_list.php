<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var $APPLICATION CAllMain */
/** @var $arResult array */
/** @var $arParams array */
?>
<?php foreach($arResult['USERS'] as $user) { ?>
<span class="bx-webdav-invite-users">
	<span class="bx-webdav-invite-us-avatar"><?
		if(!empty($user['PHOTO_SRC']))
		{
			?><img height="21" width="21" src="<?= $user['PHOTO_SRC'] ?>" alt="<?= htmlspecialcharsbx($user['FORMATTED_NAME']); ?>"/><?
		}
	?></span>
	<a class="bx-webdav-invite-us-name" href="<?= $user['HREF']; ?>" target="_blank" title="<?= htmlspecialcharsbx($user['FORMATTED_NAME']); ?>" ><?= htmlspecialcharsbx($user['FORMATTED_NAME']); ?></a><? if($arResult['CURRENT_USER_CAN_DETACH']){ ?><span onclick="diskDetachUser(this, <?= $arResult['OBJECT']['ID']; ?>, <?= $user['ID']; ?>, '<?= $arResult['URL_TO_DETACH_USER'] ?>')" class="bx-webdav-invite-us-set"></span><? } ?>
</span><?php } ?>
<?
if($arResult['PAGE'] != $arResult['TOTAL_PAGE']) {
$portion = $arResult['TOTAL_COUNT'] - $arResult['PAGE']*$arResult['ON_PAGE'];
?>
<div class="bx-webdav-invite-us-place-for-users"></div>
<div class="bx-webdav-invite-us-more">
	<? if($portion > 0) {?>
	<span class="bx-webdav-invite-us-more-link" onclick="diskLoadPortionUsersList(this, <?= $arResult['OBJECT']['ID']; ?>, '<?= $arResult['USER_LIST_TYPE']; ?>', <?= ++$arResult['PAGE']; ?>, '<?= $arResult['URL_TO_SHOW_USER_LIST'] ?>');"><?=
					\Bitrix\Disk\Ui\Text::getNumericCase(
						$portion,
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_1', array('#COUNT#' => $portion)),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_1_21', array('#COUNT#' => $portion)),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_2_4', array('#COUNT#' => $portion)),
						GetMessage('DISK_FOLDER_LIST_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_5_20', array('#COUNT#' => $portion))
					);
				?></span>
	<? }?>
</div>
<? } ?>