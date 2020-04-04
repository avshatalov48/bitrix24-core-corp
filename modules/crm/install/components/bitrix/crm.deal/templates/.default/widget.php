<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('CRM_DEAL_WGT_PAGE_TITLE_SHORT'));
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'DEAL_WIDGET',
		'ACTIVE_ITEM_ID' => 'DEAL',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
		'PATH_TO_DEAL_INDEX' => isset($arResult['PATH_TO_DEAL_INDEX']) ? $arResult['PATH_TO_DEAL_INDEX'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_DEAL_CATEGORY' => isset($arResult['PATH_TO_DEAL_CATEGORY']) ? $arResult['PATH_TO_DEAL_CATEGORY'] : '',
		'PATH_TO_DEAL_WIDGETCATEGORY' => isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY']) ? $arResult['PATH_TO_DEAL_WIDGETCATEGORY'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

//region Counter
$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
if($isBitrix24Template)
{
	$this->SetViewTarget('below_pagetitle', 0);
}


$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.counter.panel',
	'',
	array('SHOW_STUB' => 'Y')
);

if($isBitrix24Template)
{
	$this->EndViewTarget();
}
//endregion

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
	|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

if($isSupervisor && isset($_REQUEST['super']))
{
	$isSupervisor = strtoupper($_REQUEST['super']) === 'Y';
}

$categoryID = isset($arResult['VARIABLES']['category_id']) ? (int)$arResult['VARIABLES']['category_id'] : -1;

$pathToList = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_CATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_CATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '');

$pathToKanban = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_KANBANCATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_KANBANCATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_KANBAN']) ? $arResult['PATH_TO_DEAL_KANBAN'] : '');

$pathToCalendar = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_CALENDARCATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_CALENDARCATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_CALENDAR']) ? $arResult['PATH_TO_DEAL_CALENDAR'] : '');

$pathToWidget = $categoryID >= 0 && isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY'])
	? CComponentEngine::makePathFromTemplate(
		$arResult['PATH_TO_DEAL_WIDGETCATEGORY'],
		array('category_id' => $categoryID))
	: (isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '');

$contextData = array();
$filterExtras = array();
if($categoryID >= 0)
{
	$contextData = array('dealCategoryID' => $categoryID);
	$filterExtras = array('dealCategoryID' => '?');
}

$rowData = array(
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'title' => GetMessage('CRM_DEAL_WGT_FUNNEL'),
						'typeName' => 'funnel',
						'entityTypeName' => CCrmOwnerType::DealName,
						'filter' => array('extras' => $filterExtras)
					)
				)
			),
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'layout' => 'tiled',
						'configs' => array(
							array(
								'name' => 'sum1',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_DEAL_OVERALL'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => $filterExtras,
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
							array(
								'name' => 'sum2',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_DEAL_WON'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
							array(
								'name' => 'diff',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_DEAL_IN_WORK'),
								'dataSource' => array(
									'name' => 'EXPRESSION',
									'operation' => 'diff',
									'arguments' => array('%sum1%', '%sum2%')
								),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							)
						)
					)
				)
			)
		)
	),
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'layout' => 'tiled',
						'configs' => array(
							array(
								'name' => 'sum1',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_INVOICE_OVERALL'),
								'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_INVOICE_STATS',
								'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
								'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
							array(
								'name' => 'sum2',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_INVOICE_OWED'),
								'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_OWED_SUM',
								'dataSource' => 'DEAL_INVOICE_STATS',
								'select' => array('name' => 'TOTAL_OWED', 'aggregate' => 'SUM'),
								'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
							array(
								'name' => 'sum3',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_DEAL_WON'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							)
						)
					)
				)
			),
			array(
				'controls' => array(
					array(
						'title' => GetMessage('CRM_DEAL_WGT_PAYMENT_CONTROL'),
						'typeName' => 'bar',
						'group' => 'DATE',
						'context' => 'F',
						'combineData' => 'Y',
						'enableStack' => 'Y',
						'format' => array('isCurrency' => 'Y'),
						'configs' => array(
							array(
								'name' => 'sum1',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_INVOICE_OVERALL'),
								'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_INVOICE_STATS',
								'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
								'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
								'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'green')
							),
							array(
								'name' => 'sum2',
								'title' => GetMessage('CRM_DEAL_WGT_SUM_INVOICE_OWED'),
								'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_OWED_SUM',
								'dataSource' => 'DEAL_INVOICE_STATS',
								'select' => array('name' => 'TOTAL_OWED', 'aggregate' => 'SUM'),
								'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
								'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'red')
							)
						)
					)
				)
			)
		)
	),
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'title' => GetMessage('CRM_DEAL_WGT_DEAL_IN_WORK'),
						'typeName' => 'graph',
						'group' => 'DATE',
						'context' => 'E',
						'combineData' => 'Y',
						'configs' => array(
							array(
								'name' => 'qty1',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_DEAL_IN_WORK'),
								'dataPreset' => 'DEAL_IN_WORK::OVERALL_COUNT',
								'dataSource' => 'DEAL_IN_WORK',
								'select' => array('name' => 'COUNT'),
								'filter' => $filterExtras
							),
							array(
								'name' => 'qty2',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_CALL'),
								'dataPreset' => 'DEAL_ACTIVITY_STATS::CALL_OVERALL_COUNT',
								'dataSource' => 'DEAL_ACTIVITY_STATS',
								'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
								'filter' => $filterExtras
							),
							array(
								'name' => 'qty3',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_ACTIVITY'),
								'dataPreset' => 'DEAL_ACTIVITY_STATS::OVERALL_COUNT',
								'dataSource' => 'DEAL_ACTIVITY_STATS',
								'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM'),
								'filter' => $filterExtras
							)
						)
					)
				)
			),
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'layout' => 'tiled',
						'configs' => array(
							array(
								'name' => 'qty1',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_DEAL_IN_WORK'),
								'dataPreset'=> 'DEAL_IN_WORK::OVERALL_COUNT',
								'dataSource' => 'DEAL_IN_WORK',
								'select' => array('name' => 'COUNT'),
								'filter' => $filterExtras
							),
							array(
								'name' => 'qty2',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_ACTIVITY'),
								'dataPreset' => 'DEAL_ACTIVITY_STATS::OVERALL_COUNT',
								'dataSource' => 'DEAL_ACTIVITY_STATS',
								'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM'),
								'filter' => $filterExtras
							),
							array(
								'name' => 'qty3',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_CALL'),
								'dataPreset' => 'DEAL_ACTIVITY_STATS::CALL_OVERALL_COUNT',
								'dataSource' => 'DEAL_ACTIVITY_STATS',
								'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
								'filter' => $filterExtras
							)
						)
					)
				)
			)
		)
	),
	array(
		'height' => 180,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'configs' => array(
							array(
								'name' => 'qty1',
								'title' => GetMessage('CRM_DEAL_WGT_QTY_DEAL_IDLE'),
								'dataPreset' => 'DEAL_IDLE::OVERALL_COUNT',
								'dataSource' => 'DEAL_IDLE',
								'select' => array('name' => 'COUNT'),
								'filter' => $filterExtras
							)
						)
					)
				)
			)
		)
	)
);

if($isSupervisor)
{
	array_splice(
		$rowData,
		1,
		0,
		array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => GetMessage('CRM_DEAL_WGT_EMPLOYEE_DEAL_IN_WORK'),
								'typeName' => 'bar',
								'group' => 'USER',
								'context' => 'E',
								'combineData' => 'Y',
								'enableStack' => 'N',
								'integersOnly' => 'Y',
								'configs' => array(
									array(
										'name' => 'qty1',
										'title' => GetMessage('CRM_DEAL_WGT_QTY_DEAL_IN_WORK'),
										'dataPreset' => 'DEAL_IN_WORK::OVERALL_COUNT',
										'dataSource' => 'DEAL_IN_WORK',
										'select' => array('name' => 'COUNT'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty2',
										'title' => GetMessage('CRM_DEAL_WGT_QTY_CALL'),
										'dataPreset' => 'DEAL_ACTIVITY_STATS::CALL_OVERALL_COUNT',
										'dataSource' => 'DEAL_ACTIVITY_STATS',
										'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty3',
										'title' => GetMessage('CRM_DEAL_WGT_QTY_MEETING'),
										'dataPreset' => 'DEAL_ACTIVITY_STATS::MEETING_OVERALL_COUNT',
										'dataSource' => 'DEAL_ACTIVITY_STATS',
										'select' => array('name' => 'MEETING_QTY', 'aggregate' => 'SUM'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty4',
										'title' => GetMessage('CRM_DEAL_WGT_QTY_EMAIL'),
										'dataPreset' => 'DEAL_ACTIVITY_STATS::EMAIL_OVERALL_COUNT',
										'dataSource' => 'DEAL_ACTIVITY_STATS',
										'select' => array('name' => 'EMAIL_QTY', 'aggregate' => 'SUM'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty5',
										'title' => GetMessage('CRM_DEAL_WGT_QTY_DEAL_WON'),
										'dataPreset' => 'DEAL_SUM_STATS::OVERALL_COUNT',
										'dataSource' => 'DEAL_SUM_STATS',
										'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
										'filter' => array_merge($filterExtras, array('semanticID' => 'S'))
									)
								)
							)
						)
					)
				)
			)
		)
	);
}
else
{
	array_splice(
		$rowData,
		1,
		0,
		array(
			array(
				'height' => 180,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => GetMessage('CRM_DEAL_WGT_RATING'),
								'typeName' => 'rating',
								'group' => 'USER',
								'nominee' => CCrmSecurityHelper::GetCurrentUserID(),
								'configs' => array(
									array(
										'name' => 'sum1',
										'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S'),
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
								)
							)
						)
					)
				)
			)
		)
	);
}

?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject(Bitrix\Crm\Category\DealCategory::getJavaScriptInfos())?>;
			BX.CrmDealWidgetFactory.messages =
			{
				notSelected: "<?=GetMessageJS('CRM_DEAL_WGT_NO_SELECTED')?>",
				current: "<?=GetMessageJS('CRM_DEAL_WGT_CURRENT')?>",
				categoryConfigParamCaption: "<?=GetMessageJS('CRM_DEAL_WGT_DEAL_CATEGORY')?>"
			};
			BX.CrmWidgetManager.getCurrent().registerFactory(
				BX.CrmEntityType.names.deal,
				BX.CrmDealWidgetFactory.create(BX.CrmEntityType.names.deal, {})
			);
		}
	);
</script><?
?><div class="bx-crm-view"><?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => $categoryID >= 0 ? 'deal_category_widget' : 'deal_widget',
			'ENTITY_TYPE' => 'DEAL',
			'LAYOUT' => 'L50R50',
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'CONTEXT_DATA' => $contextData,
			'PATH_TO_LIST' => $pathToList,
			'PATH_TO_WIDGET' => $pathToWidget,
			'PATH_TO_KANBAN' => $pathToKanban,
			'PATH_TO_CALENDAR' => $pathToCalendar,
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'NAVIGATION_COUNTER_ID' => CCrmUserCounter::CurrentDealActivies,
			'DEMO_TITLE' => GetMessage('CRM_DEAL_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => GetMessage(
				'CRM_DEAL_WGT_DEMO_CONTENT',
				array(
					'#URL#' => CCrmOwnerType::GetEditUrl(CCrmOwnerType::Deal, 0, false),
					'#CLASS_NAME#' => 'crm-widg-white-link'
				)
			)
		)
	);
?></div>