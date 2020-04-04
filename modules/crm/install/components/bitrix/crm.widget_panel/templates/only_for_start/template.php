<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

use Bitrix\Crm;

CJSCore::Init(array('amcharts', 'amcharts_funnel', 'amcharts_serial', 'amcharts_pie', 'fx', 'drag_drop', 'popup', 'date'));
$asset = Bitrix\Main\Page\Asset::getInstance();
$asset->addJs('/bitrix/js/crm/common.js');
$asset->addCss('/bitrix/themes/.default/crm-entity-show.css');
$asset->addCss('/bitrix/js/crm/css/crm.css');

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/bitrix24/crm-entity-show.css');
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'pagetitle-toolbar-field-view flexible-layout crm-toolbar crm-pagetitle-view');
}
$quid = $arResult['GUID'];
$prefix = strtolower($quid);
$containerID = "{$prefix}_container";
$settingButtonID = "{$prefix}_settings";
$disableDemoModeButtonID = "{$prefix}_disable_demo";
$demoModeInfoCloseButtonID = "{$prefix}_demo_info_close";
$demoModeInfoContainerID = "{$prefix}_demo_info";

if($arResult['ENABLE_TOOLBAR'])
{
	$toolbarButtons = array(
		array(
			'TEXT' => GetMessage('CRM_WGT_MENU_ITEM_ADD'),
			'ONCLICK' => 'BX.CrmWidgetPanel.current.processAction("add")'
		),
		array(
			'NEWBAR' => true
		),
		array(
			'TEXT' => GetMessage('CRM_WGT_MENU_CHANGE_LAYOUT'),
			'ONCLICK' => 'BX.CrmWidgetPanel.current.processAction("layout")'
		),
		array(
			'TEXT' => GetMessage('CRM_WGT_MENU_ITEM_RESET'),
			'ONCLICK' => 'BX.CrmWidgetPanel.current.processAction("reset")'
		),
	);

	if ($arResult['USE_DEMO'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_WGT_MENU_ITEM_ENABLE_DEMO_MODE'),
			'ONCLICK' => 'BX.CrmWidgetPanel.current.processAction("enabledemomode")'
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'title',
		array(
			'TOOLBAR_ID' => "{$prefix}_toolbar",
			'BUTTONS' => $toolbarButtons
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

if($arResult['ENABLE_DEMO']):
	?><div id="<?=htmlspecialcharsbx($demoModeInfoContainerID)?>" class="crm-widg-white-tooltip">
		<div class="crm-widg-white-text"><?=$arResult['DEMO_TITLE']?></div>
		<div class="crm-widg-white-text"><?=$arResult['DEMO_CONTENT']?></div>
		<div class="crm-widg-white-text">
			<div id="<?=htmlspecialcharsbx($disableDemoModeButtonID)?>" class="crm-widg-white-bottom-link"><?=GetMessage('CRM_WGT_DISABLE_DEMO')?></div>
		</div>
		<div id="<?=htmlspecialcharsbx($demoModeInfoCloseButtonID)?>" class="crm-widg-white-close"></div>
	</div><?
endif;

$listUrl = $arResult['PATH_TO_LIST'];
$widgetUrl = $arResult['PATH_TO_WIDGET'];
$kanbanUrl = $arResult['PATH_TO_KANBAN'];
$switchToListButtonID = "{$prefix}_list";
$reloadButtonID = "{$prefix}_widget";
$settings = array(
	'defaultEntityType' => $arResult['DEFAULT_ENTITY_TYPE'],
	'entityTypes' => $arResult['ENTITY_TYPES'],
	'layout' => $arResult['LAYOUT'],
	'rows' => $arResult['ROWS'],
	'prefix' => $prefix,
	'containerId' => $containerID,
	'settingButtonId' => $settingButtonID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.widget_panel/settings.php?'.bitrix_sessid_get(),
	'listUrl' => $listUrl,
	'widgetUrl' => $widgetUrl,
	'currencyFormat' => $arResult['CURRENCY_FORMAT'],
	'maxGraphCount' => $arResult['MAX_GRAPH_COUNT'],
	'maxWidgetCount' => $arResult['MAX_WIDGET_COUNT'],
	'isDemoMode' => $arResult['ENABLE_DEMO'],
	'useDemoMode' => $arResult['USE_DEMO'],
	'demoModeInfoContainerId'=> $demoModeInfoContainerID,
	'disableDemoModeButtonId' => $disableDemoModeButtonID,
	'demoModeInfoCloseButtonId' => $demoModeInfoCloseButtonID,
	'isAjaxMode' => \Bitrix\Main\Page\Frame::isAjaxRequest()
);

$filterFieldInfos = array();

$headViewID =  isset($arParams['~RENDER_HEAD_INTO_VIEW']) ? $arParams['~RENDER_HEAD_INTO_VIEW'] : false;
if($headViewID && is_string($headViewID))
	$this->SetViewTarget('below_pagetitle', 0);

if(!$arResult['ENABLE_TOOLBAR'])
{
	?><div class="crm-btn-panel"><span id="<?=htmlspecialcharsbx($settingButtonID)?>" class="crm-btn-panel-btn"></span></div><?
}
?><div class="crm-filter-wrap"><?

$navigationBar = null;
if($arResult['ENABLE_NAVIGATION'])
{
	$navigationBar = array(
		'ITEMS' => array(),
		'BINDING' => array(
			'category' => 'crm.navigation',
			'name' => 'index',
			'key' => strtolower($arResult['NAVIGATION_CONTEXT_ID'])
		)
	);

	if($kanbanUrl !== '')
	{
		$navigationBar['ITEMS'][] = array(
			//'icon' => 'kanban',
			'id' => 'kanban',
			'name' => GetMessage('CRM_WGT_FILTER_NAV_BUTTON_KANBAN'),
			'active' => false,
			'url' => $kanbanUrl
		);
	}

	$navigationBar['ITEMS'][] = array(
		//'icon' => 'table',
		'id' => 'list',
		'name' => GetMessage('CRM_WGT_FILTER_NAV_BUTTON_LIST'),
		'active' => false,
		'counter' => $arResult['NAVIGATION_COUNTER'],
		'url' => $listUrl
	);

	$navigationBar['ITEMS'][] = array(
		//'icon' => 'chart',
		'id' => 'widget',
		'name' => GetMessage('CRM_WGT_FILTER_NAV_BUTTON_WIDGET'),
		'active' => true,
		'hint' => array(
			'title' => GetMessage('CRM_WGT_LIST_HINT_TITLE'),
			'content' => GetMessage('CRM_WGT_LIST_HINT_CONTENT'),
			'disabling' => GetMessage('CRM_WGT_DISABLE_LIST_HINT')
		),
		'url' => $widgetUrl
	);
}

if($arParams['NOT_CALCULATE_DATA'] == true)
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.filter',
		'title',
		array(
			'GRID_ID' => $quid,
			'FILTER' => $arResult['FILTER'],
			'FILTER_ROWS' => $arResult['FILTER_ROWS'],
			'FILTER_FIELDS' => $arResult['FILTER_FIELDS'],
			'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
			'RENDER_FILTER_INTO_VIEW' => false,
			'OPTIONS' => $arResult['OPTIONS'],
			'ENABLE_PROVIDER' => true,
			'DISABLE_SEARCH' => true,
			'VALUE_REQUIRED_MODE' => true,
			'NAVIGATION_BAR' => $navigationBar
		),
		$component,
		array('HIDE_ICONS' => true)
	);
}

if($headViewID && is_string($headViewID))
{
	$this->EndViewTarget();
}

$filterTypeDescriptions =  Crm\Widget\FilterPeriodType::getAllDescriptions();
//Remove unsupported types
unset($filterTypeDescriptions[Crm\Widget\FilterPeriodType::BEFORE]);

?></div>


<?php if($arParams['NOT_CALCULATE_DATA'] == false): ?>


<?
if(!empty($arResult['BUILDERS'])):
	?><div id="rebuildMessageWrapper" ></div><?
endif;
?><div class="crm-widget" id="<?=htmlspecialcharsbx($containerID)?>"></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmEntityType.captions =
				{
					"<?=CCrmOwnerType::ActivityName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Activity)?>",
					"<?=CCrmOwnerType::LeadName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>",
					"<?=CCrmOwnerType::ContactName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>",
					"<?=CCrmOwnerType::CompanyName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>",
					"<?=CCrmOwnerType::DealName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>",
					"<?=CCrmOwnerType::InvoiceName?>": "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>"
				};

			BX.CrmWidgetManager.serviceUrl = "<?='/bitrix/components/bitrix/crm.widget_panel/ajax.php?'.bitrix_sessid_get()?>";
			BX.CrmWidgetManager.filter = <?=CUtil::PhpToJSObject($arResult['WIDGET_FILTER'])?>;
			BX.CrmWidgetManager.contextData = <?=CUtil::PhpToJSObject($arResult['CONTEXT_DATA'])?>;
			BX.CrmWidgetManager.contextEntityTypeName = "<?=CUtil::JSEscape($arResult['DEFAULT_ENTITY_TYPE'])?>";
			BX.CrmWidgetManager.contextEntityID = <?=(int)$arResult['DEFAULT_ENTITY_ID']?>;

			BX.CrmWidgetDataPreset.items = <?=CUtil::PhpToJSObject(Crm\Widget\Data\DataSourceFactory::getPresets())?>;
			BX.CrmWidgetDataPreset.notSelected = "<?=GetMessageJS("CRM_WGT_PRESET_NOT_SELECTED")?>";

			BX.CrmWidgetDataCategory.items = <?=CUtil::PhpToJSObject(Crm\Widget\Data\DataSourceFactory::getCategiries())?>;
			BX.CrmWidgetDataCategory.notSelected = "<?=GetMessageJS("CRM_WGT_CATEGORY_NOT_SELECTED")?>";
			BX.CrmWidgetDataCategory.groupTitle = "<?=GetMessageJS("CRM_WGT_CATEGORY_GROUP_TITLE")?>";

			BX.CrmPhaseSemantics.descriptions = <?=CUtil::PhpToJSObject(Crm\PhaseSemantics::getAllDescriptions())?>;
			BX.CrmPhaseSemantics.detailedInfos = <?=CUtil::PhpToJSObject(Crm\PhaseSemantics::getEntityDetailInfos($arResult['ENTITY_TYPES']))?>;
			BX.CrmWidgetColorScheme.descriptions =
				{
					red: "<?=GetMessageJS("CRM_WGT_COLOR_RED")?>",
					green: "<?=GetMessageJS("CRM_WGT_COLOR_GREEN")?>",
					blue: "<?=GetMessageJS("CRM_WGT_COLOR_BLUE")?>",
					cyan: "<?=GetMessageJS("CRM_WGT_COLOR_CYAN")?>",
					yellow: "<?=GetMessageJS("CRM_WGT_COLOR_YELLOW")?>"
				};

			BX.CrmWidgetDataContext.descriptions = <?=CUtil::PhpToJSObject(Crm\Widget\Data\DataContext::getAllDescriptions())?>;

			BX.CrmWidgetDataGroup.descriptions = <?=CUtil::PhpToJSObject(Crm\Widget\Data\DataGrouping::getAllDescriptions())?>;
			BX.CrmWidgetDataGroup.extras = <?=CUtil::PhpToJSObject(Crm\Widget\Data\DataSourceFactory::getGroupingExtras())?>;

			BX.CrmWidgetFilterPeriod.descriptions = <?=CUtil::PhpToJSObject($filterTypeDescriptions)?>;
			BX.CrmWidgetExpressionOperation.descriptions = <?=CUtil::PhpToJSObject(Crm\Widget\Data\ExpressionOperation::getAllDescriptions())?>;

			BX.CrmWidgetExpressionOperation.messages =
				{
					"diffLegend": "<?=GetMessageJS("CRM_WGT_EXPR_LEGEND_DIFF")?>",
					"sumLegend": "<?=GetMessageJS("CRM_WGT_EXPR_LEGEND_SUM")?>",
					"percentLegend": "<?=GetMessageJS("CRM_WGT_EXPR_LEGEND_PERCENT")?>",
					"hint": "<?=GetMessageJS("CRM_WGT_EXPR_HINT")?>"
				};

			BX.CrmWidget.messages =
				{
					"legend": "<?=GetMessageJS("CRM_WGT_RATING_LEGEND")?>",
					"nomineeRatingPosition": "<?=GetMessageJS("CRM_WGT_RATING_NOMINEE_POSITION")?>",
					"ratingPosition": "<?=GetMessageJS("CRM_WGT_RATING_POSITION")?>",
					"configDialogTitle": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_TITLE")?>",
					"configDialogSaveButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_SAVE_BTN")?>",
					"configDialogCancelButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_CANCEL_BTN")?>",
					"periodCaption": "<?=GetMessageJS("CRM_WGT_PERIOD_CAPTION")?>",
					"removalConfirmation": "<?=GetMessageJS("CRM_WGT_REMOVAL_CONFIRMATION")?>",
					"menuItemConfigure": "<?=GetMessageJS("CRM_WGT_MENU_ITEM_CONFIGURE")?>",
					"menuItemRemove": "<?=GetMessageJS("CRM_WGT_MENU_ITEM_REMOVE")?>",
					"untitled": "<?=GetMessageJS("CRM_WGT_UNTITLED")?>"
				};

			BX.CrmWidgetConfigEditor.messages =
				{
					"dialogTitle": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_TITLE")?>",
					"dialogSaveButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_SAVE_BTN")?>",
					"dialogCancelButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_CANCEL_BTN")?>"
				};

			BX.CrmGraphWidgetConfigEditor.messages =
				{
					"addGraph": "<?=GetMessageJS("CRM_WGT_CONFIG_ADD_GRAPH")?>",
					"maxGraphError": "<?=GetMessageJS("CRM_WGT_CONFIG_ERROR_MAX_GRAPH_COUNT")?>"
				};

			BX.CrmWidgetConfigPeriodEditor.messages =
				{
					"yearDescription": "<?=GetMessageJS("CRM_WGT_PERIOD_DESCR_YEAR")?>",
					"quarterDescription": "<?=GetMessageJS("CRM_WGT_PERIOD_DESCR_QUARTER")?>",
					"monthDescription": "<?=GetMessageJS("CRM_WGT_PERIOD_DESCR_MONTH")?>",
					"lastDaysDescription": "<?=GetMessageJS("CRM_WGT_PERIOD_DESCR_LAST_DAYS")?>",
					"accordingToFilter": "<?=GetMessageJS("CRM_WGT_PERIOD_ACCORDING_TO_FILTER")?>",
					"caption": "<?=GetMessageJS("CRM_WGT_PERIOD_CAPTION")?>"
				};

			BX.CrmWidgetConfigPresetEditor.messages =
				{
					"semanticsCaption": "<?=GetMessageJS("CRM_WGT_PRESET_SEMANTICS")?>",
					"categoryCaption": "<?=GetMessageJS("CRM_WGT_PRESET_CATEGORY")?>",
					"nameCaption": "<?=GetMessageJS("CRM_WGT_PRESET_NAME")?>",
					"notSelected": "<?=GetMessageJS("CRM_WGT_PRESET_NOT_SELECTED")?>"
				};

			BX.CrmWidgetConfigTitleEditor.messages =
				{
					"placeholder": "<?=GetMessageJS("CRM_WGT_FIELD_TITLE_PLACEHOLDER")?>",
					"untitled": "<?=GetMessageJS("CRM_WGT_UNTITLED")?>"
				};

			BX.CrmWidgetConfigGroupingEditor.messages =
				{
					"caption": "<?=GetMessageJS("CRM_WGT_GROUPING_CAPTION")?>"
				};

			BX.CrmWidgetTypeSelector.messages =
				{
					"dialogTitle": "<?=GetMessageJS("CRM_WGT_TYPE_SELECTOR_DLG_TITLE")?>",
					"dialogSaveButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_SAVE_BTN")?>",
					"dialogCancelButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_CANCEL_BTN")?>"
				};

			BX.CrmWidgetPanelLayoutTypeSelector.messages =
				{
					"dialogTitle": "<?=GetMessageJS("CRM_WGT_LAYOUT_TYPE_SELECTOR_DLG_TITLE")?>",
					"dialogSaveButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_SAVE_BTN")?>",
					"dialogCancelButton": "<?=GetMessageJS("CRM_WGT_CONFIG_DLG_CANCEL_BTN")?>"
				};

			BX.CrmWidgetPanel.messages =
				{
					"menuItemReset": "<?=GetMessageJS("CRM_WGT_MENU_ITEM_RESET")?>",
					"menuItemAdd": "<?=GetMessageJS("CRM_WGT_MENU_ITEM_ADD")?>",
					"menuChangeLayout": "<?=GetMessageJS("CRM_WGT_MENU_CHANGE_LAYOUT")?>",
					"menuItemEnableDemoMode": "<?=GetMessageJS("CRM_WGT_MENU_ITEM_ENABLE_DEMO_MODE")?>",
					"maxWidgetError": "<?=GetMessageJS("CRM_WGT_CONFIG_ERROR_MAX_WIDGET_COUNT")?>"
				};

			BX.CrmWidgetTypeSelector.entityTypeInfos =
				[
					{ name: "<?=CCrmOwnerType::DealName?>", description: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Deal)?>" },
					{ name: "<?=CCrmOwnerType::InvoiceName?>", description: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Invoice)?>" },
					{ name: "<?=CCrmOwnerType::LeadName?>", description: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Lead)?>" },
					{ name: "<?=CCrmOwnerType::ContactName?>", description: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Contact)?>" },
					{ name: "<?=CCrmOwnerType::CompanyName?>", description: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Company)?>" },
					{ name: "<?=CCrmOwnerType::ActivityName?>", description: "<?=CCrmOwnerType::GetDescription(CCrmOwnerType::Activity)?>" }
				];

			BX.CrmWidgetTypeSelector.infos =
				[
					{
						name: "number",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_NUMBER')?>",
						logo: "<?=$templateFolder?>/images/view-number.jpg",
						params:
							{
								rowHeight: 180,
								config: { typeName: "number", configs: [ { name: "param1" } ] },
								data: { items: [ {} ] }
							}
					},
					{
						name: "numberBlock",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_NUMBER_BLOCK')?>",
						logo: "<?=$templateFolder?>/images/view-number-block.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "number",
										configs: [ { name: "param1" }, { name: "param2" }, { name: "param3" } ],
										layout:"tiled"
									},
								data: { items: [ {}, {}, {} ] }
							}
					},
					{
						name: "numberBlockExpr",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_NUMBER_BLOCK_EXPR')?>",
						logo: "<?=$templateFolder?>/images/view-number-block-expr.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "number",
										configs:
											[
												{ name: "param1" },
												{ name: "param2" },
												{
													name: "expr",
													dataSource:
														{
															name: "EXPRESSION",
															operation: "sum",
															arguments: [ "%param1%", "%param2%" ]
														}
												}
											],
										layout:"tiled"
									},
								data: { items: [ {}, {}, {} ] }
							}
					},
					{
						name: "rating",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_RATING')?>",
						logo: "<?=$templateFolder?>/images/view-rating.jpg",
						params:
							{
								rowHeight: 180,
								config:
									{
										typeName: "rating",
										group: "USER",
										nominee: <?=$arResult['CURRENT_USER_ID']?>,
										configs: [ { name: "param1" } ]
									},
								data: { items: [ {} ] }
							}
					},
					{
						name: "funnel",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_FUNNEL')?>",
						logo: "<?=$templateFolder?>/images/view-funnel.jpg",
						params:
							{
								rowHeight: 380,
								config: { typeName: "funnel" }
							}
					},
					{
						name: "barCluster",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_BAR_CLUSTERED')?>",
						logo: "<?=$templateFolder?>/images/view-bar.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "bar",
										group: "DATE",
										combineData: "Y",
										enableStack: "N",
										configs: [ { name: "param1" } ]
									}
							}
					},
					<?/* TODO delete this code in case impossible to fulfill
				{
					name: "barClusterAvatar",
					title: "",
					logo: "<?=$templateFolder?>/images/view-bar.jpg",
					params:
						{
							rowHeight: 380,
							config:
								{
									typeName: "bar",
									group: "USER",
									combineData: "Y",
									enableStack: "N",
									enableAvatar: "Y",
									configs: [ { name: "param1" } ]
								}
						}
				},
<?*/?>				{
					name: "barStack",
					title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_BAR_STACKED')?>",
					logo: "<?=$templateFolder?>/images/view-bar-stack.jpg",
					params:
						{
							rowHeight: 380,
							config:
								{
									typeName: "bar",
									group: "DATE",
									combineData: "Y",
									enableStack: "Y",
									configs: [ { name: "param1", display: { graph: { clustered: 'N' } } } ]
								}
						}
				},
					{
						name: "barStackAvatar",
						title: '<?=GetMessageJs("CRM_WGT_SELECTOR_TYPE_BAR_STACKED_WITH_AVATARS")?>',
						logo: "<?=$templateFolder//TODO: change logo?>/images/view-bar-stack.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "bar",
										group: "USER",
										combineData: "Y",
										enableStack: "N",
										enableAvatar: "Y",
										configs: [ { name: "param1", display: { graph: { clustered: 'N' } } } ]
									}
							}
					},
					{
						name: "graph",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_GRAPH')?>",
						logo: "<?=$templateFolder?>/images/view-graph.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "graph",
										group: "DATE",
										combineData: "Y",
										configs: [ { name: "param1" } ]
									}
							}
					},
					{
						name: "graphArea",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_GRAPH_AREA')?>",
						logo: "<?=$templateFolder?>/images/view-graph-with-zoom.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "graph",
										group: "DATE",
										combineData: "Y",
										display: {
											chartScrollbar: "Y",
											graph: {
												type : "area"
											}
										},
										configs: [ { name: "param1"} ]
									}
							}
					},
					{
						name: "pie",
						title: "<?=GetMessageJS('CRM_WGT_SELECTOR_TYPE_PIE')?>",
						logo: "<?=$templateFolder?>/images/view-pie.jpg",
						params:
							{
								rowHeight: 380,
								config:
									{
										typeName: "pie",
										group: "USER",
										configs: [ { name: "param1" } ]
									}
							}
					}
				];

			BX.CrmWidgetPanel.isAjaxMode = <?=\Bitrix\Main\Page\Frame::isAjaxRequest() ? 'true' : 'false'?>;
			BX.CrmWidgetPanel.current = BX.CrmWidgetPanel.create("<?=CUtil::JSEscape("{$quid}")?>", <?=CUtil::PhpToJSObject($settings)?>);
			BX.CrmWidgetPanel.current.layout();
		}
	);
</script>
<?if(!empty($arResult['BUILDERS'])):?>
    <script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmLongRunningProcessDialog.messages =
					{
						startButton: "<?=GetMessageJS('CRM_WGT_LRP_DLG_BTN_START')?>",
						stopButton: "<?=GetMessageJS('CRM_WGT_LRP_DLG_BTN_STOP')?>",
						closeButton: "<?=GetMessageJS('CRM_WGT_LRP_DLG_BTN_CLOSE')?>",
						wait: "<?=GetMessageJS('CRM_WGT_LRP_DLG_WAIT')?>",
						requestError: "<?=GetMessageJS('CRM_WGT_LRP_DLG_REQUEST_ERR')?>"
					};

				var builderData, builderSettings, builderPanel, builderId, builderPrefix;
				var prefix = "<?=CUtil::JSEscape($prefix)?>";
				<?foreach($arResult['BUILDERS'] as $builderData):?>
				builderData = <?=CUtil::PhpToJSObject($builderData)?>;
				builderId = BX.type.isNotEmptyString(builderData["ID"]) ? builderData["ID"] : "";
				builderPrefix = builderId !== "" ? (prefix + "_" + builderId.toLowerCase()) : prefix;
				builderSettings = BX.type.isPlainObject(builderData["SETTINGS"]) ? builderData["SETTINGS"] : {};
				builderPanel = BX.CrmLongRunningProcessPanel.create(
					builderId,
					{
						"containerId": "rebuildMessageWrapper",
						"prefix": builderPrefix,
						"active": true,
						"message": BX.type.isNotEmptyString(builderData["MESSAGE"]) ? builderData["MESSAGE"] : "",
						"manager":
							{
								dialogTitle: BX.type.isNotEmptyString(builderSettings["TITLE"]) ? builderSettings["TITLE"] : "",
								dialogSummary: BX.type.isNotEmptyString(builderSettings["SUMMARY"]) ? builderSettings["SUMMARY"] : "",
								actionName: BX.type.isNotEmptyString(builderSettings["ACTION"]) ? builderSettings["ACTION"] : "",
								serviceUrl: BX.type.isNotEmptyString(builderSettings["URL"]) ? builderSettings["URL"] : ""
							}
					}
				);
				builderPanel.layout();
				<?endforeach;?>
			}
		);
    </script>
<?endif;?>
<?endif;?>
