<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

use Bitrix\Main;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Widget\FilterPeriodType;

$arResult['PATH_TO_LIST'] = isset($arParams['PATH_TO_LIST']) ? $arParams['PATH_TO_LIST'] : $APPLICATION->GetCurPage();
$arResult['PATH_TO_WIDGET'] = isset($arParams['PATH_TO_WIDGET']) ? $arParams['PATH_TO_WIDGET'] : $APPLICATION->GetCurPage();
$arResult['PATH_TO_KANBAN'] = isset($arParams['PATH_TO_KANBAN']) ? $arParams['PATH_TO_KANBAN'] : '';
$arResult['PATH_TO_CALENDAR'] = isset($arParams['PATH_TO_CALENDAR']) ? $arParams['PATH_TO_CALENDAR'] : '';
$arParams['PATH_TO_DEMO_DATA'] = isset($arParams['PATH_TO_DEMO_DATA']) ? $arParams['PATH_TO_DEMO_DATA'] : '';
$arResult['GUID'] = $arParams['GUID'] = isset($arParams['GUID']) ? $arParams['GUID'] : 'crm_widget_panel';
$arResult['LAYOUT'] = $arParams['LAYOUT'] =isset($arParams['LAYOUT']) ? $arParams['LAYOUT'] : 'L50R50';
$arResult['MAX_GRAPH_COUNT'] = $arParams['MAX_GRAPH_COUNT'] = isset($arParams['MAX_GRAPH_COUNT']) ? (int)$arParams['MAX_GRAPH_COUNT'] : 6;
$arResult['MAX_WIDGET_COUNT'] = $arParams['MAX_WIDGET_COUNT'] = isset($arParams['MAX_WIDGET_COUNT']) ? (int)$arParams['MAX_WIDGET_COUNT'] : 15;
$arResult['NAVIGATION_CONTEXT_ID'] = $arParams['NAVIGATION_CONTEXT_ID'] =isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';
$arResult['ENABLE_NAVIGATION'] = $arParams['ENABLE_NAVIGATION'] =isset($arParams['ENABLE_NAVIGATION']) ? $arParams['ENABLE_NAVIGATION'] : true;
$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
$arParams['IS_SUPERVISOR'] = isset($arParams['IS_SUPERVISOR']) && $arParams['IS_SUPERVISOR'];
$arResult['DEMO_TITLE'] = isset($arParams['~DEMO_TITLE']) ? $arParams['~DEMO_TITLE'] : '';
$arResult['DEMO_CONTENT'] = isset($arParams['~DEMO_CONTENT']) ? $arParams['~DEMO_CONTENT'] : '';
$arResult['CONTEXT_DATA'] = isset($arParams['CONTEXT_DATA']) && is_array($arParams['CONTEXT_DATA']) ? $arParams['CONTEXT_DATA'] : [];
$arResult['ENABLE_TOOLBAR'] = true;

$counterID = isset($arParams['~NAVIGATION_COUNTER_ID']) ? (int)$arParams['~NAVIGATION_COUNTER_ID'] : CCrmUserCounter::Undefined;
if(CCrmUserCounter::IsTypeDefined($counterID))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), $counterID);
	$arResult['NAVIGATION_COUNTER'] = $counter->GetValue(false);
}
else
{
	$arResult['NAVIGATION_COUNTER'] = isset($arParams['~NAVIGATION_COUNTER'])
		? (int)$arParams['~NAVIGATION_COUNTER'] : 0;
}

$entityType = isset($arParams['~ENTITY_TYPE'])? mb_strtoupper($arParams['~ENTITY_TYPE']) : '';
$entityTypes = isset($arParams['~ENTITY_TYPES']) && is_array($arParams['~ENTITY_TYPES']) ? $arParams['~ENTITY_TYPES'] : [];
if(empty($entityTypes))
{
	if($entityType !== '')
	{
		$entityTypes[] = $entityType;
	}
}
elseif($entityType === '')
{
	$entityType = $entityTypes[0];
}

$arResult['ENTITY_TYPES'] = $entityTypes;
$arResult['DEFAULT_ENTITY_TYPE'] = $entityType;
$arResult['DEFAULT_ENTITY_ID'] = isset($arParams['~ENTITY_ID']) ? (int)$arParams['~ENTITY_ID'] : 0;

$options = CUserOptions::GetOption('crm.widget_panel', $arResult['GUID'], array());

// todo tmp: a workaround to avoid updating options for all users
if (Bitrix\Main\Loader::includeModule('bitrix24') && !Bitrix\Bitrix24\Feature::isFeatureEnabled("crm_sale_target"))
{
	if (isset($options['rows']) && is_array($options['rows']))
	{
		foreach ($options['rows'] as $parentKey => $parentValue)
		{
			foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($parentValue)) as $key => $value)
			{
				if ($value === "saletarget")
				{
					unset($options['rows'][$parentKey]);
					$options['rows'] = array_values($options['rows']);
				}
			}
		}
	}
}

if(isset($options['layout']))
{
	$arResult['LAYOUT'] = $options['layout'];
}


$notCalculateData = isset($arParams['NOT_CALCULATE_DATA']) && $arParams['NOT_CALCULATE_DATA'];
$enableDemo = $arResult['ENABLE_DEMO'] = !isset($options['enableDemoMode']) || $options['enableDemoMode'] === 'Y';
$arResult['USE_DEMO'] = isset($arParams['USE_DEMO']) && $arParams['USE_DEMO'] === 'N' ? false : true;
if (!$arResult['USE_DEMO'])
{
	$enableDemo = $arResult['ENABLE_DEMO'] = false;
}

$arParams['ROWS'] = $arParams['ROWS'] ?? [];
if(!$enableDemo && isset($options['rows']))
{
	$arParams['ROWS'] = $options['rows'];
}

$arResult['HIDE_FILTER'] = (bool)($arParams['HIDE_FILTER'] ?? false);
$arParams['FILTER'] = $arParams['FILTER'] ?? [];
$arResult['FILTER'] = array(
	array(
		'id' => 'RESPONSIBLE_ID',
		'name' => GetMessage('CRM_FILTER_FIELD_RESPONSIBLE'),
		'default' => true,
		'type' => 'dest_selector',
		'params' => array(
			'context' => 'CRM_WIDGET_FILTER_RESPONSIBLE_ID',
			'multiple' => 'N',
			'contextCode' => 'U',
			'enableAll' => 'N',
			'enableSonetgroups' => 'N',
			'allowEmailInvitation' => 'N',
			'allowSearchEmailUsers' => 'N',
			'departmentSelectDisable' => 'Y',
			'isNumeric' => 'Y',
			'prefix' => 'U',
		)
	),
	array(
		'id' => 'PERIOD',
		'name' => GetMessage('CRM_FILTER_FIELD_PERIOD'),
		'default' => true,
		'type' => 'date',
		'exclude' => array(
			Main\UI\Filter\DateType::NONE,
			Main\UI\Filter\DateType::CURRENT_DAY,
			Main\UI\Filter\DateType::CURRENT_WEEK,
			Main\UI\Filter\DateType::YESTERDAY,
			Main\UI\Filter\DateType::TOMORROW,
			Main\UI\Filter\DateType::PREV_DAYS,
			Main\UI\Filter\DateType::NEXT_DAYS,
			Main\UI\Filter\DateType::NEXT_WEEK,
			Main\UI\Filter\DateType::NEXT_MONTH,
			Main\UI\Filter\DateType::LAST_MONTH,
			Main\UI\Filter\DateType::LAST_WEEK,
			Main\UI\Filter\DateType::EXACT,
			Main\UI\Filter\DateType::RANGE
		)
	)
);

$arResult['FILTER_ROWS'] = array(
	'RESPONSIBLE_ID' => true,
	'PERIOD' => true
);

//region Filter Presets
$monthPresetFilter = [];
Filter::addDateType(
	$monthPresetFilter,
	'PERIOD',
	FilterPeriodType::convertToDateType(FilterPeriodType::CURRENT_MONTH)
);

$quarterPresetFilter = [];
Filter::addDateType(
	$quarterPresetFilter,
	'PERIOD',
	FilterPeriodType::convertToDateType(FilterPeriodType::CURRENT_QUARTER)
);

$arResult['FILTER_PRESETS'] = array(
	'filter_current_month' => array(
		'name' => FilterPeriodType::getDescription(FilterPeriodType::CURRENT_MONTH),
		'fields' => $monthPresetFilter
	),
	'filter_current_quarter' => array(
		'name' => FilterPeriodType::getDescription(FilterPeriodType::CURRENT_QUARTER),
		'fields' => $quarterPresetFilter
	)
);
//endregion

$gridOptions = new CGridOptions($arResult['GUID']);
$filterOptions = new Main\UI\Filter\Options($arResult['GUID'], $arResult['FILTER_PRESETS']);
$arResult['FILTER_FIELDS'] = $filterOptions->getFilter($arResult['FILTER']);

//region Try to apply default settings if period is not assigned
if(Filter::getDateType($arResult['FILTER_FIELDS'], 'PERIOD') === '')
{
	$defaultFilter = [];
	Filter::addDateType(
		$defaultFilter,
		'PERIOD',
		FilterPeriodType::convertToDateType(FilterPeriodType::LAST_DAYS_30)
	);
	$filterOptions->setupDefaultFilter(
		$defaultFilter,
		array_keys($arResult['FILTER_ROWS'])
	);
	$arResult['FILTER_FIELDS'] = $filterOptions->getFilter($arResult['FILTER']);
}
//endregion

Filter::convertPeriodFromDateType($arResult['FILTER_FIELDS'], 'PERIOD');
$arResult['WIDGET_FILTER'] = Filter::internalizeParams($arResult['FILTER_FIELDS']);

$gridSettings = $gridOptions->GetOptions();
$visibleRows = isset($gridSettings['filter_rows']) ? explode(',', $gridSettings['filter_rows']) : [];

if(!empty($visibleRows))
{
	foreach(array_keys($arResult['FILTER_ROWS']) as $k)
	{
		$arResult['FILTER_ROWS'][$k] = in_array($k, $visibleRows);
	}
}

$arResult['OPTIONS'] = array(
	'filter_rows' => implode(',', array_keys($arResult['FILTER_ROWS'])),
	'filters' => array_merge($arResult['FILTER_PRESETS'], $gridSettings['filters'])
);

Filter::sanitizeParams($arResult['WIDGET_FILTER']);
$commonFilter = new Filter($arResult['WIDGET_FILTER']);
if($commonFilter->isEmpty())
{
	$commonFilter->setPeriodTypeID(FilterPeriodType::LAST_DAYS_30);
	$arResult['WIDGET_FILTER'] = $commonFilter->getParams();
}

if($arResult['DEFAULT_ENTITY_TYPE'] !== '')
{
	$commonFilter->setContextEntityTypeName($arResult['DEFAULT_ENTITY_TYPE']);
	if($arResult['DEFAULT_ENTITY_ID'] > 0)
	{
		$commonFilter->setContextEntityID($arResult['DEFAULT_ENTITY_ID']);
	}
}

$arResult['WIDGET_FILTER']['enableEmpty'] = false;
$arResult['WIDGET_FILTER']['defaultPeriodType'] = FilterPeriodType::LAST_DAYS_30;

$demoRows = null;
if($enableDemo && $arParams['PATH_TO_DEMO_DATA'] !== '')
{
	$demoFileName = $arParams['IS_SUPERVISOR'] ? 'supervisor' : 'employee';
	$demoRows = (include "{$arParams['PATH_TO_DEMO_DATA']}/{$demoFileName}.php");
}

$arResult['ROWS'] = [];
$rowQty = count($arParams['ROWS']);

$widgetCount = 0;
$maxWidgetCount = $arResult['MAX_WIDGET_COUNT'];
$factoryOptions = array('maxGraphCount' => $arResult['MAX_GRAPH_COUNT']);
for($i = 0; $i < $rowQty; $i++)
{
	if($maxWidgetCount > 0 && $widgetCount >= $maxWidgetCount)
	{
		break;
	}

	if(!isset($arParams['ROWS'][$i]))
	{
		continue;
	}

	$rowConfig = $arParams['ROWS'][$i];
	$row = array('cells' => array());
	if(isset($rowConfig['height']))
	{
		$row['height'] = $rowConfig['height'];
	}

	$cellConfigs = isset($rowConfig['cells']) ? $rowConfig['cells'] : [];
	$cellQty = count($cellConfigs);
	for($j = 0; $j < $cellQty; $j++)
	{
		if($maxWidgetCount > 0 && $widgetCount >= $maxWidgetCount)
		{
			break;
		}

		$demoCell = $enableDemo && isset($demoRows[$i]['cells'][$j])
			? $demoRows[$i]['cells'][$j] : null;

		$cell = array('controls' => [], 'data' => array());
		$cellConfig = isset($cellConfigs[$j]) ? $cellConfigs[$j] : [];
		$controls = isset($cellConfig['controls']) ? $cellConfig['controls'] : [];
		$controlQty = count($controls);

		for($k = 0; $k < $controlQty; $k++)
		{
			if($maxWidgetCount > 0 && $widgetCount >= $maxWidgetCount)
			{
				break;
			}

			$control = $controls[$k];

			$cell['controls'][] = $control;
			if(isset($control['filter']) && is_array($control['filter']) && !empty($control['filter']))
			{
				$filter = new Filter($control['filter']);
				Filter::merge($commonFilter, $filter, array('overridePeriod' => false));
			}
			else
			{
				$filter = $commonFilter;
			}

			$widget = Bitrix\Crm\Widget\WidgetFactory::create($control, $filter, $factoryOptions);
			$widget->setFilterContextData($arResult['CONTEXT_DATA']);
			if(!$enableDemo && !$notCalculateData)
			{
				$cell['data'][] = $widget->prepareData();
			}
			else
			{
				if($k === 0 && isset($demoCell['data']))
				{
					$demoData = $demoCell['data'];
				}
				elseif(isset($demoCell[$k]) && isset($demoCell[$k]['data']))
				{
					$demoData = $demoCell[$k]['data'];
				}
				else
				{
					$demoData = [];
				}

				$cell['data'][] = $widget->initializeDemoData($demoData);
			}

			$widgetCount++;
		}
		$row['cells'][] = $cell;
	}
	$arResult['ROWS'][] = $row;
}

$arResult['CURRENCY_FORMAT'] = CCrmCurrency::GetCurrencyFormatParams(CCrmCurrency::GetBaseCurrencyID());
$arResult['BUILDERS'] = [];

if (!$notCalculateData && !$enableDemo && CCrmPerms::IsAdmin())
{
	$builders = null;
	foreach($arResult['ENTITY_TYPES'] as $entityType)
	{
		$entityBuilders = Bitrix\Crm\Statistics\StatisticEntryManager::prepareBuilderData(CCrmOwnerType::ResolveID($entityType));
		if(is_array($builders))
		{
			$builders = array_merge($builders, $entityBuilders);
		}
		else
		{
			$builders = $entityBuilders;
		}
	}

	if(is_array($builders))
	{
		foreach($builders as $builder)
		{
			if($builder['ACTIVE'])
			{
				$arResult['BUILDERS'][] = $builder;
			}
		}
	}
}
$arResult['CUSTOM_WIDGETS'] = isset($arParams['CUSTOM_WIDGETS']) && is_array($arParams['CUSTOM_WIDGETS']) ? $arParams['CUSTOM_WIDGETS'] : [];
$this->IncludeComponentTemplate();
