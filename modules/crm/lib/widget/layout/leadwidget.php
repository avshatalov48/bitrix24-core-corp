<?php

namespace Bitrix\Crm\Widget\Layout;

use Bitrix\Main\Localization\Loc;

class LeadWidget
{
	public static function getDefaultRows(array $params = [])
	{
		$isSupervisor = $params['isSupervisor'] === true;
		$rowData = array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_LEAD_WGT_FUNNEL'),
								'typeName' => 'funnel',
								'entityTypeName' => \CCrmOwnerType::LeadName
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_LEAD_WGT_SOURCE'),
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
										'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_IN_WORK'),
										'dataPreset'=> 'LEAD_IN_WORK::OVERALL_COUNT',
										'dataSource' => 'LEAD_IN_WORK',
										'select' => array('name' => 'COUNT')
									),
									array(
										'name' => 'qty_success',
										'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_SUCCESSFUL'),
										'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
										'dataSource' => 'LEAD_CONV_STATS',
										'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
									),
									array(
										'name' => 'qty_fail',
										'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_FAILED'),
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
										'title' => Loc::getMessage('CRM_LEAD_WGT_CONVERSION_SUCCESS'),
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
										'title' => Loc::getMessage('CRM_LEAD_WGT_CONVERSION_FAIL'),
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
								'title' => Loc::getMessage('CRM_LEAD_WGT_CONVERSION_SUCCESS'),
								'typeName' => 'graph',
								'group' => 'DATE',
								'context' => 'P',
								'configs' => array(
									array(
										'name' => 'rate_success',
										'title' => Loc::getMessage('CRM_LEAD_WGT_CONVERSION_SUCCESS'),
										'dataPreset' => 'LEAD_CONV_RATE::SUCCESS',
										'dataSource' => 'LEAD_CONV_RATE',
										'select' => array('name' => 'SUCCESS')
									),
									array(
										'name' => 'rate_fail',
										'title' => Loc::getMessage('CRM_LEAD_WGT_CONVERSION_FAIL'),
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
								'title' => Loc::getMessage('CRM_LEAD_WGT_RATING'),
								'typeName' => 'rating',
								'group' => 'USER',
								'nominee' => \CCrmSecurityHelper::GetCurrentUserID(),
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
										'title' => Loc::getMessage('CRM_LEAD_WGT_EMPLOYEE_LEAD_PROC'),
										'typeName' => 'bar',
										'group' => 'USER',
										'context' => 'E',
										'combineData' => 'Y',
										'enableStack' => 'N',
										'integersOnly' => 'Y',
										'configs' => array(
											array(
												'name' => 'qty_inwork',
												'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_IN_WORK'),
												'dataPreset' => 'LEAD_IN_WORK::OVERALL_COUNT',
												'dataSource' => 'LEAD_IN_WORK',
												'select' => array('name' => 'COUNT')
											),
											array(
												'name' => 'qty_activity',
												'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_ACTIVITY'),
												'dataPreset' => 'LEAD_ACTIVITY_STATS::OVERALL_COUNT',
												'dataSource' => 'LEAD_ACTIVITY_STATS',
												'select' => array('name' => 'TOTAL', 'aggregate' => 'SUM')
											),
											array(
												'name' => 'qty_success',
												'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_SUCCESSFUL'),
												'dataPreset' => 'LEAD_CONV_STATS::OVERALL_COUNT',
												'dataSource' => 'LEAD_CONV_STATS',
												'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
											),
											array(
												'name' => 'qty_fail',
												'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_FAILED'),
												'dataPreset' => 'LEAD_JUNK::OVERALL_COUNT',
												'dataSource' => 'LEAD_JUNK',
												'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
											),
											array(
												'name' => 'qty_idle',
												'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_IDLE'),
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
										'title' => Loc::getMessage('CRM_LEAD_WGT_QTY_LEAD_IDLE'),
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

		return $rowData;
	}

	public static function getDemoTitle()
	{
		return GetMessage('CRM_LEAD_WGT_DEMO_TITLE');
	}

	public static function getDemoContent()
	{
		return GetMessage(
			'CRM_LEAD_WGT_DEMO_CONTENT',
			array(
				'#URL#' => \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Lead, 0, false),
				'#CLASS_NAME#' => 'crm-widg-white-link'
			)
		);
	}

}