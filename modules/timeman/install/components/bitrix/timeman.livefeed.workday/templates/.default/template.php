<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(array('timeman'));
?><div class="feed-workday-table"><?
	?><span class="feed-workday-left-side"><?
		?><span class="feed-workday-table-text"><?=GetMessage("TIMEMAN_ENTRY_FROM")?>:</span><?
		?><span class="feed-workday-avatar"
			<? if (strlen($arParams["USER"]["PHOTO"]) > 0): ?>
				style="background:url('<?=$arParams["USER"]["PHOTO"]?>') no-repeat center; background-size: cover;"
			<? endif ?>
			><?
		?></span><?
		?><span class="feed-user-name-wrap"><a href="<?=$arParams['USER']["URL"]?>" class="feed-workday-user-name" bx-tooltip-user-id="<?=$arParams['USER']["ID"]?>"><?=$arParams['USER']["NAME"]?></a><?
		if (!empty($arParams['USER']["WORK_POSITION"]))
		{
			?><span class="feed-workday-user-position"><?=$arParams['USER']["WORK_POSITION"]?></span><?
		}
		?></span><?
	?></span><?
	?><span class="feed-workday-right-side"><?
		?><span class="feed-workday-table-text"><?=GetMessage("TIMEMAN_ENTRY_TO")?>:</span><?
		?><span class="feed-workday-avatar"
			<? if (strlen($arParams["MANAGER"]["PHOTO"]) > 0): ?>
				style="background:url('<?=$arParams["MANAGER"]["PHOTO"]?>') no-repeat center; background-size: cover;"
			<? endif ?>
			><?
		?></span><?
		?><span class="feed-user-name-wrap"><a href="<?=$arParams['MANAGER']["URL"]?>" class="feed-workday-user-name" bx-tooltip-user-id="<?=$arParams['MANAGER']["ID"]?>"><?=$arParams['MANAGER']["NAME"]?></a><?
		if (!empty($arParams['MANAGER']["WORK_POSITION"]))
		{
			?><span class="feed-workday-user-position"><?=$arParams['MANAGER']["WORK_POSITION"]?></span><?
		}
		?></span><?
	?></span><?
?></div><?
?>