<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('CRM_INVOICE_WGT_PAGE_TITLE_SHORT'));
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'INVOICE_WIDGET',
		'ACTIVE_ITEM_ID' => 'INVOICE',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
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

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
	|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

if($isSupervisor && isset($_REQUEST['super']))
{
	$isSupervisor = strtoupper($_REQUEST['super']) === 'Y';
}

$rowData = array(
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'title' => GetMessage('CRM_INVOICE_WGT_FUNNEL'),
						'typeName' => 'funnel',
						'entityTypeName' => CCrmOwnerType::InvoiceName
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
								'name' => 'sum_inwork',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_IN_WORK'),
								'dataPreset'=> 'INVOICE_IN_WORK::OVERALL_SUM',
								'dataSource' => 'INVOICE_IN_WORK',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
							array(
								'name' => 'sum_success',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_SUCCESSFUL'),
								'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'INVOICE_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
							array(
								'name' => 'sum_owed',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_OWED'),
								'dataPreset'=> 'INVOICE_IN_WORK::OVERALL_SUM_OWED',
								'dataSource' => 'INVOICE_IN_WORK',
								'select' => array('name' => 'SUM_OWED_TOTAL', 'aggregate' => 'SUM'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
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
						'configs' => array(
							array(
								'name' => 'qty_overdue',
								'title' => GetMessage('CRM_INVOICE_WGT_QTY_INVOICE_OVERDUE'),
								'dataPreset' => 'INVOICE_OVERDUE::OVERALL_COUNT',
								'dataSource' => 'INVOICE_OVERDUE',
								'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
								'display' => array('colorScheme' => 'yellow')
							)
						)
					),
					array(
						'typeName' => 'number',
						'configs' => array(
							array(
								'name' => 'sum_overdue',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_OVERDUE'),
								'dataPreset' => 'INVOICE_OVERDUE::OVERALL_SUM',
								'dataSource' => 'INVOICE_OVERDUE',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
								'display' => array('colorScheme' => 'green')
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
						'title' => GetMessage('CRM_INVOICE_WGT_INVOCE_PAYMENT'),
						'typeName' => 'graph',
						'group' => 'DATE',
						'context' => 'F',
						'combineData' => 'Y',
						'configs' => array(
							array(
								'name' => 'sum_in_work',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_IN_WORK'),
								'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'INVOICE_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
							),
							array(
								'name' => 'sum_successful',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_SUCCESSFUL'),
								'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'INVOICE_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S')
							),
							array(
								'name' => 'sum_overdue',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_OVERDUE'),
								'dataPreset' => 'INVOICE_OVERDUE::OVERALL_SUM',
								'dataSource' => 'INVOICE_OVERDUE',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
							),
							array(
								'name' => 'sum_owed',
								'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_OWED'),
								'dataPreset'=> 'INVOICE_IN_WORK::OVERALL_SUM_OWED',
								'dataSource' => 'INVOICE_IN_WORK',
								'select' => array('name' => 'SUM_OWED_TOTAL', 'aggregate' => 'SUM')
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
						'title' => GetMessage('CRM_INVOICE_WGT_RATING'),
						'typeName' => 'rating',
						'group' => 'USER',
						'nominee' => $currentUserID,
						'configs' => array(
							array(
								'name' => 'sum_payed',
								'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'INVOICE_SUM_STATS',
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
);

if($isSupervisor)
{
	$rowData = array_merge(
		$rowData,
		array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => GetMessage('CRM_INVOICE_WGT_INVOCE_MANAGER'),
								'typeName' => 'bar',
								'group' => 'USER',
								'context' => 'F',
								'combineData' => 'Y',
								'configs' => array(
									array(
										'name' => 'sum_in_work',
										'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_IN_WORK'),
										'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'INVOICE_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name' => 'sum_successful',
										'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_SUCCESSFUL'),
										'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'INVOICE_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S')
									),
									array(
										'name' => 'sum_overdue',
										'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_OVERDUE'),
										'dataPreset' => 'INVOICE_OVERDUE::OVERALL_SUM',
										'dataSource' => 'INVOICE_OVERDUE',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name' => 'sum_owed',
										'title' => GetMessage('CRM_INVOICE_WGT_SUM_INVOICE_OWED'),
										'dataPreset'=> 'INVOICE_IN_WORK::OVERALL_SUM',
										'dataSource' => 'INVOICE_IN_WORK',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'P')
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
								'title' => GetMessage('CRM_INVOICE_WGT_DEAL_PAYMENT_CONTROL'),
								'entityTypeName' => CCrmOwnerType::DealName,
								'typeName' => 'bar',
								'group' => 'USER',
								'context' => 'F',
								'combineData' => 'Y',
								'enableStack' => 'Y',
								'format' => array('isCurrency' => 'Y'),
								'configs' => array(
									array(
										'name' => 'sum_total',
										'title' => GetMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OVERALL'),
										'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_INVOICE_STATS',
										'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S'),
										'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'green')
									),
									array(
										'name' => 'sum_owed',
										'title' => GetMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OWED'),
										'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_OWED_SUM',
										'dataSource' => 'DEAL_INVOICE_STATS',
										'select' => array('name' => 'TOTAL_OWED', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S'),
										'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'red')
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
	$rowData[1]['cells'][] = array(
		'controls' => array(
			array(
				'title' => GetMessage('CRM_INVOICE_WGT_DEAL_PAYMENT_CONTROL'),
				'entityTypeName' => CCrmOwnerType::DealName,
				'typeName' => 'bar',
				'group' => 'DATE',
				'context' => 'F',
				'combineData' => 'Y',
				'enableStack' => 'Y',
				'format' => array('isCurrency' => 'Y'),
				'configs' => array(
					array(
						'name' => 'sum_total',
						'title' => GetMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OVERALL'),
						'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
						'dataSource' => 'DEAL_INVOICE_STATS',
						'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
						'filter' => array('semanticID' => 'S'),
						'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'green')
					),
					array(
						'name' => 'sum_owed',
						'title' => GetMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OWED'),
						'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_OWED_SUM',
						'dataSource' => 'DEAL_INVOICE_STATS',
						'select' => array('name' => 'TOTAL_OWED', 'aggregate' => 'SUM'),
						'filter' => array('semanticID' => 'S'),
						'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'red')
					)
				)
			)
		)
	);
}

?><div class="bx-crm-view"><?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => 'invoice_widget',
			'ENTITY_TYPES' => array(CCrmOwnerType::InvoiceName, CCrmOwnerType::DealName),
			'LAYOUT' => 'L50R50',
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'PATH_TO_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_WIDGET' => isset($arResult['PATH_TO_INVOICE_WIDGET']) ? $arResult['PATH_TO_INVOICE_WIDGET'] : '',
			'PATH_TO_KANBAN' => isset($arResult['PATH_TO_INVOICE_KANBAN']) ? $arResult['PATH_TO_INVOICE_KANBAN'] : '',
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'NAVIGATION_COUNTER' => CCrmInvoice::GetCounterValue(),
			'DEMO_TITLE' => GetMessage('CRM_INVOICE_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => GetMessage(
				'CRM_INVOICE_WGT_DEMO_CONTENT',
				array(
					'#URL#' => CCrmOwnerType::GetEditUrl(CCrmOwnerType::Invoice, 0, false),
					'#CLASS_NAME#' => 'crm-widg-white-link'
				)
			)
		)
	);
?></div>