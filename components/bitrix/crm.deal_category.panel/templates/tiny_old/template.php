<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 */

$guid = $arResult['GUID'];
$containerID = "{$guid}_container";
$selectorButtonID = "{$guid}_selector";
$counterContainerID = "{$guid}_counter";

$layoutWrapClassName = isset($arParams['LAYOUT_WRAP_CLASSNAME']) ? $arParams['LAYOUT_WRAP_CLASSNAME'] : 'pagetitle-container pagetitle-align-right-container';

\Bitrix\Main\UI\Extension::load("ui.buttons");

?><div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-interface-toolbar-button-container">
    <button id="<?=htmlspecialcharsbx($selectorButtonID)?>" class="ui-btn ui-btn-light-border ui-btn-themes
		<?if($arResult['IS_CUSTOMIZED'])
    {
        ?> ui-btn-dropdown<?
    }
    elseif($arResult['CAN_CREATE_CATEGORY'])
    {
        ?> ui-btn-icon-add<?
    }
    ?>">
        <?=$arResult['CATEGORY_NAME'] !== ''
            ? htmlspecialcharsbx($arResult['CATEGORY_NAME']) : GetMessage('CRM_DEAL_CATEGORY_SELECTOR')?>
        <?if($arResult['CATEGORY_COUNTER'] > 0)
        {
            ?><i id="<?=htmlspecialcharsbx($counterContainerID)?>" class="ui-btn-counter"><?=$arResult['CATEGORY_COUNTER']?></i><?
        }
        ?></button>
</div>
<script>
	BX.ready(
		function()
		{
			BX.CrmDealCategoryTinyPanel.messages =
			{
				"create": "<?=GetMessageJS('CRM_DEAL_CATEGORY_PANEL_ADD_CATEGORY')?>"
			};

			BX.CrmDealCategoryTinyPanel.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					items: <?=CUtil::PhpToJSObject($arResult['ITEMS'])?>,
					containerId: "<?=CUtil::JSEscape($containerID)?>",
					selectorButtonId: "<?=CUtil::JSEscape($selectorButtonID)?>",
					counterContainerId: "<?=CUtil::JSEscape($counterContainerID)?>",
					counterId: "<?=CUtil::JSEscape($arResult['CATEGORY_COUNTER_CODE'])?>",
					isCustomized: <?=$arResult['IS_CUSTOMIZED'] ? 'true' : 'false'?>,
					isSlider: <?=($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER') ? 'true' : 'false'?>,
					enableCreation: <?=$arResult['CAN_CREATE_CATEGORY'] ? 'true' : 'false'?>,
					createUrl: "<?=CUtil::JSEscape($arResult['CATEGORY_CREATE_URL'])?>",
					createLockScript: "<?=CUtil::JSEscape($arResult['CREATE_CATEGORY_LOCK_SCRIPT'])?>"
				}
			);
		}
	);
</script>

