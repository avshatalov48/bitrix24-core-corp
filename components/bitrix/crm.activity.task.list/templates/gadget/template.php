<?php

use Bitrix\Crm\ItemMiniCard\Builder\MiniCardHtmlBuilder;
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load(["ui.tooltip", "ui.fonts.opensans"]);

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['TASK']))
	echo Loc::getMessage('CRM_TASK_EMPTY');
else
{
	foreach($arResult['TASK'] as $arTask)
	{
		?>
		<div class="crm-task-element">
			<?php if($arResult['ACTIVITY_ENTITY_LINK'] == 'Y'): ?>
			<div class="crm-task-element-title">
				<span>
					<?= Loc::getMessage("CRM_ENTITY_{$arTask['ENTITY_TYPE']}") ?>
				</span>
				<?=
					(new MiniCardHtmlBuilder(CCrmOwnerType::ResolveID($arTask['ENTITY_TYPE']), (int)$arTask['ENTITY_ID']))
						->setTitle($arTask['ENTITY_TITLE'])
						->build()
				?>
			</div>
			<?php endif; ?>
			<div class="crm-task-element-type"><a href="<?=$arTask['PATH_TO_TASK_SHOW']?>"><?=$arTask['TITLE']?></a></div>
			<div class="crm-task-element-name">
				<div class="crm-task-element-name-date"><?=FormatDate('x', MakeTimeStamp($arTask['CREATED_DATE']))?></div>
			</div>
		</div>
		<?php
	}
}
?>