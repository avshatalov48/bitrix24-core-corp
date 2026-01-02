<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\ItemMiniCard\Builder\MiniCardHtmlBuilder;
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
			<?php if($arResult['EVENT_ENTITY_LINK'] == 'Y'):?>
			<div class="crm-event-element-title">
				<span>
					<?=GetMessage('CRM_EVENT_ENTITY_'.$arEvent['ENTITY_TYPE'])?>
				</span>
				<?=
					(new MiniCardHtmlBuilder(CCrmOwnerType::ResolveID($arEvent['ENTITY_TYPE']), $arEvent['ENTITY_ID']))
						->setTitle($arEvent['ENTITY_TITLE'])
						->build()
				?>
			</div>
			<?php endif;?>
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



