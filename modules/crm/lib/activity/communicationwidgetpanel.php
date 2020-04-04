<?php
namespace Bitrix\Crm\Activity;

use Bitrix\Crm\Widget\FilterPeriodType;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CommunicationWidgetPanel
{
	private static $providersTypesCache;

	public static function getRowData($entityTypeId, $isSupervisor = false)
	{
		$entityPrefix = \CCrmOwnerType::ResolveName($entityTypeId);

		$rowData = array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_COMM_WGT_GROWTH'),
								'typeName' => 'graph',
								'group' => 'DATE',
								'configs' => array(
									array(
										'name' => 'total_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_'.$entityPrefix.'_GROWTH_COUNT'),
										'dataPreset' => $entityPrefix.'_GROWTH_STATS::TOTAL_COUNT',
										'dataSource' => $entityPrefix.'_GROWTH_STATS',
										'select' => array('name' => 'TOTAL_COUNT')
									)
								),
								'filter' => array(
									'periodType' => FilterPeriodType::YEAR,
									'year' => date('Y')
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_COMM_WGT_COMMUNICATIONS'),
								'typeName' => 'pie',
								'group' => 'PROVIDER_ID',
								'configs' => array(
									array(
										'name' => 'source_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					)
				)
			)
		);

		if (Provider\OpenLine::isActive())
		{
			$openLines = self::getProviderTypes(Provider\OpenLine::className());

			foreach ($openLines as $line)
			{
				$rowData[] = array(
					'height' => 180,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'typeName' => 'number',
									'configs'  => array(
										array(
											'name'       => 'total_qty',
											'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
												'#PROVIDER_NAME#' => $line['NAME']
											)),
											'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL_QTY',
											'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
											'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
										)
									)
								)
							)
						)
					)
				);
				$rowData[] = array(
					'height' => 380,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'title'    => Loc::getMessage('CRM_COMM_WGT_STATUSES'),
									'typeName' => 'pie',
									'group'      => 'STATUS',
									'configs'  => array(
										array(
											'name'       => 'status_qty',
											'dataPreset' => $entityPrefix.'_ACTIVITY_STATUS_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL',
											'dataSource' => $entityPrefix.'_ACTIVITY_STATUS_STATS',
											'select'     => array('name' => 'TOTAL')
										)
									)
								)
							)
						)/*,
						array(
							'controls' => array(
								array(
									'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
									'typeName' => 'pie',
									'group'      => 'MARK',
									'configs'  => array(
										array(
											'name'       => 'marks_qty',
											'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL',
											'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'TOTAL')
										)
									)
								)
							)
						)*/
					)
				);
				$rowData[] = array(
					'height' => 380,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'title'        => Loc::getMessage('CRM_COMM_WGT_SOURCES'),
									'typeName'     => 'bar',
									'group'        => 'SOURCE',
									'context'      => 'E',
									'combineData'  => 'Y',
									'enableStack'  => 'N',
									'integersOnly' => 'Y',
									'configs'      => array(
										array(
											'name'       => 'qty_total',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_ALL'),
											'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL',
											'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'TOTAL', 'aggregate' => 'SUM')
										)/*,
										array(
											'name'       => 'qty_positive',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_POSITIVE'),
											'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':POSITIVE_QTY',
											'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'POSITIVE_QTY', 'aggregate' => 'SUM')
										),
										array(
											'name'       => 'qty_negative',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NEGATIVE'),
											'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':NEGATIVE_QTY',
											'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'NEGATIVE_QTY', 'aggregate' => 'SUM')
										),
										array(
											'name'       => 'qty_nomark',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NONE'),
											'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':NONE_QTY',
											'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'NONE_QTY', 'aggregate' => 'SUM')
										)*/
									)
								)
							)
						)
					)
				);
			}
		}
		else
		{
			$rowData[] = array(
				'height' => 180,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\OpenLine::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\OpenLine::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\OpenLine::getId()),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			);
		}

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'call_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Call::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Call::getId().':CALL:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
								)
							)
						)
					)
				)
			)
		);

		if (Provider\Call::isActive())
		{
			$rowData[] = array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_STREAMS'),
								'typeName' => 'pie',
								'group'      => 'STREAM',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_STREAM_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_STREAM_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
								'typeName' => 'pie',
								'group'      => 'MARK',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL')
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
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\Call::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\Call::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\Call::getId()),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			);
		}

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'meeting_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Meeting::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Meeting::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
								)
							)
						)
					)
				)
			)
		);

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'email_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Email::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Email::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'green')
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
									'name' => 'lf_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Livefeed::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Livefeed::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'yellow')
								)
							)
						)
					)
				)
			)
		);

		if (Provider\ExternalChannel::isActive())
		{
			$cells = array(array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'configs'  => array(
							array(
								'name'       => 'total_qty',
								'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
									'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
								)),
								'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\ExternalChannel::getId().':*:TOTAL_QTY',
								'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
								'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
								'display' => array('colorScheme' => 'blue')
							)
						)
					)
				)
			));

			if ($entityTypeId === \CCrmOwnerType::Company)
			{
				$cells[] = array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs'  => array(
								array(
									'name'       => 'total_sum',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
										'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_SUM_STATS::'.Provider\ExternalChannel::getId().':*:SUM_TOTAL',
									'dataSource' => $entityPrefix.'_ACTIVITY_SUM_STATS',
									'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
									'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
									'display' => array('colorScheme' => 'red')
								)
							)
						)
					)
				);
			}

			$rowData[] = array(
				'height' => 180,
				'cells'  => $cells
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
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\ExternalChannel::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\ExternalChannel::getId()),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			);
		}

		if (Provider\WebForm::isActive())
		{
			$rowData[] = array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\WebForm::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_sum',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_SUM_STATS::'.Provider\WebForm::getId().':*:SUM_TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_SUM_STATS',
										'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
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
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\WebForm::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\WebForm::getId()),
										'display' => array('colorScheme' => 'yellow')
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

	public static function getActivityRowData($isSupervisor = false)
	{
		$rowData = array();

		if (Provider\OpenLine::isActive())
		{
			$openLines = self::getProviderTypes(Provider\OpenLine::className());

			foreach ($openLines as $line)
			{
				$rowData[] = array(
					'height' => 180,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'typeName' => 'number',
									'configs'  => array(
										array(
											'name'       => 'total_qty',
											'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
												'#PROVIDER_NAME#' => $line['NAME']
											)),
											'dataPreset' => 'ACTIVITY_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL_QTY',
											'dataSource' => 'ACTIVITY_STATS',
											'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
										)
									)
								)
							)
						)
					)
				);
				$rowData[] = array(
					'height' => 380,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'title'    => Loc::getMessage('CRM_COMM_WGT_STATUSES'),
									'typeName' => 'pie',
									'group'      => 'STATUS',
									'configs'  => array(
										array(
											'name'       => 'status_qty',
											'dataPreset' => 'ACTIVITY_STATUS_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL',
											'dataSource' => 'ACTIVITY_STATUS_STATS',
											'select'     => array('name' => 'TOTAL')
										)
									)
								)
							)
						)/*,
						array(
							'controls' => array(
								array(
									'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
									'typeName' => 'pie',
									'group'      => 'MARK',
									'configs'  => array(
										array(
											'name'       => 'marks_qty',
											'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL',
											'dataSource' => 'ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'TOTAL')
										)
									)
								)
							)
						)*/
					)
				);
				$rowData[] = array(
					'height' => 380,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'title'        => Loc::getMessage('CRM_COMM_WGT_SOURCES'),
									'typeName'     => 'bar',
									'group'        => 'SOURCE',
									'context'      => 'E',
									'combineData'  => 'Y',
									'enableStack'  => 'N',
									'integersOnly' => 'Y',
									'configs'      => array(
										array(
											'name'       => 'qty_total',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_ALL'),
											'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL',
											'dataSource' => 'ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'TOTAL', 'aggregate' => 'SUM')
										)/*,
										array(
											'name'       => 'qty_positive',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_POSITIVE'),
											'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':POSITIVE_QTY',
											'dataSource' => 'ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'POSITIVE_QTY', 'aggregate' => 'SUM')
										),
										array(
											'name'       => 'qty_negative',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NEGATIVE'),
											'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':NEGATIVE_QTY',
											'dataSource' => 'ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'NEGATIVE_QTY', 'aggregate' => 'SUM')
										),
										array(
											'name'       => 'qty_nomark',
											'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NONE'),
											'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':NONE_QTY',
											'dataSource' => 'ACTIVITY_MARK_STATS',
											'select'     => array('name' => 'NONE_QTY', 'aggregate' => 'SUM')
										)*/
									)
								)
							)
						)
					)
				);
			}
		}
		else
		{
			$rowData[] = array(
				'height' => 180,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\OpenLine::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\OpenLine::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\OpenLine::getId()),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			);
		}

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'call_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Call::getName()
									)),
									'dataPreset' => 'ACTIVITY_STATS::'.Provider\Call::getId().':CALL:TOTAL_QTY',
									'dataSource' => 'ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
								)
							)
						)
					)
				)
			)
		);

		if (Provider\Call::isActive())
		{
			$rowData[] = array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_STREAMS'),
								'typeName' => 'pie',
								'group'      => 'STREAM',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => 'ACTIVITY_STREAM_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => 'ACTIVITY_STREAM_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
								'typeName' => 'pie',
								'group'      => 'MARK',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL')
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
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\Call::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\Call::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\Call::getId()),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			);
		}

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'meeting_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Meeting::getName()
									)),
									'dataPreset' => 'ACTIVITY_STATS::'.Provider\Meeting::getId().':*:TOTAL_QTY',
									'dataSource' => 'ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
								)
							)
						)
					)
				)
			)
		);

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'email_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Email::getName()
									)),
									'dataPreset' => 'ACTIVITY_STATS::'.Provider\Email::getId().':*:TOTAL_QTY',
									'dataSource' => 'ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'green')
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
									'name' => 'lf_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Livefeed::getName()
									)),
									'dataPreset' => 'ACTIVITY_STATS::'.Provider\Livefeed::getId().':*:TOTAL_QTY',
									'dataSource' => 'ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'yellow')
								)
							)
						)
					)
				)
			)
		);

		if (Provider\ExternalChannel::isActive())
		{
			$rowData[] = array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\ExternalChannel::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
										'display' => array('colorScheme' => 'blue')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_sum',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
											'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
										)),
										'dataPreset' => 'ACTIVITY_SUM_STATS::'.Provider\ExternalChannel::getId().':*:SUM_TOTAL',
										'dataSource' => 'ACTIVITY_SUM_STATS',
										'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
										'display' => array('colorScheme' => 'red')
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
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\ExternalChannel::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\ExternalChannel::getId()),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			);
		}

		if (Provider\WebForm::isActive())
		{
			$rowData[] = array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\WebForm::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_sum',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => 'ACTIVITY_SUM_STATS::'.Provider\WebForm::getId().':*:SUM_TOTAL',
										'dataSource' => 'ACTIVITY_SUM_STATS',
										'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
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
								'typeName' => 'custom',
								'configs' => array(
									array(
										'name' => 'provider_status',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_STATUS', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => 'ACTIVITY_PROVIDER_STATUS::'.Provider\WebForm::getId(),
										'dataSource' => 'ACTIVITY_PROVIDER_STATUS',
										'select' => array('name' => Provider\WebForm::getId()),
										'display' => array('colorScheme' => 'yellow')
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

	public static function getPortraitRowData($entityTypeId, $isSupervisor = false)
	{
		$entityPrefix = \CCrmOwnerType::ResolveName($entityTypeId);

		$rowData = array();

		$rowData[] = array(
			'height' => '380',
			'cells'  =>
				array(
					array(
						'controls' =>
							array(
								array(
									'typeName' => 'number',
									'configs'  =>
										array(
											array(
												'name'       => 'param1',
												'title'      => GetMessage('CRM_COMM_WGT_DEAL_SUCCESS_SUM'),
												'dataPreset' => $entityPrefix.'_DEAL_SUM_STATS::SUCCESS_SUM',
												'dataSource' => $entityPrefix.'_DEAL_SUM_STATS',
												'select'     => array('name' => 'SUCCESS_SUM',	'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
											array(
												'name'       => 'param2',
												'title'      => GetMessage('CRM_COMM_WGT_DEAL_PROCESS_SUM'),
												'dataPreset' => $entityPrefix.'_DEAL_SUM_STATS::PROCESS_SUM',
												'dataSource' => $entityPrefix.'_DEAL_SUM_STATS',
												'select'     => array('name' => 'PROCESS_SUM', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
											array(
												'name'       => 'param3',
												'title'      => GetMessage('CRM_COMM_WGT_DEAL_FAILED_SUM'),
												'dataPreset' => $entityPrefix.'_DEAL_SUM_STATS::FAILURE_SUM',
												'dataSource' => $entityPrefix.'_DEAL_SUM_STATS',
												'select'     => array('name' => 'FAILURE_SUM', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
										),
									'layout'   => 'tiled',
								),
							),
					),
					array(
						'controls' =>
							array(
								array(
									'typeName' => 'number',
									'configs'  =>
										array(
											array(
												'name'       => 'param1',
												'title'      => GetMessage('CRM_COMM_WGT_PAID_INTIME_COUNT'),
												'dataPreset' => $entityPrefix.'_INVOICE_SUM_STATS::PAID_INTIME_COUNT',
												'dataSource' => $entityPrefix.'_INVOICE_SUM_STATS',
												'select'     => array('name' => 'PAID_INTIME_COUNT', 'aggregate' => 'COUNT'),
											),
											array(
												'name'       => 'param2',
												'title'      => GetMessage('CRM_COMM_WGT_OVERALL_INVOICES'),
												'dataPreset' => $entityPrefix.'_INVOICE_SUM_STATS::OVERALL_COUNT',
												'dataSource' => $entityPrefix.'_INVOICE_SUM_STATS',
												'select'     => array('name' => 'OVERALL_COUNT', 'aggregate' => 'COUNT'),
											),
											array(
												'name'       => 'expr',
												'dataSource' =>
													array(
														'name'      => 'EXPRESSION',
														'operation' => 'PC',
														'arguments' =>
															array(
																0 => '%param1%',
																1 => '%param2%',
															),
													),
												'title'      => GetMessage('CRM_COMM_WGT_FINANCE_INDEX'),
												'format'     => array('isPercent' => 'Y')
											),
										),
									'layout'   => 'tiled',
								),
							),
					),
				),
		);

		$rowData[] = array(
			'height' => '380',
			'cells'  =>
				array(
					array(
						'controls' =>
							array(
								array(
									'typeName' => 'number',
									'configs'  =>
										array(
											array(
												'name'       => 'param1',
												'title'      => GetMessage('CRM_COMM_WGT_INVOICES_ACTIVE_SUM'),
												'dataPreset' => $entityPrefix.'_INVOICE_SUM_STATS::OVERALL_SUM',
												'dataSource' => $entityPrefix.'_INVOICE_SUM_STATS',
												'select'     => array('name'      => 'OVERALL_SUM', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
											array(
												'name'       => 'param2',
												'title'      => GetMessage('CRM_COMM_WGT_INVOICES_PAID_SUM'),
												'dataPreset' => $entityPrefix.'_INVOICE_SUM_STATS::SUCCESS_SUM',
												'dataSource' => $entityPrefix.'_INVOICE_SUM_STATS',
												'select'     => array('name' => 'SUCCESS_SUM', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
											array(
												'name'       => 'expr',
												'dataSource' =>
													array(
														'name'      => 'EXPRESSION',
														'operation' => 'DIFF',
														'arguments' => array('%param1%', '%param2%'),
													),
												'title'      => GetMessage('CRM_COMM_WGT_INVOICES_WAITING'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
										),
									'layout'   => 'tiled',
								),
							),
					),
					array(
						'controls' =>
							array(
								array(
									'typeName' => 'number',
									'configs'  =>
										array(
											array(
												'name'       => 'param1',
												'title'      => GetMessage('CRM_COMM_WGT_DEAL_SUM_PERIOD'),
												'dataPreset' => $entityPrefix.'_DEAL_SUM_STATS::SUM_TOTAL',
												'dataSource' => $entityPrefix.'_DEAL_SUM_STATS',
												'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
										),
								),
								array(
									'typeName' => 'number',
									'configs'  =>
										array(
											array(
												'name'       => 'param1',
												'title'      => GetMessage('CRM_COMM_WGT_CONVERSION'),
												'dataPreset' => $entityPrefix.'_DEAL_CONV_RATE::ACTIVITY',
												'dataSource' => $entityPrefix.'_DEAL_CONV_RATE',
												'select'     => array('name' => 'ACTIVITY'),
												'format'     => array('isPercent' => 'Y')
											),
										),
								),
							),
					),
				)
		);

		$rowData[] = array(
			'height' => '380',
			'cells'  =>
				array(
					array(
						'controls' =>
							array(
								array(
									'typeName'    => 'graph',
									'group'       => 'DATE',
									'combineData' => 'Y',
									'configs'     =>
										array(
											array(
												'name'       => 'param1',
												'title'      => GetMessage('CRM_COMM_WGT_DEALS'),
												'dataPreset' => $entityPrefix.'_DEAL_SUM_STATS::SUM_TOTAL',
												'dataSource' => $entityPrefix.'_DEAL_SUM_STATS',
												'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
											array(
												'name'       => 'param2',
												'title'      => GetMessage('CRM_COMM_WGT_SUCCESS_DEALS'),
												'dataPreset' => $entityPrefix.'_DEAL_SUM_STATS::SUCCESS_SUM',
												'dataSource' => $entityPrefix.'_DEAL_SUM_STATS',
												'select'     => array('name' => 'SUCCESS_SUM', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
											array(
												'name'       => 'param3',
												'title'      => GetMessage('CRM_COMM_WGT_PAID_INVOICES'),
												'dataPreset' => $entityPrefix.'_INVOICE_SUM_STATS::SUCCESS_SUM',
												'dataSource' => $entityPrefix.'_INVOICE_SUM_STATS',
												'select'     => array('name' => 'SUCCESS_SUM', 'aggregate' => 'SUM'),
												'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
											),
										),
									'title'       => GetMessage('CRM_COMM_WGT_PAYMENTS'),
									'context'     => 'F',
									'filter' => array(
										'periodType' => FilterPeriodType::LAST_DAYS_90
									)
								),
							),
					),
				)
		);

		if (Provider\OpenLine::isActive())
		{
			$openLines = self::getProviderTypes(Provider\OpenLine::className());

			foreach ($openLines as $line)
			{
				$rowData[] = array(
					'height' => 180,
					'cells'  => array(
						array(
							'controls' => array(
								array(
									'typeName' => 'number',
									'configs'  => array(
										array(
											'name'       => 'total_qty',
											'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
												'#PROVIDER_NAME#' => $line['NAME']
											)),
											'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\OpenLine::getId().':'.$line['PROVIDER_TYPE_ID'].':TOTAL_QTY',
											'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
											'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
										)
									)
								)
							)
						)
					)
				);
			}
		}

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'call_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Call::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Call::getId().':CALL:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'green')
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
									'name' => 'meeting_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Meeting::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Meeting::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'yellow')
								)
							)
						)
					)
				)
			)
		);

		$rowData[] = array(
			'height' => 180,
			'cells' => array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs' => array(
								array(
									'name' => 'email_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Email::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Email::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'blue')
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
									'name' => 'lf_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\Livefeed::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Livefeed::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
									'display' => array('colorScheme' => 'red')
								)
							)
						)
					)
				)
			)
		);

		if (Provider\ExternalChannel::isActive())
		{
			$cells = array(
				array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs'  => array(
								array(
									'name'       => 'total_qty',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
										'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\ExternalChannel::getId().':*:TOTAL_QTY',
									'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
									'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
								)
							)
						)
					)
				)
			);

			if ($entityTypeId === \CCrmOwnerType::Company)
			{
				$cells[] = array(
					'controls' => array(
						array(
							'typeName' => 'number',
							'configs'  => array(
								array(
									'name'       => 'total_sum',
									'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
										'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
									)),
									'dataPreset' => $entityPrefix.'_ACTIVITY_SUM_STATS::'.Provider\ExternalChannel::getId().':*:SUM_TOTAL',
									'dataSource' => $entityPrefix.'_ACTIVITY_SUM_STATS',
									'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
									'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
								)
							)
						)
					)
				);
			}

			$rowData[] = array(
				'height' => 180,
				'cells'  => $cells
			);
		}

		if (Provider\WebForm::isActive())
		{
			$rowData[] = array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\WebForm::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
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
	
	public static function getDemoRowData($entityTypeId, $isSupervisor = false)
	{
		$entityPrefix = \CCrmOwnerType::ResolveName($entityTypeId);

		$externalChannelCells = array(
			array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'configs'  => array(
							array(
								'name'       => 'total_qty',
								'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
									'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
								)),
								'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\ExternalChannel::getId().':*:TOTAL_QTY',
								'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
								'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
								'display' => array('colorScheme' => 'blue')
							)
						)
					)
				)
			)
		);

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			$externalChannelCells[] = array(
				'controls' => array(
					array(
						'typeName' => 'number',
						'configs'  => array(
							array(
								'name'       => 'total_sum',
								'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
									'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
								)),
								'dataPreset' => $entityPrefix.'_ACTIVITY_SUM_STATS::'.Provider\ExternalChannel::getId().':*:SUM_TOTAL',
								'dataSource' => $entityPrefix.'_ACTIVITY_SUM_STATS',
								'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
								'display' => array('colorScheme' => 'red')
							)
						)
					)
				)
			);
		}

		$rowData = array(
			array(
				'height' => 380,
				'cells' => array(
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_COMM_WGT_GROWTH'),
								'typeName' => 'graph',
								'group' => 'DATE',
								'configs' => array(
									array(
										'name' => 'total_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_'.$entityPrefix.'_GROWTH_COUNT'),
										'dataPreset' => $entityPrefix.'_GROWTH_STATS::TOTAL_COUNT',
										'dataSource' => $entityPrefix.'_GROWTH_STATS',
										'select' => array('name' => 'TOTAL_COUNT')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title' => Loc::getMessage('CRM_COMM_WGT_COMMUNICATIONS'),
								'typeName' => 'pie',
								'group' => 'PROVIDER_ID',
								'configs' => array(
									array(
										'name' => 'source_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\OpenLine::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\OpenLine::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_STATUSES'),
								'typeName' => 'pie',
								'group'      => 'STATUS',
								'configs'  => array(
									array(
										'name'       => 'status_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATUS_STATS::'.Provider\OpenLine::getId().':*:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATUS_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
								'typeName' => 'pie',
								'group'      => 'MARK',
								'configs'  => array(
									array(
										'name'       => 'marks_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'        => Loc::getMessage('CRM_COMM_WGT_SOURCES'),
								'typeName'     => 'bar',
								'group'        => 'SOURCE',
								'context'      => 'E',
								'combineData'  => 'Y',
								'enableStack'  => 'N',
								'integersOnly' => 'Y',
								'configs'      => array(
									array(
										'name'       => 'qty_total',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_ALL'),
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name'       => 'qty_positive',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_POSITIVE'),
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:POSITIVE_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'POSITIVE_QTY', 'aggregate' => 'SUM')
									),
									array(
										'name'       => 'qty_negative',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NEGATIVE'),
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:NEGATIVE_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'NEGATIVE_QTY', 'aggregate' => 'SUM')
									),
									array(
										'name'       => 'qty_nomark',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NONE'),
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:NONE_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'NONE_QTY', 'aggregate' => 'SUM')
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
										'name' => 'call_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Call::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Call::getId().':CALL:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_STREAMS'),
								'typeName' => 'pie',
								'group'      => 'STREAM',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_STREAM_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_STREAM_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
								'typeName' => 'pie',
								'group'      => 'MARK',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => $entityPrefix.'_ACTIVITY_MARK_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL')
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
										'name' => 'meeting_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Meeting::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Meeting::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
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
										'name' => 'email_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Email::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Email::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
										'display' => array('colorScheme' => 'green')
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
										'name' => 'lf_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Livefeed::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\Livefeed::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 180,
				'cells'  => $externalChannelCells
			),
			array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_STATS::'.Provider\WebForm::getId().':*:TOTAL_QTY',
										'dataSource' => $entityPrefix.'_ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name' => 'total_sum',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => $entityPrefix.'_ACTIVITY_SUM_STATS::'.Provider\WebForm::getId().':*:SUM_TOTAL',
										'dataSource' => $entityPrefix.'_ACTIVITY_SUM_STATS',
										'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									)
								)
							)
						)
					)
				)
			)
		);


		return $rowData;
	}

	public static function getDemoData($entityTypeId, $isSupervisor = false)
	{
		$externalChannelCells = array(
			array(
				"data" => array(
					"items" => array(
						array("name" => "total_qty", "value" => "15")
					)
				)
			)
		);

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			$externalChannelCells[] = array(
				"data" => array(
					"items" => array(
						array("name" => "total_sum", "value" => "200000")
					)
				)
			);
		}

		$rowData = array(
			array(
				'cells' => array(
					array(
						"data" => array(
							"dateFormat" => "YYYY-MM-DD",
							"items" => array(
								array(
									"groupField" => "DATE",
									"graphs" => array(
										array(
											"name" => "total_qty",
											"selectField" => "TOTAL_QTY"
										)
									),
									"values" => array(
										array("DATE" => "2016-06-01", "TOTAL_QTY" => "55"),
										array("DATE" => "2016-06-10", "TOTAL_QTY" => "76"),
										array("DATE" => "2016-06-20", "TOTAL_QTY" => "87"),
										array("DATE" => "2016-06-25", "TOTAL_QTY" => "95"),
										array("DATE" => "2016-07-01", "TOTAL_QTY" => "101"),
										array("DATE" => "2016-07-05", "TOTAL_QTY" => "111")
									)
								)
							)
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("PROVIDER_ID" => Provider\Call::getId(), "TOTAL_QTY" => "45"),
								array("PROVIDER_ID" => Provider\Email::getId(), "TOTAL_QTY" => "20"),
								array("PROVIDER_ID" => Provider\ExternalChannel::getId(), "TOTAL_QTY" => "15"),
								array("PROVIDER_ID" => Provider\Livefeed::getId(), "TOTAL_QTY" => "14"),
								array("PROVIDER_ID" => Provider\Meeting::getId(), "TOTAL_QTY" => "10"),
								array("PROVIDER_ID" => Provider\OpenLine::getId(), "TOTAL_QTY" => "36"),
								array("PROVIDER_ID" => Provider\WebForm::getId(), "TOTAL_QTY" => "35")
							),
							"valueField" => "TOTAL_QTY",
							"titleField" => "PROVIDER",
							"identityField" => "PROVIDER_ID"
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_qty", "value" => "36")
							)
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("STATUS_ID" => StatisticsStatus::Answered, "TOTAL" => "36"),
								array("STATUS_ID" => StatisticsStatus::Unanswered, "TOTAL" => "5")
							),
							"valueField" => "TOTAL",
							"titleField" => "STATUS",
							"identityField" => "STATUS_ID"
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("MARK_ID" => StatisticsMark::Positive, "TOTAL" => "24"),
								array("MARK_ID" => StatisticsMark::Negative, "TOTAL" => "5"),
								array("MARK_ID" => StatisticsMark::None, "TOTAL" => "7")
							),
							"valueField" => "TOTAL",
							"titleField" => "MARK",
							"identityField" => "MARK_ID"
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						'data' => array(
							"items" => array(
								array(
									"groupField" => "SOURCE",
									"graphs" => array(
										array(
											"name" => "qty_total",
											"selectField" => "TOTAL"
										),
										array(
											"name" => "qty_positive",
											"selectField" => "POSITIVE_QTY"
										),
										array(
											"name" => "qty_negative",
											"selectField" => "NEGATIVE_QTY"
										),
										array(
											"name" => "qty_nomark",
											"selectField" => "NONE_QTY"
										)
									),
									"values" => array(
										array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_FB'),
											"TOTAL" => "13",
											"POSITIVE_QTY" => "4",
											"NEGATIVE_QTY" => "4",
											"NONE_QTY" => "5"
										),array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_TG'),
											"TOTAL" => "8",
											"POSITIVE_QTY" => "5",
											"NEGATIVE_QTY" => "1",
											"NONE_QTY" => "2"
										),array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_SK'),
											"TOTAL" => "5",
											"POSITIVE_QTY" => "2",
											"NEGATIVE_QTY" => "3",
											"NONE_QTY" => "0"
										),
										array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_VK'),
											"TOTAL" => "10",
											"POSITIVE_QTY" => "7",
											"NEGATIVE_QTY" => "2",
											"NONE_QTY" => "1"
										)
									)
								)
							)
						)
					)
				)
			),
			array(
				'cells' => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "call_qty", "value" => "45")
							)
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("STREAM_ID" => StatisticsStream::Incoming, "TOTAL" => "21"),
								array("STREAM_ID" => StatisticsStream::Outgoing, "TOTAL" => "15"),
								array("STREAM_ID" => StatisticsStream::Reversing, "TOTAL" => "4"),
								array("STREAM_ID" => StatisticsStream::Missing, "TOTAL" => "5")
							),
							"valueField" => "TOTAL",
							"titleField" => "STREAM",
							"identityField" => "STREAM_ID"
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("MARK_ID" => StatisticsMark::Positive, "TOTAL" => "24"),
								array("MARK_ID" => StatisticsMark::Negative, "TOTAL" => "12"),
								array("MARK_ID" => StatisticsMark::None, "TOTAL" => "9")
							),
							"valueField" => "TOTAL",
							"titleField" => "MARK",
							"identityField" => "MARK_ID"
						)
					)
				)
			),
			array(
				'cells' => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "meeting_qty", "value" => "10")
							)
						)
					)
				)
			),
			array(
				'cells' => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "email_qty", "value" => "20")
							)
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("name" => "lf_qty", "value" => "14")
							)
						)
					)
				)
			),
			array(
				'cells'  => $externalChannelCells
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_qty", "value" => "35")
							)
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_sum", "value" => "150000")
							)
						)
					)
				)
			)
		);

		return $rowData;
	}

	public static function getActivityDemoRowData($isSupervisor = false)
	{
		$rowData = array(
			array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title'      => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\OpenLine::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\OpenLine::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_STATUSES'),
								'typeName' => 'pie',
								'group'      => 'STATUS',
								'configs'  => array(
									array(
										'name'       => 'status_qty',
										'dataPreset' => 'ACTIVITY_STATUS_STATS::'.Provider\OpenLine::getId().':*:TOTAL',
										'dataSource' => 'ACTIVITY_STATUS_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
								'typeName' => 'pie',
								'group'      => 'MARK',
								'configs'  => array(
									array(
										'name'       => 'marks_qty',
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:TOTAL',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'        => Loc::getMessage('CRM_COMM_WGT_SOURCES'),
								'typeName'     => 'bar',
								'group'        => 'SOURCE',
								'context'      => 'E',
								'combineData'  => 'Y',
								'enableStack'  => 'N',
								'integersOnly' => 'Y',
								'configs'      => array(
									array(
										'name'       => 'qty_total',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_ALL'),
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:TOTAL',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL', 'aggregate' => 'SUM')
									),
									array(
										'name'       => 'qty_positive',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_POSITIVE'),
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:POSITIVE_QTY',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'POSITIVE_QTY', 'aggregate' => 'SUM')
									),
									array(
										'name'       => 'qty_negative',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NEGATIVE'),
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:NEGATIVE_QTY',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'NEGATIVE_QTY', 'aggregate' => 'SUM')
									),
									array(
										'name'       => 'qty_nomark',
										'title'      => Loc::getMessage('CRM_COMM_WGT_MARK_NONE'),
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\OpenLine::getId().':*:NONE_QTY',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'NONE_QTY', 'aggregate' => 'SUM')
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
										'name' => 'call_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Call::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\Call::getId().':CALL:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 380,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_STREAMS'),
								'typeName' => 'pie',
								'group'      => 'STREAM',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => 'ACTIVITY_STREAM_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => 'ACTIVITY_STREAM_STATS',
										'select'     => array('name' => 'TOTAL')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'title'    => Loc::getMessage('CRM_COMM_WGT_MARKS'),
								'typeName' => 'pie',
								'group'      => 'MARK',
								'configs'  => array(
									array(
										'name'       => 'source_qty',
										'dataPreset' => 'ACTIVITY_MARK_STATS::'.Provider\Call::getId().':CALL:TOTAL',
										'dataSource' => 'ACTIVITY_MARK_STATS',
										'select'     => array('name' => 'TOTAL')
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
										'name' => 'meeting_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Meeting::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\Meeting::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
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
										'name' => 'email_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Email::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\Email::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
										'display' => array('colorScheme' => 'green')
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
										'name' => 'lf_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\Livefeed::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\Livefeed::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select' => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
										'display' => array('colorScheme' => 'yellow')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\ExternalChannel::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM'),
										'display' => array('colorScheme' => 'blue')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_sum',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
											'#PROVIDER_NAME#' => Provider\ExternalChannel::getName()
										)),
										'dataPreset' => 'ACTIVITY_SUM_STATS::'.Provider\ExternalChannel::getId().':*:SUM_TOTAL',
										'dataSource' => 'ACTIVITY_SUM_STATS',
										'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N'),
										'display' => array('colorScheme' => 'red')
									)
								)
							)
						)
					)
				)
			),
			array(
				'height' => 180,
				'cells'  => array(
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name'       => 'total_qty',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_TOTAL_QTY', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => 'ACTIVITY_STATS::'.Provider\WebForm::getId().':*:TOTAL_QTY',
										'dataSource' => 'ACTIVITY_STATS',
										'select'     => array('name' => 'TOTAL_QTY', 'aggregate' => 'SUM')
									)
								)
							)
						)
					),
					array(
						'controls' => array(
							array(
								'typeName' => 'number',
								'configs'  => array(
									array(
										'name' => 'total_sum',
										'title' => Loc::getMessage('CRM_COMM_WGT_PROVIDER_SUM', array(
											'#PROVIDER_NAME#' => Provider\WebForm::getName()
										)),
										'dataPreset' => 'ACTIVITY_SUM_STATS::'.Provider\WebForm::getId().':*:SUM_TOTAL',
										'dataSource' => 'ACTIVITY_SUM_STATS',
										'select'     => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
										'format'     => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
									)
								)
							)
						)
					)
				)
			)
		);


		return $rowData;
	}

	public static function getActivityDemoData($isSupervisor = false)
	{
		$rowData = array(
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_qty", "value" => "36")
							)
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("STATUS_ID" => StatisticsStatus::Answered, "TOTAL" => "36"),
								array("STATUS_ID" => StatisticsStatus::Unanswered, "TOTAL" => "5")
							),
							"valueField" => "TOTAL",
							"titleField" => "STATUS",
							"identityField" => "STATUS_ID"
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("MARK_ID" => StatisticsMark::Positive, "TOTAL" => "24"),
								array("MARK_ID" => StatisticsMark::Negative, "TOTAL" => "5"),
								array("MARK_ID" => StatisticsMark::None, "TOTAL" => "7")
							),
							"valueField" => "TOTAL",
							"titleField" => "MARK",
							"identityField" => "MARK_ID"
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						'data' => array(
							"items" => array(
								array(
									"groupField" => "SOURCE",
									"graphs" => array(
										array(
											"name" => "qty_total",
											"selectField" => "TOTAL"
										),
										array(
											"name" => "qty_positive",
											"selectField" => "POSITIVE_QTY"
										),
										array(
											"name" => "qty_negative",
											"selectField" => "NEGATIVE_QTY"
										),
										array(
											"name" => "qty_nomark",
											"selectField" => "NONE_QTY"
										)
									),
									"values" => array(
										array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_FB'),
											"TOTAL" => "13",
											"POSITIVE_QTY" => "4",
											"NEGATIVE_QTY" => "4",
											"NONE_QTY" => "5"
										),array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_TG'),
											"TOTAL" => "8",
											"POSITIVE_QTY" => "5",
											"NEGATIVE_QTY" => "1",
											"NONE_QTY" => "2"
										),array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_SK'),
											"TOTAL" => "5",
											"POSITIVE_QTY" => "2",
											"NEGATIVE_QTY" => "3",
											"NONE_QTY" => "0"
										),
										array(
											"SOURCE" => Loc::getMessage('CRM_COMM_WGT_MARK_SOURCE_VK'),
											"TOTAL" => "10",
											"POSITIVE_QTY" => "7",
											"NEGATIVE_QTY" => "2",
											"NONE_QTY" => "1"
										)
									)
								)
							)
						)
					)
				)
			),
			array(
				'cells' => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "call_qty", "value" => "45")
							)
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("STREAM_ID" => StatisticsStream::Incoming, "TOTAL" => "21"),
								array("STREAM_ID" => StatisticsStream::Outgoing, "TOTAL" => "15"),
								array("STREAM_ID" => StatisticsStream::Reversing, "TOTAL" => "4"),
								array("STREAM_ID" => StatisticsStream::Missing, "TOTAL" => "5")
							),
							"valueField" => "TOTAL",
							"titleField" => "STREAM",
							"identityField" => "STREAM_ID"
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("MARK_ID" => StatisticsMark::Positive, "TOTAL" => "24"),
								array("MARK_ID" => StatisticsMark::Negative, "TOTAL" => "12"),
								array("MARK_ID" => StatisticsMark::None, "TOTAL" => "9")
							),
							"valueField" => "TOTAL",
							"titleField" => "MARK",
							"identityField" => "MARK_ID"
						)
					)
				)
			),
			array(
				'cells' => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "meeting_qty", "value" => "10")
							)
						)
					)
				)
			),
			array(
				'cells' => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "email_qty", "value" => "20")
							)
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("name" => "lf_qty", "value" => "14")
							)
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_qty", "value" => "15")
							)
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_sum", "value" => "200000")
							)
						)
					)
				)
			),
			array(
				'cells'  => array(
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_qty", "value" => "35")
							)
						)
					),
					array(
						"data" => array(
							"items" => array(
								array("name" => "total_sum", "value" => "150000")
							)
						)
					)
				)
			)
		);

		return $rowData;
	}

	/**
	 * @return array
	 */
	public static function getProvidersTypesRelation()
	{
		if (self::$providersTypesCache === null)
		{
			self::$providersTypesCache = array();
			$providers = \CCrmActivity::GetProviders();
			foreach ($providers as $provider)
			{
				self::$providersTypesCache[$provider::className()] = $provider::getTypes();
			}
		}

		return self::$providersTypesCache;
	}

	/**
	 * @param string $provider
	 * @return array
	 */
	public static function getProviderTypes($provider)
	{
		$relation = self::getProvidersTypesRelation();
		return isset($relation[$provider]) ? $relation[$provider] : array();
	}
}