<?php

namespace Bitrix\Crm\Widget\Layout;

use Bitrix\Main\Localization\Loc;

class StartCrmWidget
{
	public static function getDefaultRows(array $params = [])
	{
		$isSupervisor = ($params['isSupervisor'] ?? false);
		$showSaleTarget = ($params['showSaleTarget'] ?? false);

		$rowData = array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'entityTypeName' => \CCrmOwnerType::ActivityName,
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'activity_control',
										'title' => Loc::getMessage("CRM_CH_TRACKER_WGT_ACTIVITY_DYNAMIC"),
										'dataPreset' => 'ACTIVITY_DYNAMIC::ALL',
										'dataSource' => 'ACTIVITY_DYNAMIC',
										'display' => array(
											'colorScheme' => 'white'
										)
									)
								),
								'filter' => array('periodType' => 'D0')
							)
						)
					)
				)
			),
			array(
				"height" => 380,
				"cells" => array(
					array(
						"controls" => array(
							array(
								"typeName" => "graph",
								"group" => "DATE",
								"combineData" => "Y",
								"configs" => array(
									array(
										"name" => "param1",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_COMMUNICATION_DYNAMIC_TELEPHONY"),
										"display" => array(
											"colorScheme" => "blue"
										),
										"dataPreset" => "ACTIVITY_STATS::VOXIMPLANT_CALL:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT"
										)
									),
									array(
										"name" => "param2",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_COMMUNICATION_DYNAMIC_EMAIL"),
										"display" => array(
											"colorScheme" => "green"),
										"dataPreset" => "ACTIVITY_STATS::CRM_EMAIL:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT"
										)
									),
									array(
										"name" => "param3",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_COMMUNICATION_DYNAMIC_OPENLINES"),
										"display" => array(
											"colorScheme" => "yellow"
										),
										"dataPreset" => "ACTIVITY_STATS::IMOPENLINES_SESSION:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY", "aggregate" => "COUNT",)
									),
									array(
										"name" => "param4",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_COMMUNICATION_DYNAMIC_FORM"),
										"display" => array(
											"colorScheme" => "red",
										),
										"dataPreset" => "ACTIVITY_STATS::CRM_WEBFORM:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT",
										)
									)
								),
								"entityTypeName" => "ACTIVITY",
								"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_COMMUNICATION_DYNAMIC"),
								"filter" => array(
									"periodType" => ""
								),
								"display" => array(
									"graph" => array(
										"type" => "line"
									)
								),
								"context" => "E",
							)
						),
					),
					array(
						"controls" => array(
							array(
								"typeName" => "bar",
								"group" => "USER",
								"combineData" => "Y",
								"enableStack" => "N",
								"configs" => array(
									array(
										"name" => "param1",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_PERSONAL_LOAD_TELEPHONY"),
										"display" => array(
											"colorScheme" => "blue",
										),
										"dataPreset" => "ACTIVITY_STATS::VOXIMPLANT_CALL:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT",
										)
									),
									array(
										"name" => "param2",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_PERSONAL_LOAD_EMAIL"),
										"display" => array(
											"colorScheme" => "green",
										),
										"dataPreset" => "ACTIVITY_STATS::CRM_EMAIL:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT",
										)
									),
									array(
										"name" => "param3",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_PERSONAL_LOAD_OPENLINES"),
										"display" => array(
											"colorScheme" => "yellow",
										),
										"dataPreset" => "ACTIVITY_STATS::IMOPENLINES_SESSION:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT",
										)
									),
									array(
										"name" => "param4",
										"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_PERSONAL_LOAD_FORM"),
										"display" => array(
											"colorScheme" => "red",
										),
										"dataPreset" => "ACTIVITY_STATS::CRM_WEBFORM:*:TOTAL_QTY",
										"dataSource" => "ACTIVITY_STATS",
										"select" => array(
											"name" => "TOTAL_QTY",
											"aggregate" => "COUNT",
										)
									)
								),
								"entityTypeName" => "ACTIVITY",
								"title" => Loc::getMessage("CRM_CH_TRACKER_WGT_PERSONAL_LOAD"),
								"filter" => array(
									"periodType" => ""
								),
								"context" => "E",
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
								'entityTypeName' => \CCrmOwnerType::Lead,
								'typeName' => 'number',
								'configs' => array(
									array(
										'name' => 'client_success1',
										'title' => Loc::getMessage("CRM_CH_TRACKER_WGT_NEW_CLIENTS"),
										'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
										'dataSource' => 'LEAD_CONV_STATS',
										'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
										'display' => array(
											'colorScheme' => 'blue'
										)
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'entityTypeName' => \CCrmOwnerType::Undefined,
								'typeName' => 'number',
								'configs' => array(
									array(
										'name' => 'client_success1',
										'title' => Loc::getMessage("CRM_CH_TRACKER_WGT_NEW_CLIENTS_PER_DAY"),
										'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
										'dataSource' => 'LEAD_CONV_STATS',
										'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT'),
										'display' => array(
											'colorScheme' => 'blue'
										)
									)
								),
								'filter' => array('periodType' => 'D0')
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
								'entityTypeName' => \CCrmOwnerType::ActivityName,
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'activity_manager_counters',
										'title' => Loc::getMessage("CRM_CH_TRACKER_WGT_MANAGERS_FAILS"),
										'dataPreset' => 'activity_manager_counters::ALL',
										'dataSource' => 'activity_manager_counters',
										'display' => array(
											'colorScheme' => 'white'
										)
									)
								)
							)
						)
					)
				)
			)
		);

		if ($showSaleTarget)
		{
			$rowData[] = array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage("CRM_CH_TRACKER_WGT_SALE_TARGET_NAME"),
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
		}

		if($isSupervisor)
		{
			$rowData[] = array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'entityTypeName' => \CCrmOwnerType::DealName,
								'typeName' => 'bar',
								'title' => Loc::getMessage("CRM_CH_TRACKER_WGT_MANAGERS_SUCCESSES"),
								'group' => 'USER',
								'context' => 'F',
								'combineData' => 'Y',
								'enableStack' => 'Y',
								'enableAvatar' => 'Y',
								'format' => array('isCurrency' => 'Y'),
								'configs' => array(
									array(
										'name' => 'deal_success',
										'title' => Loc::getMessage('CRM_CH_TRACKER_WGT_DEAL_SUCCESS_SUM'),
										'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S'),
										'display' => array(
											'colorScheme' => 'green',
											'graph' => array(
												'clustered' => 'Y'
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
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'entityTypeName' => \CCrmOwnerType::DealName,
								'typeName' => 'rating',
								'title' => Loc::getMessage('CRM_CH_TRACKER_WGT_RATING_BY_SUCCESSFUL_DEALS'),
								'group' => 'USER',
								'nominee' => \CCrmSecurityHelper::GetCurrentUserID(),
								'configs' => array(
									array(
										'name' => 'deal_success',
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
			);
		}
		return $rowData;
	}

	public static function getDemoTitle()
	{
		return Loc::getMessage('CRM_CH_TRACKER_WGT_DEMO_TITLE');
	}
}