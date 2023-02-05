<?php

namespace Bitrix\Crm\Integration\Report\Handler\Order;

use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
use Bitrix\Crm\Integration\Report\View\FunnelGrid;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\Binding\OrderContactCompanyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Report\VisualConstructor\Fields\Valuable\Hidden;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;
use Bitrix\Crm\Integration\Report\Handler;
use Bitrix\Sale\Internals\OrderTable;

/**
 * Class Order
 * @package Bitrix\Crm\Integration\Report\Handler\Order
 */
abstract class BaseGrid extends Handler\Base implements IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	protected $loadedData = null;

	const WHAT_WILL_CALCULATE_ORDER_COUNT = 'ORDER_COUNT';
	const WHAT_WILL_CALCULATE_ORDER_SUM = 'ORDER_SUM';
	const WHAT_WILL_CALCULATE_ORDER_WON_COUNT = 'ORDER_WON_COUNT';
	const WHAT_WILL_CALCULATE_ORDER_WON_SUM = 'ORDER_WON_SUM';
	const WHAT_WILL_CALCULATE_ORDER_LOSES_COUNT = 'ORDER_LOSES_COUNT';
	const WHAT_WILL_CALCULATE_ORDER_LOSES_SUM = 'ORDER_LOSES_SUM';

	const WHAT_WILL_CALCULATE_ORDER_PRODUCT_COUNT = 'ORDER_PRODUCT_COUNT';
	const WHAT_WILL_CALCULATE_ORDER_DISCOUNT_SUM = 'ORDER_DISCOUNT_SUM';
	const WHAT_WILL_CALCULATE_ORDER_AVERAGE_SUM = 'ORDER_AVERAGE_SUM';
	const WHAT_WILL_CALCULATE_ORDER_CONVERSION = 'ORDER_CONVERSION';
	const WHAT_WILL_CALCULATE_ORDER_LOSES = 'ORDER_LOSES';

	const WHAT_WILL_CALCULATE_ORDER_DATA_FOR_FUNNEL = 'ORDER_DATA_FOR_FUNNEL';
	const WHAT_WILL_CALCULATE_SUCCESS_ORDER_DATA_FOR_FUNNEL = 'SUCCESS_ORDER_DATA_FOR_FUNNEL';

	const WHAT_WILL_CALCULATE_FIRST_ORDER_WON_SUM = 'FIRST_ORDER_WON_SUM';

	const GROUPING_BY_STATUS_ID = 'STATUS_ID';
	const GROUPING_BY_RESPONSIBLE_ID = 'RESPONSIBLE_ID';
	const GROUPING_BY_USER_ID = 'USER';
	const FILTER_FIELDS_PREFIX = 'FROM_ORDER_';

	const STATUS_DEFAULT_COLORS = [
		'DEFAULT_COLOR' => '#ACE9FB',
		'DEFAULT_FINAL_SUCCESS_COLOR' => '#DBF199',
		'DEFAULT_FINAL_UN_SUCCESS_COLOR' => '#FFBEBD',
		'DEFAULT_LINE_COLOR' => '#ACE9FB',
	];

	private $permissionEntity = null;

	abstract protected function formatData(array $data = []): array;

	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Order');
		$this->setCategoryKey('sale');
	}

	protected function collectFormElements()
	{
		parent::collectFormElements();
		$disableSuccessStagesField = new Hidden('disableSuccessStages');
		$disableSuccessStagesField->setDefaultValue(false);
		$this->addFormElement($disableSuccessStagesField);

		$shortModeField = new Hidden('shortMode');
		$shortModeField->setDefaultValue(false);
		$this->addFormElement($shortModeField);

		$isPastPeriodField = new Hidden('pastPeriod');
		$isPastPeriodField->setDefaultValue(false);
		$this->addFormElement($isPastPeriodField);

	}

	/**
	 *
	 * @param null $groupingValue Grouping field value.
	 *
	 * @return array
	 */
	public function getWhatWillCalculateOptions($groupingValue = null)
	{
		return [
			self::WHAT_WILL_CALCULATE_ORDER_COUNT => 'count'
		];
	}

	/**
	 * @param $runtime
	 *
	 * @return array
	 */
	protected function getQueryFilter(array $runtime = []): array
	{
		$filter = $this->getFilter();
		$filterId = $filter->getFilterParameters()['FILTER_ID'];
		$options = new Options($filterId, $filter::getPresetsList());


		$fieldList = $filter::getFieldsList();
		$rawParameters = $options->getFilter($fieldList);
		$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
			\Bitrix\Crm\Filter\Factory::createEntitySettings(\CCrmOwnerType::Order, $filterId)
		);
		$entityFilter->prepareListFilterParams($rawParameters);
		return $this->formatQueryFilter($rawParameters, $runtime);
	}

	private function formatQueryFilter(array $filter, array $runtime)
	{
		$result = array();
		$orderFields = array_flip(\Bitrix\Crm\Order\Order::getAllFields());

		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		if(!$searchRestriction->isExceeded(\CCrmOwnerType::Order))
		{
			\Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(\CCrmOwnerType::Order, $filter);
			if(isset($filter['SEARCH_CONTENT']))
			{
				$searchValue = $filter['SEARCH_CONTENT'];
				unset($filter['SEARCH_CONTENT']);
				if(is_string($searchValue))
				{
					$find = trim($searchValue);
					if($find !== '')
					{
						$preparedFindFilter = \Bitrix\Crm\Search\SearchEnvironment::prepareEntityFilter(
							\CCrmOwnerType::Order,
							array(
								'SEARCH_CONTENT' => \Bitrix\Crm\Search\SearchEnvironment::prepareSearchContent($find)
							)
						);
						$filter = array_merge($filter, $preparedFindFilter);
					}
				}
			}
		}

		$isFilteredByClient = false;
		$propertyItterator = 0;
		foreach($filter as $k => $v)
		{
			$k = str_replace(self::FILTER_FIELDS_PREFIX, '', $k);
			$name = preg_replace('/^\W+/', '', $k);

			if ($name === 'COMPANY_ID')
			{
				$result[] = [
					'CLIENT.ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
					'CLIENT.ENTITY_ID' => $v,
				];
				$isFilteredByClient = true;
			}
			elseif ($name === 'ASSOCIATED_CONTACT_ID')
			{
				$result[] = [
					'CLIENT.ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
					'CLIENT.ENTITY_ID' => $v,
				];
				$isFilteredByClient = true;
			}
			elseif ($name === 'SOURCE_ID')
			{
				$result['TRADING_PLATFORM.TRADING_PLATFORM_ID'] = $v;
			}
			elseif ($name === 'PAY_SYSTEM')
			{
				$result['PAYMENT.PAY_SYSTEM_ID'] = $v;
			}
			elseif ($name === 'DELIVERY_SERVICE')
			{
				$result['SHIPMENT.DELIVERY_ID'] = $v;
				$result['SHIPMENT.SYSTEM'] = 'N';
			}
			elseif($name === 'ASSOCIATED_DEAL_ID')
			{
				$result['=ORDER_BINDING.OWNER_ID'] = $v;
				$result['=ORDER_BINDING.OWNER_TYPE_ID'] = \CCrmOwnerType::Deal;

				$runtime[] = new Reference('ORDER_BINDING',
					\Bitrix\Crm\Binding\OrderEntityTable::getEntity(),
					['=ref.ORDER_ID' => 'this.ID',],
					['join_type' => 'LEFT',]
				);
			}
			elseif($name === 'COUPON')
			{
				$result['=ORDER_COUPONS.COUPON'] = $v;
			}
			elseif($name === 'XML_ID')
			{
				$result['%XML_ID'] = $v;
			}
			elseif($name === 'SHIPMENT_TRACKING_NUMBER')
			{
				$result['%SHIPMENT.TRACKING_NUMBER'] = $v;
			}
			elseif($name === 'SHIPMENT_DELIVERY_DOC_DATE')
			{
				$docDateName = str_replace('SHIPMENT_DELIVERY_DOC_DATE', 'SHIPMENT.DELIVERY_DOC_DATE', $k);
				$result[$docDateName] = $v;
			}
			elseif($name === 'TIME_PERIOD')
			{
				$timePeriodName = str_replace('TIME_PERIOD', 'DATE_INSERT', $k);
				$result[$timePeriodName] = $v;
			}
			elseif ($name === 'USER')
			{
				$buyerFilter = \Bitrix\Main\UserUtils::getAdminSearchFilter([
					'FIND' => $v
				]);
				foreach ($buyerFilter as $key => $userFilterItem)
				{
					$key = str_replace('INDEX', 'USER.INDEX', $key);
					$result[$key] = $userFilterItem;
				}
			}
			if (mb_strpos($name, 'PROPERTY_') === 0)
			{
				$propertyId = (int)str_replace('PROPERTY_', '', $name);
				if ("PROPERTY_{$propertyId}" !== $name)
				{
					continue;
				}
				$propertyTableName = "PROPERTY_{$propertyItterator}";

				$propertyValueCode = str_replace($name, "{$propertyTableName}.VALUE", $k);
				$runtime[] =
					new Reference($propertyTableName,
						OrderPropsValueTable::getEntity(),
						array(
							'=ref.ORDER_ID' => 'this.ID',
						),
						array('join_type' => 'inner')
					);

				$result[] = [
					"={$propertyTableName}.ORDER_PROPS_ID" => $propertyId,
					$propertyValueCode => $v
				];

				$propertyItterator++;
			}
			elseif (isset($orderFields[$name]) || mb_strpos($name, 'UF_') === 0)
			{
				$result[$k] = $v;
			}
		}

		if ($isFilteredByClient)
		{
			$runtime[] =
				new Reference('CLIENT',
					OrderContactCompanyTable::getEntity(),
					array(
						'=ref.ORDER_ID' => 'this.ID',
					),
					array('join_type' => 'LEFT')
				);
		}

		if(isset($filter['ACTIVITY_COUNTER']))
		{
			$this->addActivityCounterFilter($filter, $result, $runtime);
		}

		return $result;
	}

	protected function addActivityCounterFilter(array &$filter, array &$glFilter, array &$runtime)
	{
		if (is_array($filter['ACTIVITY_COUNTER']))
		{
			$counterTypeID = \Bitrix\Crm\Counter\EntityCounterType::joinType(
				array_filter($filter['ACTIVITY_COUNTER'], 'is_numeric')
			);
		}
		else
		{
			$counterTypeID = (int)$filter['ACTIVITY_COUNTER'];
		}

		if (\Bitrix\Crm\Counter\EntityCounterType::isDefined($counterTypeID))
		{
			$counterUserIDs = array();

			if(isset($filter['RESPONSIBLE_ID']))
			{
				if(is_array($filter['RESPONSIBLE_ID']))
				{
					$counterUserIDs = array_filter($filter['RESPONSIBLE_ID'], 'is_numeric');
				}
				elseif($filter['RESPONSIBLE_ID'] > 0)
				{
					$counterUserIDs[] = $filter['RESPONSIBLE_ID'];
				}
			}

			if(empty($counterUserIDs))
			{
				$counterUserIDs[] = \CCrmSecurityHelper::GetCurrentUserID();
			}

			$counter = \Bitrix\Crm\Counter\EntityCounterFactory::create(
				\CCrmOwnerType::Order,
				$counterTypeID,
				0
			);
			$activityFilterSql = $counter->getEntityListSqlExpression([
				'USER_IDS' => $counterUserIDs
			]);
			if (!empty($activityFilterSql))
			{
				if (isset($glFilter['@ID']))
				{
					$glFilter[] = [
						'@ID' => new \Bitrix\Main\DB\SqlExpression($activityFilterSql)
					];
				}
				else
				{
					$glFilter['@ID'] =  new \Bitrix\Main\DB\SqlExpression($activityFilterSql);
				}
			}
		}
	}

	protected function loadData(): array
	{
		if ($this->loadedData === null)
		{
			$this->loadedData = [];

			if (!Loader::includeModule('sale'))
			{
				return $this->loadedData;
			}

			$query = new Query(OrderTable::getEntity());

			$this->prepareQuery($query);
			$exec = $query->exec();
			$this->loadedData = $exec->fetchAll();
			$orderIds = [];
			while ($orderData = $exec->fetch())
			{
				if (isset($orderData['ID']))
				{
					$orderIds[] = $orderData['ID'];
					$orderData['BASKET'] = [];
					$this->loadedData[$orderData['ID']] = $orderData;
				}
				else
				{
					$this->loadedData[] = $orderData;
				}
			}

			if (!empty($orderIds))
			{
				$basketRows = \Bitrix\Crm\Order\Basket::getList([
					'filter' => ['=ORDER_ID' => $orderIds]
				]);
				while ($basketItem = $basketRows->fetch())
				{
					$this->loadedData[$basketItem['ORDER_ID']]['BASKET'][] = $basketItem;
				}
			}
		}
		return $this->loadedData;
	}

	/**
	 * @return array
	 */
	public function prepare()
	{
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmAuthorizationHelper::CheckReadPermission(\CCrmOwnerType::Order, 0, $userPermission))
		{
			return [];
		}

		return $this->formatData($this->loadData());
	}

	protected function convertSumToReportCurrency($sum, $currencyId)
	{
		$reportCurrencyId = \CCrmCurrency::GetAccountCurrencyID();
		if ($currencyId !== $reportCurrencyId)
		{
			$sum = \CCrmCurrency::ConvertMoney($sum, $currencyId, $reportCurrencyId);
		}
		return $sum;
	}

	public function prepareQuery(Query $query): Query
	{
		$runtimeFields = [];
		$filterParameters = $this->getQueryFilter($runtimeFields);
		$this->addToQueryFilterCase($query, $filterParameters);
		foreach ($runtimeFields as $runtime)
		{
			$query->registerRuntimeField($runtime);
		}

		$this->addPermissionsCheck($query);
		$query->addSelect('CURRENCY');

		return $query;
	}

	protected function isSumCalculation(): bool
	{
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;
		$calculationFields = [
			self::WHAT_WILL_CALCULATE_ORDER_SUM,
			self::WHAT_WILL_CALCULATE_ORDER_WON_SUM,
			self::WHAT_WILL_CALCULATE_ORDER_LOSES_SUM,
			self::WHAT_WILL_CALCULATE_ORDER_AVERAGE_SUM,
			self::WHAT_WILL_CALCULATE_ORDER_DISCOUNT_SUM
		];
		return in_array($calculateValue, $calculationFields, true);
	}

	protected function isConversionCalculateMode(): bool
	{
		$result = false;
		$viewKey = $this->getView()->getKey();
		$widget = $this->getWidgetHandler();
		if ($viewKey === ColumnFunnel::VIEW_KEY)
		{
			$funnelCalculateModeField = $widget ? $widget->getFormElement('calculateMode') : '';
			$funnelCalculateModeValue = $funnelCalculateModeField ? $funnelCalculateModeField->getValue() : '';
			if ($funnelCalculateModeValue === ColumnFunnel::CONVERSION_CALCULATE_MODE)
			{
				$result = true;
			}
		}
		elseif ($viewKey === FunnelGrid::VIEW_KEY)
		{
			$gridCalculationModeField = $widget ? $widget->getFormElement('calculateMode') : '';
			$gridCalculationModeValue = $gridCalculationModeField ? $gridCalculationModeField->getValue() : '';
			if ($gridCalculationModeValue === FunnelGrid::CONVERSION_CALCULATE_MODE)
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param Query $query
	 *
	 */
	protected function addToQueryFilterCase(Query $query, $filterParameters)
	{
		foreach ($filterParameters as $key => $value)
		{
			if ($key === 'FIND')
			{
				if ($value !== '')
				{
					$query->whereMatch('SEARCH_CONTENT', $value);
				}

				continue;
			}
			$query->addFilter($key, $value);
		}
	}

	private function addPermissionsCheck(Query $query)
	{
		global $USER;

		if (is_object($USER) && $USER->IsAdmin())
		{
			return;
		}

		$permissionEntity = $this->getPermissionEntity();
		if (isset($permissionEntity))
		{

			$query->registerRuntimeField(
				'',
				new Reference(
					'PERMS', $permissionEntity, ['=this.ID' => 'ref.ENTITY_ID'], ['join_type' => 'INNER']
				)
			);
		}
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
		$items = is_array($calculatedData['items']) ? $calculatedData['items'] : [];
		$resultItem = array_shift($items);
		return (!empty($resultItem) && is_array($resultItem)) ? $resultItem : [];
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
		$items = (isset($calculatedData['items']) && is_array($calculatedData['items'])) ? $calculatedData['items'] : [];
		$config = (isset($calculatedData['config']) && is_array($calculatedData['config'])) ? $calculatedData['config'] : [];

		$elementLabel = $this->getFormElement('label');
		$config['title'] = $elementLabel ? $elementLabel->getValue() : '';
		$config['titleShort'] = Loc::getMessage('CRM_REPORT_ORDER_HANDLER_ORDER_COUNT_SHORT_TITLE');
		$config['valuesAmount'] = [
			'firstAdditionalAmount' => [
				'title' => Loc::getMessage('CRM_REPORT_ORDER_HANDLER_ORDER_SUM_SHORT_TITLE'),
				'value' => \CCrmCurrency::MoneyToString(
					$calculatedData['amount']['sum'],
					\CCrmCurrency::GetAccountCurrencyID()
				),
				'targetUrl' => $this->getTargetUrl('/shop/orders/analytics/list/'),
			]
		];

		$shortModeField = $this->getFormElement('shortMode');
		$shortModeValue = $shortModeField ? $shortModeField->getValue() : false;
		if ($shortModeValue)
		{
			$config['mode'] = 'singleData';
		}

		return [
			'items' => $items,
			'config' => $config,
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

		$config = [];
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		$amount = [];
		$amount['prefix'] = '';
		$amount['postfix'] = '';

		if ($this->isSumCalculation())
		{
			$amount['value'] = $calculatedData['amount']['sum'];
		}
		elseif ($calculateValue === self::WHAT_WILL_CALCULATE_ORDER_CONVERSION || $calculateValue === self::WHAT_WILL_CALCULATE_ORDER_LOSES)
		{
			$amount['value'] += round( $calculatedData['amount']['ratio'] * 100, 2);
			$amount['postfix'] = '%';
		}
		else
		{
			$amount['value'] = $calculatedData['amount']['count'];
		}

		$items = $calculatedData['items'];
		foreach ($items as $groupingKey => $item)
		{
			$config['groupsLabelMap'][$groupingKey] = $item['label'];
			$config['groupsLogoMap'][$groupingKey] = $item['logo'];
			$config['groupsTargetUrlMap'][$groupingKey] = $item['profileUrl'];
		}

		$elementLabel = $this->getFormElement('label');
		$config['reportTitle'] = $elementLabel ? $elementLabel->getValue() : '';

		$sliderDisableCalculateTypes = [
			self::WHAT_WILL_CALCULATE_ORDER_CONVERSION,
			self::WHAT_WILL_CALCULATE_ORDER_LOSES
		];

		if (!in_array($calculateValue, $sliderDisableCalculateTypes))
		{
			$amount['slider'] = true;
			$amount['targetUrl'] = $this->getTargetUrl('/shop/orders/list/');
		}

		$config['amount'] = $amount;
		return  [
			'items' => $items,
			'config' => $config,
		];
	}

	/**
	 * @return array
	 */
	public function getMultipleGroupedDemoData()
	{
		return [];
	}

	protected function getConversionStatuses($statusId): array
	{
		$conversionStatuses = [];
		$allStatuses = OrderStatus::getListInCrmFormat();
		if (!isset($allStatuses[$statusId]))
		{
			return $conversionStatuses;
		}

		$statusSemantic = OrderStatus::getSemanticID($statusId);
		if ($statusSemantic === PhaseSemantics::SUCCESS || $statusSemantic === PhaseSemantics::FAILURE)
		{
			$sortValue = $allStatuses[$statusId]['SORT'];
			foreach ($allStatuses as $status)
			{
				if ($status['SORT'] >= $sortValue && OrderStatus::getSemanticID($status["STATUS_ID"]) !== PhaseSemantics::FAILURE)
				{
					$conversionStatuses[] = $status["STATUS_ID"];
				}
			}
		}

		return $conversionStatuses;
	}

	private function getPermissionEntity()
	{
		if (isset($this->permissionEntity))
		{
			return $this->permissionEntity;
		}

		$permissionSql = \CCrmPerms::BuildSql(
			\CCrmOwnerType::OrderName,
			'',
			'READ',
			['RAW_QUERY' => true, 'PERMS'=> \CCrmPerms::GetCurrentUserPermissions()]
		);

		if ($permissionSql)
		{
			$this->permissionEntity = \Bitrix\Main\Entity\Base::compileEntity(
				'order_user_perms',
				['ENTITY_ID' => ['data_type' => 'integer']],
				['table_name' => "({$permissionSql})"]
			);
		}

		return $this->permissionEntity;
	}
}
