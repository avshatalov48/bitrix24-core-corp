<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

if (empty($arResult['EVENT']))
	echo GetMessage('CRM_EVENT_EMPTY');
else
{
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'AJAX_ONLY' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	foreach($arResult['EVENT'] as $arEvent)
	{
		?>
		<div class="crm-event-element">
			<?if($arResult['EVENT_ENTITY_LINK'] == 'Y'):?>
			<div class="crm-event-element-title"><span><?=GetMessage('CRM_EVENT_ENTITY_'.$arEvent['ENTITY_TYPE'])?></span> <a href="<?=$arEvent['ENTITY_LINK']?>" bx-tooltip-user-id="<?=$arEvent['ENTITY_TYPE']?>_<?=$arEvent['ENTITY_ID']?>" bx-tooltip-loader="<?=htmlspecialcharsbx('/bitrix/components/bitrix/crm.'.mb_strtolower($arEvent['ENTITY_TYPE']).'.show/card.ajax.php')?>" bx-tooltip-classname="crm_balloon<?=($arEvent['ENTITY_TYPE'] == 'LEAD' || $arEvent['ENTITY_TYPE'] == 'DEAL' || $arEvent['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.mb_strtolower($arEvent['ENTITY_TYPE']))?>"><?=$arEvent['ENTITY_TITLE']?></a></div>
			<?endif;?>
			<div class="crm-event-element-type"><?=$arEvent['EVENT_NAME']?></div>
			<div class="crm-event-element-name">
				<div class="crm-event-element-name-date"><?=FormatDate('x', MakeTimeStamp($arEvent['DATE_CREATE']), (time() + CTimeZone::GetOffset()))?></div>
				<div class="crm-event-element-name-author"><a href="<?=$arEvent['CREATED_BY_LINK']?>" id="balloon_<?=$arResult['GRID_ID']?>_<?=$arEvent['ID']?>" bx-tooltip-user-id="<?=$arEvent['CREATED_BY_ID']?>"><?=$arEvent['CREATED_BY_FULL_NAME']?></a></div>
			</div>
		</div>
		<?
	}
}
?>



