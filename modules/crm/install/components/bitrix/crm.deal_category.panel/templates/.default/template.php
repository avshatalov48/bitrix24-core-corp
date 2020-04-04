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
?>
<!-- Use class name "crm-deal-panel" if empty -->
<div class="crm-deal-panel-array-have" id="<?=htmlspecialcharsbx($containerID)?>">
	<div class="crm-deal-panel-tab"><?
		$iconNumber = 0;
		foreach($arResult['ITEMS'] as $item):
			if(isset($item['ENABLED']) && $item['ENABLED'] === false)
				continue;

			$iconNumber++;
			if($iconNumber > 10):
				$iconNumber = 1;
			endif;

			$classNames = array('crm-deal-panel-tab-item');
			$classNames[] = "crm-deal-panel-tab-item-icon-{$iconNumber}";
			if(isset($item['IS_ACTIVE']) && $item['IS_ACTIVE']):
				$classNames[] = 'crm-deal-panel-tab-item-active';
			endif;
			?>
			<div class="<?=implode(' ', $classNames)?>">
				<?if(isset($item['COUNTER']) && $item['COUNTER'] > 0):?>
					<span class="crm-deal-panel-tab-item-message"><?=$item['COUNTER']?></span>
				<?endif;?>
				<a href="<?=htmlspecialcharsbx($item['URL'])?>" class="crm-deal-panel-tab-item-text">
					<?=htmlspecialcharsbx($item['NAME'])?></a><?
			if(isset($item['CREATE_URL']) && $item['CREATE_URL'] !== ''):
				?><a class="crm-deal-tab-item-plus" href="<?=htmlspecialcharsbx($item['CREATE_URL'])?>"></a><?
			endif;
			?></div><?
		endforeach;
		?>
		<span class="crm-deal-panel-tab-item-show-more"><?=GetMessage('CRM_DEAL_CATEGORY_PANEL_MENU_BUTTON')?></span>
	</div>
	<div class="crm-deal-panel-tab-add"><?
		if($arResult['CAN_CREATE_CATEGORY']):
			if(isset($arResult['CATEGORY_CREATE_URL']) && $arResult['CATEGORY_CREATE_URL'] !== ''):
				?><a class="webform-small-button webform-small-button-transparent webform-small-button-icon-add"
				     id="crm-deal-panel-tab-item-add" href="<?=htmlspecialcharsbx($arResult['CATEGORY_CREATE_URL'])?>"
				     target="_blank"><?=GetMessage('CRM_DEAL_CATEGORY_PANEL_ADD_CATEGORY')?>
				</a><?
			endif;
			if(isset($arResult['CREATE_CATEGORY_LOCK_SCRIPT']) && $arResult['CREATE_CATEGORY_LOCK_SCRIPT'] !== ''):
				?><a class="webform-small-button webform-small-button-transparent webform-small-button-icon-add"
					id="crm-deal-panel-tab-item-add" href="#"
					onclick="<?=htmlspecialcharsbx($arResult['CREATE_CATEGORY_LOCK_SCRIPT'])?>; return BX.PreventDefault(this);">
				<?=GetMessage('CRM_DEAL_CATEGORY_PANEL_ADD_CATEGORY')?>
				</a>
				<div class="crm-deal-panel-lock"></div><?
			endif;
		endif;
	?></div>

	<script type="text/javascript">
		(
			function()
			{
				var container = BX("<?=CUtil::JSEscape($containerID)?>");
				var nodes = container.getElementsByClassName("crm-deal-panel-tab-item");

				var enableMenu = false;
				var tail = null;
				for(var i = nodes.length - 1; i >= 0; i--)
				{
					var n = nodes[i];
					if(n.offsetTop === 0)
					{
						tail = n;
						break;
					}
					else if(!enableMenu)
					{
						enableMenu = true;
					}
				}

				var button = container.getElementsByClassName("crm-deal-panel-tab-item-show-more")[0];
				if(!enableMenu)
				{
					button.style.display = "none";
				}
				else
				{
					button.style.display = "block";
					if(tail)
					{
						button.style.left = tail.offsetLeft + tail.offsetWidth + 20 + "px";
					}
				}
			}
		)();
		BX.ready(
			function()
			{
				BX.CrmDealCategoryPanel.create(
					"<?=CUtil::JSEscape($guid)?>",
					{
						containerId: "<?=CUtil::JSEscape($containerID)?>",
						enableCreation: <?=$arResult['CAN_CREATE_CATEGORY'] ? 'true' : 'false'?>
					}
				);
			}
		);
	</script>
</div><?

