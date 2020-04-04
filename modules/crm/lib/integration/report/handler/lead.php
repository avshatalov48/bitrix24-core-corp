<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\StatusTable;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;


/**
 * Class Lead
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class Lead extends Base implements IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	const WHAT_WILL_CALCULATE_LEAD_COUNT = 'LEAD_COUNT';
	const WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT = 'LEAD_COUNT';
	const WHAT_WILL_CALCULATE_LEAD_CONVERSION = 'LEAD_CONVERSION';
	const WHAT_WILL_CALCULATE_LEAD_LOSES = 'LEAD_LOSES';
	const WHAT_WILL_CALCULATE_ACTIVE_LEAD_COUNT = 'ACTIVE_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT = 'CONVERTED_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_LOST_LEAD_COUNT = 'LOST_LEAD_COUNT';

	const WHAT_WILL_CALCULATE_NEW_LEAD_COUNT = 'NEW_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_NEW_GOOD_LEAD_COUNT = 'NEW_GOOD_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION = 'NEW_LEAD_CONVERSION';
	const WHAT_WILL_CALCULATE_NEW_LEAD_LOSES = 'NEW_LEAD_LOSES';
	const WHAT_WILL_CALCULATE_NEW_ACTIVE_LEAD_COUNT = 'NEW_ACTIVE_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_NEW_CONVERTED_LEAD_COUNT = 'NEW_CONVERTED_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_NEW_LOST_LEAD_COUNT = 'NEW_LOST_LEAD_COUNT';


	const WHAT_WILL_CALCULATE_REPEATED_LEAD_COUNT = 'REPEATED_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_REPEATED_GOOD_LEAD_COUNT = 'REPEATED_GOOD_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION = 'REPEATED_LEAD_CONVERSION';
	const WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES = 'REPEATED_LEAD_LOSES';
	const WHAT_WILL_CALCULATE_REPEATED_ACTIVE_LEAD_COUNT = 'REPEATED_ACTIVE_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_REPEATED_CONVERTED_LEAD_COUNT = 'REPEATED_CONVERTED_LEAD_COUNT';
	const WHAT_WILL_CALCULATE_REPEATED_LOST_LEAD_COUNT = 'REPEATED_LOST_LEAD_COUNT';

	const GROUPING_BY_STATE = 'STATE';
	const GROUPING_BY_DATE = 'DATE';
	const GROUPING_BY_SOURCE = 'SOURCE';
	const GROUPING_BY_RESPONSIBLE = 'RESPONSIBLE';


	const FILTER_FIELDS_PREFIX = 'FROM_LEAD_';


	const STATUS_DEFAULT_COLORS = [
		'DEFAULT_COLOR' => '#ACE9FB',
		'DEFAULT_FINAL_SUCCESS__COLOR' => '#DBF199',
		'DEFAULT_FINAL_UN_SUCCESS_COLOR' => '#FFBEBD',
		'DEFAULT_LINE_COLOR' => '#ACE9FB',
	];

	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Lead');
		$this->setCategoryKey('crm');
	}

	protected function collectFormElements()
	{
		parent::collectFormElements();
	}


	/**
	 * @return array
	 */
	protected function getGroupByOptions()
	{
		return [
			self::GROUPING_BY_DATE => 'Date',
			self::GROUPING_BY_STATE => 'State',
			self::GROUPING_BY_SOURCE => 'Source',
			self::GROUPING_BY_RESPONSIBLE => 'Responsible'
		];
	}

	/**
	 *
	 * @param null $groupingValue Grouping field value.
	 * @return array
	 */
	public function getWhatWillCalculateOptions($groupingValue = null)
	{
		return [
			self::WHAT_WILL_CALCULATE_LEAD_COUNT => 'Lead count',
		];
	}

	public function mutateFilterParameter($filterParameters)
	{
		$filterParameters =  parent::mutateFilterParameter($filterParameters);

		$fieldsToOrmMap =  $this->getLeadFieldsToOrmMap();

		foreach ($filterParameters as $key => $value)
		{
			if ($key == 'TIME_PERIOD' || (strpos($key, 'UF_') === 0))
			{
				continue;
			}

			if ($key == 'COMMUNICATION_TYPE')
			{
				if (in_array(\CCrmFieldMulti::PHONE, $value['value']))
				{
					$filterParameters['HAS_PHONE']['type'] = 'checkbox';
					$filterParameters['HAS_PHONE']['value'] = 'Y';
				}

				if (in_array(\CCrmFieldMulti::EMAIL, $value['value']))
				{
					$filterParameters['HAS_EMAIL']['type'] = 'checkbox';
					$filterParameters['HAS_EMAIL']['value'] = 'Y';
				}

				unset($filterParameters[$key]);
				continue;
			}

			if (isset($fieldsToOrmMap[$key]) && $fieldsToOrmMap[$key] !== $key)
			{
				$filterParameters[$fieldsToOrmMap[$key]] = $value;
				unset($filterParameters[$key]);
			}
			elseif (!isset($fieldsToOrmMap[$key]))
			{
				unset($filterParameters[$key]);
			}

		}

		return $filterParameters;
	}

	/**
	 * Called every time when calculate some report result before passing some concrete handler, such us getMultipleData or getSingleData.
	 * Here you can get result of configuration fields of report, if report in widget you can get configurations of widget.
	 *
	 * @return mixed
	 */
	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();

		/** @var DropDown $grouping */
		$groupingField = $this->getFormElement('groupingBy');
		$groupingValue = $groupingField ? $groupingField->getValue() : null;

		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;



		$query = new Query(LeadTable::getEntity());

		switch ($groupingValue)
		{
			case self::GROUPING_BY_DATE:
				$query->registerRuntimeField(new ExpressionField('DATE_CREATE_DAY', "DATE_FORMAT(%s, '%%Y-%%m-%%d 00:00')", 'DATE_CREATE'));
				$query->addSelect('DATE_CREATE_DAY');
				$query->addGroup('DATE_CREATE_DAY');
				break;
			case self::GROUPING_BY_STATE:
				$query->addGroup('STATUS_ID');
				$query->addSelect('STATUS_ID');

				$statusNameListByStatusId = [];
				foreach ($this->getStatusNameList() as $status)
				{
					$statusNameListByStatusId[$status['STATUS_ID']] = $status['NAME'];
				}

				break;
			case self::GROUPING_BY_SOURCE:
				$query->addGroup('SOURCE_ID');
				$query->addSelect('SOURCE_ID');

				$sourceNameListByStatusId = [];
				foreach ($this->getSourceNameList() as $source)
				{
					$sourceNameListByStatusId[$source['STATUS_ID']] = $source['NAME'];
				}
				break;
			case self::GROUPING_BY_RESPONSIBLE:
				$query->addGroup('ASSIGNED_BY_ID');
				$query->addSelect('ASSIGNED_BY_ID');
				break;
		}


		$query->addSelect(new ExpressionField('VALUE', 'COUNT(*)'));

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_REPEATED_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_REPEATED_ACTIVE_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_LOST_LEAD_COUNT:
				$query->where('IS_RETURN_CUSTOMER', 'Y');
				break;
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_NEW_ACTIVE_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_LOST_LEAD_COUNT:
				$query->where('IS_RETURN_CUSTOMER', 'N');
				break;
		}



		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_GOOD_LEAD_COUNT:
				$query->whereIn('STATUS_SEMANTIC_ID', ['P', 'S']);
				break;
			case self::WHAT_WILL_CALCULATE_ACTIVE_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_ACTIVE_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_ACTIVE_LEAD_COUNT:
				$query->where('STATUS_SEMANTIC_ID', 'P');
				break;
			case self::WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_CONVERTED_LEAD_COUNT:
				$query->where('STATUS_SEMANTIC_ID', 'S');
				break;
			case self::WHAT_WILL_CALCULATE_LOST_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_LOST_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_LOST_LEAD_COUNT:
				$query->where('STATUS_SEMANTIC_ID', 'F');
				break;
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
				$query->addGroup('STATUS_SEMANTIC_ID');
				$query->addSelect('STATUS_SEMANTIC_ID');
				break;
		}

		foreach ($filterParameters as $key => $value)
		{
			if ($key === 'TIME_PERIOD')
			{
				if ($value['from'] !== "" && $value['to'] !== "")
				{
					$query->where('DATE_CREATE', '<=', $value['to'])
						  ->where(
							  Query::filter()
								   ->logic('or')
								   ->whereNull('DATE_CLOSED')
								   ->where('DATE_CLOSED', '>=', $value['from'])
						  );

					continue;
				}
			}

			switch 	($value['type'])
			{
				case 'date':
				case 'diapason':
					if ($value['from'] !== "")
					{
						$query->where($key, '>=', $value['from']);
					}

					if ($value['to'] !== "")
					{
						$query->where($key, '<=', $value['to']);
					}
					break;
				case 'none':
				case 'list':
				case 'text':
				case 'checkbox':
				case 'custom_entity':
					$query->addFilter($key, $value['value']);
					break;

			}
		}

		$this->addPermissionsCheck($query);
		$results = $query->exec()->fetchAll();


		$amountValue = 0;
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
				$allLeadCount = [];
				$successLeadCount = [];
				$successAmountLeadCount = 0;
				$allAmountLeadCount = 0;
				$groupingFieldName = 'withoutGrouping';
				foreach ($results as $result)
				{
					switch ($groupingValue)
					{
						case self::GROUPING_BY_RESPONSIBLE:
							$groupingFieldName = 'ASSIGNED_BY_ID';
							$groupingFieldValue = $result[$groupingFieldName];
							break;
						default:
							$groupingFieldValue = 'withoutGrouping';
					}


					$allLeadCount[$groupingFieldValue] += $result['VALUE'];
					$allAmountLeadCount += $result['VALUE'];
					if ($result['STATUS_SEMANTIC_ID'] == 'S')
					{
						$successLeadCount[$groupingFieldValue] += $result['VALUE'];
						$successAmountLeadCount += $result['VALUE'];
					}
				}
				$results = [];

				foreach ($allLeadCount as $groupingKey => $count)
				{
					if (!empty($successLeadCount[$groupingKey]))
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => ($successLeadCount[$groupingKey] / $count) * 100
						];
					}
					else
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => 0
						];
					}

				}

				$amountValue = $allAmountLeadCount ? (($successAmountLeadCount / $allAmountLeadCount) * 100) : 0;

				break;
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
				$allLeadCount = [];
				$loseLeadCount = [];
				$losesAmountLeadCount = 0;
				$allAmountLeadCount = 0;
				$groupingFieldName = 'withoutGrouping';
				foreach ($results as $result)
				{
					switch ($groupingValue)
					{
						case self::GROUPING_BY_RESPONSIBLE:
							$groupingFieldName = 'ASSIGNED_BY_ID';
							$groupingFieldValue = $result[$groupingFieldName];
							break;
						default:
							$groupingFieldValue = 'withoutGrouping';
					}


					$allLeadCount[$groupingFieldValue] += $result['VALUE'];
					$allAmountLeadCount += $result['VALUE'];
					if ($result['STATUS_SEMANTIC_ID'] == 'F')
					{
						$loseLeadCount[$groupingFieldValue] += $result['VALUE'];
						$losesAmountLeadCount += $result['VALUE'];
					}
				}
				$results = [];

				foreach ($allLeadCount as $groupingKey => $count)
				{
					if (!empty($loseLeadCount[$groupingKey]))
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => ($loseLeadCount[$groupingKey] / $count) * 100
						];
					}
					else
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => 0
						];
					}

				}

				$amountValue = $allAmountLeadCount ? (($losesAmountLeadCount / $allAmountLeadCount) * 100) : 0;
				break;
		}



		$leadCalculatedValue = [];
		$percentageMetricsList = [
			self::WHAT_WILL_CALCULATE_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_LEAD_LOSES,
			self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES,
			self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES,
		];
		foreach ($results as $result)
		{
			if (!in_array($calculateValue, $percentageMetricsList))
			{
				$amountValue += $result['VALUE'];
			}

			switch ($groupingValue)
			{
				case self::GROUPING_BY_DATE:
					$leadCalculatedValue[$result['DATE_CREATE_DAY']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['DATE_CREATE_DAY']]['title'] = $result['DATE_CREATE_DAY'];
					break;
				case self::GROUPING_BY_STATE:
					$leadCalculatedValue[$result['STATUS_ID']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['STATUS_ID']]['title'] = !empty($statusNameListByStatusId[$result['STATUS_ID']]) ? $statusNameListByStatusId[$result['STATUS_ID']] : '';
					$leadCalculatedValue[$result['STATUS_ID']]['color'] = $this->getStatusColor($result['STATUS_ID']);
					break;
				case self::GROUPING_BY_SOURCE:
					$leadCalculatedValue[$result['SOURCE_ID']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['SOURCE_ID']]['title'] = !empty($sourceNameListByStatusId[$result['SOURCE_ID']]) ? $sourceNameListByStatusId[$result['SOURCE_ID']] : '';
					break;
				case self::GROUPING_BY_RESPONSIBLE:
					//TODO optimise here
					$userInfo = $this->getUserInfo($result['ASSIGNED_BY_ID']);
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['value'] = $result['VALUE'];
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['title'] = $userInfo['name'];
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['logo'] = $userInfo['icon'];
					$leadCalculatedValue[$result['ASSIGNED_BY_ID']]['targetUrl'] = $userInfo['link'];
					break;
				default:
					$leadCalculatedValue['withoutGrouping'] = $result['VALUE'];
					break;

			}

		}

		if ($groupingValue === self::GROUPING_BY_STATE && isset($statusNameListByStatusId))
		{
			$sortedLeadCountListByStatus = [];
			foreach ($statusNameListByStatusId as $statusId => $statusName)
			{
				if ($statusId === $this->getLeadUnSuccessStatusName())
				{
					continue;
				}

				if (!empty($leadCalculatedValue[$statusId]))
				{
					$sortedLeadCountListByStatus[$statusId] = $leadCalculatedValue[$statusId];
				}
				else
				{
					$sortedLeadCountListByStatus[$statusId] = [
						'value' => 0,
						'title' => $statusName,
						'color' => $this->getStatusColor($statusId)
					];
				}
			}
			$leadCalculatedValue = $sortedLeadCountListByStatus;
		}

		$leadCalculatedValue['amount'] = $amountValue;
		return $leadCalculatedValue;
	}


	private function getLeadFieldsToOrmMap()
	{
		$map = array(
			'ID' => 'ID',
			'TITLE' => 'TITLE',
			'SOURCE_ID' => 'SOURCE_ID',
			'NAME' => 'NAME',
			'SECOND_NAME' => 'SECOND_NAME',
			'LAST_NAME' => 'LAST_NAME',
			'BIRTHDATE' => 'BIRTHDATE',
			'DATE_CREATE' => 'DATE_CREATE',
			'DATE_MODIFY' => 'DATE_MODIFY',
			'STATUS_ID' => 'STATUS_ID',
			'STATUS_SEMANTIC_ID' => 'STATUS_SEMANTIC_ID',
			//'STATUS_CONVERTED' => 'STATUS_CONVERTED',
			'OPPORTUNITY' => 'OPPORTUNITY',
			'CURRENCY_ID' => 'CURRENCY_ID',
			'ASSIGNED_BY_ID' => 'ASSIGNED_BY_ID',
			'CREATED_BY_ID' => 'CREATED_BY_ID',
			'MODIFY_BY_ID' => 'MODIFY_BY_ID',
			'IS_RETURN_CUSTOMER' => 'IS_RETURN_CUSTOMER',
			//'ACTIVITY_COUNTER' => 'ACTIVITY_COUNTER',
			//'COMMUNICATION_TYPE' => 'COMMUNICATION_TYPE',
			'HAS_PHONE' => 'HAS_PHONE',
			'PHONE' => 'PHONE',
			'HAS_EMAIL' => 'HAS_EMAIL',
			'EMAIL' => 'EMAIL',
			//'WEB' => 'WEB',
			//'IM' => 'IM',
			'CONTACT_ID' => 'CONTACT_ID',
			'COMPANY_ID' => 'COMPANY_ID',
			'COMPANY_TITLE' => 'COMPANY_TITLE',
			'POST' => 'POST',
			'ADDRESS' => 'ADDRESS',
			'ADDRESS_2' => 'ADDRESS_ENTITY.ADDRESS_2',
			'ADDRESS_CITY' => 'ADDRESS_ENTITY.CITY',
			'ADDRESS_REGION' => 'ADDRESS_ENTITY.REGION',
			'ADDRESS_PROVINCE' => 'ADDRESS_ENTITY.PROVINCE',
			'ADDRESS_POSTAL_CODE' => 'ADDRESS_ENTITY.POSTAL_CODE',
			'ADDRESS_COUNTRY' => 'ADDRESS_ENTITY.COUNTRY',
			'COMMENTS' => 'COMMENTS',
			'PRODUCT_ROW_PRODUCT_ID' => 'PRODUCT_ROW.PRODUCT_ID',
			'WEBFORM_ID' => 'WEBFORM_ID',
			//'TRACKING_SOURCE' => 'TRACKING_SOURCE',
			//'TRACKING_ASSIGNED' => 'TRACKING_ASSIGNED',
		);

		//region UTM
		foreach (UtmTable::getCodeNames() as $code => $name)
		{
			$map[$code] = $code . '.VALUE';
		}

		return $map;
	}


	private function addPermissionsCheck(Query $query, $userId = 0)
	{
		static $permissionEntity;
		if($userId <= 0)
		{
			$userId = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userId);

		$permissionSql = $this->buildPermissionSql(
			array(
				'alias' => 'L',
				'permissionType' => 'READ',
				'options' => array(
					'PERMS' => $userPermissions,
					'RAW_QUERY' => true
				)
			)
		);

		if ($permissionSql)
		{
			if (!$permissionEntity)
			{
				$permissionEntity = \Bitrix\Main\Entity\Base::compileEntity(
					'user_perms',
					array('ENTITY_ID' => array('data_type' => 'integer')),
					array('table_name' => "({$permissionSql})")
				);
			}


			$query->registerRuntimeField('',
				new ReferenceField('PERMS',
					$permissionEntity,
					array('=this.ID' => 'ref.ENTITY_ID'),
					array('join_type' => 'INNER')
				)
			);


		}

	}

	private function buildPermissionSql(array $params)
	{
		return \CCrmLead::BuildPermSql(
			isset($params['alias']) ? $params['alias'] : 'L',
			isset($params['permissionType']) ? $params['permissionType'] : 'READ',
			isset($params['options']) && is_array($params['options']) ? $params['options'] : array()
		);
	}

	/**
	 * @param $statusId
	 * @return mixed
	 */
	private function getStatusColor($statusId)
	{
		$colorsList = $this->getStatusColorList();
		if (!isset($colorsList[$statusId]))
		{
			return self::STATUS_DEFAULT_COLORS['DEFAULT_COLOR'];
		}

		return $colorsList[$statusId];
	}

	/**
	 * @return array
	 */
	private function getStatusColorList()
	{
		static $result = [];
		if (!empty($result))
		{
			return $result;
		}

		$leadStatusColors = (array)unserialize(\COption::GetOptionString('crm', 'CONFIG_STATUS_STATUS'));

		if ($leadStatusColors)
		{
			foreach ($leadStatusColors as $statusKey => $value)
			{
				$result[$statusKey] = $value['COLOR'];
			}
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getStatusNameList()
	{
		$statusListQuery = new Query(StatusTable::getEntity());
		$statusListQuery->where('ENTITY_ID', 'STATUS');
		$statusListQuery->addSelect('STATUS_ID');
		$statusListQuery->addSelect('NAME');
		$statusListQuery->addOrder('SORT');
		return $statusListQuery->exec()->fetchAll();
	}


	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getSourceNameList()
	{
		$sourceListQuery = new Query(StatusTable::getEntity());
		$sourceListQuery->where('ENTITY_ID', 'SOURCE');
		$sourceListQuery->addSelect('STATUS_ID');
		$sourceListQuery->addSelect('NAME');
		return $sourceListQuery->exec()->fetchAll();
	}


	/**
	 * @param $userId
	 * @return mixed|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getUserInfo($userId)
	{
		static $users = array();

		if(!$userId)
		{
			return null;
		}

		if(!$users[$userId])
		{
			// prepare link to profile
			$replaceList = array('user_id' => $userId);
			$template = '/company/personal/user/#user_id#/';
			$link = \CComponentEngine::makePathFromTemplate($template, $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if(!$userFields)
			{
				return null;
			}

			// format name
			$userName = \CUser::FormatName(
				\CSite::GetNameFormat(),
				array(
					'LOGIN' => $userFields['LOGIN'],
					'NAME' => $userFields['NAME'],
					'LAST_NAME' => $userFields['LAST_NAME'],
					'SECOND_NAME' => $userFields['SECOND_NAME']
				),
				true, false
			);

			// prepare icon
			$fileTmp = \CFile::ResizeImageGet(
				$userFields['PERSONAL_PHOTO'],
				array('width' => 42, 'height' => 42),
				BX_RESIZE_IMAGE_EXACT,
				false
			);
			$userIcon = $fileTmp['src'];

			$users[$userId] = array(
				'id' => $userId,
				'name' => $userName,
				'link' => $link,
				'icon' => $userIcon
			);
		}

		return $users[$userId];
	}

	/**
	 * @return mixed
	 */
	private function getLeadUnSuccessStatusName()
	{
		static $unSuccessStatusName;
		if (!$unSuccessStatusName)
		{
			$statusSemanticInfo = \CCrmStatus::GetLeadStatusSemanticInfo();
			$unSuccessStatusName = $statusSemanticInfo['FINAL_UNSUCCESS_FIELD'];
		}

		return $unSuccessStatusName;
	}


	/**
	 * array with format
	 * array(
	 *     'title' => 'Some Title',
	 *     'value' => 0,
	 *     'targetUrl' => 'http://url.domain?params=param'
	 * )
	 * @return array
	 */
	public function getSingleData()
	{
		$calculatedData = $this->getCalculatedData();
		$result = [
			'value' => $calculatedData['withoutGrouping'],
		];

		$calculateValue = $this->getFormElement('calculate')->getValue();
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
				$result['config']['unitOfMeasurement'] = '%';
				$result['value'] = round($result['value'], 2);
				break;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getSingleDemoData()
	{
		return [
			'value' => 5
		];
	}

	/**
	 * array with format
	 * array(
	 *     'items' => array(
	 *            array(
	 *                'label' => 'Some Title',
	 *                'value' => 5,
	 *                'targetUrl' => 'http://url.domain?params=param'
	 *          )
	 *     )
	 * )
	 * @return array
	 */
	public function getMultipleData()
	{

		$calculatedData = $this->getCalculatedData();
		$items = [];
		$config = [];
		if (!empty($calculatedData))
		{
			$calculateField = $this->getFormElement('calculate');
			$calculateValue = $calculateField ? $calculateField->getValue() : null;
			switch ($calculateValue)
			{
				case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
				case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
					$config['mode'] = 'singleData';
					$config['unitOfMeasurement'] = '%';
					$item['value'] = round($calculatedData['withoutGrouping'], 2);
					$item['color'] = '#9DCF00';
					$items[] = $item;
					break;
				default:
					$amountLeadCount = 0;
					foreach ($calculatedData as $key => $data)
					{
						if ($key === 'amount')
						{
							continue;
						}
						//TEMP solution for funnel

						$amountLeadCount += $data['value'];
						if ($this->getView()->getKey() === ColumnFunnel::VIEW_KEY)
						{
							$funnelCalculateModeField = $this->getWidgetHandler()->getFormElement('calculateMode');
							$funnelCalculateModeValue = $funnelCalculateModeField->getValue();

							if ($funnelCalculateModeValue === ColumnFunnel::CLASSIC_CALCULATE_MODE)
							{
								foreach ($items as &$previewsItem)
								{
									$previewsItem['value'] += $data['value'];
								}
							}

						}

						$items[] = [
							'label' => $data['title'],
							'value' => $data['value'],
							'color' => $data['color'],
						];
					}
					$config['titleShort'] = Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_COUNT_SHORT_TITLE');
					$config['titleMedium'] = 'meduim';

					$config['valuesAmount'] = [
						'firstAdditionalAmount' => [
							'title' => Loc::getMessage('CRM_REPORT_LEAD_HANDLER_LEAD_COUNT_SHORT_TITLE'),
							'value' => $amountLeadCount
						]
					];
			}

		}



		return [
			'items' => $items,
			'config' => $config
		];
	}



	/**
	 * @return array
	 */
	public function getMultipleDemoData()
	{
		return [
			'items' => [
				[
					'label' => 'First group',
					'value' => 1
				],
				[
					'label' => 'Second group',
					'value' => 5
				],
				[
					'label' => 'Third group',
					'value' => 1
				],
				[
					'label' => 'Fourth group',
					'value' => 8
				]
			]
		];
	}

	/**
	 * Array format for return this method:<br>
	 * array(
	 *      'items' => array(
	 *           array(
	 *              'groupBy' => 01.01.1970 or 15 etc.
	 *              'title' => 'Some Title',
	 *              'value' => 1,
	 *              'targetUrl' => 'http://url.domain?params=param'
	 *          ),
	 *          array(
	 *              'groupBy' => 01.01.1970 or 15 etc.
	 *              'title' => 'Some Title',
	 *              'value' => 2,
	 *              'targetUrl' => 'http://url.domain?params=param'
	 *          )
	 *      ),
	 *      'config' => array(
	 *          'groupsLabelMap' => array(
	 *              '01.01.1970' => 'Start of our internet evolution'
	 *              '15' =>  'Just a simple integer'
	 *          ),
	 *          'reportTitle' => 'Some title for this report'
	 *      )
	 * )
	 * @return array
	 */
	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();

		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;
		$items = [];
		$config = [];
		if ($groupingValue == self::GROUPING_BY_DATE)
		{
			$config['mode'] = 'date';
		}
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		$amount = [];
		$amount['value'] = 0;
		$amount['prefix'] = '';
		$amount['postfix'] = '';

		$amountCalculateItem = $calculatedData['amount'];
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES:
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
				$amount['value'] += round($amountCalculateItem, 2);
				$amount['postfix'] = '%';
				break;
			default:
				$amount['value'] += $amountCalculateItem;
		}

		unset($calculatedData['amount']);

		foreach ($calculatedData as $groupingKey => $item)
		{

			switch ($calculateValue)
			{
				case self::WHAT_WILL_CALCULATE_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_LEAD_LOSES:
				case self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION:
				case self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES:
				case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES:
					$config['unitOfMeasurement'] = '%';
					$items[] = [
						'groupBy' => $groupingKey,
						'label' => $item['title'],
						'value' => round($item['value'], 2),
						'postfix' => '%',
					];
					break;
				default:
					$items[] = array(
						'groupBy' => $groupingKey,
						'label' => $item['title'],
						'value' => $item['value'],
						'slider' => true,
						'targetUrl' => $this->getTargetUrl('/crm/lead/analytics/list/', [
							'ASSIGNED_BY_ID_name[]' => $item['title'],
							'ASSIGNED_BY_ID_label[]' => $item['title'],
							'ASSIGNED_BY_ID_value[]' => $groupingKey,
							'ASSIGNED_BY_ID[]' => $groupingKey,
						]),
					);
			}


			$config['groupsLabelMap'][$groupingKey] = $item['title'];
			$config['groupsLogoMap'][$groupingKey] = $item['logo'];
			$config['groupsTargetUrlMap'][$groupingKey] = $item['targetUrl'];
		}

		$config['reportTitle'] = $this->getFormElement('label')->getValue();

		$sliderDisableCalculateTypes = [
			self::WHAT_WILL_CALCULATE_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_LEAD_LOSES,
			self::WHAT_WILL_CALCULATE_NEW_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_REPEATED_LEAD_CONVERSION,
			self::WHAT_WILL_CALCULATE_NEW_LEAD_LOSES,
			self::WHAT_WILL_CALCULATE_REPEATED_LEAD_LOSES,
		];

		if (!in_array($calculateValue, $sliderDisableCalculateTypes))
		{
			$amount['slider'] = true;
			$amount['targetUrl'] = $this->getTargetUrl('/crm/lead/analytics/list/');
		}

		$config['amount'] = $amount;
		$result =  [
			'items' => $items,
			'config' => $config,
		];
		return $result;
	}

	/**
	 * @param $baseUri
	 * @param array $params
	 * @return string
	 */
	public function getTargetUrl($baseUri, $params = [])
	{
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;
		$filterParameters = $this->getFilterParameters();

		if (!empty($filterParameters['TIME_PERIOD']))
		{
			/** @var DateTime $from */
			$from = $filterParameters['TIME_PERIOD']['from'];
			/** @var DateTime $to */
			$to = $filterParameters['TIME_PERIOD']['to'];


			$params['ACTIVE_TIME_PERIOD_datesel'] =  $filterParameters['TIME_PERIOD']['datesel'];
			$params['ACTIVE_TIME_PERIOD_month'] =  $filterParameters['TIME_PERIOD']['month'];
			$params['ACTIVE_TIME_PERIOD_year'] =  $filterParameters['TIME_PERIOD']['year'];
			$params['ACTIVE_TIME_PERIOD_quarter'] =  $filterParameters['TIME_PERIOD']['quarter'];
			$params['ACTIVE_TIME_PERIOD_days'] =  $filterParameters['TIME_PERIOD']['days'];
			$params['ACTIVE_TIME_PERIOD_from'] =  $from->toString();
			$params['ACTIVE_TIME_PERIOD_to'] =  $to->toString();


		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_REPEATED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_ACTIVE_LEAD_COUNT:
				$params['IS_RETURN_CUSTOMER'] = 'Y';
				break;
			case self::WHAT_WILL_CALCULATE_NEW_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_NEW_ACTIVE_LEAD_COUNT:
				$params['IS_RETURN_CUSTOMER'] = 'N';
				break;
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_NEW_LOST_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_LOST_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_LOST_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID'] = 'F';
				break;
			case self::WHAT_WILL_CALCULATE_NEW_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_GOOD_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_GOOD_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID'] = ['P', 'S'];
				break;
			case self::WHAT_WILL_CALCULATE_NEW_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_CONVERTED_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_CONVERTED_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID'] = 'S';
				break;
			case self::WHAT_WILL_CALCULATE_NEW_ACTIVE_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_REPEATED_ACTIVE_LEAD_COUNT:
			case self::WHAT_WILL_CALCULATE_ACTIVE_LEAD_COUNT:
				$params['STATUS_SEMANTIC_ID'] = 'P';
				break;
		}
		return parent::getTargetUrl($baseUri, $params); // TODO: Change the autogenerated stub
	}

	/**
	 * @return array
	 */
	public function getMultipleGroupedDemoData()
	{
		return [];
	}

	/**
	 * In some case, need to dynamically disable some report handler
	 * @return bool
	 */
	public function isEnabled()
	{
		return \Bitrix\Crm\Settings\LeadSettings::isEnabled();
	}
}