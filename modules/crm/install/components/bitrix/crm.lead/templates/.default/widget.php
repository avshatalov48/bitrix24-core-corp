<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

//show the crm type popup (with or without leads)
if (!\Bitrix\Crm\Settings\LeadSettings::isEnabled())
{
	CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
	?><script><?=\Bitrix\Crm\Settings\LeadSettings::showCrmTypePopup();?></script><?
}

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('CRM_LEAD_WGT_PAGE_TITLE_SHORT'));
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'DEAL_WIDGET',
		'ACTIVE_ITEM_ID' => 'LEAD',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_DEAL_WIDGET' => isset($arResult['PATH_TO_DEAL_WIDGET']) ? $arResult['PATH_TO_DEAL_WIDGET'] : '',
		'PATH_TO_DEAL_INDEX' => isset($arResult['PATH_TO_DEAL_INDEX']) ? $arResult['PATH_TO_DEAL_INDEX'] : '',
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

$rowData = array(
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'title' => GetMessage('CRM_LEAD_WGT_FUNNEL'),
						'typeName' => 'funnel',
						'entityTypeName' => CCrmOwnerType::LeadName
					)
				)
			),
			array(
				'controls' => array(
					array(
						'title' => GetMessage('CRM_LEAD_WGT_SOURCE'),
						'typeName' => 'pie',
						'group' => 'SOURCE',
						'configs' => array(
							array(
								'name' => 'source_qty',
								'dataPreset' => 'LEAD_SUM_STATS::OVERALL_COUNT',
								'dataSource' => 'LEAD_SUM_STATS',
								'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
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
								'name' => 'qty_inwork',
								'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_IN_WORK'),
								'dataPreset'=> 'LEAD_IN_WORK::OVERALL_COUNT',
								'dataSource' => 'LEAD_IN_WORK',
								'select' => array('name' => 'COUNT')
							),
							array(
								'name' => 'qty_success',
								'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_SUCCESSFUL'),
								'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
								'dataSource' => 'LEAD_CONV_STATS',
								'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
							),
							array(
								'name' => 'qty_fail',
								'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_FAILED'),
								'dataPreset' => 'LEAD_JUNK::OVERALL_COUNT',
								'dataSource' => 'LEAD_JUNK',
								'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
							)
						)
					)
				)
			),
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'configs' => array(
							array(
								'name' => 'rate_success',
								'title' => GetMessage('CRM_LEAD_WGT_CONVERSION_SUCCESS'),
								'dataPreset' => 'LEAD_CONV_RATE::SUCCESS',
								'dataSource' => 'LEAD_CONV_RATE',
								'select' => array('name' => 'SUCCESS'),
								'display' => array('colorScheme' => 'blue'),
								'format' => array('isPercent' => 'Y')
							)
						)
					),
					array(
						'typeName' => 'number',
						'configs' => array(
							array(
								'name' => 'rate_fail',
								'title' => GetMessage('CRM_LEAD_WGT_CONVERSION_FAIL'),
								'dataPreset' => 'LEAD_CONV_RATE::FAIL',
								'dataSource' => 'LEAD_CONV_RATE',
								'select' => array('name' => 'FAIL'),
								'display' => array('colorScheme' => 'red'),
								'format' => array('isPercent' => 'Y')
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
						'title' => GetMessage('CRM_LEAD_WGT_CONVERSION_SUCCESS'),
						'typeName' => 'graph',
						'group' => 'DATE',
						'context' => 'P',
						'configs' => array(
							array(
								'name' => 'rate_success',
								'title' => GetMessage('CRM_LEAD_WGT_CONVERSION_SUCCESS'),
								'dataPreset' => 'LEAD_CONV_RATE::SUCCESS',
								'dataSource' => 'LEAD_CONV_RATE',
								'select' => array('name' => 'SUCCESS')
							),
							array(
								'name' => 'rate_fail',
								'title' => GetMessage('CRM_LEAD_WGT_CONVERSION_FAIL'),
								'dataPreset' => 'LEAD_CONV_RATE::FAIL',
								'dataSource' => 'LEAD_CONV_RATE',
								'select' => array('name' => 'FAIL')
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
						'title' => GetMessage('CRM_LEAD_WGT_RATING'),
						'typeName' => 'rating',
						'group' => 'USER',
						'nominee' => CCrmSecurityHelper::GetCurrentUserID(),
						'configs' => array(
							array(
								'name' => 'qty_success',
								'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
								'dataSource' => 'LEAD_CONV_STATS',
								'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
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
	array_splice(
		$rowData,
		count($rowData) - 1,
		0,
		array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => GetMessage('CRM_LEAD_WGT_EMPLOYEE_LEAD_PROC'),
								'typeName' => 'bar',
								'group' => 'USER',
								'context' => 'E',
								'combineData' => 'Y',
								'enableStack' => 'N',
								'integersOnly' => 'Y',
								'configs' => array(
									array(
										'name' => 'qty_inwork',
										'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_IN_WORK'),
										'dataPreset' => 'LEAD_IN_WORK::OVERALL_COUNT',
										'dataSource' => 'LEAD_IN_WORK',
										'select' => array('name' => 'COUNT')
									),
									array(
										'name' => 'qty_activity',
										'title' => GetMessage('CRM_LEAD_WGT_QTY_ACTIVITY'),
										'dataPreset' => 'LEAD_ACTIVITY_STATS::OVERALL_COUNT',
										'dataSource' => 'LEAD_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name' => 'qty_success',
										'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_SUCCESSFUL'),
										'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
										'dataSource' => 'LEAD_CONV_STATS',
										'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
									),
									array(
										'name' => 'qty_fail',
										'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_FAILED'),
										'dataPreset' => 'LEAD_JUNK::OVERALL_COUNT',
										'dataSource' => 'LEAD_JUNK',
										'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
									),
									array(
										'name' => 'qty_idle',
										'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_IDLE'),
										'dataPreset' => 'LEAD_IDLE::OVERALL_COUNT',
										'dataSource' => 'LEAD_IDLE',
										'select' => array('name' => 'COUNT')
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
	$rowData[] = array(
		'height' => 180,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'configs' => array(
							array(
								'name' => 'qty_idle',
								'title' => GetMessage('CRM_LEAD_WGT_QTY_LEAD_IDLE'),
								'dataPreset' => 'LEAD_IDLE::OVERALL_COUNT',
								'dataSource' => 'LEAD_IDLE',
								'select' => array('name' => 'COUNT')
							)
						)
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
			'GUID' => 'lead_widget',
			'ENTITY_TYPE' => 'LEAD',
			'LAYOUT' => 'L50R50',
			'NAVIGATION_CONTEXT_ID' => $arResult['NAVIGATION_CONTEXT_ID'],
			'PATH_TO_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_WIDGET' => isset($arResult['PATH_TO_LEAD_WIDGET']) ? $arResult['PATH_TO_LEAD_WIDGET'] : '',
			'PATH_TO_KANBAN' => isset($arResult['PATH_TO_LEAD_KANBAN']) ? $arResult['PATH_TO_LEAD_KANBAN'] : '',
			'PATH_TO_CALENDAR' => isset($arResult['PATH_TO_LEAD_CALENDAR']) ? $arResult['PATH_TO_LEAD_CALENDAR'] : '',
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'NAVIGATION_COUNTER_ID' => CCrmUserCounter::CurrentLeadActivies,
			'DEMO_TITLE' => GetMessage('CRM_LEAD_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => GetMessage(
				'CRM_LEAD_WGT_DEMO_CONTENT',
				array(
					'#URL#' => CCrmOwnerType::GetEditUrl(CCrmOwnerType::Lead, 0, false),
					'#CLASS_NAME#' => 'crm-widg-white-link'
				)
			)
		)
	);
?></div>