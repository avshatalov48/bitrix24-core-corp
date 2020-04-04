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

$guid = $arResult['GUID'];
$prefix = strtolower($guid);
$caption = $arResult['ENTITY_CAPTION'];
$wrapperID = "{$prefix}_wrapper";
$containerID = "{$prefix}_container";
$counterContainerID = "{$prefix}_counter";

?><div class="crm-alert" id="<?=htmlspecialcharsbx($wrapperID)?>" style="display: none;">
	<div class="crm-alert-inner" id="<?=htmlspecialcharsbx($containerID)?>">
		<span class="crm-alert-inner-text"><?=$caption?>:</span>
		<span class="crm-alert-entity-counter" id="<?=htmlspecialcharsbx($counterContainerID)?>">
		</span>
	</div>
</div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmNewEntityCounterPanel.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					userId: <?=$arResult['USER_ID']?>,
					entityTypeId: <?=($arResult['ENTITY_TYPE_ID'])?>,
					lastEntityId: <?=($arResult['ENTITY_LAST_ID'])?>,
					gridId: "<?=CUtil::JSEscape($arResult['GRID_ID'])?>",
					serviceUrl: "/bitrix/components/bitrix/crm.newentity.counter.panel/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					pullTagName: "<?=CUtil::JSEscape($arResult['PULL_TAG_NAME'])?>",
					pullCommands: <?=CUtil::PhpToJSObject($arResult['PULL_COMMANDS'])?>,
					wrapperId: "<?=CUtil::JSEscape($wrapperID)?>",
					containerId: "<?=CUtil::JSEscape($containerID)?>",
					counterContainerId: "<?=CUtil::JSEscape($counterContainerID)?>"
				}
			);
		}
	);
</script><?