<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$action = (
	!empty($arParams["REPLY_ACTION"])
		? $arParams["REPLY_ACTION"]
		: ''
);

?><div class="post-add-comment-box" onclick="<?=$action?>">
	<div class="ui-icon ui-icon-common-user post-add-comment-user-icon">
		<i></i>
	</div>
	<div class="post-add-comment-main">
		<div class="post-add-comment-btn"><?=Loc::getMessage('MOBILE_PSEUDOFORM_COMMENT_ADD')?></div>
		<div class="post-add-comment-icon-box">
			<div class="post-add-comment-icon post-add-comment-icon-file"></div>
			<div class="post-add-comment-icon post-add-comment-icon-emoji"></div>
		</div>
	</div>
</div>