<?php

namespace Bitrix\Crm\Integration\Report\Handler;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\History\Entity\DealStageHistoryWithSupposedTable;
use Bitrix\Crm\Integration\Report\View\ColumnFunnel;
use Bitrix\Crm\Integration\Report\View\ComparePeriods;
use Bitrix\Crm\Integration\Report\View\ComparePeriodsGrid;
use Bitrix\Crm\Integration\Report\View\FunnelGrid;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\UtmTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;
use Bitrix\Report\VisualConstructor\Fields\Valuable\Hidden;
use Bitrix\Report\VisualConstructor\IReportMultipleData;
use Bitrix\Report\VisualConstructor\IReportMultipleGroupedData;
use Bitrix\Report\VisualConstructor\IReportSingleData;
use Bitrix\Crm\StatusTable;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\LinearGraph;

/**
 * Class Deal
 * @package Bitrix\Crm\Integration\Report\Handler
 */
class Deal extends Base implements IReportSingleData, IReportMultipleData, IReportMultipleGroupedData
{
	const WHAT_WILL_CALCULATE_DEAL_COUNT = 'DEAL_COUNT';
	const WHAT_WILL_CALCULATE_DEAL_SUM = 'DEAL_SUM';
	const WHAT_WILL_CALCULATE_DEAL_WON_COUNT = 'DEAL_WON_COUNT';
	const WHAT_WILL_CALCULATE_DEAL_WON_SUM = 'DEAL_WON_SUM';
	const WHAT_WILL_CALCULATE_DEAL_LOSES_COUNT = 'DEAL_LOSES_COUNT';
	const WHAT_WILL_CALCULATE_DEAL_LOSES_SUM = 'DEAL_LOSES_SUM';

	const WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL = 'DEAL_DATA_FOR_FUNNEL';
	const WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL = 'SUCCESS_DEAL_DATA_FOR_FUNNEL';

	const WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM = 'RETURN_DEAL_WON_SUM';

	const WHAT_WILL_CALCULATE_DEAL_CONVERSION = 'DEAL_CONVERSION';

	const GROUPING_BY_STAGE = 'STAGE';
	const GROUPING_BY_DATE = 'DATE';
	const GROUPING_BY_SOURCE = 'SOURCE';
	const GROUPING_BY_RESPONSIBLE = 'RESPONSIBLE';

	const MONTH_DIVISION = 'MONTH_DIVISION';
	const SHIFTED_MONTH_DIVISION = 'SHIFTED_MONTH_DIVISION';
	const QUARTER_MONTH_DIVISION = 'MONTH_DIVISION';
	const WEEK_DAY_DIVISION = 'WEEK_DAY_DIVISION';
	const DAY_DIVISION = 'DAY_DIVISION';
	const DAY_MONTH_DIVISION = 'DAY_MONTH_DIVISION';

	const FILTER_FIELDS_PREFIX = 'FROM_DEAL_';

	const STAGE_DEFAULT_COLORS = [
		'DEFAULT_COLOR' => '#ACE9FB',
		'DEFAULT_FINAL_SUCCESS__COLOR' => '#DBF199',
		'DEFAULT_FINAL_UN_SUCCESS_COLOR' => '#FFBEBD',
		'DEFAULT_LINE_COLOR' => '#ACE9FB',
	];

	private array $stageList = [];
    private array $stageColorList = [];

	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Deal');
		$this->setCategoryKey('crm');
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
	 * @return array
	 */
	public function getGroupByOptions()
	{
		return [
			self::GROUPING_BY_STAGE => 'stage'
		];
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
			self::WHAT_WILL_CALCULATE_DEAL_COUNT => 'count'
		];
	}

	/**
	 * @param $filterParameters
	 *
	 * @return array
	 */
	public function mutateFilterParameter($filterParameters, array $fieldList)
	{
		$filterParameters = parent::mutateFilterParameter($filterParameters, $fieldList);

		$fieldsToOrmMap = $this->getDealFieldsToOrmMap();

		foreach ($filterParameters as $key => $value)
		{
			if (in_array($key, ['TIME_PERIOD', 'FIND', 'PREVIOUS_PERIOD']) || (mb_strpos($key, 'UF_') === 0))
			{
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

	public function prepare()
	{
		$filterParameters = $this->getFilterParameters();;
		$categoryId = $filterParameters['CATEGORY_ID']['value'] ?: 0;
		$userPermission = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmDeal::CheckReadPermission(0, $userPermission, $categoryId))
		{
			return false;
		}

		/** @var DropDown $grouping */
		$groupingField = $this->getFormElement('groupingBy');
		$groupingValue = $groupingField ? $groupingField->getValue() : null;

		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		$disableSuccessStageField = $this->getFormElement('disableSuccessStages');
		$disableSuccessStageValue = $disableSuccessStageField ? $disableSuccessStageField->getValue() : false;

		$query = new Query(DealTable::getEntity());

		$groupingSqlFieldName = '';
		switch ($groupingValue)
		{
			case self::GROUPING_BY_DATE:

				$dateFormat = "%%Y-%%m-%%d";
				if ($this->isDatePeriodCompare())
				{
					switch ($this->getDivisionOfDate())
					{
						case self::MONTH_DIVISION:
						case self::SHIFTED_MONTH_DIVISION:
							$dateFormat = "%%c";
							break;
						case self::WEEK_DAY_DIVISION:
							$dateFormat = "%%w";
							break;
						case self::DAY_DIVISION:
							$dateFormat = "%%Y-%%c-%%d";
							break;
						case self::DAY_MONTH_DIVISION:
							$dateFormat = "%%Y-%%c-%%d";
					}
				}
				elseif ($this->getView() instanceof LinearGraph)
				{
					switch ($this->getDivisionOfDate())
					{
						case self::MONTH_DIVISION:
						case self::SHIFTED_MONTH_DIVISION:
							$dateFormat = "'%%c'";
							break;
						case self::WEEK_DAY_DIVISION:
							$dateFormat = "%%w";
							break;
						case self::DAY_DIVISION:
							$dateFormat = "%%Y-%%c-%%d";
							break;
						case self::DAY_MONTH_DIVISION:
							$dateFormat = "%%Y-%%c-%%d";
					}
				}

				$helper = Application::getConnection()->getSqlHelper();

				if (in_array(
					$calculateValue,
					[
						self::WHAT_WILL_CALCULATE_DEAL_WON_SUM,
						self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM
					]
				))
				{
					$query->registerRuntimeField(
						new ExpressionField(
							'DATE_CREATE_DAY', $helper->formatDate($dateFormat, '%s'), 'FULL_HISTORY.CLOSE_DATE'
						)
					);
				}
				else
				{
					$query->registerRuntimeField(
						new ExpressionField('DATE_CREATE_DAY', $helper->formatDate($dateFormat, '%s'), 'DATE_CREATE')
					);
				}

				if (!in_array(
					$calculateValue,
					[
						self::WHAT_WILL_CALCULATE_DEAL_SUM,
						self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM,
						self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM,
					]
				))
				{
					$query->addGroup('DATE_CREATE_DAY');
				}
				else
				{
					$query->addSelect(new ExpressionField('DISTINCT_OWNER_ID', 'DISTINCT %s', 'FULL_HISTORY.OWNER_ID'));
				}
				$groupingSqlFieldName = 'DATE_CREATE_DAY';

				$query->addSelect('DATE_CREATE_DAY');
				break;
			case self::GROUPING_BY_STAGE:

				$query->addSelect('FULL_HISTORY.STAGE_ID', 'STAGE_KEY');
				$query->addGroup('FULL_HISTORY.STAGE_ID');

				if (in_array(
					$calculateValue,
					[
						self::WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL,
						self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL,
					]
				))
				{
					$query->addGroup('FULL_HISTORY.OWNER_ID');
				}
				$stageNameListByStageId = [];
				foreach ($this->getStageList() as $status)
				{
					$stageNameListByStageId[$status['STATUS_ID']]['NAME'] = $status['NAME'];
					$stageNameListByStageId[$status['STATUS_ID']]['ENTITY_ID'] = $status['ENTITY_ID'];
				}

				break;
			case self::GROUPING_BY_SOURCE:

				if (!in_array(
					$calculateValue,
					[
						self::WHAT_WILL_CALCULATE_DEAL_SUM,
						self::WHAT_WILL_CALCULATE_DEAL_WON_SUM,
						self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM,
						self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM,
					]
				))
				{
					$query->addGroup('SOURCE_ID');
				}
				else
				{
					$query->addSelect(new ExpressionField('DISTINCT_OWNER_ID', 'DISTINCT %s', 'FULL_HISTORY.OWNER_ID'));
				}
				$query->addSelect('SOURCE_ID');
				$groupingSqlFieldName = 'SOURCE_ID';

				foreach ($this->getSourceNameList() as $source)
				{
					$sourceNameListByStatusId[$source['STATUS_ID']] = $source['NAME'];
				}
				break;
			case self::GROUPING_BY_RESPONSIBLE:

				if (!in_array(
					$calculateValue,
					[
						self::WHAT_WILL_CALCULATE_DEAL_SUM,
						self::WHAT_WILL_CALCULATE_DEAL_WON_SUM,
						self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM,
						self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM,
					]
				))
				{
					$query->addGroup('ASSIGNED_BY_ID');
				}
				else
				{
					$query->addSelect(new ExpressionField('DISTINCT_OWNER_ID', 'DISTINCT %s', 'FULL_HISTORY.OWNER_ID'));
				}
				$query->addSelect('ASSIGNED_BY_ID');
				$groupingSqlFieldName = 'ASSIGNED_BY_ID';

				break;
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL:
				$query->addSelect(Query::expr()->max('OPPORTUNITY_ACCOUNT'), 'MAX_OPPORTUNITY_ACCOUNT');
				$query->addSelect('FULL_HISTORY.OWNER_ID', 'FULL_HISTORY_OWNER_ID');
				$query->addSelect(Query::expr()->min('FULL_HISTORY.IS_SUPPOSED'), 'FULL_HISTORY_IS_SUPPOSED');
				$query->addSelect(Query::expr()->max('FULL_HISTORY.SPENT_TIME'), 'FULL_HISTORY_SPENT_TIME');
				$query->addSelect(Query::expr()->max('ACCOUNT_CURRENCY_ID'), 'MAX_ACCOUNT_CURRENCY_ID');
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_CONVERSION:
				$query->addSelect(new ExpressionField('VALUE', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_WON_COUNT:
			case self::WHAT_WILL_CALCULATE_DEAL_COUNT:
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_COUNT:
				$query->addSelect(new ExpressionField('VALUE', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM:
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				$query->addSelect('OPPORTUNITY_ACCOUNT');
				$query->addSelect('ACCOUNT_CURRENCY_ID');
				break;
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				$query->where('IS_RETURN_CUSTOMER', 'Y');
				break;
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_COUNT:
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				$query->where('FULL_HISTORY.STAGE_SEMANTIC_ID', 'S');
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_COUNT:
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM:
				$query->where('FULL_HISTORY.STAGE_SEMANTIC_ID', 'F');
				break;
			case self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL:
				$query->where('FULL_HISTORY.STAGE_SEMANTIC_ID', 'S');
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_CONVERSION:
				$query->addGroup('FULL_HISTORY.STAGE_SEMANTIC_ID');
				$query->addSelect('FULL_HISTORY.STAGE_SEMANTIC_ID', 'STAGE_SEMANTIC_ID_VALUE');
				break;
		}

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		$this->addPermissionsCheck($query);

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_DEAL_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM:
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				$querySql = "SELECT res.{$groupingSqlFieldName}, SUM(res.OPPORTUNITY_ACCOUNT) as VALUE, res.ACCOUNT_CURRENCY_ID FROM(";
				$querySql .= $query->getQuery();
				$querySql .= ") as res GROUP BY {$groupingSqlFieldName}";
				$results = Application::getConnection()->query($querySql);
				break;
			case self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL:
				$querySql = "SELECT res.STAGE_KEY, (SUM(res.FULL_HISTORY_SPENT_TIME) / SUM(CASE WHEN (res.FULL_HISTORY_IS_SUPPOSED = 'N' AND res.FULL_HISTORY_SPENT_TIME IS NOT NULL) THEN 1 ELSE 0 END)) as SPENT_TIME,  COUNT(DISTINCT res.FULL_HISTORY_OWNER_ID) as VALUE, SUM(res.MAX_OPPORTUNITY_ACCOUNT) as SUM, MAX_ACCOUNT_CURRENCY_ID as ACCOUNT_CURRENCY_ID FROM(";
				$querySql .= $query->getQuery();
				$querySql .= ") as res GROUP BY res.STAGE_KEY";

				$results = Application::getConnection()->query($querySql);

				break;
			default:
				$results = $query->exec()->fetchAll();
		}

		$amountValue = 0;
		$amountSum = 0;
		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_DEAL_CONVERSION:
				$allDealCount = [];
				$successDealCount = [];
				$successAmountDealCount = 0;
				$groupingFieldName = 'withoutGrouping';

				switch ($groupingValue)
				{
					case self::GROUPING_BY_RESPONSIBLE:
						$allDealCount = $this->getDealAmountCountByResponsible();
						$allAmountDealCount = array_sum($allDealCount);
						break;
					default:
						$allDealCount['withoutGrouping'] = $this->getDealAmountCount();
						$allAmountDealCount = $allDealCount['withoutGrouping'];
				}

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

					if ($result['STAGE_SEMANTIC_ID_VALUE'] == 'S')
					{
						$successDealCount[$groupingFieldValue] += $result['VALUE'];
						$successAmountDealCount += $result['VALUE'];
					}

				}
				$results = [];

				foreach ($allDealCount as $groupingKey => $count)
				{
					if (!empty($successDealCount[$groupingKey]))
					{
						$results[] = [
							$groupingFieldName => $groupingKey,
							'VALUE' => $count > 0 ? ($successDealCount[$groupingKey] / $count) * 100 : 0
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

				$amountValue = $allAmountDealCount ? ($successAmountDealCount / $allAmountDealCount) * 100 : 0;
				break;
		}

		$dealCalculatedValue = [];

		$stageNum = 0;
		foreach ($results as $result)
		{
			if ($calculateValue !== static::WHAT_WILL_CALCULATE_DEAL_CONVERSION)
			{
				$stageNum++;
				if ($this->getView()->getKey() !== ColumnFunnel::VIEW_KEY)
				{
					$amountValue += $result['VALUE'];
				}
			}

			switch ($groupingValue)
			{
				case self::GROUPING_BY_DATE:
					$dealCalculatedValue[$result['DATE_CREATE_DAY']]['value'] = $result['VALUE'];
					$dealCalculatedValue[$result['DATE_CREATE_DAY']]['title'] = $result['DATE_CREATE_DAY'];
					$dealCalculatedValue[$result['DATE_CREATE_DAY']]['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
						? $result['ACCOUNT_CURRENCY_ID'] : null;
					break;
				case self::GROUPING_BY_STAGE:

					if ($this->getView()->getKey() === ColumnFunnel::VIEW_KEY)
					{
						if ($stageNum === 1)
						{
							$dealCountAndSum = $this->getDealAmountCountAndSum();
							$amountValue = $dealCountAndSum['COUNT'];
							$amountSum += $dealCountAndSum['SUM'];
						}

						$stageSemanticId = \CCrmDeal::GetSemanticID($result['STAGE_KEY']);
						if ($this->isConversionCalculateMode())
						{
							$dealCalculatedValue[$result['STAGE_KEY']]['value'] = $result['VALUE'];
							$dealCalculatedValue[$result['STAGE_KEY']]['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
								? $result['ACCOUNT_CURRENCY_ID'] : null;
							$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['sum']['VALUE'] = 0;
							$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['sum']['currencyId'] = \CCrmCurrency::GetAccountCurrencyID();;

							if ($result['SUM'])
							{
								$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['sum'] = [
									'VALUE' => $result['SUM'],
									'currencyId' => !empty($result['ACCOUNT_CURRENCY_ID'])
										? $result['ACCOUNT_CURRENCY_ID'] : null
								];
							}

							if (!PhaseSemantics::isFinal($stageSemanticId))
							{
								$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['avgSpentTime']['VALUE'] = (float)$result['SPENT_TIME'];
							}

							$dealCalculatedValue[$result['STAGE_KEY']]['title'] = !empty($stageNameListByStageId[$result['STAGE_KEY']]['NAME'])
								? $stageNameListByStageId[$result['STAGE_KEY']]['NAME'] : '';
							$dealCalculatedValue[$result['STAGE_KEY']]['color'] = $this->getStageColor(
								$result['STAGE_KEY']
							);
						}
						else
						{
							$dealCalculatedValue[$result['STAGE_KEY']]['value'] = $result['VALUE'];
							$dealCalculatedValue[$result['STAGE_KEY']]['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
								? $result['ACCOUNT_CURRENCY_ID'] : null;
							$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['sum']['VALUE'] = 0;
							$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['sum']['currencyId'] = \CCrmCurrency::GetAccountCurrencyID();

							if ($result['SUM'])
							{
								$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['sum'] = [
									'VALUE' => $result['SUM'],
									'currencyId' => !empty($result['ACCOUNT_CURRENCY_ID'])
										? $result['ACCOUNT_CURRENCY_ID'] : null
								];
							}

							if (!PhaseSemantics::isFinal($stageSemanticId))
							{
								$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues']['avgSpentTime']['VALUE'] = (float)$result['SPENT_TIME'];
							}

							$dealCalculatedValue[$result['STAGE_KEY']]['title'] = !empty($stageNameListByStageId[$result['STAGE_KEY']]['NAME'])
								? $stageNameListByStageId[$result['STAGE_KEY']]['NAME'] : '';
							$dealCalculatedValue[$result['STAGE_KEY']]['color'] = $this->getStageColor(
								$result['STAGE_KEY']
							);
						}

					}
					else
					{
						$dealCalculatedValue[$result['STAGE_KEY']]['value'] = $result['VALUE'];
						$dealCalculatedValue[$result['STAGE_KEY']]['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
							? $result['ACCOUNT_CURRENCY_ID'] : null;
						if ($result['SUM'])
						{
							$dealCalculatedValue[$result['STAGE_KEY']]['additionalValues'] = [
								'sum' => [
									'VALUE' => $result['SUM'],
									'currencyId' => !empty($result['ACCOUNT_CURRENCY_ID'])
										? $result['ACCOUNT_CURRENCY_ID'] : null
								]
							];
						}
						$dealCalculatedValue[$result['STAGE_KEY']]['title'] = !empty($stageNameListByStageId[$result['STAGE_KEY']]['NAME'])
							? $stageNameListByStageId[$result['STAGE_KEY']]['NAME'] : '';
						$dealCalculatedValue[$result['STAGE_KEY']]['color'] = $this->getStageColor(
							$result['STAGE_KEY']
						);
					}

					break;
				case self::GROUPING_BY_SOURCE:
					$dealCalculatedValue[$result['SOURCE_ID']]['value'] = $result['VALUE'];
					$dealCalculatedValue[$result['SOURCE_ID']]['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
						? $result['ACCOUNT_CURRENCY_ID'] : null;
					$dealCalculatedValue[$result['SOURCE_ID']]['title'] = !empty($sourceNameListByStatusId[$result['SOURCE_ID']])
						? $sourceNameListByStatusId[$result['SOURCE_ID']] : '';
					break;
				case self::GROUPING_BY_RESPONSIBLE:
					if ($result['ASSIGNED_BY_ID'] == 0)
					{
						continue 2;
					}
					//TODO optimise here
					$userInfo = $this->getUserInfo($result['ASSIGNED_BY_ID']);
					$dealCalculatedValue[$result['ASSIGNED_BY_ID']]['value'] = $result['VALUE'];
					$dealCalculatedValue[$result['ASSIGNED_BY_ID']]['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
						? $result['ACCOUNT_CURRENCY_ID'] : null;
					$dealCalculatedValue[$result['ASSIGNED_BY_ID']]['title'] = $userInfo['name'];
					$dealCalculatedValue[$result['ASSIGNED_BY_ID']]['logo'] = $userInfo['icon'];
					$dealCalculatedValue[$result['ASSIGNED_BY_ID']]['defaultUserLogo'] = true;
					$dealCalculatedValue[$result['ASSIGNED_BY_ID']]['targetUrl'] = $userInfo['link'];
					break;
				default:
					$dealCalculatedValue['withoutGrouping'] = $result['VALUE'];
					$dealCalculatedValue['currencyId'] = !empty($result['ACCOUNT_CURRENCY_ID'])
						? $result['ACCOUNT_CURRENCY_ID'] : null;
					break;
			}

		}

		if ($groupingValue === self::GROUPING_BY_STAGE &&
			isset($stageNameListByStageId) &&
			$calculateValue !== self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL)
		{
			$sortedDealCountListByStage = [];
			foreach ($stageNameListByStageId as $stageId => $status)
			{
				if (!empty($dealCalculatedValue[$stageId]))
				{
					$sortedDealCountListByStage[$stageId] = $dealCalculatedValue[$stageId];
				}
				else
				{
					$sortedDealCountListByStage[$stageId] = [
						'value' => 0,
						'title' => $status['NAME'],
						'color' => $this->getStageColor($stageId)
					];
				}
			}

			$dealCalculatedValue = $sortedDealCountListByStage;
		}

		$dealCalculatedValue['amount']['value'] = $amountValue;
		$dealCalculatedValue['amount']['sum'] = $amountSum;

		if ($calculateValue !== self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL)
		{
			$dealCalculatedValue['amount']['successPassTime'] = $this->getDealPassingTime();
		}

		if ($disableSuccessStageValue)
		{
			unset($dealCalculatedValue['WON']);
		}

		return $dealCalculatedValue;
	}

	protected function isConversionCalculateMode()
	{
		$result = false;
		$viewKey = $this->getView()->getKey();
		if ($viewKey === ColumnFunnel::VIEW_KEY)
		{
			$funnelCalculateModeField = $this->getWidgetHandler()->getFormElement('calculateMode');
			$funnelCalculateModeValue = $funnelCalculateModeField->getValue();
			if ($funnelCalculateModeValue === ColumnFunnel::CONVERSION_CALCULATE_MODE)
			{
				$result = true;
			}
		}
		elseif ($viewKey === FunnelGrid::VIEW_KEY)
		{
			$gridCalculationModeField = $this->getWidgetHandler()->getFormElement('calculateMode');
			$gridCalculationModeValue = $gridCalculationModeField->getValue();
			if ($gridCalculationModeValue === FunnelGrid::CONVERSION_CALCULATE_MODE)
			{
				$result = true;
			}
		}

		return $result;
	}

	private function isDatePeriodCompare()
	{
		$view = $this->getView();

		return ($view instanceof ComparePeriodsGrid || $view instanceof ComparePeriods);

	}

	private function getDealPassingTime()
	{
		$filterParameters = $this->getFilterParameters();
		$query = new Query(DealTable::getEntity());
		$query->addSelect(new ExpressionField('AVG_SPENT_TIME', 'AVG(%s)', 'FULL_HISTORY.SPENT_TIME'));
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		$this->addPermissionsCheck($query);
		$query->whereNot('FULL_HISTORY.STAGE_SEMANTIC_ID', 'S');
		$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
		//$query->addGroup('FULL_HISTORY.STAGE_ID');

		$results = $query->exec()->fetchAll();

		$successSpentTime = 0;
		if (!$results)
		{
			return $successSpentTime;
		}
		foreach ($results as $result)
		{
			$successSpentTime += $result['AVG_SPENT_TIME'];
		}

		return $successSpentTime;
	}

	private function getDealAmountCount()
	{
		$filterParameters = $this->getFilterParameters();

		$query = new Query(DealTable::getEntity());
		$query->addSelect(new ExpressionField('COUNT', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));

		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);
		$this->addPermissionsCheck($query);
		$result = $query->exec()->fetchAll();

		return !empty($result[0]['COUNT']) ? $result[0]['COUNT'] : 0;
	}

	private function getDealAmountCountByResponsible()
	{
		$filterParameters = $this->getFilterParameters();

		$query = new Query(DealTable::getEntity());
		$query->addSelect(new ExpressionField('COUNT', 'COUNT(DISTINCT %s)', 'FULL_HISTORY.OWNER_ID'));
		$query->addSelect('ASSIGNED_BY_ID');
		$query->addGroup('ASSIGNED_BY_ID');
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);

		$this->addPermissionsCheck($query);
		$results = $query->exec()->fetchAll();

		$amountByResponsible = [];
		foreach ($results as $result)
		{
			$amountByResponsible[$result['ASSIGNED_BY_ID']] = $result['COUNT'];
		}

		return $amountByResponsible;
	}

	private function getDealAmountCountAndSum()
	{
		$filterParameters = $this->getFilterParameters();

		$query = new Query(DealTable::getEntity());
		$query->addSelect(new ExpressionField('DISTINCT_OWNER_ID', 'DISTINCT %s', 'FULL_HISTORY.OWNER_ID'));
		$query->addSelect('OPPORTUNITY_ACCOUNT');
		$query->addSelect('ACCOUNT_CURRENCY_ID', 'CURRENCY');
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);

		$this->addPermissionsCheck($query);

		$connection = Application::getConnection();

		$querySql = 'SELECT SUM(res.OPPORTUNITY_ACCOUNT) as SUM, COUNT(res.DISTINCT_OWNER_ID) COUNT, res.CURRENCY as CURRENCY FROM(';
		$querySql .= $query->getQuery();
		$querySql .= ') as res';
		$queryWithResult = $connection->query($querySql);
		$result = $queryWithResult->fetchAll();

		return !empty($result[0])
			? $result[0]
			: [
				'COUNT' => 0,
				'SUM' => 0,
				'CURRENCY' => \CCrmCurrency::GetAccountCurrencyID()
			];
	}

	/**
	 * @param Query $query
	 *
	 */
	protected function addToQueryFilterCase(Query $query, $filterParameters)
	{
		if (!$this->isConversionCalculateMode())
		{
			$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
		}

		foreach ($filterParameters as $key => $value)
		{
			if ($key === 'TIME_PERIOD' || $key === 'PREVIOUS_PERIOD')
			{
				continue;
			}
			else if ($key === 'FIND')
			{
				if ($value !== '')
				{
					$query->whereMatch('SEARCH_CONTENT', $value);
				}

				continue;
			}

			switch ($value['type'])
			{
				case 'date':
					if ($value['from'] !== "")
					{
						$query->where($key, '>=', new DateTime($value['from']));
					}

					if ($value['to'] !== "")
					{
						$query->where($key, '<=', new DateTime($value['to']));
					}
					break;
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
				case 'text':
					// todo: check
					$query->whereLike($key, '%'.$value['value'].'%');
					break;
				case 'none':
				case 'list':
				case 'checkbox':
				case 'custom_entity':
				case 'dest_selector':
				case 'entity_selector':
					$query->addFilter($key, $value['value']);
					break;
			}

			if ($key === 'STAGE_SEMANTIC_ID')
			{
				$subQuery = DealStageHistoryWithSupposedTable::query();
				$subQuery->addSelect('OWNER_ID');
				if (!empty($filterParameters['TIME_PERIOD']))
				{
					$subQuery
						->where('LAST_UPDATE_DATE', '<=', new DateTime($filterParameters['TIME_PERIOD']['to']))
						->where('CLOSE_DATE', '>=', new DateTime($filterParameters['TIME_PERIOD']['from']));
				}

				$subQuery->registerRuntimeField(
					new ExpressionField(
						'IS_EXIST_HISTORY', "CASE WHEN {$query->getInitAlias()}.ID = %s THEN 1 ELSE 0 END", 'OWNER_ID'
					)
				);
				$subQuery->whereIn('STAGE_SEMANTIC_ID', $value['value']);
				$subQuery->where('IS_EXIST_HISTORY', 1);

				$query->whereExists($subQuery);
			}
		}

		//GET only not template entities
		$query->where('IS_RECURRING', 'N');

		if (!isset($filterParameters['CATEGORY_ID']['value']))
		{
			$query->where('CATEGORY_ID', 0);
		}
	}

	protected function addTimePeriodToQuery(Query $query, $timePeriodValue)
	{
		if ($timePeriodValue['from'] !== "" && $timePeriodValue['to'] !== "")
		{
			$toDateValue = DateTime::createFromUserTime($timePeriodValue['to']);
			$fromDateValue = DateTime::createFromUserTime($timePeriodValue['from']);

			$isPastPeriodField = $this->getFormElement('pastPeriod');

			if ($isPastPeriodField && $isPastPeriodField->getValue())
			{
				$timePeriodDiffSecs = $this->getTimePeriodDiff();
				$diffDaysCount = $this->getDayCountFromSecs($timePeriodDiffSecs);
				$toDateValue->add("-{$diffDaysCount} days");
				$fromDateValue->add("-{$diffDaysCount} days");
			}

			$query
				->where('FULL_HISTORY.LAST_UPDATE_DATE', '<=', $toDateValue)
				->where('FULL_HISTORY.CLOSE_DATE', '>=', $fromDateValue);
		}
	}

	private function getDivisionOfDate()
	{
		$filterParameters = $this->getFilterParameters();
		$view = $this->getView();
		if ($filterParameters['TIME_PERIOD'])
		{
			$timePeriodFilterParams = $filterParameters['TIME_PERIOD'];

			switch ($timePeriodFilterParams['datesel'])
			{
				case DateType::CURRENT_WEEK:
				case DateType::LAST_WEEK:
				case DateType::NEXT_WEEK:
					return self::WEEK_DAY_DIVISION;
					break;
				case DateType::CURRENT_MONTH:
				case DateType::NEXT_MONTH:
				case DateType::LAST_MONTH:
				case DateType::MONTH:
					return self::DAY_DIVISION;
					break;
				case DateType::LAST_7_DAYS:
				case DateType::LAST_30_DAYS:
				case DateType::LAST_60_DAYS:
				case DateType::LAST_90_DAYS:
				case DateType::PREV_DAYS:
				case DateType::NEXT_DAYS:
				case DateType::RANGE:
					if ($view instanceof ComparePeriodsGrid || $view instanceof ComparePeriods)
					{
						return self::DAY_MONTH_DIVISION;
					}
					else
					{
						return self::DAY_DIVISION;
					}
					break;
				case DateType::QUARTER:
				case DateType::CURRENT_QUARTER:
					if ($view instanceof ComparePeriodsGrid || $view instanceof ComparePeriods)
					{
						return self::SHIFTED_MONTH_DIVISION;
					}
					else
					{
						return self::MONTH_DIVISION;
					}
					break;
				case DateType::YEAR:
					return self::MONTH_DIVISION;
					break;
			}
		}

		$timePeriodDiff = $this->getTimePeriodDiff();
		$monthCount = $timePeriodDiff / (60 * 60 * 24 * 31);
		if ($monthCount >= 12)
		{
			return self::MONTH_DIVISION;
		}
		elseif ($monthCount > 1)
		{
			if ($view instanceof ComparePeriodsGrid || $view instanceof ComparePeriods)
			{
				return self::SHIFTED_MONTH_DIVISION;
			}
			else
			{
				return self::MONTH_DIVISION;
			}
		}
		else

		{
			return self::DAY_DIVISION;
		}
	}

	private function getTimePeriodDiff()
	{
		$filterParameters = $this->getFilterParameters();
		if ($filterParameters['TIME_PERIOD'])
		{
			$toDateValue = new DateTime($filterParameters['TIME_PERIOD']['to']);
			$fromDateValue = new DateTime($filterParameters['TIME_PERIOD']['from']);

			$timePeriodDiff = $toDateValue->getTimestamp() - $fromDateValue->getTimestamp();
		}
		else
		{
			$currentDate = new Date();
			$timePeriodDiff = $currentDate->getTimestamp();
		}

		return $timePeriodDiff;
	}

	private function getDayCountFromSecs($secs)
	{
		return (int)ceil($secs / (60 * 60 * 24));
	}

	protected function addPermissionsCheck(Query $query, $userId = 0)
	{
		if ($userId <= 0)
		{
			$userId = EntityAuthorization::getCurrentUserID();
		}
		$userPermissions = EntityAuthorization::getUserPermissions($userId);

		$permissionSql = \CCrmDeal::BuildPermSql('D','READ', [
			'PERMS' => $userPermissions,
			'RAW_QUERY' => true
		]);

		if ($permissionSql)
		{
			$query->whereIn('ID', new SqlExpression($permissionSql));
		}
	}

	private function getDealFieldsToOrmMap()
	{
		$fields = [
			'ID' => 'ID',
			'TITLE' => 'TITLE',
			'ASSIGNED_BY_ID' => 'ASSIGNED_BY_ID',
			'OPPORTUNITY' => 'OPPORTUNITY',
			'CURRENCY_ID' => 'CURRENCY_ID',
			'PROBABILITY' => 'PROBABILITY',
			'IS_NEW' => 'IS_NEW',
			'IS_RETURN_CUSTOMER' => 'IS_RETURN_CUSTOMER',
			'IS_REPEATED_APPROACH' => 'IS_REPEATED_APPROACH',
			'SOURCE_ID' => 'SOURCE_ID',
			'STAGE_SEMANTIC_ID' => 'STAGE_SEMANTIC_ID',
			'STAGE_ID' => 'STAGE_ID',
			'STAGE_ID_FROM_HISTORY' => 'HISTORY.STAGE_ID',
			'STAGE_SEMANTIC_ID_FROM_HISTORY' => 'HISTORY.STAGE_SEMANTIC_ID',
			'CATEGORY_ID' => 'CATEGORY_ID',
			'BEGINDATE' => 'BEGINDATE',
			'CLOSEDATE' => 'CLOSEDATE',
			'CLOSED' => 'CLOSED',
			//'*ACTIVITY_COUNTER' => 'CLOSED',
			'EVENT_DATE' => 'EVENT_DATE',
			'EVENT_ID' => 'EVENT_ID',
			'CONTACT_ID' => 'CONTACT_ID',
			'CONTACT_FULL_NAME' => 'CONTACT.FULL_NAME',
			'COMPANY_ID' => 'COMPANY_ID',
			'COMPANY_TITLE' => 'COMPANY.TITLE',
			'COMMENTS' => 'COMMENTS',
			'TYPE_ID' => 'TYPE_ID',
			'DATE_CREATE' => 'DATE_CREATE',
			'DATE_MODIFY' => 'DATE_MODIFY',
			'CREATED_BY_ID' => 'CREATED_BY_ID',
			'MODIFY_BY_ID' => 'MODIFY_BY_ID',
			'PRODUCT_ROW_PRODUCT_ID' => 'PRODUCT_ROW.PRODUCT_ID',
			'ORIGINATOR_ID' => 'ORIGINATOR_ID',
			'WEBFORM_ID' => 'WEBFORM_ID',
			'CRM_DEAL_RECURRING_ACTIVE' => 'CRM_DEAL_RECURRING.ACTIVE',
			'CRM_DEAL_RECURRING_NEXT_EXECUTION' => 'CRM_DEAL_RECURRING.NEXT_EXECUTION',
			'CRM_DEAL_RECURRING_LIMIT_DATE' => 'CRM_DEAL_RECURRING.LIMIT_DATE',
			'CRM_DEAL_RECURRING_COUNTER_REPEAT' => 'CRM_DEAL_RECURRING.COUNTER_REPEAT',
		];

		$codeList = UtmTable::getCodeList();
		foreach ($codeList as $code)
		{
			$fields[$code] = $code.'.VALUE';
		}

		return $fields;
	}

	private function getStageColor($statusId)
	{
		$stageList = $this->getStageList();

		$colorsList = $this->getStageColorList($stageList[$statusId]['ENTITY_ID']);
		if (!isset($colorsList[$statusId]))
		{
			return self::STAGE_DEFAULT_COLORS['DEFAULT_COLOR'];
		}

		return $colorsList[$statusId];
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				$params['IS_RETURN_CUSTOMER'] = 'Y';
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_COUNT:
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM:
				$params['STAGE_SEMANTIC_ID_FROM_HISTORY'] = 'F';
				break;
		}

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_COUNT:
				$params['STAGE_SEMANTIC_ID_FROM_HISTORY'] = 'S';
				break;
		}

		return parent::getTargetUrl($baseUri, $params);
	}

	public function prepareEntityListFilter($requestParameters)
	{
		$filterParameters = $this->getFilterParameters();
		$query = DealTable::query();
		$query->addSelect('ID');
		$this->addToQueryFilterCase($query, $filterParameters);
		$this->addTimePeriodToQuery($query, $filterParameters['TIME_PERIOD']);

		foreach ($requestParameters as $parameter => $value)
		{
			switch ($parameter)
			{
				case 'STAGE_SEMANTIC_ID':
				case 'ASSIGNED_BY_ID':
					$query->where($parameter, $value);
					break;
				case 'STAGE_SEMANTIC_ID_FROM_HISTORY':
					$query->where('FULL_HISTORY.STAGE_SEMANTIC_ID', $value);
					$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
					break;
				case 'STAGE_ID_FROM_HISTORY':
					$query->where('FULL_HISTORY.STAGE_ID', $value);
					$query->where('FULL_HISTORY.IS_SUPPOSED', 'N');
					break;

				case 'STAGE_ID_FROM_SUPPOSED_HISTORY':
					$query->where('FULL_HISTORY.STAGE_ID', $value);
					break;

			}
		}

		$query->setDistinct(true);

		return [
			'__JOINS' => [
				[
					'TYPE' => 'INNER',
					'SQL' => 'INNER JOIN('.$query->getQuery().') REP ON REP.ID = L.ID'
				]
			]
		];
	}

	private function getStageColorList($entityId)
	{
		if (!empty($this->stageColorList[$entityId]))
		{
			return $this->stageColorList[$entityId];
		}

		$stageInfos = DealCategory::getStageInfos(DealCategory::convertFromStatusEntityID($entityId));

		$this->stageColorList[$entityId] = array_column($stageInfos, 'COLOR', 'STATUS_ID');

		return $this->stageColorList[$entityId];
	}

	private function getStageList(): array
	{
		if (empty($this->stageList))
		{
			$filterParameters = $this->getFilterParameters();
			$categories = [];
			if (!isset($filterParameters['CATEGORY_ID']['value']))
			{
				$categories[] = 0;
			}
			else
			{
				$categories[] = $filterParameters['CATEGORY_ID']['value'];
			}

			foreach ($categories as $category)
			{
				$stageListByCategory = DealCategory::getStageList($category);
				$entityId = $category == 0 ? 'DEAL_STAGE' : 'DEAL_STAGE_' . $category;

				foreach ($stageListByCategory as $stageId => $name)
				{
					$this->stageList[$stageId] = [
						'NAME' => $name,
						'STATUS_ID' => $stageId,
						'ENTITY_ID' => $entityId
					];
				}
			}
		}

		return $this->stageList;
	}

	private function getSourceNameList()
	{
		$sourceListQuery = new Query(StatusTable::getEntity());
		$sourceListQuery->where('ENTITY_ID', 'SOURCE');
		$sourceListQuery->addSelect('STATUS_ID');
		$sourceListQuery->addSelect('NAME');

		return $sourceListQuery->exec()->fetchAll();
	}



	private function getMonthParamsList()
	{
		return [
			1 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_JANUARY'),
				'length' => 31,
			],
			2 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_FEBRUARY'),
				'length' => 28,
			],
			3 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_MARCH'),
				'length' => 31,
			],
			4 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_APRIL'),
				'length' => 30,
			],
			5 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_MAY'),
				'length' => 31,
			],
			6 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_JUNE'),
				'length' => 30,
			],
			7 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_JULY'),
				'length' => 31,
			],
			8 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_AUGUST'),
				'length' => 31,
			],
			9 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_SEPTEMBER'),
				'length' => 30,
			],
			10 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_OCTOBER'),
				'length' => 30,
			],
			11 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_NOVEMBER'),
				'length' => 30,
			],
			12 => [
				'name' => Loc::getMessage('CRM_REPORT_DEAL_MONTH_NAME_DECEMBER'),
				'length' => 30,
			],
		];

	}

	private function getMonthNameByNum($num)
	{
		$monthList = $this->getMonthParamsList();

		return $monthList[(int)$num]['name'];
	}

	private function getWeekDayNameByNum($num)
	{
		$weekDays = [
			1 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_MONDAY'),
			2 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_THUSDAY'),
			3 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_WEDNSDAY'),
			4 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_THURSDAY'),
			5 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_FRIDAY'),
			6 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_SATURDAY'),
			7 => Loc::getMessage('CRM_REPORT_DEAL_WEEK_DAY_NAME_SUNDAY'),
		];

		return $weekDays[$num];
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

		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_DEAL_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				$resultItem['value'] = \CCrmCurrency::MoneyToString(
					$calculatedData['withoutGrouping'],
					$calculatedData['currencyId']
				);
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
		$amountDealCurrencyId = \CCrmCurrency::GetAccountCurrencyID();

		$config = [
			'title' => $this->getFormElement('label')->getValue()
		];

		if (!empty($calculatedData))
		{
			$calculateField = $this->getFormElement('calculate');
			$calculateValue = $calculateField ? $calculateField->getValue() : null;

			$shortModeField = $this->getFormElement('shortMode');
			$shortModeValue = $shortModeField ? $shortModeField->getValue() : false;

			switch ($calculateValue)
			{
				case self::WHAT_WILL_CALCULATE_DEAL_CONVERSION:
					$config['mode'] = 'singleData';
					$config['unitOfMeasurement'] = '%';
					$item['value'] = round($calculatedData['withoutGrouping'], 2);
					$item['color'] = '#9DCF00';
					$items[] = $item;
					break;
				default:
					$itemCount = 0;
					foreach ($calculatedData as $key => $data)
					{

						if ($key === 'amount') //TODO: optimise calculating of amount values
						{
							continue;
						}

						$item = [
							'label' => $data['title'],
							'value' => $data['value'],
							'color' => $data['color']
						];



						if ($calculateValue === self::WHAT_WILL_CALCULATE_DEAL_DATA_FOR_FUNNEL
							|| $calculateValue === self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL
						)
						{
							if ($this->isConversionCalculateMode())
							{
								$item['link'] = $this->getTargetUrl('/crm/deal/analytics/list/', [
									'STAGE_ID_FROM_SUPPOSED_HISTORY' => $key
								]);
							}
							else
							{
								$item['link'] = $this->getTargetUrl('/crm/deal/analytics/list/', [
									'STAGE_ID_FROM_HISTORY' => $key
								]);
							}
						}

						$config['additionalValues']['firstAdditionalValue']['titleShort'] = Loc::getMessage(
							'CRM_REPORT_DEAL_HANDLER_DEAL_COUNT_SHORT_TITLE'
						);
						$item['additionalValues']['firstAdditionalValue'] = [
							'value' => $data['value']
						];

						if (isset($data['additionalValues']['sum']))
						{
							$amountDealCurrencyId = $data['additionalValues']['sum']['currencyId'];
							$config['additionalValues']['secondAdditionalValue']['titleShort'] = Loc::getMessage(
								'CRM_REPORT_DEAL_HANDLER_DEAL_SUM_SHORT_TITLE'
							);

							$item['additionalValues']['secondAdditionalValue'] = [
								'value' => \CCrmCurrency::MoneyToString(
									$data['additionalValues']['sum']['VALUE'],
									$data['additionalValues']['sum']['currencyId']
								),
								'currencyId' => $data['additionalValues']['sum']['currencyId']
							];
						}

						if (isset($data['additionalValues']['avgSpentTime']))
						{
							$config['additionalValues']['thirdAdditionalValue']['titleShort'] = Loc::getMessage(
								'CRM_REPORT_DEAL_HANDLER_DEAL_SPENT_TIME_SHORT_TITLE'
							);
							$item['additionalValues']['thirdAdditionalValue'] = [
								'value' => $this->getFormattedPassTime(
									$data['additionalValues']['avgSpentTime']['VALUE']
								)
							];
						}

						$stageSemanticId = \CCrmDeal::GetSemanticID($key);
						$config['additionalValues']['forthAdditionalValue']['titleShort'] = Loc::getMessage(
							'CRM_REPORT_DEAL_HANDLER_DEAL_CONVERSION_SHORT_TITLE'
						);
						$item['additionalValues']['forthAdditionalValue'] = [
							'title' => PhaseSemantics::isLost($stageSemanticId) ?
											Loc::getMessage("CRM_REPORT_DEAL_HANDLER_DEAL_LOSSES_SHORT_TITLE")
											: Loc::getMessage("CRM_REPORT_DEAL_HANDLER_DEAL_CONVERSION_SHORT_TITLE"),
							'value' => $calculatedData['amount']['value'] ? round(
								($data['value'] / $calculatedData['amount']['value']) * 100,
								2
							) : 0,
							'unitOfMeasurement' => '%',
							'helpLink' => 'someLink',
							'helpInSlider' => true
						];
						//hidden conversion on first column
						if ($calculateValue !== self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL && $itemCount < 1)
						{
							unset($item['additionalValues']['forthAdditionalValue']);
						}

						$itemCount++;



						$items[] = $item;
					}

					$calculateField = $this->getFormElement('calculate');
					$calculateValue = $calculateField ? $calculateField->getValue() : null;

					$config['titleShort'] = Loc::getMessage('CRM_REPORT_DEAL_HANDLER_DEAL_COUNT_SHORT_TITLE');

					$config['valuesAmount'] = [
						'firstAdditionalAmount' => [
							'title' => Loc::getMessage('CRM_REPORT_DEAL_HANDLER_DEAL_SUM_SHORT_TITLE'),
							'value' => \CCrmCurrency::MoneyToString(
								$calculatedData['amount']['sum'],
								$amountDealCurrencyId
							),
							'targetUrl' => $this->getTargetUrl('/crm/deal/analytics/list/'),
						]
					];

					if ($calculatedData['amount']['successPassTime'] ?? false)
					{
						$config['valuesAmount']['secondAdditionalAmount'] = [
							'title' => Loc::getMessage('CRM_REPORT_DEAL_HANDLER_DEAL_PASS_AVG_TIME_SHORT_TITLE'),
							'value' => $this->getFormattedPassTime($calculatedData['amount']['successPassTime'])
						];
					}

					switch ($calculateValue)
					{
						case self::WHAT_WILL_CALCULATE_SUCCESS_DEAL_DATA_FOR_FUNNEL:
							$config['topAdditionalTitle'] = Loc::getMessage(
								'CRM_REPORT_DEAL_HANDLER_DEAL_CONVERSION_SHORT_TITLE'
							);
							$config['topAdditionalValue'] = !empty($items[0]['additionalValues']['forthAdditionalValue']['value'])
								? $items[0]['additionalValues']['forthAdditionalValue']['value'] : 0;
							$config['topAdditionalValueUnit'] = '%';
							$config['valuesAmount']['firstAdditionalAmount']['value'] =
								($items[0]['additionalValues']['secondAdditionalValue']['value'] ?? null)
							;
							//$config['valuesAmount']['secondAdditionalAmount']['value'] =
							//    $items[0]['additionalValues']['thirdAdditionalValue']['value']
							//;

							if ($shortModeValue)
							{
								$config['mode'] = 'singleData';
							}
							unset($config['valuesAmount']['thirdAdditionalAmount']);
							break;
					}
			}

		}

		$result = [
			'items' => $items,
			'config' => $config,
		];

		return $result;
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

	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();

		$grouping = $this->getFormElement('groupingBy');
		$groupingValue = $grouping ? $grouping->getValue() : null;

		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;

		$items = [];
		$config = [];
		if ($groupingValue == self::GROUPING_BY_DATE)
		{
			$config['mode'] = 'date';
			if ($this->isDatePeriodCompare() || $this->getView() instanceof LinearGraph)
			{
				$dateDivisionType = $this->getDivisionOfDate();
				if ($dateDivisionType === self::MONTH_DIVISION)
				{
					unset($config['mode']);
				}
				else if ($dateDivisionType === self::DAY_DIVISION)
				{
					unset($config['mode']);
				}
				else if ($dateDivisionType === self::DAY_MONTH_DIVISION)
				{
					unset($config['mode']);
				}
				else if ($dateDivisionType === self::WEEK_DAY_DIVISION)
				{
					unset($config['mode']);
				}
				else if ($dateDivisionType === self::SHIFTED_MONTH_DIVISION)
				{
					unset($config['mode']);
				}
			}

		}

		$amount = [];
		$amount['value'] = 0;
		$amount['prefix'] = '';
		$amount['postfix'] = '';

		$amountCalculateItem = $calculatedData['amount']['value'];
		$calculatedDataValues = array_values($calculatedData);
		$currencyOfFirstElement =  $calculatedDataValues[0]['currencyId'] ?? \CCrmCurrency::GetAccountCurrencyID();

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_DEAL_CONVERSION:
				$amount['value'] = round($amountCalculateItem, 2);
				$amount['postfix'] = '%';
				break;
			case self::WHAT_WILL_CALCULATE_DEAL_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
			case self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM:
				$amount['value'] = \CCrmCurrency::MoneyToString(
					round($amountCalculateItem, 2),
					$currencyOfFirstElement
				);
				break;
			default:
				$amount['value'] = $amountCalculateItem;
		}

		unset($calculatedData['amount']);
		$view = $this->getView();
		foreach ($calculatedData as $groupingKey => $item)
		{

			switch ($groupingValue)
			{
				case self::GROUPING_BY_DATE:
					if ($this->isDatePeriodCompare() || $view instanceof LinearGraph)
					{
						$dateDivisionType = $this->getDivisionOfDate();
						if ($dateDivisionType === self::MONTH_DIVISION)
						{
							$item['title'] = $this->getMonthNameByNum($groupingKey);
						}
						elseif ($dateDivisionType === self::SHIFTED_MONTH_DIVISION)
						{
							$monthNum = $groupingKey;
							$firstMonth = $this->getFirstMonthNumFromTimePeriod();

							$diff = $groupingKey - $firstMonth + 1;

							$monthCount = $this->getMonthCountInTimePeriod();

							if ($diff > 0)
							{
								if ($monthNum - $monthCount > 0)
								{
									$pastMonthName = $this->getMonthNameByNum($monthNum - $monthCount);
								}
								else
								{
									$pastMonthName = $this->getMonthNameByNum($monthNum - $monthCount + 12);
								}

								$groupingKey = $this->getMonthNameByNum($monthNum).'-'.$pastMonthName;
							}
							else
							{
								$groupingKey = $this->getMonthNameByNum($monthNum + $monthCount).'-'.$this->getMonthNameByNum($monthNum);
							}

							$item['title'] = $groupingKey;

						}
						elseif ($dateDivisionType === self::WEEK_DAY_DIVISION)
						{
							if ($groupingKey === 0)
							{
								$groupingKey = 7;
							}
							$item['title'] = $this->getWeekDayNameByNum($groupingKey);
						}
						elseif ($dateDivisionType === self::DAY_DIVISION)
						{
							if (get_class($view) !== LinearGraph::class)
							{
								$currentDate = $groupingKey;
								$date = new DateTime($groupingKey, 'Y-n-d');
								$groupingKey = (int)$date->format('j');
								$item['title'] = $groupingKey;
							}

						}
						elseif ($dateDivisionType === self::DAY_MONTH_DIVISION)
						{
							$currentDate = $groupingKey;
							$daysCount = $this->getDaysCountInTimePeriod();
							$filterParameters = $this->getFilterParameters();
							if (!empty($filterParameters['TIME_PERIOD']))
							{
								$fromDateValue = new DateTime(
									$filterParameters['TIME_PERIOD']['from']
								);
								$date = new DateTime($groupingKey, 'Y-n-d');

								$dayNumInTimePeriod = $this->getDayCountBetween($fromDateValue, $date);

								if ($dayNumInTimePeriod > 0)
								{
									$prevDate = new DateTime($groupingKey, 'Y-n-d');
									$prevDate->add("-{$daysCount} days");
									$groupingKey = $date->format('d.m').'-'.$prevDate->format('d.m');
								}
								else
								{
									$nextDate = new DateTime($groupingKey, 'Y-n-d');
									$nextDate->add("+{$daysCount} days");
									$groupingKey = $nextDate->format('d.m').'-'.$date->format('d.m');
								}

								$item['title'] = $groupingKey;
							}

						}
					}
					break;
			}

			$resultItem = [
				'groupBy' => $groupingKey,
				'label' => $item['title'],
				'value' => $item['value'],
			];

			switch ($groupingValue)
			{
				case self::GROUPING_BY_DATE:
					if ($this->isDatePeriodCompare())
					{
						if ($dateDivisionType === self::SHIFTED_MONTH_DIVISION)
						{
							$resultItem['label'] = $this->getMonthNameByNum($monthNum);
						}
						elseif ($dateDivisionType === self::DAY_MONTH_DIVISION)
						{
							$resultItem['label'] = $currentDate;
						}
						elseif ($dateDivisionType === self::DAY_DIVISION)
						{
							$resultItem['label'] = $currentDate;
						}
					}
					break;
			}

			switch ($calculateValue)
			{
				case self::WHAT_WILL_CALCULATE_DEAL_CONVERSION:
					$resultItem['postfix'] = '%';
					$resultItem['value'] = round($resultItem['value'], 2);
					$resultItem['slider'] = false;
					break;
				case self::WHAT_WILL_CALCULATE_DEAL_SUM:
				case self::WHAT_WILL_CALCULATE_DEAL_WON_SUM:
				case self::WHAT_WILL_CALCULATE_RETURN_DEAL_WON_SUM:
				case self::WHAT_WILL_CALCULATE_DEAL_LOSES_SUM:

					if (!($view instanceof LinearGraph))
					{
						$resultItem['value'] = \CCrmCurrency::MoneyToString($resultItem['value'], $item['currencyId']);
					}
					break;
			}

			switch ($groupingValue)
			{
				case self::GROUPING_BY_RESPONSIBLE:
					$resultItem['slider'] = true;
					$resultItem['targetUrl'] = $this->getTargetUrl(
						'/crm/deal/analytics/list/',
						[
							'ASSIGNED_BY_ID' => $groupingKey,
						]
					);
					break;
			}

			$items[$groupingKey] = $resultItem;
			$config['groupsLabelMap'][$groupingKey] = $item['title'];
			$config['groupsLogoMap'][$groupingKey] = $item['logo'];
			$config['groupsTargetUrlMap'][$groupingKey] = $item['targetUrl'];

		}

		$config['reportTitle'] = $this->getFormElement('label')->getValue();
		$config['reportColor'] = $this->getFormElement('color')->getValue();
		$config['reportTitleShort'] = 'dasda';
		$config['reportTitleMedium'] = 'meduim';

		if ($groupingValue === self::GROUPING_BY_RESPONSIBLE)
		{
			$sliderDisableCalculateTypes = [
				self::WHAT_WILL_CALCULATE_DEAL_CONVERSION
			];
			if (!in_array($calculateValue, $sliderDisableCalculateTypes))
			{
				$amount['slider'] = true;
				$amount['targetUrl'] = $this->getTargetUrl('/crm/deal/analytics/list/');
			}
		}

		$config['amount'] = $amount;

		if ($groupingValue === self::GROUPING_BY_DATE && $this->getView() instanceof ComparePeriods)
		{
			switch ($this->getDivisionOfDate())
			{
				case self::MONTH_DIVISION:
					$monthCount = $this->getMonthCountInTimePeriod();
					$firstMonth = $this->getFirstMonthNumFromTimePeriod();
					$currentMonthNum = $firstMonth;
					for ($i = 1; $i <= $monthCount; $i++)
					{
						if (!isset($items[$currentMonthNum]))
						{
							$items[$i] = [
								'groupBy' => $currentMonthNum,
								'label' => $this->getMonthNameByNum($currentMonthNum),
								'value' => 0,
							];
							$config['groupsLabelMap'][$currentMonthNum] = $this->getMonthNameByNum($currentMonthNum);
						}
						$currentMonthNum++;
					}
					break;
				case self::SHIFTED_MONTH_DIVISION:
					$filterParameters = $this->getFilterParameters();
					if (!empty($filterParameters['TIME_PERIOD']))
					{
						$newItems = [];
						$fromDateValue = new DateTime($filterParameters['TIME_PERIOD']['from']);
						for ($i = 0; $i < 3; $i++)
						{
							$currentDate = clone $fromDateValue;
							$currentDate->add("+{$i} months");
							$pastDate = clone $fromDateValue;
							$pastMonthCount = 3 - $i;
							$pastDate->add("-{$pastMonthCount} months");

							$grouping = $this->getMonthNameByNum($currentDate->format('n')) . '-' . $this->getMonthNameByNum($pastDate->format('n'));
							if (!isset($items[$grouping]))
							{
								$newItems[$grouping] = [
									'groupBy' => $grouping,
									'label' => $grouping,
									'value' => 0,
								];
								$config['groupsLabelMap'][$grouping] = $grouping;
							}
							else
							{
								$newItems[$grouping] = $items[$grouping];
							}
						}
						$items = $newItems;
					}

					break;
				case self::WEEK_DAY_DIVISION:
					$newItems = [];
					for ($i = 1; $i <= 7; $i++)
					{
						if (!isset($items[$i]))
						{
							$newItems[$i] = [
								'groupBy' => $i,
								'label' => $this->getWeekDayNameByNum($i),
								'value' => 0,
							];
							$config['groupsLabelMap'][$i] = $this->getWeekDayNameByNum($i);
						}
						else
						{
							$newItems[$i] = $items[$i];
						}
					}

					$items = $newItems;
					break;
				case self::DAY_DIVISION:
					$newItems = [];
					for ($i = 1; $i <= 31; $i++)
					{
						if (!isset($items[$i]))
						{
							$newItems[$i] = [
								'groupBy' => $i,
								'label' => $i,
								'value' => 0,
							];
							$config['groupsLabelMap'][$i] = $i;
						}
						else
						{
							$newItems[$i] = $items[$i];
						}
					}
					$items = $newItems;
					break;
				case self::DAY_MONTH_DIVISION:
					$filterParameters = $this->getFilterParameters();
					if (!empty($filterParameters['TIME_PERIOD']))
					{
						$newItems = [];
						$fromDateValue = new DateTime($filterParameters['TIME_PERIOD']['from']);
						$daysCount = $this->getDaysCountInTimePeriod();
						for ($i = 1; $i <= $daysCount; $i++)
						{
							$currentDate = clone $fromDateValue;
							$currentDate->add("+{$i} days");

							$prevDate = clone $fromDateValue;
							$daysCountBeforeStartDate = $daysCount - $i;
							$prevDate->add("-{$daysCountBeforeStartDate} days");
							$groupingNewKey = $currentDate->format('d.m').'-'.$prevDate->format('d.m');
							if (!isset($items[$groupingNewKey]))
							{
								$newItems[$groupingNewKey] = [
									'groupBy' => $groupingNewKey,
									'label' => $currentDate->format('Y-n-d'),
									'value' => 0,
								];
								$config['groupsLabelMap'][$groupingNewKey] = $groupingNewKey;
							}
							else
							{
								$newItems[$groupingNewKey] = $items[$groupingNewKey];
							}
						}
						$items = $newItems;

						if ($daysCount > 15)
						{
							$config['categoryAxis']['labelFrequency'] = (int)ceil($daysCount / 8);
						}
					}

					break;
			}
		}
		elseif($groupingValue === self::GROUPING_BY_DATE)
		{
			switch ($this->getDivisionOfDate())
			{
				case self::DAY_DIVISION:
					$daysCount = $this->getDaysCountInTimePeriod();
					if ($daysCount > 15)
					{
						$config['categoryAxis']['labelFrequency'] = (int)ceil($daysCount / 8);
					}
					break;
			}
		}


		if ($groupingValue === self::GROUPING_BY_DATE && get_class($view) === LinearGraph::class)
		{
			switch ($this->getDivisionOfDate())
			{
				case self::MONTH_DIVISION:
					$monthCount = $this->getMonthCountInTimePeriod();
					$firstMonth = $this->getFirstMonthNumFromTimePeriod();
					$currentMonthNum = $firstMonth;
					for ($i = 1; $i <= $monthCount; $i++)
					{
						if (!isset($items[$currentMonthNum]))
						{
							$items[$i] = [
								'groupBy' => $currentMonthNum,
								'label' => $this->getMonthNameByNum($currentMonthNum),
								'value' => 0,
							];
							$config['groupsLabelMap'][$currentMonthNum] = $this->getMonthNameByNum($currentMonthNum);
						}
						$currentMonthNum++;
					}
					break;
			}
		}

		$result = [
			'items' => $items,
			'config' => $config,
		];

		return $result;
	}

	private function getDayCountBetween(DateTime $fromDate, DateTime $toDate)
	{
		$diff = $toDate->getTimestamp() - $fromDate->getTimestamp();

		return (int)floor($diff / (60 * 60 * 24));
	}

	private function getDaysCountInTimePeriod()
	{
		$filterParameters = $this->getFilterParameters();
		if (!empty($filterParameters['TIME_PERIOD']))
		{
			$diff = $this->getTimePeriodDiff();

			return (int)floor($diff / (60 * 60 * 24));
		}

		return 120;
	}

	private function getMonthCountInTimePeriod()
	{
		$filterParameters = $this->getFilterParameters();
		if (!empty($filterParameters['TIME_PERIOD']))
		{
			$fromDateValue = new DateTime($filterParameters['TIME_PERIOD']['from']);
			$toDateValue = new DateTime($filterParameters['TIME_PERIOD']['to']);

			$fromDateYear = (int)$fromDateValue->format('Y');
			$toDateYear = (int)$toDateValue->format('Y');

			$fromMonthNum = (int)$fromDateValue->format('n');
			$toMonthNum = (int)$toDateValue->format('n');

			if ($fromDateYear < $toDateYear)
			{
				$monthCount = 12 - $fromMonthNum + $toMonthNum;
			}
			else if ($fromDateYear === $toDateYear)
			{
				if ($fromMonthNum > $toMonthNum)
				{
					return 0;
				}

				$monthCount = $toMonthNum - $fromMonthNum + 1;
			}
			else
			{
				return 0;
			}

			return $monthCount;
		}

		return 12;
	}

	private function getFirstMonthNumFromTimePeriod()
	{
		$filterParameters = $this->getFilterParameters();
		if (!empty($filterParameters['TIME_PERIOD']))
		{
			$fromDateValue = new DateTime($filterParameters['TIME_PERIOD']['from']);

			return $fromDateValue->format('n');
		}

		return 1;
	}

	/**
	 * @return array
	 */
	public function getMultipleGroupedDemoData()
	{
		return [];
	}

}
