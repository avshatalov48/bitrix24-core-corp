<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Crm\Counter\EntityCounterType;
\Bitrix\Main\UI\Extension::load("ui.fonts.opensans");

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/counter.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/message.js');

$guid = $arResult['GUID'];
$prefix = strtolower($guid);
$caption = $arResult['ENTITY_CAPTION'];
$total = isset($arResult['TOTAL']) ? $arResult['TOTAL'] : '0';
$data = isset($arResult['DATA']) ? $arResult['DATA'] : array();
$containerID = "{$prefix}_container";
$valueContainerID = "{$prefix}_value_container";
$stubContainerID = "{$prefix}_stub_container";

$showStub = $arResult['SHOW_STUB'] ? $arResult['SHOW_STUB'] : false;

if($showStub)
{
	?><div class="crm-counter">
		<div class="crm-counter-title"><?=GetMessage('CRM_COUNTER_STUB')?></div>
	</div><?
}
else
{
	?><div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-counter">
		<div id="<?=htmlspecialcharsbx($valueContainerID)?>" class="crm-counter-title" style="<?=$total > 0 ? '' : 'display: none;'?>">
			<span class="crm-page-name"><?=htmlspecialcharsbx($caption)?>: </span><?
			foreach($data as $code => $item)
			{
				$typeID = isset($item['TYPE_ID']) ? $item['TYPE_ID'] : 0;
				$typeName = isset($item['TYPE_NAME']) ? $item['TYPE_NAME'] : '';
				$value = isset($item['VALUE']) ? $item['VALUE'] : '';
				$url = isset($item['URL']) ? $item['URL'] : '#';

				$className = 'crm-counter-link';
				if($typeName === EntityCounterType::IDLE_NAME)
				{
					$className = 'crm-counter-nodate';
				}
				elseif($typeName === EntityCounterType::OVERDUE_NAME)
				{
					$className = 'crm-counter-overdue';
				}
				elseif($typeName === EntityCounterType::PENDING_NAME)
				{
					$className = 'crm-counter-pending';
				}
				?><a data-entity-counter-code="<?=$code?>"
					data-type-id="<?=$typeID?>"
					href="<?=htmlspecialcharsbx($url)?>"
					class="crm-counter-container <?=$className?>"
					style="<?=$value > 0 ? '' : 'display: none;'?>">
				<?=GetMessage("CRM_COUNTER_TYPE_{$typeName}", array('#VALUE#' => $value))?>
				</a><?
			}
		?></div>
		<div id="<?=htmlspecialcharsbx($stubContainerID)?>" class="crm-counter-title" style="<?=$total > 0 ? 'display: none;' : ''?>">
			<div class="crm-page-nocounter"><?=$arResult['STUB_MESSAGE']?></div>
		</div>
	</div>

	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmEntityCounterPanel.create(
					"<?=CUtil::JSEscape($guid)?>",
					{
						userId: <?=$arResult['USER_ID']?>,
						userName: "<?=CUtil::JSEscape($arResult['USER_NAME'])?>",
						codes: <?=CUtil::PhpToJSObject($arResult['CODES'])?>,
						extras: <?=CUtil::PhpToJSObject($arResult['EXTRAS'])?>,
						entityTypeId: "<?=CUtil::JSEscape($arResult['ENTITY_TYPE_ID'])?>",
						entityNumberDeclensions: <?=CUtil::PhpToJSObject($arResult['ENTITY_NUMBER_DECLENSIONS'])?>,
						containerId: "<?=CUtil::JSEscape($containerID)?>",
						valueContainerId: "<?=CUtil::JSEscape($valueContainerID)?>",
						stubContainerId: "<?=CUtil::JSEscape($stubContainerID)?>",
						serviceUrl: "<?='/bitrix/components/bitrix/crm.entity.counter.panel/ajax.php?'.bitrix_sessid_get()?>",
						data: <?=CUtil::PhpToJSObject($data)?>,
						totalInfo: { value: <?=$total?>, caption: "<?=CUtil::JSEscape($caption)?>" }
					}
				);
			}
		);
	</script><?
}