<?php

namespace Bitrix\Crm\Widget\Layout;

use Bitrix\Main\Localization\Loc;

class DealWidget
{
	public static function getDefaultRows(array $params = [])
	{
		$filterExtras = $params['filterExtras'];
		$isSupervisor = $params['isSupervisor'];

		$rowData = array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_DEAL_WGT_FUNNEL'),
								'typeName' => 'funnel',
								'entityTypeName' => \CCrmOwnerType::DealName,
								//'filter' => array('extras' => $filterExtras)
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
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_DEAL_OVERALL'),
										'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										//'filter' => $filterExtras,
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
									array(
										'name' => 'sum2',
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_DEAL_WON'),
										'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										//'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
									array(
										'name' => 'diff',
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_DEAL_IN_WORK'),
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
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_INVOICE_OVERALL'),
										'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_INVOICE_STATS',
										'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
										'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
									array(
										'name' => 'sum2',
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_INVOICE_OWED'),
										'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_OWED_SUM',
										'dataSource' => 'DEAL_INVOICE_STATS',
										'select' => array('name' => 'TOTAL_OWED', 'aggregate' => 'SUM'),
										'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
									array(
										'name' => 'sum3',
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_DEAL_WON'),
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
								'title' => Loc::getMessage('CRM_DEAL_WGT_PAYMENT_CONTROL'),
								'typeName' => 'bar',
								'group' => 'DATE',
								'context' => 'F',
								'combineData' => 'Y',
								'enableStack' => 'Y',
								'format' => array('isCurrency' => 'Y'),
								'configs' => array(
									array(
										'name' => 'sum1',
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_INVOICE_OVERALL'),
										'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
										'dataSource' => 'DEAL_INVOICE_STATS',
										'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
										'filter' => array_merge($filterExtras, array('semanticID' => 'S')),
										'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'green')
									),
									array(
										'name' => 'sum2',
										'title' => Loc::getMessage('CRM_DEAL_WGT_SUM_INVOICE_OWED'),
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
								'title' => Loc::getMessage('CRM_DEAL_WGT_DEAL_IN_WORK'),
								'typeName' => 'graph',
								'group' => 'DATE',
								'context' => 'E',
								'combineData' => 'Y',
								'configs' => array(
									array(
										'name' => 'qty1',
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_DEAL_IN_WORK'),
										'dataPreset' => 'DEAL_IN_WORK::OVERALL_COUNT',
										'dataSource' => 'DEAL_IN_WORK',
										'select' => array('name' => 'COUNT'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty2',
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_CALL'),
										'dataPreset' => 'DEAL_ACTIVITY_STATS::CALL_OVERALL_COUNT',
										'dataSource' => 'DEAL_ACTIVITY_STATS',
										'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty3',
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_ACTIVITY'),
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
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_DEAL_IN_WORK'),
										'dataPreset'=> 'DEAL_IN_WORK::OVERALL_COUNT',
										'dataSource' => 'DEAL_IN_WORK',
										'select' => array('name' => 'COUNT'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty2',
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_ACTIVITY'),
										'dataPreset' => 'DEAL_ACTIVITY_STATS::OVERALL_COUNT',
										'dataSource' => 'DEAL_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM'),
										'filter' => $filterExtras
									),
									array(
										'name' => 'qty3',
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_CALL'),
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
										'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_DEAL_IDLE'),
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
										'title' => Loc::getMessage('CRM_DEAL_WGT_EMPLOYEE_DEAL_IN_WORK'),
										'typeName' => 'bar',
										'group' => 'USER',
										'context' => 'E',
										'combineData' => 'Y',
										'enableStack' => 'N',
										'integersOnly' => 'Y',
										'configs' => array(
											array(
												'name' => 'qty1',
												'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_DEAL_IN_WORK'),
												'dataPreset' => 'DEAL_IN_WORK::OVERALL_COUNT',
												'dataSource' => 'DEAL_IN_WORK',
												'select' => array('name' => 'COUNT'),
												'filter' => $filterExtras
											),
											array(
												'name' => 'qty2',
												'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_CALL'),
												'dataPreset' => 'DEAL_ACTIVITY_STATS::CALL_OVERALL_COUNT',
												'dataSource' => 'DEAL_ACTIVITY_STATS',
												'select' => array('name' => 'CALL_QTY', 'aggregate' => 'SUM'),
												'filter' => $filterExtras
											),
											array(
												'name' => 'qty3',
												'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_MEETING'),
												'dataPreset' => 'DEAL_ACTIVITY_STATS::MEETING_OVERALL_COUNT',
												'dataSource' => 'DEAL_ACTIVITY_STATS',
												'select' => array('name' => 'MEETING_QTY', 'aggregate' => 'SUM'),
												'filter' => $filterExtras
											),
											array(
												'name' => 'qty4',
												'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_EMAIL'),
												'dataPreset' => 'DEAL_ACTIVITY_STATS::EMAIL_OVERALL_COUNT',
												'dataSource' => 'DEAL_ACTIVITY_STATS',
												'select' => array('name' => 'EMAIL_QTY', 'aggregate' => 'SUM'),
												'filter' => $filterExtras
											),
											array(
												'name' => 'qty5',
												'title' => Loc::getMessage('CRM_DEAL_WGT_QTY_DEAL_WON'),
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
										'title' => Loc::getMessage('CRM_DEAL_WGT_RATING'),
										'typeName' => 'rating',
										'group' => 'USER',
										'nominee' => \CCrmSecurityHelper::GetCurrentUserID(),
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
		return $rowData;
	}

	public static function getDemoTitle()
	{
		return Loc::getMessage('CRM_DEAL_WGT_DEMO_TITLE');
	}

	public static function getDemoContent()
	{
		return Loc::getMessage(
			'CRM_DEAL_WGT_DEMO_CONTENT',
			array(
				'#URL#' => \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Deal, 0, false),
				'#CLASS_NAME#' => 'crm-widg-white-link'
			)
		);
	}
}