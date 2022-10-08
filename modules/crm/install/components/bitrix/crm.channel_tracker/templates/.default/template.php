<?php

use Bitrix\Crm\Widget\Layout\StartCrmWidget;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
* Bitrix vars
* @global CUser $USER
* @global CMain $APPLICATION
* @global CDatabase $DB
* @var array $arParams
* @var array $arResult
* @var CBitrixComponent $component
* @var
*/
\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'START',
		'ACTIVE_ITEM_ID' => 'START',
		'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

if (SITE_TEMPLATE_ID === 'bitrix24')
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-start-page');
}
\Bitrix\Main\Loader::includeModule('intranet');
CJSCore::Init(array('amcharts', 'amcharts_funnel', 'amcharts_serial', 'amcharts_pie', 'fx', 'drag_drop', 'popup', 'date', 'ajax', 'intranet_notify_dialog'));

$showSaleTarget = true;
if (Bitrix\Main\Loader::includeModule("bitrix24") && !Bitrix\Bitrix24\Feature::isFeatureEnabled("crm_sale_target"))
{
	$showSaleTarget = false;
}

$guid = $arResult['GUID'];
$items = $arResult['ITEMS'];
$groupItems = $arResult['GROUP_ITEMS'];
$totals = $arResult['TOTALS'];
$messages = $arResult['MESSAGES'];
$containerID = "{$guid}_container";
$APPLICATION->ShowViewContent('widget_panel_header');
$activities = array("active" => "", "inactive" => "");
$inactiveItems = array();
$inactiveItemCaptions = array();
$activeCount = 0;
$video = <<<HTML
<div class="crm-start-title-icons">
	<div id="crm-start-video" class="crm-start-title-icons-item crm-start-title-icons-item-video"></div>
</div>
HTML;
$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
	|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

$startOptions = \CUserOptions::GetOption('crm.entity.channeltracker', $guid, array());
$startVersion = isset($startOptions['version']) ? (int)$startOptions['version'] : 0;

if ($showSaleTarget && $startVersion < 2)
{
	$options = CUserOptions::GetOption('crm.widget_panel', $arResult['WIDGET_GUID'], array());
	if(isset($options['rows']) && is_array($options['rows']))
	{
		$options['rows'][] = array(
			'height' => 380,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'title' => GetMessage("CRM_CH_TRACKER_WGT_SALE_TARGET"),
							'entityTypeName' => \CCrmOwnerType::DealName,
							'typeName' => 'custom',
							'customType' => 'saletarget',
							'configs' => array(
								array(
									'name' => 'sale_target',
									'dataPreset' => 'DEAL_SALE_TARGET::ACTIVE',
									'dataSource' => 'DEAL_SALE_TARGET'
								)
							)
						)
					)
				)
			)
		);
		CUserOptions::SetOption('crm.widget_panel', $arResult['WIDGET_GUID'], $options);
	}

	$startOptions['version'] = 2;
	\CUserOptions::SetOption('crm.entity.channeltracker', $guid, $startOptions);
}
?>


<div class="crm-start">
	<?
	$customWidgets = [];
	if ($showSaleTarget)
	{
		$customWidgets[] = 'saletarget';
	}
	$rowData = StartCrmWidget::getDefaultRows([
		'isSupervisor' => $isSupervisor,
		'showSaleTarget' => $showSaleTarget,
	]);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => $arResult['WIDGET_GUID'],
			'LAYOUT' => 'L50R50',
			'ENABLE_NAVIGATION' => false,
			'NOT_CALCULATE_DATA' => true, //this is HACK for performance
			'ENTITY_TYPES' => array(
				CCrmOwnerType::ActivityName,
				CCrmOwnerType::LeadName,
				CCrmOwnerType::DealName,
				CCrmOwnerType::ContactName,
				CCrmOwnerType::CompanyName,
				CCrmOwnerType::InvoiceName
			),
			'DEFAULT_ENTITY_TYPE' => CCrmOwnerType::ActivityName,
			'PATH_TO_WIDGET' => isset($arResult['PATH_TO_LEAD_WIDGET']) ? $arResult['PATH_TO_LEAD_WIDGET'] : '',
			'PATH_TO_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.channel_tracker/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'DEMO_TITLE' => GetMessage('CRM_CH_TRACKER_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => '',
			'RENDER_HEAD_INTO_VIEW' => 'widget_panel_header',
			'CUSTOM_WIDGETS' => $customWidgets
		)
	);?>
<?
if ($_REQUEST["restoreAnimation"] == "Y")
{
	\CUserOptions::DeleteOption("crm.widget", "activityDynamic");
	\CUserOptions::DeleteOption("crm.widget", "counters");
}
$firstSeen = (!(($res = \CUserOptions::GetOption("crm.widget", "activityDynamic")) && is_array($res) && $res["firstSeen"] === "N"));
$attempts = 3;

$spotLightO = \CUserOptions::GetOption("crm.widget", "counters");
$spotLightO = (is_array($spotLightO) ? $spotLightO : array());

$spotLight = array(
	"activityDynamic" => GetMessage("CRM_CH_TRACKER_WGT_ANIMATION_1"),
	"demoMode" => GetMessage("CRM_CH_TRACKER_WGT_ANIMATION_4"),
	"inactiveControl" => GetMessage("CRM_CH_TRACKER_WGT_ANIMATION_2"),
	"managerCounters" => ($isSupervisor ? GetMessage("CRM_CH_TRACKER_WGT_ANIMATION_3_SUPERVISOR") : GetMessage("CRM_CH_TRACKER_WGT_ANIMATION_3_EMPLOYEE"))
);
foreach ($spotLight as $key => $mess)
{
	$spotLight[$key] = array(
		"counter" => 0,
		"message" => $mess
	);
	if (isset($spotLightO[$key]) && $spotLightO[$key] > 0)
		$spotLight[$key]["counter"] = $spotLightO[$key];
}
if (defined("crm.channel_tracker.demo"))
	unset($spotLight["demoMode"]);

if (array_sum($spotLight) >= ($attempts * count($spotLight)))
{

}
else
{
	CJSCore::Init(array('spotlight'));
?>

<script>

   new BX.Crm.Start({
        renderArea: document.querySelector('.crm-start'),
        loadingTimeout: <?=CUtil::PhpToJSObject(CUserOptions::GetOption('crm', 'crm_start_loading_timeout', 0))?>,
        url: <?=CUtil::PhpToJSObject( $_SERVER['ROOT'] . '/bitrix/components/bitrix/crm.channel_tracker/ajax.php')?>,
        params: {
        	LAZY_LOAD_COMPONENT: true,
        	GUID: <?=CUtil::PhpToJSObject($arResult['WIDGET_GUID'])?>,
	        PATH_TO_LEAD_WIDGET: <?=CUtil::PhpToJSObject(isset($arResult['PATH_TO_LEAD_WIDGET']) ? $arResult['PATH_TO_LEAD_WIDGET'] : '')?>,
	        PATH_TO_LEAD_LIST: <?=CUtil::PhpToJSObject(isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '')?>,
	        ROW_DATA: <?=CUtil::PhpToJSObject($rowData)?>,
            DEMO_TITLE: "<?=GetMessage('CRM_CH_TRACKER_WGT_DEMO_TITLE')?>"
        }

    });
	BX.ready(function() {
		new ((function() {
			var d = function(counters) {
				this.counters = {};
				for (var ii in counters)
				{
					if (counters.hasOwnProperty(ii))
					{
						this.counters[ii] = {
							counter: parseInt(counters[ii]["counter"]),
							node: null,
							message: counters[ii]["message"]
						};
					}
				}
				this.bind();
				<?if (!$firstSeen) { ?>setTimeout(BX.proxy(this.check, this), 1000);<? } ?>
			};
			d.prototype = {
				max: <?=$attempts?>,
				bind : function() {
					BX.addCustomEvent(window, "crm.widget", BX.proxy(function(widgetName, state, nodeToSpot) {
						if (!this.counters[widgetName])
							return;
						if (state === "append")
						{
							this.counters[widgetName]["node"] = nodeToSpot;
							if (widgetName === "inactiveControl")
							{
								this.counters[widgetName]["node"] = BX.findChild(nodeToSpot, {className: "crm-widget-activity-dynamic-more-toggler"}, true);
							}
						}
						else if (state === "close")
						{
							if (this.counters[widgetName]["counter"] )
							this.counters[widgetName]["counter"]++;
							if (this.counters[widgetName]["counter"] <= this.max)
							{
								BX.userOptions.save('crm.widget', 'counters', widgetName, this.counters[widgetName]["counter"]);
							}
							this.check();
						}
					}, this));
					if (this.counters["demoMode"])
						this.counters["demoMode"]["node"] = BX.findChild(BX("start_widget_toolbar"), {className : "webform-button-icon"}, true);
					BX.bind(window, 'scroll', BX.throttle(this.check, 350, this));
				},
				check : function(e) {
					for (var ii in this.counters)
					{
						if (this.counters.hasOwnProperty(ii))
						{
							if (BX(this.counters[ii]["node"]) &&
								this.counters[ii]["node"].hasAttribute("busy"))
								return;
						}
					}
					for (ii in this.counters)
					{
						if (this.counters.hasOwnProperty(ii))
						{
							if (this.counters[ii]["counter"] < this.max &&
								BX(this.counters[ii]["node"]) &&
								!this.counters[ii]["node"].hasAttribute("busy") &&
								this.checkVisibility(this.counters[ii]["node"]))
							{
								this.counters[ii]["node"].setAttribute("busy", "Y");
								this.lightOn(ii, this.counters[ii]["node"]);
								this.counters[ii]["counter"]++;
								BX.userOptions.save('crm.widget', 'counters', ii, this.counters[ii]["counter"]);
								break;
							}
						}
					}
				},
				lightOn: function(widgetName, node) {
					var obj = new BX.SpotLight({
						renderTo: node,
						top: parseInt(node.offsetHeight / 2),
						left: parseInt(node.offsetWidth / 2),
						content: this.counters[widgetName]["message"]
					});
					node.setAttribute("busy", "Y");
					BX.addCustomEvent(obj, "spotLightOk", BX.proxy(function(node){
						node.removeAttribute("busy");
						this.counters[widgetName]["counter"] = this.max;
						BX.userOptions.save('crm.widget', 'counters', widgetName, this.max);
						this.check();
					}, this));
					obj.show();
				},
				checkVisibility: function(node) {
					var pos = BX.pos(node),
						wSize = BX.GetWindowSize(),
						v = (
							(
								wSize["scrollLeft"] < pos["left"] &&
								pos["left"] < (wSize["scrollLeft"] + wSize["innerWidth"])
								||
								wSize["scrollLeft"] < pos["right"] &&
								pos["right"] < (wSize["scrollLeft"] + wSize["innerWidth"])
							)
							&&
							(
								wSize["scrollTop"] < pos["top"] &&
								pos["top"] < (wSize["scrollTop"] + wSize["innerHeight"])
								||
								wSize["scrollTop"] < pos["bottom"] &&
								pos["bottom"] < (wSize["scrollTop"] + wSize["innerHeight"])
							)
						);
					return v;
				}
			};
			return d;
		})())(<?=\CUtil::PhpToJSObject($spotLight)?>);
	});
</script>
<?
}
?>
