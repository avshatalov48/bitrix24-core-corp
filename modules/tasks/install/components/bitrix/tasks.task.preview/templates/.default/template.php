<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arResult
 */

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");
?>

<div class="task-preview">
	<div class="task-preview-header">
		<div class="task-preview-header-icon">
			<img src="<?=(isset($arResult['TASK']["CREATED_BY_PHOTO"]) && $arResult['TASK']["CREATED_BY_PHOTO"] <> '' ? $arResult['TASK']["CREATED_BY_PHOTO"] : "/bitrix/images/1.gif")?>" width="<?=$arParams["AVATAR_SIZE"]?>" height="<?=$arParams["AVATAR_SIZE"]?>">
		</div>
		<span class="task-preview-header-title">
			<a id="a_<?=htmlspecialcharsbx($arResult['TASK']['CREATED_BY_UNIQID'])?>" href="<?=htmlspecialcharsbx($arResult["TASK"]["CREATED_BY_PROFILE"])?>" target="_blank" bx-tooltip-user-id="<?=htmlspecialcharsbx($arResult["TASK"]["CREATED_BY"])?>">
				<?=htmlspecialcharsbx($arResult['TASK']['CREATED_BY_FORMATTED'])?>
			</a>
		</span>

		<?if((int)$arResult["TASK"]["RESPONSIBLE_ID"] > 0):?>
			<span class="urlpreview__icon-destination"></span>
			<div class="task-preview-header-icon">
				<img src="<?=(isset($arResult['TASK']["RESPONSIBLE_PHOTO"]) && $arResult['TASK']["RESPONSIBLE_PHOTO"] <> '' ? $arResult['TASK']["RESPONSIBLE_PHOTO"] : "/bitrix/images/1.gif")?>" width="<?=$arParams["AVATAR_SIZE"]?>" height="<?=$arParams["AVATAR_SIZE"]?>">
			</div>
			<span class="task-preview-header-title">
				<a id="a_<?=htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_UNIQID'])?>" href="<?=htmlspecialcharsbx($arResult["TASK"]["RESPONSIBLE_PROFILE"])?>" target="_blank" bx-tooltip-user-id="<?=htmlspecialcharsbx($arResult["TASK"]["RESPONSIBLE_ID"])?>">
					<?=htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_FORMATTED'])?>
				</a>
			</span>
		<?endif?>
		<span class="urlpreview__time-wrap">
			<a href="<?=htmlspecialcharsbx($arParams['URL'])?>">
				<span class="urlpreview__time">
					<?=htmlspecialcharsbx($arResult["TASK"]["CREATED_DATE_FORMATTED"])?>
				</span>
			</a>
		</span>
	</div>
	<div class="task-preview-info">
		<?=GetMessage("TASKS_TASK_TITLE_LABEL")?>:
		<a href="<?=$arParams['URL']?>" target="_blank"><?=htmlspecialcharsbx($arResult["TASK"]["TITLE"])?></a><br>

		<?=GetMessage('TASKS_STATUS')?>:
		<?= GetMessage("TASKS_STATUS_".$arResult["TASK"]["REAL_STATUS"])?><br>

		<?if($arResult["TASK"]["DEADLINE"] <> ''):?>
			<?=GetMessage("TASKS_DEADLINE")?>:
			<?=FormatDateFromDB($arResult["TASK"]["DEADLINE"], "SHORT")?><br>
		<?endif?>

		<?if($arResult["TASK"]["CLOSED_DATE"] <> ''):?>
			<?=GetMessage("TASKS_CLOSED_DATE")?>:
			<?=FormatDateFromDB($arResult["TASK"]["CLOSED_DATE"], "SHORT")?><br>
		<?endif?>
	</div>
</div>