<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?php
/** @var $APPLICATION CAllMain */
/** @var $arResult array */
/** @var $arParams array */
?>
<?php foreach($arResult['USERS'] as $user) { ?>
<span class="bx-webdav-invite-users">
	<span class="bx-webdav-invite-us-avatar"><?
		if(!empty($user['INVITE_USER']['PHOTO_SRC']))
		{
			?><img height="21" width="21" src="<?= $user['INVITE_USER']['PHOTO_SRC'] ?>" alt="<?= htmlspecialcharsbx($user['INVITE_USER']['FORMATTED_NAME']); ?>"/><?
		}
	?></span>
	<a class="bx-webdav-invite-us-name" href="<?= $user['INVITE_USER']['HREF']; ?>" target="_blank" title="<?= htmlspecialcharsbx($user['INVITE_USER']['FORMATTED_NAME']); ?>" ><?= htmlspecialcharsbx($user['INVITE_USER']['FORMATTED_NAME']); ?></a><? if($arParams['currentUserCanUnshare']){ ?><span onclick="wdUnshareUser(this, <?= $user['INVITE_USER']['ID']; ?>)" class="bx-webdav-invite-us-set"></span><? } ?>
</span><?php } ?>
<?
if($arResult['PAGE'] != $arResult['TOTAL_PAGE']) {
$portion = $arResult['TOTAL_COUNT'] - $arResult['PAGE']*$arResult['ON_PAGE'];
?>
<div class="bx-webdav-invite-us-place-for-users"></div>
<div class="bx-webdav-invite-us-more">
	<? if($portion > 0) {?>
	<span class="bx-webdav-invite-us-more-link" onclick="wdLoadPortionUsersList(this, '<?= $arResult['USER_LIST_TYPE']; ?>', <?= ++$arResult['PAGE']; ?>);"><?=
					CWebDavTools::getNumericCase(
						$portion,
						GetMessage('WD_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_1', array('#COUNT#' => $portion)),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_1_21', array('#COUNT#' => $portion)),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_2_4', array('#COUNT#' => $portion)),
						GetMessage('WD_INVITE_MODAL_TAB_USERS_LOAD_MORE_COUNT_5_20', array('#COUNT#' => $portion))
					);
				?></span>
	<? }?>
</div>
<? } ?>