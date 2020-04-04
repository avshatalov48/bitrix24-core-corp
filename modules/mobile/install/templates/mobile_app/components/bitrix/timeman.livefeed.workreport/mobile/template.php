<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$userAvatarId = "workreport-user-".randString(5);
$managerAvatarId = "workreport-manager-".randString(5);

?><div class="lenta-info-block info-block-<?=($arParams["MARK"] == "G" ? "green" : ($arParams["MARK"] == "B" ? "red" : "blue"))?>">
	<div class="lenta-info-block-l">
		<div class="lenta-info-block-l-text"><?=GetMessage("REPORT_FROM")?>:</div>
		<div class="lenta-info-block-l-text"><?=GetMessage("REPORT_TO")?>:</div>
	</div>
	<div class="lenta-info-block-r">
		<div class="lenta-info-block-data">
			<div class="lenta-info-avatar avatar" id="<?=$userAvatarId?>"<?
				if(strlen($arParams["USER"]["PHOTO"]) > 0)
				{
					?> style="background-image:url('<?=$arParams["USER"]["PHOTO"]?>')"<?
				}
			?>></div>
			<div class="lenta-info-name">
				<a href="<?=$arParams["USER"]["URL"]?>" class="lenta-info-name-text"><?=$arParams["~USER"]["NAME"]?></a>
				<div class="lenta-info-name-description"><?=$arParams["~USER"]["WORK_POSITION"]?></div>
			</div>
		</div>
		<div class="lenta-info-block-data">
			<div class="lenta-info-avatar avatar" id="<?=$managerAvatarId?>"<?
				if(strlen($arParams["MANAGER"]["PHOTO"]) > 0)
				{
					?> style="background-image:url('<?=$arParams["MANAGER"]["PHOTO"]?>')"<?
				}
			?>></div>
			<div class="lenta-info-name">
				<a href="<?=$arParams["MANAGER"]["URL"]?>" class="lenta-info-name-text"><?=$arParams["~MANAGER"]["NAME"]?></a>
				<div class="lenta-info-name-description"><?=$arParams["~MANAGER"]["WORK_POSITION"]?></div>
			</div>
		</div>
	</div>
</div>