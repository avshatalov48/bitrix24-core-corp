<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load(["ui.tooltip", "ui.fonts.opensans"]);

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['TASK']))
	echo GetMessage('CRM_TASK_EMPTY');
else
{
	foreach($arResult['TASK'] as $arTask)
	{
		?>
		<div class="crm-task-element">
			<?if($arResult['ACTIVITY_ENTITY_LINK'] == 'Y'):?>
			<div class="crm-task-element-title"><span><?=GetMessage('CRM_ENTITY_'.$arTask['ENTITY_TYPE'])?></span> <a href="<?=$arTask['ENTITY_LINK']?>" bx-tooltip-user-id="<?=$arTask['ENTITY_TYPE']?>_<?=$arTask['ENTITY_ID']?>" bx-tooltip-loader="<?=htmlspecialcharsbx('/bitrix/components/bitrix/crm.'.mb_strtolower($arTask['ENTITY_TYPE']).'.show/card.ajax.php')?>" bx-tooltip-classname="crm_balloon<?=($arTask['ENTITY_TYPE'] == 'LEAD' || $arTask['ENTITY_TYPE'] == 'DEAL' || $arTask['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.mb_strtolower($arTask['ENTITY_TYPE']))?>"><?=$arTask['ENTITY_TITLE']?></a></div>
			<?endif;?>
			<div class="crm-task-element-type"><a href="<?=$arTask['PATH_TO_TASK_SHOW']?>"><?=$arTask['TITLE']?></a></div>
			<div class="crm-task-element-name">
				<div class="crm-task-element-name-date"><?=FormatDate('x', MakeTimeStamp($arTask['CREATED_DATE']))?></div>
			</div>
		</div>
		<?
	}
}
?>