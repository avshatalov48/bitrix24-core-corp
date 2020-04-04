<?php

namespace Bitrix\Crm\Widget\Layout;

use Bitrix\Main\Localization\Loc;

class InvoiceWidget
{
	public static function getDefaultRows(array $params = [])
	{
		$isSupervisor = $params['isSupervisor'];

		$rowData = array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_INVOICE_WGT_FUNNEL'),
								'typeName' => 'funnel',
								'entityTypeName' => \CCrmOwnerType::InvoiceName
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
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_IN_WORK'),
										'dataPreset'=> 'INVOICE_IN_WORK::OVERALL_SUM',
										'dataSource' => 'INVOICE_IN_WORK',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
									array(
										'name' => 'sum_success',
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_SUCCESSFUL'),
										'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'INVOICE_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S'),
										'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									),
									array(
										'name' => 'sum_owed',
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_OWED'),
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
										'title' => Loc::getMessage('CRM_INVOICE_WGT_QTY_INVOICE_OVERDUE'),
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
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_OVERDUE'),
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
								'title' => Loc::getMessage('CRM_INVOICE_WGT_INVOCE_PAYMENT'),
								'typeName' => 'graph',
								'group' => 'DATE',
								'context' => 'F',
								'combineData' => 'Y',
								'configs' => array(
									array(
										'name' => 'sum_in_work',
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_IN_WORK'),
										'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'INVOICE_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name' => 'sum_successful',
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_SUCCESSFUL'),
										'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
										'dataSource' => 'INVOICE_SUM_STATS',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'filter' => array('semanticID' => 'S')
									),
									array(
										'name' => 'sum_overdue',
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_OVERDUE'),
										'dataPreset' => 'INVOICE_OVERDUE::OVERALL_SUM',
										'dataSource' => 'INVOICE_OVERDUE',
										'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name' => 'sum_owed',
										'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_OWED'),
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
								'title' => Loc::getMessage('CRM_INVOICE_WGT_RATING'),
								'typeName' => 'rating',
								'group' => 'USER',
								'nominee' => \CCrmSecurityHelper::GetCurrentUserID(),
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
										'title' => Loc::getMessage('CRM_INVOICE_WGT_INVOCE_MANAGER'),
										'typeName' => 'bar',
										'group' => 'USER',
										'context' => 'F',
										'combineData' => 'Y',
										'configs' => array(
											array(
												'name' => 'sum_in_work',
												'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_IN_WORK'),
												'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
												'dataSource' => 'INVOICE_SUM_STATS',
												'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
											),
											array(
												'name' => 'sum_successful',
												'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_SUCCESSFUL'),
												'dataPreset' => 'INVOICE_SUM_STATS::OVERALL_SUM',
												'dataSource' => 'INVOICE_SUM_STATS',
												'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
												'filter' => array('semanticID' => 'S')
											),
											array(
												'name' => 'sum_overdue',
												'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_OVERDUE'),
												'dataPreset' => 'INVOICE_OVERDUE::OVERALL_SUM',
												'dataSource' => 'INVOICE_OVERDUE',
												'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')
											),
											array(
												'name' => 'sum_owed',
												'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_INVOICE_OWED'),
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
										'title' => Loc::getMessage('CRM_INVOICE_WGT_DEAL_PAYMENT_CONTROL'),
										'entityTypeName' => \CCrmOwnerType::DealName,
										'typeName' => 'bar',
										'group' => 'USER',
										'context' => 'F',
										'combineData' => 'Y',
										'enableStack' => 'Y',
										'format' => array('isCurrency' => 'Y'),
										'configs' => array(
											array(
												'name' => 'sum_total',
												'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OVERALL'),
												'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
												'dataSource' => 'DEAL_INVOICE_STATS',
												'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
												'filter' => array('semanticID' => 'S'),
												'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'green')
											),
											array(
												'name' => 'sum_owed',
												'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OWED'),
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
						'title' => Loc::getMessage('CRM_INVOICE_WGT_DEAL_PAYMENT_CONTROL'),
						'entityTypeName' => \CCrmOwnerType::DealName,
						'typeName' => 'bar',
						'group' => 'DATE',
						'context' => 'F',
						'combineData' => 'Y',
						'enableStack' => 'Y',
						'format' => array('isCurrency' => 'Y'),
						'configs' => array(
							array(
								'name' => 'sum_total',
								'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OVERALL'),
								'dataPreset' => 'DEAL_INVOICE_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_INVOICE_STATS',
								'select' => array('name' => 'TOTAL_INVOICE_SUM', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S'),
								'display' => array('graph'=> array('clustered' => 'N'), 'colorScheme' => 'green')
							),
							array(
								'name' => 'sum_owed',
								'title' => Loc::getMessage('CRM_INVOICE_WGT_SUM_DEAL_INVOICE_OWED'),
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
		return $rowData;
	}

	public static function getDemoTitle()
	{
		return Loc::getMessage('CRM_INVOICE_WGT_DEMO_TITLE');
	}

	public static function getDemoContent()
	{
		return Loc::getMessage(
			'CRM_INVOICE_WGT_DEMO_CONTENT',
			array(
				'#URL#' => \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Invoice, 0, false),
				'#CLASS_NAME#' => 'crm-widg-white-link'
			)
		);
	}
}