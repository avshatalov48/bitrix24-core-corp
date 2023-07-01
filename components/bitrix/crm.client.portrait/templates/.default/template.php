<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm\Activity\CommunicationWidgetPanel;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\UI;

UI\Extension::load([
	"ui.design-tokens",
	"ui.fonts.opensans",
	"ui.tooltip",
]);

/* @global CMain $APPLICATION */
/* @var array $arResult */

global $APPLICATION;
$APPLICATION->SetTitle($arResult['PAGE_TITLE']);
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
CJSCore::Init(array('amcharts', 'amcharts_pie'));

$element = $arResult['ELEMENT'];
$loadbars = $arResult['LOADBARS'];
$primaryBar = $loadbars['primary'];
unset($loadbars['primary']);

$comments = \CrmClientPortraitComponent::prepareComments($element['COMMENTS']);
$rowData = CommunicationWidgetPanel::getPortraitRowData($arResult['ENTITY_TYPE_ID']);
$currentUserID = Container::getInstance()->getContext()->getUserId();
$isSupervisor = Container::getInstance()->getUserPermissions($currentUserID)->isAdmin()
	|| IntranetManager::isSupervisor($currentUserID);

if (isset($arParams['IS_FRAME']) && $arParams['IS_FRAME'] === 'Y' && empty($arParams['IS_FRAME_RELOAD'])):?>
	<div class="pagetitle-wrap">
		<div class="pagetitle-inner-container">
			<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
				<?
				$APPLICATION->ShowViewContent("inside_pagetitle");
				?>
			</div>
		</div>
	</div>
<?endif?>
	<div class="crm-portriat">
		<div class="crm-portrait-title"><?=$arResult['PAGE_TITLE']?></div>
		<? $APPLICATION->ShowViewContent('widget_panel_head'); ?>
		<div class="crm-portrait-title-block">
			<div class="crm-portrait-title-small"><?=$arResult['LOAD_TITLE']?></div>
			<?if (count($loadbars) > 0):?>
			<div class="crm-portrait-direction" data-role="loadbarmenu-menu"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_SHOW_DEAL_CATEGORIES')?></div>
			<?endif;?>
		</div>
		<div class="crm-portrait-item crm-portrait-item-show">
			<div class="crm-portrait-item-wrapper">
				<div class="crm-portrait-load"
					data-role="loadbar-primary"
					data-loaddata="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($primaryBar['data']))?>"
					data-context="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($primaryBar['context']))?>"
					<?if ($arResult['CAN_WRITE_CONFIG']):?> style="margin-right: 40px;"<?endif;?>>
					<div class="crm-portrait-load-item-blue" data-role="level-1" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_IDEAL')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_IDEAL')?></span></div>
					</div>
					<div class="crm-portrait-load-item-green" data-role="level-2" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_LOW')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_LOW')?></span></div>
					</div>
					<div class="crm-portrait-load-item-gray" data-role="level-3" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_LOW')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_LOW')?></span></div>
					</div>
					<div class="crm-portrait-load-item-light" data-role="level-4" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_DESIRABLE')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_DESIRABLE')?></span></div>
					</div>
					<div class="crm-portrait-load-item-yellow" data-role="level-5" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_HIGH')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_HIGH')?></span></div>
					</div>
					<div class="crm-portrait-load-item-orange" data-role="level-6" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_HIGH')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_HIGH')?></span></div>
					</div>
					<div class="crm-portrait-load-item-red" data-role="level-7" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_CRITICAL')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_CRITICAL')?></span></div>
					</div>
					<div class="crm-portrait-load-bar" data-role="current">
						<div class="crm-portrait-load-baloon" data-role="current-text"></div>
					</div>
					<?if ($arResult['CAN_WRITE_CONFIG']):?>
					<div class="crm-widget-settings crm-portrait-settings" data-role="config-btn"></div>
					<?endif;?>
				</div>
			</div>
		</div>
		<?if (count($loadbars) > 0):?>
		<div class="crm-portrait-item" data-role="loadbarmenu-container">
			<?foreach ($loadbars as $bar):
			?><div class="crm-portrait-item-wrapper">
				<div class="crm-portrait-item-title"><?=htmlspecialcharsbx($bar['name'])?></div>
				<div class="crm-portrait-load"
					data-role="loadbar"
					data-loaddata="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($bar['data']))?>"
					data-context="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($bar['context']))?>"
					<?if ($arResult['CAN_WRITE_CONFIG']):?> style="margin-right: 40px;"<?endif;?>>
					<div class="crm-portrait-load-item-blue" data-role="level-1" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_IDEAL')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_IDEAL')?></span></div>
					</div>
					<div class="crm-portrait-load-item-green" data-role="level-2" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_LOW')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_LOW')?></span></div>
					</div>
					<div class="crm-portrait-load-item-gray" data-role="level-3" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_LOW')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_LOW')?></span></div>
					</div>
					<div class="crm-portrait-load-item-light" data-role="level-4" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_DESIRABLE')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_DESIRABLE')?></span></div>
					</div>
					<div class="crm-portrait-load-item-yellow" data-role="level-5" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_HIGH')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_HIGH')?></span></div>
					</div>
					<div class="crm-portrait-load-item-orange" data-role="level-6" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_HIGH')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_VERY_HIGH')?></span></div>
					</div>
					<div class="crm-portrait-load-item-red" data-role="level-7" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_CRITICAL')?>">
						<div class="crm-portrait-load-item-text"><span class="crm-portriat-load-tem-text-inner"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_ITEM_CRITICAL')?></span></div>
					</div>
					<div class="crm-portrait-load-bar" data-role="current">
						<div class="crm-portrait-load-baloon" data-role="current-text"></div>
					</div>
					<?if ($arResult['CAN_WRITE_CONFIG']):?>
						<div class="crm-widget-settings crm-portrait-settings" data-role="config-btn"></div>
					<?endif;?>
				</div>
			</div>
			<?endforeach;?>
		</div>
		<?endif;?>
		<div class="crm-portriat-wrapper">
			<div class="crm-portrait-user">
				<div class="crm-portrait-user-avatar"<?if ($arResult['ELEMENT_PHOTO_SRC']):?> style="background-image: url('<?=htmlspecialcharsbx($arResult['ELEMENT_PHOTO_SRC'])?>');"<?endif?>></div>
				<div class="crm-portrait-user-info">
					<?if ($arResult['IS_COMPANY']):?>
					<div class="crm-portrait-user-name"><?=htmlspecialcharsbx($element['TITLE'])?></div>
					<?else:?>
					<div class="crm-portrait-user-name"><?=htmlspecialcharsbx($element['FULL_NAME'])?></div>
					<?endif?>
					<table class="crm-portrait-user-table">
						<col class="crm-portrait-user-table-col-1">
						<col class="crm-portrait-user-table-col-2">
						<?if ($arResult['IS_COMPANY']):?>
							<?if (!empty($element['FM']['EMAIL'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_EMAIL')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=\CCrmViewHelper::PrepareFormMultiField($element, 'EMAIL')?></td>
								</tr>
							<?endif?>
							<?if (!empty($element['FM']['PHONE'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_PHONE')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=\CCrmViewHelper::PrepareFormMultiField($element, 'PHONE')?></td>
								</tr>
							<?endif?>
							<?if (!empty($element['FM']['WEB'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_WEB')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=\CCrmViewHelper::PrepareFormMultiField($element, 'WEB')?></td>
								</tr>
							<?endif?>
							<?if (!empty($element['COMPANY_TYPE_TITLE'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_COMPANY_TYPE')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=htmlspecialcharsbx($element['COMPANY_TYPE_TITLE'])?></td>
								</tr>
							<?endif?>
							<?if (!empty($element['INDUSTRY_TITLE'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_INDUSTRY')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=htmlspecialcharsbx($element['INDUSTRY_TITLE'])?></td>
								</tr>
							<?endif?>
						<?else:?>
							<?if (!empty($element['FM']['EMAIL'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_EMAIL')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=\CCrmViewHelper::PrepareFormMultiField($element, 'EMAIL')?></td>
								</tr>
							<?endif?>
							<?if (!empty($element['FM']['PHONE'])):?>
								<tr>
									<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_PHONE')?>:</td>
									<td class="crm-portrait-user-table-dark"><?=\CCrmViewHelper::PrepareFormMultiField($element, 'PHONE')?></td>
								</tr>
							<?endif?>
							<?if (!empty($element['POST'])):?>
							<tr>
								<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_POST')?>:</td>
								<td class="crm-portrait-user-table-dark"><?=htmlspecialcharsbx($element['POST'])?></td>
							</tr>
							<?endif?>
							<?if (!empty($element['COMPANY_TITLE'])):?>
							<tr>
								<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_COMPANY')?>:</td>
								<td class="crm-portrait-user-table-dark"><?=htmlspecialcharsbx($element['COMPANY_TITLE'])?></td>
							</tr>
							<?endif?>
						<?endif?>
						<?if (!empty($element['ASSIGNED_BY_URL'])):?>
						<tr>
							<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_RESPONSIBLE')?>:</td>
							<td class="crm-portrait-user-table-dark">
								<a href="<?=htmlspecialcharsbx($element['ASSIGNED_BY_URL'])?>" id="balloon_portrait<?=$element['ID']?>" bx-tooltip-user-id="<?=(int)$element['ASSIGNED_BY_ID']?>">
									<?=htmlspecialcharsbx($element['ASSIGNED_BY_FORMATTED_NAME'])?>
								</a>
							</td>
						</tr>
						<?endif?>
					</table>
					<?if ($comments):?>
					<table class="crm-portrait-user-table crm-portrait-user-table-intro">
						<tr>
							<td class="crm-portrait-user-table-light"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_COMMENTS')?>:</td>
						</tr>
						<tr>
							<td class="crm-portrait-user-table-dark crm-portrait-intro">
								<div class="crm-portrait-intro-overflow crm-portrait-intro-overflow-show">
									<?=htmlspecialcharsbx($comments)?>
								</div>
							</td>
						</tr>
					</table>
					<?endif?>
				</div>
			</div>
			<div class="cem-widget-round-statistic">
				<div class="crm-fake-widget-row crm-fake-widget-row-height-auto crm-fake-widget-row-height-auto">
					<div class="crm-fake-widget-container crm-fake-widget-right">

						<div class="crm-fake-widget">
							<div class="crm-fake-widget-head">
								<span class="crm-fake-widget-title-container">
									<span class="crm-fake-widget-title-inner">
										<span class="crm-fake-widget-title" title="<?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_PIE_TITLE')?>"><?=GetMessage('CRM_CLIENT_PORTRAIT_LOAD_PIE_TITLE')?></span>
									</span>
								</span>
							</div>
							<div class="crm-fake-widget-content" style="height: 380px; padding: 0">
								<div id="crm-load-pie" style="height: 340px; overflow: hidden; text-align: left;">

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script>
				BX.ready(function()
				{
					var createPie = function()
					{
						AmCharts.makeChart('crm-load-pie',
							{
								"type": "pie",
								"theme": "none",
								"titleField": 'LABEL',
								"valueField": 'CNT',
								"dataProvider": <?=CUtil::PhpToJSObject($arResult['ACTIVITIES_STAT']['ITEMS'])?>,
								"labelsEnabled": false,
								"depth3D": 15,
								"angle": 30,
								"outlineAlpha": 0.4,
								"outlineColor": "#FFFFFF",
								"outlineThickness": 1,
								"legend":
									{
										"markerType": "circle",
										"position": "bottom",
										"marginBottom": 10,
										"autoMargins": false
									}
							}
						);
					};

					AmCharts.isReady ? createPie() : AmCharts.ready(createPie);
				});
			</script>
			</div>
		</div>
		<div class="crm-fake-widget-50-50">
			<div class="crm-fake-widget-row crm-fake-widget-row-height-auto crm-fake-widget-row-height-auto">
				<div class="crm-fake-widget-container crm-fake-widget-left">
					<div class="crm-fake-widget crm-fake-widget-green crm-fake-widget-number crm-fake-widget-height-auto" style="background-color: rgb(5, 210, 21);">
						<div class="crm-fake-widget-head">
							<!--<span class="crm-fake-widget-settings"></span>-->
							<span class="crm-fake-widget-title-container">
								<span class="crm-fake-widget-title-inner">
									<span class="crm-fake-widget-title"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_WON_DEALS')?></span>
								</span>
							</span>
						</div>
						<div class="crm-fake-widget-content crm-fake-widget-content-height-auto">
							<div class="crm-fake-widget-content-amt">
								<span class="crm-fake-widget-content-text" style="font-size: 72px; line-height: 80px; opacity: 1;"><?=$arResult['WON_DEALS_STAT']['SUM_FORMATTED']?></span>
							</div>
						</div>
					</div>
				</div>
				<div class="crm-fake-widget-container crm-fake-widget-right">
					<div class="crm-fake-widget crm-fake-widget-number crm-fake-widget-height-auto" style="background-color: rgb(79, 195, 247);">
						<div class="crm-fake-widget-head">
							<!--<span class="crm-fake-widget-settings"></span>-->
							<span class="crm-fake-widget-title-container">
								<span class="crm-fake-widget-title-inner">
									<span class="crm-fake-widget-title"><?=GetMessage('CRM_CLIENT_PORTRAIT_INFO_CONVERSION')?></span>
								</span>
							</span>
						</div>
						<div class="crm-fake-widget-content crm-fake-widget-content-height-auto">
							<div class="crm-fake-widget-content-amt">
								<span class="crm-fake-widget-content-text" style="font-size: 72px; line-height: 80px; opacity: 1;"><?=number_format($arResult['CONVERSION_PERCENT'],2)?>%</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="crm-widget-panel-paddings">
		<div class="bx-crm-view" data-role="crm-portrait-widgets">
			<?$APPLICATION->IncludeComponent(
				'bitrix:crm.widget_panel',
				'',
				array(
					'GUID' => mb_strtolower($arResult['ENTITY_TYPE']).'_portrait',
					'ENTITY_TYPE' => $arResult['ENTITY_TYPE'],
					'ENTITY_ID' => $arResult['ELEMENT']['ID'],
					'ENTITY_TYPES' => array(
						$arResult['ENTITY_TYPE'],
						\CCrmOwnerType::DealName,
						\CCrmOwnerType::InvoiceName
					),
					'LAYOUT' => 'L50R50',
					'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'] ?? null,
					'PATH_TO_WIDGET' => CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_'.$arResult['ENTITY_TYPE'].'_PORTRAIT'] ?? '',
						[
							'contact_id' => $arResult['ELEMENT']['ID'],
							'company_id' => $arResult['ELEMENT']['ID'],
						]
					),
					'PATH_TO_LIST' => CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_'.$arResult['ENTITY_TYPE'].'_SHOW'] ?? '',
						[
							'contact_id' => $arResult['ELEMENT']['ID'],
							'company_id' => $arResult['ELEMENT']['ID'],
						]
					),
					'IS_SUPERVISOR' => $isSupervisor,
					'ROWS' => $rowData,
					'USE_DEMO' => 'N',
					'RENDER_HEAD_INTO_VIEW' => (SITE_TEMPLATE_ID === 'bitrix24') ? null : 'widget_panel_head',
					'MAX_WIDGET_COUNT' => 30
				)
			);
			?>
		</div>
	</div>
<script>
	BX.ready(function()
	{
		BX.namespace('BX.Crm.ClientPortrait');
		if (BX.Crm.ClientPortrait.Loadbar && BX.Crm.ClientPortrait.LoadbarMenu)
		{
			BX.Crm.ClientPortrait.Loadbar.messages = {
				SAVE: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_SAVE')?>',
				CANCEL: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_CANCEL')?>',
				CONFIG_TITLE: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_TITLE')?>',
				CONFIG_DESCRIPTION: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_DESCRIPTION')?>',
				CONFIG_HELP: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_HELP')?>',
				CONFIG_AUTO: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_AUTO')?>',
				CONFIG_MANUAL: '<?=GetMessageJS('CRM_CLIENT_PORTRAIT_LOAD_CONFIG_MANUAL')?>'
			};

			var primaryBarNode = document.querySelector('[data-role="loadbar-primary"]');
			var barMenuNode = document.querySelector('[data-role="loadbarmenu-menu"]');
			var barMenuContainerNode = document.querySelector('[data-role="loadbarmenu-container"]');

			if (primaryBarNode)
				(new BX.Crm.ClientPortrait.Loadbar(primaryBarNode)).init().refreshView();

			if (barMenuNode && barMenuContainerNode)
				(new BX.Crm.ClientPortrait.LoadbarMenu(barMenuNode, barMenuContainerNode)).init();
		}
		<?if (isset($arParams['IS_FRAME']) && $arParams['IS_FRAME'] === 'Y'):?>
		// crutch for Widget Panel
		if(BX.CrmWidgetPanel && BX.CrmWidgetPanel.current)
		{
			BX.CrmWidgetPanel.current.reloadWindow = function()
			{
				var oldNode = this.getContainer();
				if (!oldNode || !oldNode.parentNode)
				{
					return;
				}
				oldNode.innerHTML = '';
				BX.ajax({
					method: 'POST',
					dataType: 'html',
					url: '/bitrix/components/bitrix/crm.client.portrait/lazyload.ajax.php',
					data: {
						site: BX.message('SITE_ID'),
						sessid: BX.bitrix_sessid(),
						PARAMS: {
							template: '.default',
							signedParameters: '<?=CUtil::JSEscape(\CCrmInstantEditorHelper::signComponentParams([
									'ELEMENT_ID' => (int)$arResult['ELEMENT']['ID'],
									'ELEMENT_TYPE' => $arResult['ENTITY_TYPE_ID'],
									'IS_FRAME' => 'Y',
									'IS_FRAME_RELOAD' => 'Y',
								], 'crm.client.portrait'))?>',
							}
						}
					},
					onsuccess: function(html)
					{
						var newNode = BX.create('DIV', {html:html}).querySelector('[data-role="crm-portrait-widgets"]');
						if (newNode)
						{
							oldNode.parentNode.replaceChild(newNode, oldNode);
						}
					}
				});
			}
		}
		<?endif;?>
	});
</script>
