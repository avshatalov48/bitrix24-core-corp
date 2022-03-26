<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\Filter;

use Bitrix\Bitrix24\Feature;
use Bitrix\Report\VisualConstructor;
use Bitrix\Crm\Tracking;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

/**
 * Class CrmReportVcWidgetContentChartComponent
 */
class CrmReportVcWidgetContentChartComponent extends VisualConstructor\Views\Component\BaseViewComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	/** @var Tracking\Analytics\DataProvider $dataProvider */
	protected $dataProvider;

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		$this->dataProvider = new Tracking\Analytics\DataProvider();

		if (!Loader::includeModule('crm'))
		{
			return;
		}

		$this->initParams();
		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
			return;
		}

		if (!Tracking\Manager::isConfigured())
		{
			if (!$this->arParams['IS_GRID'])
			{
				$this->includeComponentTemplate('configure');
			}

			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->includeComponentTemplate();
	}

	protected function checkRequiredParams()
	{
		return true;
	}

	protected function initParams()
	{
		//$this->arParams['MULTIPLE'] = isset($this->arParams['MULTIPLE']) ? (bool) $this->arParams['MULTIPLE'] : true;
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? \CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);
	}

	protected function prepareResult()
	{
		$this->arResult['FEATURE_CODE'] = Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled("crm_tracking_reports")
			? "crm_tracking_reports"
			: null
		;

		/** @var Bitrix\Report\VisualConstructor\Entity\Widget $widget */
		$this->arParams['IS_AD_ACCESSIBLE'] = Tracking\Manager::isAdAccessible();
		$this->arParams['IS_COSTABLE'] = $this->dataProvider->isCostable();
		$widget = $this->arParams['WIDGET'];
		$filterOptions = new Filter\Options($widget->getFilterId(), []);
		$filterRequest = $filterOptions->getFilter([]);

		$periodFrom = !empty($filterRequest['TIME_PERIOD_from'])
			? (new DateTime($filterRequest['TIME_PERIOD_from']))
			: (new DateTime())->add('-30 day');

		$periodTo = !empty($filterRequest['TIME_PERIOD_to'])
			? (new DateTime($filterRequest['TIME_PERIOD_to']))
			: (new DateTime());

		$sources = (empty($filterRequest['SOURCE_CODE']) || !is_array($filterRequest['SOURCE_CODE']))
			? []
			: $filterRequest['SOURCE_CODE'];

		$this->arResult['DATA'] = $this->arResult['FEATURE_CODE']
			? []
			: $this->getComparedData($sources, $periodFrom, $periodTo)
		;

		if ($this->arParams['IS_GRID'])
		{
			if ($this->arResult['FEATURE_CODE'])
			{
				$this->arResult['GRID'] = [];
			}
			else if ($this->arParams['IS_TRAFFIC'])
			{
				$this->arResult['GRID'] = $this->getGridDataByEmployee($sources, $periodFrom, $periodTo);
			}
			else
			{
				$this->arResult['GRID'] = $this->getGridData($sources, $periodFrom, $periodTo);
			}
		}

		return true;
	}

	protected function getGridDataByEmployee($sources, DateTime $periodFrom, DateTime $periodTo)
	{
		$rowsByEmployee = [];

		$data = $this->getReportData($sources, $periodFrom, $periodTo, true, true, true);
		$stages = $data['dict']['stages'];
		$firstStage = current($stages);
		$lastStage = end($stages);

		$stages = array_filter(
			$stages,
			function ($stageCode)
			{
				return $stageCode !== 'actions';
			}
		);

		$employee = [];
		foreach ($stages as $stageCode)
		{
			foreach ($data['dict']['sources'] as $sourceCode)
			{
				if (empty($data['items'][$stageCode][$sourceCode]))
				{
					continue;
				}

				$users = array_keys($data['items'][$stageCode][$sourceCode]);
				foreach ($users as $userId)
				{
					$employee[$userId] = [];
				}
			}
		}

		foreach ($stages as $stageCode)
		{
			foreach ($data['dict']['sources'] as $sourceCode)
			{
				foreach ($employee as $userId => $items)
				{
					if (empty($data['items'][$stageCode][$sourceCode][$userId]))
					{
						continue;
					}

					$items[$stageCode][$sourceCode] = $data['items'][$stageCode][$sourceCode][$userId];
					$employee[$userId] = $items;
				}
			}
		}

		foreach ($employee as $userId => $userDataItems)
		{
			$user = $this->getUserInfo($userId);
			$rows = [];
			$summary = [
				'ID' => 'user-id-' . $userId,
				'COLOR' => '',
				'SOURCE_CODE' => $user,
			];
			foreach ($data['dict']['stages'] as $stageCode)
			{
				if ($stageCode === 'actions')
				{
					continue;
				}

				$stageCodeUpped = mb_strtoupper($stageCode);
				$summary[$stageCodeUpped] = 0;
			}

			foreach ($data['dict']['sources'] as $sourceCode)
			{
				$row = [
					'ID' => $sourceCode,
					'SOURCE_CODE' => $data['sources'][$sourceCode],
				];
				$row['SOURCE_CODE']['usePadding'] = true;
				$initialQuantity = 0;
				foreach ($stages as $stageCode)
				{
					$stage = $data['stages'][$stageCode];
					$source = $data['sources'][$sourceCode];
					$stageCodeUpped = mb_strtoupper($stageCode);
					$items = $userDataItems[$stageCode];
					$itemDef = [
						'quantity' => 0,
						'sum' => 0,
					];
					if (empty($items[$sourceCode]))
					{
						$item = $itemDef;
					}
					else
					{
						$item = $items[$sourceCode];
					}

					$item = $item + $itemDef;
					self::formatNumbers($item);

					if (!empty($user) && $stage['pathToList'] && $source['hasPathToList'] && $item['quantity'])
					{
						$filterParameters = [
							Tracking\UI\Filter::SourceId => [$sourceCode ?: 'organic'],
							'DATE_CREATE_datesel' => 'RANGE',
							'DATE_CREATE_from' => $periodFrom->format(Date::getFormat()),
							'DATE_CREATE_to' => $periodTo->format(Date::getFormat()),
							'ASSIGNED_BY_ID' => [$user['ID']],
							'ASSIGNED_BY_ID_value' => [$user['ID']],
							'ASSIGNED_BY_ID_name' => [$user['NAME']],
							'ASSIGNED_BY_ID_label' => [$user['NAME']],

							'DATE_INSERT_datesel' => 'RANGE',
							'DATE_INSERT_from' => $periodFrom->format(Date::getFormat()),
							'DATE_INSERT_to' => $periodTo->format(Date::getFormat()),
							'RESPONSIBLE_ID' => [$user['ID']],
							'RESPONSIBLE_ID_value' => [$user['ID']],
							'RESPONSIBLE_ID_name' => [$user['NAME']],
							'RESPONSIBLE_ID_label' => [$user['NAME']],
						];

						$details = $item['details'] ?? null;
						if ($details)
						{
							foreach ($details as $detailIndex => $detailItem)
							{
								$details[$detailIndex] = [
									'NAME' => $detailItem['name'],
									'VALUE' => self::formatNumber($detailItem['quantity']),
									'PATH' => self::getFilterUrl($detailItem['path'], $filterParameters)
								];
							}
						}

						$row[$stageCodeUpped] = [
							'NAME' => $item['name'],
							'VALUE' => $item['quantityPrint'],
							'PATH' => self::getFilterUrl($item['path'] ?: $stage['pathToList'], $filterParameters),
							'DETAILS' => $details,
						];
					}
					else
					{
						$row[$stageCodeUpped] = $item['quantityPrint'];
					}
					$summary[$stageCodeUpped] += $item['quantity'];

					if ($stageCode === $firstStage || $initialQuantity == 0)
					{
						$initialQuantity = $item['quantity'];
					}

					if ($stageCode === $lastStage)
					{
						$row['CONVERSION'] = $this->calculateConversion($item['quantity'], $initialQuantity);
						$row['SUM'] = self::formatMoney($item['sum']);

						$summary['CONVERSION'][] = [
							'quantity' => $item['quantity'],
							'actions' => $initialQuantity,
						];
						$summary['SUM'] += round($item['sum'], 2);
					}
				}

				$isEmpty = true;
				foreach ($stages as $stageCode)
				{
					$stageCode = mb_strtoupper($stageCode);
					if (is_array($row[$stageCode]))
					{
						if (!empty($row[$stageCode]['VALUE']))
						{
							$isEmpty = false;
							break;
						}
					}
					else
					{
						if (!empty($row[$stageCode]))
						{
							$isEmpty = false;
							break;
						}
					}
				}

				if (!$isEmpty)
				{
					$rows[] = $row;
				}
			}

			$summary['COSTS'] = self::formatMoney($summary['COSTS']);
			$summary['SUM'] = self::formatMoney($summary['SUM']);
			self::formatNumbers($summary, false);
			$summary['CONVERSION'] = $this->calculateConversionArray($summary['CONVERSION']);
			$summary['CONVERSION'] = null;

			$rowsByEmployee = array_merge($rowsByEmployee, [$summary], $rows);
		}


		// END /////////////////

		$messages = [
			'ACTIONS' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SHORT_ACTION'),
			'DEALS-SUCCESS' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SHORT_DEAL_SUCCESS'),
		];

		$columns = [
			[
				"id" => "SOURCE_CODE",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SOURCE_CODE'),
				"default" => true,
			],
			/*
			[
				"id" => "SOURCE_COLOR",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SOURCE_COLOR'),
				"default" => true,
			],
			*/
		];

		foreach ($data['stages'] as $stageCode => $stage)
		{
			if ($stageCode === 'actions')
			{
				continue;
			}

			$stageCode = mb_strtoupper($stageCode);
			$columns[] = [
				"id" => $stageCode,
				"name" => empty($messages[$stageCode]) ? $stage['caption'] : $messages[$stageCode],
				"default" => true,
			];
		}

		$columns[] = [
			"id" => "CONVERSION",
			"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_CONVERSION'),
			"default" => true,
		];

		$columns[] = [
			"id" => "SUM",
			"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_INCOME'),
			"default" => true,
		];

		return [
			'ROWS' => $rowsByEmployee,
			'COLUMNS' => $columns
		];
	}

	protected function getGridData(array $sources, DateTime $periodFrom, DateTime $periodTo)
	{
		$rows = [];

		$data = $this->arResult['DATA'];
		$stages = $data['dict']['stages'];
		$firstStage = current($stages);
		$lastStage = end($stages);
		$summary = [
			'ID' => 'summary',
			'SOURCE_CODE' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_GRID_SUMMARY'),
			'COLOR' => '',
			'COSTS' => 0,
			'SUM' => 0,
			'VIEWS' => 0,
			'CONVERSION' => [],
			'ROI' => [],
		];
		$summaryAd = [
			'ID' => 'summary-ad',
			'SOURCE_CODE' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_GRID_SUMMARY_AD'),
			'COLOR' => '',
			'COSTS' => 0,
			'SUM' => 0,
			'VIEWS' => 0,
			'CONVERSION' => [],
			'ROI' => [],
		];
		$organic = [
			'ID' => '',
			'SOURCE_CODE' => '',
			'COLOR' => '',
			'COSTS' => 0,
			'SUM' => 0,
			'VIEWS' => 0,
			'CONVERSION' => 0,
			'ROI' => 0,
		];
		foreach ($data['dict']['stages'] as $stageCode)
		{
			$stageCodeUpped = mb_strtoupper($stageCode);
			$organic[$stageCodeUpped] = 0;
			$summary[$stageCodeUpped] = 0;
			$summaryAd[$stageCodeUpped] = 0;
		}

		foreach ($data['dict']['sources'] as $sourceCode)
		{
			$row = [
				'ID' => $sourceCode,
				'SOURCE_CODE' => $data['sources'][$sourceCode],
			];

			$isOrganicSource = !$sourceCode;

			$cost = 0;
			$actions = 0;
			foreach ($data['dict']['stages'] as $stageCode)
			{
				$stage = $data['stages'][$stageCode];
				$source = $data['sources'][$sourceCode];
				$stageCodeUpped = mb_strtoupper($stageCode);
				$items = $data['items'][$stageCode];
				$itemDef = [
					'quantity' => 0,
					'sum' => 0,
				];
				if (empty($items[$sourceCode]))
				{
					$item = $itemDef;
				}
				else
				{
					$item = $items[$sourceCode];
				}

				$item = $item + $itemDef;
				self::formatNumbers($item);

				if ($stage['pathToList'] && $source['hasPathToList'] && $item['quantity'])
				{
					$filterParameters = [
						Tracking\UI\Filter::SourceId => [$sourceCode ?: 'organic'],
						'DATE_CREATE_datesel' => 'RANGE',
						'DATE_CREATE_from' => $periodFrom->format(Date::getFormat()),
						'DATE_CREATE_to' => $periodTo->format(Date::getFormat()),
						'DATE_INSERT_datesel' => 'RANGE',
						'DATE_INSERT_from' => $periodFrom->format(Date::getFormat()),
						'DATE_INSERT_to' => $periodTo->format(Date::getFormat()),
					];

					$details = $item['details'] ?? null;
					if ($details)
					{
						foreach ($details as $detailIndex => $detailItem)
						{
							$details[$detailIndex] = [
								'NAME' => $detailItem['name'],
								'VALUE' => self::formatNumber($detailItem['quantity']),
								'PATH' => self::getFilterUrl($detailItem['path'], $filterParameters)
							];
						}
					}

					//TRACKING_ASSIGNED
					$row[$stageCodeUpped] = [
						'VALUE' => $item['quantityPrint'],
						'NAME' => $item['name'],
						'PATH' => self::getFilterUrl($item['path'] ?: $stage['pathToList'], $filterParameters),
						'DETAILS' => $details,
					];
				}
				else
				{
					$row[$stageCodeUpped] = $item['quantityPrint'];
				}

				$summary[$stageCodeUpped] += $item['quantity'];
				if (!$isOrganicSource)
				{
					$summaryAd[$stageCodeUpped] += $item['quantity'];
				}


				if ($stageCode === $firstStage)
				{
					$row['COSTS'] = self::formatMoney($item['sum']);
					$row['VIEWS'] = self::formatNumber($item['views']);

					$summary['COSTS'] += $item['sum'];
					$summary['VIEWS'] += $item['views'];

					if (!$isOrganicSource)
					{
						$summaryAd['COSTS'] += $item['sum'];
						$summaryAd['VIEWS'] += $item['views'];
					}
				}

				if ($actions == 0)
				{
					$actions = $item['quantity'];
				}
				if ($stageCode === Tracking\Analytics\Provider\Action::CODE)
				{
					$cost = $item['sum'];
				}

				if ($stageCode === $lastStage)
				{
					// conversion
					$row['CONVERSION'] = $this->calculateConversion($item['quantity'], $actions);
					$summary['CONVERSION'][] = [
						'quantity' => $item['quantity'],
						'actions' => $actions,
					];

					// roi
					$row['ROI'] = $this->calculateRoi($item['sum'], $cost);
					$summary['ROI'][] = [
						'income' => $item['sum'],
						'cost' => $cost
					];

					// sum
					$row['SUM'] = self::formatMoney($item['sum']);
					$summary['SUM'] += $item['sum'];

					if (!$isOrganicSource)
					{
						$summaryAd['CONVERSION'][] = [
							'quantity' => $item['quantity'],
							'actions' => $actions,
						];
						$summaryAd['ROI'][] = [
							'income' => $item['sum'],
							'cost' => $cost
						];
						$summaryAd['SUM'] += $item['sum'];
					}
				}
			}

			if ($isOrganicSource)
			{
				$row['ROI'] = null;
				$organic = $row;
			}
			else
			{
				$rows[] = $row;
			}
		}

		/******* SUMMARY ********/
		$summary['COSTS'] = self::formatMoney($summary['COSTS']);
		$summary['SUM'] = self::formatMoney($summary['SUM']);
		$summary['VIEWS'] = self::formatNumber($summary['VIEWS']);
		self::formatNumbers($summary, false);
		$summary['CONVERSION'] = $this->calculateConversionArray($summary['CONVERSION']);
		$summary['CONVERSION'] = null;
		$summary['ROI'] = $this->calculateRoiArray($summary['ROI']);
		//$summary['ROI'] = null;
		/******* /SUMMARY ********/


		/******* SUMMARY AD ********/
		$summaryAd['COSTS'] = self::formatMoney($summaryAd['COSTS']);
		$summaryAd['SUM'] = self::formatMoney($summaryAd['SUM']);
		$summaryAd['VIEWS'] = self::formatNumber($summaryAd['VIEWS']);
		self::formatNumbers($summaryAd, false);
		$summaryAd['CONVERSION'] = $this->calculateConversionArray($summaryAd['CONVERSION']);
		//$summaryAd['CONVERSION'] = null;
		$summaryAd['ROI'] = $this->calculateRoiArray($summaryAd['ROI']);
		//$summaryAd['ROI'] = null;
		/******* /SUMMARY AD ********/

		if (count($sources) === 0 || in_array('organic', $sources))
		{
			$rows[] = $summaryAd;
			$rows[] = $organic;
		}

		$rows[] = $summary;

		$columns = [
			[
				"id" => "SOURCE_CODE",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SOURCE_CODE'),
				"default" => true,
			],
			[
				"id" => "SOURCE_COLOR",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SOURCE_COLOR'),
				"default" => true,
			],
		];

		if ($this->arParams['IS_COSTABLE'])
		{
			$columns[] = [
				"id" => "COSTS",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_COSTS'),
				"default" => true,
			];

			$columns[] = [
				"id" => "VIEWS",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SHORT_VIEWS'),
				"default" => true,
			];
		}

		$messages = [
			'VIEWS' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SHORT_VIEWS'),
			'ACTIONS' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SHORT_ACTION'),
			'DEALS-SUCCESS' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_SHORT_DEAL_SUCCESS'),
		];
		foreach ($data['stages'] as $stageCode => $stage)
		{
			$stageCode = mb_strtoupper($stageCode);
			$columns[] = [
				"id" => $stageCode,
				"name" => empty($messages[$stageCode]) ? $stage['caption'] : $messages[$stageCode],
				"default" => true,
			];
		}

		$columns[] = [
			"id" => "CONVERSION",
			"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_CONVERSION'),
			"default" => true,
		];

		$columns[] = [
			"id" => "SUM",
			"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_INCOME'),
			"default" => true,
		];

		if ($this->arParams['IS_COSTABLE'])
		{
			$columns[] = [
				"id" => "ROI",
				"name" => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_COLUMN_ROI'),
				"default" => true,
			];
		}

		return [
			'ROWS' => $rows,
			'COLUMNS' => $columns
		];
	}

	/**
	 * @param array $sources
	 * @param DateTime $periodFrom
	 * @param DateTime $periodTo
	 * @return array
	 */
	protected function getComparedData(array $sources, $periodFrom, $periodTo)
	{
		$isGrid = $this->arParams['IS_GRID'];
		$groupBySource = $isGrid || !$this->arParams['IS_TRAFFIC'];

		$data = $this->getReportData($sources, $periodFrom, $periodTo, $isGrid, $groupBySource);
		if ($isGrid)
		{
			return $data;
		}

		$diffSeconds = $periodTo->getTimestamp() - $periodFrom->getTimestamp();

		$periodFrom = clone $periodFrom;
		$periodTo = clone $periodTo;
		$periodFrom->add("-$diffSeconds second")->add('-1 day');
		$periodTo->add("-$diffSeconds second")->add('-1 day');

		$prevData = $this->getReportData($sources, $periodFrom, $periodTo, $isGrid, $groupBySource);
		foreach ($data['stages'] as $stageCode => $stage)
		{
			self::calculateChanges($stage, $prevData['stages'][$stageCode]);
			$data['stages'][$stageCode] = $stage;
		}

		foreach ($data['dict']['sources'] as $sourceCode)
		{
			foreach ($data['items'] as $stageCode => $items)
			{
				$item = $items[$sourceCode] ?: $this->getEmptyItem();
				$previousItem = $prevData['items'][$stageCode][$sourceCode] ?: $this->getEmptyItem();
				self::calculateChanges($item, $previousItem);
				$data['items'][$stageCode][$sourceCode] = $item;
			}
		}

		return $data;
	}

	protected function getEmptyItem()
	{
		return [
			'source' => 0,
			'stage' => '',
			'sum' => 0,
			'quantity' => 0,
			'views' => 0,
			'price' => 0,
			'cost' => 0,
			'costIncrease' => 0,
			'quantityDecrease' => 0,
			'employee' => [],
		];
	}

	protected function getReportData(array $sources, $periodFrom, $periodTo, $useSourceOrganic = false, $groupBySource = false, $groupByAssigned = false)
	{
		$data = [
			'currency' => \CCrmCurrency::GetAccountCurrencyID(),
			'currencyText' => '',
			'sources' => [],
			'stages' => [],
			'statuses' => [],
			'items' => [],
			'dict' => [],
			'conversion' => [],
			'demoAction' => self::formatMoney(9),
		];

		$emptyItem = $this->getEmptyItem();
		$sourceList = [];

		$sourceIds = array_column(Tracking\Provider::getActualSources(), 'ID');

		$this->dataProvider
			->setSourceId($sources)
			->groupByTrackingSource($groupBySource)
			->groupByAssigned($groupByAssigned)
			->setPeriod($periodFrom, $periodTo)
		;
		foreach ($this->dataProvider->getProviders() as $stage)
		{
			$stageCode = $stage->getCode();
			if (!empty($data['stages'][$stageCode]))
			{
				$data['stages'][$stageCode]['caption'] .= ', '
					. $stage->getName();
			}
			else
			{
				$data['stages'][$stageCode] = [
					'code' => $stageCode,
					'caption' => $stage->getName(),
					'pathToList' => $stage->getPath(),
					'sum' => 0,
					'quantity' => 0,
				];
			}

			if (!is_array($data['items'][$stageCode]))
			{
				$data['items'][$stageCode] = [];
			}

			foreach ($stage->getData() as $row)
			{
				$sourceCode = $row[Tracking\Analytics\Provider\Base::TrackingSourceId] ?: 0;

				// fixed: if source deleted, but traces exists.
				if ($sourceCode && is_numeric($sourceCode) && !in_array($sourceCode, $sourceIds))
				{
					continue;
				}

				$assigned = $row[Tracking\Analytics\Provider\Base::Assigned] ?: 0;
				$stageSum = $row['SUM'] ?: 0;
				$stageQuantity = $row['CNT'] ?: 0;
				$stageViews = isset($row['VIEWS']) ? $row['VIEWS'] : 0;
				$sourceList[] = $sourceCode;

				if (!$useSourceOrganic && !$sourceCode)
				{
					$stageSum = 0;
					$stageViews = 0;
					$stageQuantity = 0;
				}

				$row = [
					'source' => $sourceCode,
					'stage' => $stageCode,
					'sum' => $stageSum,
					'quantity' => $stageQuantity,
					'views' => $stageViews,
				] + $emptyItem;

				if ($groupByAssigned)
				{
					$prev = $data['items'][$stageCode][$sourceCode][$assigned] ?? null;
				}
				else
				{
					$prev = $data['items'][$stageCode][$sourceCode] ?? null;
				}

				$row['path'] = $stage->getPath();
				$row['name'] = $stage->getEntityName();
				if ($prev)
				{
					$details = $prev['details'] ?? [$prev];
					if ($details && $details[0]['path'] != $row['path'])
					{
						$details[] = $row;
						$row['details'] = $details;
					}

					$row['sum'] += $prev['sum'];
					$row['views'] += $prev['views'];
					$row['quantity'] += $prev['quantity'];
				}

				if ($groupByAssigned)
				{
					$data['items'][$stageCode][$sourceCode][$assigned] = $row;
				}
				else
				{
					$data['items'][$stageCode][$sourceCode] = $row;
				}
			}
		}

		$sourceList = array_unique($sourceList);
		$sourceList = $groupBySource
			? array_map(
				function ($row)
				{
					$row['CAPTION'] = $row['NAME'];
					return $row;
				},
				array_filter(
					Tracking\Provider::getActualSources(),
					function ($row) use ($sourceList)
					{
						return in_array($row['ID'], $sourceList);
					}
				)
			)
			: [
				[
					'ID' => 'summary',
					'CODE' => 'summary',
					'CAPTION' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CAPTION_SUMMARY'),
					'ICON_COLOR' => '#2f98e2',
					'ICON_CLASS' => '',
				],
			];

		foreach ($sourceList as $source)
		{
			if (!$useSourceOrganic && $source['CODE'] == '0')
			{
				continue;
			}

			if (($groupBySource && !empty($sources)) && !in_array($source['ID'], $sources))
			{
				continue;
			}

			$sourceObject = new Bitrix\Crm\Tracking\Analytics\Ad($source);
			$data['sources'][$source['ID']] = [
				'code' => $source['ID'],
				'caption' => $source['CAPTION'],
				'color' => $source['ICON_COLOR'],
				'iconClass' => $source['ICON_CLASS'],
				'hasPathToList' => $source['HAS_PATH_TO_LIST'],
				'expensesReport' => [
					'supported' => $sourceObject->isSupportExpensesReport(),
					'options' => $sourceObject->isSupportExpensesReport()
						? [
							'sourceId' => $source['ID'],
							'gridId' => 'crm-report-tracking-ad-le' . (\Bitrix\Crm\Settings\LeadSettings::isEnabled() ? '1' : '0'),
							'from' => $periodFrom->format(Date::getFormat()),
							'to' => $periodTo->format(Date::getFormat()),
						]
						: []
					,
				],
			];
		}


		$data['dict'] = [
			'sources' => array_keys($data['sources']),
			'stages' => array_keys($data['stages']),
			'statuses' => array_keys($data['statuses']),
		];


		// calculate ITEMS
		foreach ($data['dict']['sources'] as $sourceCode)
		{
			if ($groupByAssigned)
			{
				continue;
			}

			$list = [];
			foreach ($data['items'] as $stageCode => $items)
			{
				$list[$stageCode] = $items[$sourceCode] ?: [
					'source' => $sourceCode,
					'stage' => $stageCode,
				] + $emptyItem;
			}

			self::calculateCost($list);
			self::formatArrayNumbers($list);

			foreach ($list as $stageCode => $item)
			{
				self::calculatePrice($item);
				$data['items'][$stageCode][$sourceCode] = $item;
			}
		}

		foreach ($data['items'] as $stageCode => $items)
		{
			foreach ($items as $sourceCode => $item)
			{
				$stage = $data['stages'][$stageCode];

				$rows = $groupByAssigned ? $item : [$item];
				foreach ($rows  as $row)
				{
					$stage['sum'] += $row['sum'];
					$stage['quantity'] += $row['quantity'];
				}

				$data['stages'][$stageCode] = $stage;
			}
		}

		// STAGE. calculate price
		foreach ($data['stages'] as $stageCode => $stage)
		{
			self::calculatePrice($stage);
			$stage['scales'] = [
				$stage['quantity'],
				(int) ($stage['quantity'] / 2),
				0
			];
			$data['stages'][$stageCode] = $stage;
		}
		self::calculateCost($data['stages']);
		self::formatArrayNumbers($data['stages']);

		$firstStage = current($data['stages']);
		$lastStage = end($data['stages']);
		reset($data['stages']);
		$data['conversion'] = $this->calculateConversion(
			$lastStage['quantity'],
			$firstStage['quantity'],
			false
		);
		$data['roi'] = $this->calculateRoi(
			$lastStage['sum'],
			$firstStage['sum'],
			true
		);

		if (!$groupByAssigned)
		{
			self::cleanEmptySources($data);
		}

		return $data;
	}

	protected static function getFilterUrl($path, array $parameters)
	{
		$path = new \Bitrix\Main\Web\Uri($path);
		$path->addParams([
			'apply_filter' => 'Y',
			//'from_analytics' => 'Y',
		] + $parameters);

		return $path->getLocator();
	}


	/**
	 * Calculate price.
	 *
	 * @param array $data
	 */
	protected static function cleanEmptySources(array &$data)
	{
		foreach ($data['dict']['sources'] as $sourceIndex => $sourceCode)
		{
			$hasValue = false;
			foreach ($data['items'] as $stageCode => $items)
			{
				if (!$items[$sourceCode]['quantity'])
				{
					continue;
				}

				$hasValue = true;
			}

			if ($hasValue)
			{
				continue;
			}

			foreach ($data['items'] as $stageCode => $items)
			{
				unset($items[$sourceCode]);
				//$data['items'][$stageCode][$sourceCode] = $items;
				$data['items'][$stageCode] = $items;
			}

			unset($data['dict']['sources'][$sourceIndex]);
		}

		sort($data['dict']['sources']);
	}

	/**
	 * Calculate price.
	 *
	 * @param array $data
	 */
	protected static function calculatePrice(array &$data)
	{
		$data['price'] = (int) ($data['quantity'] ? $data['sum'] / $data['quantity'] : 0);
	}


	/**
	 * Calculate changes.
	 *
	 * @param array $data
	 * @param array $prevData
	 */
	protected static function calculateChanges(array &$data, array &$prevData)
	{
		$data['costChanging'] = (int) (($prevData['cost']
				? ($data['cost'] - $prevData['cost']) / $prevData['cost']
				: 0
			) * 100);

		$data['quantityChanging'] = (int) (($prevData['quantity']
				? ($data['quantity'] - $prevData['quantity']) / $prevData['quantity']
				: 0
			) * 100);
	}

	/**
	 * Calculate cost, costIncrease, quantityDecrease.
	 *
	 * @param array $dataList
	 */
	protected static function calculateCost(array &$dataList)
	{
		$baseData = current($dataList);
		$baseDataSum = $baseData['sum'];
		foreach ($dataList as $index => $data)
		{
			$data['cost'] = round($data['quantity']
				? $baseDataSum / $data['quantity']
				: 0,
			1
			);
			if ($data['cost'] >= 10)
			{
				$data['cost'] = (int) $data['cost'];
			}

			$dataList[$index] = $data;
		}
	}

	/**
	 * Format array numbers.
	 *
	 * @param array $dataList
	 * @param bool $addKeys Add keys
	 */
	protected static function formatArrayNumbers(array &$dataList, $addKeys = true)
	{
		foreach ($dataList as $index => $data)
		{
			self::formatNumbers($data, $addKeys);
			$dataList[$index] = $data;
		}
	}

	/**
	 * Format numbers.
	 *
	 * @param array $data
	 * @param bool $addKeys Add keys
	 */
	protected static function formatNumbers(array &$data, $addKeys = true)
	{
		$moneyKeys = ['cost', 'sum'];
		foreach ($data as $key => $value)
		{
			if (!is_numeric($value))
			{
				continue;
			}

			if (in_array($key, $moneyKeys))
			{
				$value = self::formatMoney($value);
			}
			else
			{
				$value = self::formatNumber($value);
			}

			$key .= $addKeys ? 'Print' : '';
			$data[$key] = $value;
		}
	}

	/**
	 * Format number.
	 *
	 * @param float $value
	 * @param string $currency
	 * @return string
	 */
	protected static function formatNumber($value, $currency = '')
	{
		return number_format($value, 0, '.', ' ') . ' ' . $currency;
	}

	/**
	 * Format money.
	 *
	 * @param float $value
	 * @return string
	 */
	protected static function formatMoney($value)
	{
		return \CCrmCurrency::MoneyToString($value, \CCrmCurrency::GetAccountCurrencyID());
	}

	/**
	 * Calculate conversion from array.
	 *
	 * @param array $list List.
	 * @return int|array
	 */
	protected function calculateConversionArray(array $list)
	{
		return $this->calculateConversion(
			array_sum(array_column($list, 'quantity')),
			array_sum(array_column($list, 'actions'))
		);
	}

	/**
	 * Calculate ROI from array.
	 *
	 * @param array $list List.
	 * @return int|array
	 */
	protected function calculateRoiArray(array $list)
	{
		return $this->calculateRoi(
			array_sum(array_column($list, 'income')),
			array_sum(array_column($list, 'cost'))
		);
	}

	/**
	 * Calculate conversion.
	 *
	 * @param double $quantity Quantity.
	 * @param double $actions Actions.
	 * @return int|array
	 */
	protected function calculateConversion($quantity, $actions, $plain = false)
	{
		$conversion = round(($actions ?
				$quantity / $actions
				: 0) * 100,
			2);

		$format = !$plain && (!$this->arParams['IS_COSTABLE'] || $this->arParams['IS_TRAFFIC']);

		return $format
			? self::formatConversion($conversion, !$this->arParams['IS_COSTABLE'])
			: $conversion . (($conversion && !$plain) ? '%' : '');
	}

	/**
	 * Calculate ROI.
	 *
	 * @param double $income Income.
	 * @param double $cost Cost.
	 * @param bool $format Format result.
	 * @return int|array
	 */
	protected function calculateRoi($income, $cost, $format = true)
	{
		$roi = round(
			(
				($cost /* && $income > $cost*/)
					? ($income - $cost) / $cost
					: 0
			) * 100,
			2
		);

		return $format
			? self::formatConversion($roi, false, true)
			: $roi;
	}

	/**
	 * Format conversion.
	 *
	 * @param double $value Value.
	 * @param bool $greater Greater coefficients.
	 * @param bool $roi ROI coefficients.
	 * @return array
	 */
	protected static function formatConversion($value, $greater = false, $roi = false)
	{
		$value = round($value, 2);

		$code = 'none';
		if ($roi)
		{
			if ($value < 0)
			{
				$code = 'bad';
			}
			if ($value > 0)
			{
				$code = 'normal';
			}
			if ($value >= 100)
			{
				$code = 'good';
			}
			if ($value == 0)
			{
				$code = 'none';
			}
		}
		elseif (!$greater)
		{
			if ($value > 0)
			{
				$code = 'bad';
			}
			if ($value >= 0.4)
			{
				$code = 'normal';
			}
			if ($value > 0.8)
			{
				$code = 'good';
			}
		}
		else
		{
			if ($value > 0)
			{
				$code = 'bad';
			}
			if ($value >= 15)
			{
				$code = 'normal';
			}
			if ($value > 40)
			{
				$code = 'good';
			}
		}


		$map = [
			'none' => [
				'text' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CONV_NONE'),
				'color' => 'gray',
			],
			'bad' => [
				'text' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CONV_BAD'),
				'color' => '#E22E29',
			],
			'normal' => [
				'text' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CONV_NORMAL'),
				'color' => '#1DB0DE',
			],
			'good' => [
				'text' => Loc::getMessage('CRM_REPORT_VC_W_C_CHART_CONV_GOOD'),
				'color' => '#6F9300',
			],
		];

		return [
			'color' => $map[$code]['color'],
			'value' => $value . ($value ? '%' : ''),
			'text' => $map[$code]['text'],
		];
	}

	protected function getUserInfo($userId)
	{
		static $users = array();

		if(!$userId)
		{
			return null;
		}

		if(!$users[$userId])
		{
			// prepare link to profile
			$replaceList = array('user_id' => $userId, 'id' => $userId);
			$link = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER_PROFILE'], $replaceList);

			$userFields = \Bitrix\Main\UserTable::getRowById($userId);
			if(!$userFields)
			{
				return null;
			}

			// format name
			$userName = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => $userFields['LOGIN'],
					'NAME' => $userFields['NAME'],
					'LAST_NAME' => $userFields['LAST_NAME'],
					'SECOND_NAME' => $userFields['SECOND_NAME']
				),
				true, false
			);

			// prepare icon
			$fileTmp = CFile::ResizeImageGet(
				$userFields['PERSONAL_PHOTO'],
				array('width' => 42, 'height' => 42),
				BX_RESIZE_IMAGE_EXACT,
				false
			);
			$userIcon = $fileTmp['src'];

			$users[$userId] = array(
				'ID' => $userId,
				'NAME' => $userName,
				'LINK' => $link,
				'ICON' => $userIcon
			);
		}

		return $users[$userId];
	}
}