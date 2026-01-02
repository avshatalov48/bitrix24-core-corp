<?php
/**
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Configuration\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;

$arResult['CAN_VIEW'] =
	$USER->CanDoOperation('biconnector_key_manage')
	|| (isset($arParams['BI_ANALYTIC']) && $arParams['BI_ANALYTIC'] === 'Y');

if (!$arResult['CAN_VIEW'])
{
	ShowError(Loc::getMessage('ACCESS_DENIED'));
	return;
}

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBUS_ERROR_INCLUDE_MODULE'));
	return;
}

$APPLICATION->SetTitle(Loc::getMessage('CC_BBUS_TITLE'));

$maxFieldsCount = 5;
$manager = Bitrix\BIConnector\Manager::getInstance();
$serviceCode = Feature::isBuilderEnabled()
	? \Bitrix\BIConnector\Services\ApacheSuperset::getServiceId()
	: \Bitrix\BIConnector\Services\GoogleDataStudio::getServiceId()
;
$service = $manager->createService($serviceCode);
$service->setLanguage(LANGUAGE_ID);
$tables = [];
$tableList = $service->getTableList();
foreach ($tableList as $table)
{
	$tables[$table[0]] = $table[1];
}

$arResult['GRID_ID'] = 'biconnector_usage_stat';
$arResult['SORT'] = ['ID' => 'DESC'];
$arResult['ROWS'] = [];

$filter = [];
if (isset($_GET['over_limit']) && $_GET['over_limit'] === 'Y')
{
	$filter['=IS_OVER_LIMIT'] = 'Y';
}
if (isset($arParams['BI_ANALYTIC']) && $arParams['BI_ANALYTIC'] === 'Y')
{
	$filter['SERVICE_ID'] = 'superset';
}
else
{
	$filter['!=SERVICE_ID'] = 'superset';
}

$gridOptions = new Bitrix\Main\Grid\Options($arResult['GRID_ID']);
$navParams = $gridOptions->GetNavParams();
$pageSize = (int)$navParams['nPageSize'];

$nav = new PageNavigation('page');
$nav->setPageSize($pageSize)->initFromUri();

$totalCount = \Bitrix\BIConnector\LogTable::getCount($filter);
$nav->setRecordCount($totalCount);

$select = [
	'ID',
	'TIMESTAMP_X',
	'KEY_ID',
	'SERVICE_ID',
	'SOURCE_ID',
	'ROW_NUM',
	'DATA_SIZE',
	'REAL_TIME',
	'FIELDS',
	'FILTERS',
	'ACCESS_KEY' => 'KEY.ACCESS_KEY',
];
if (isset($arParams['BI_ANALYTIC']) && $arParams['BI_ANALYTIC'] === 'Y')
{
	$select = array_filter($select, function($key)  {
		return !in_array($key, ['SERVICE_ID', 'KEY_ID']);
	});
}
$logList = \Bitrix\BIConnector\LogTable::getList([
	'select' => $select,
	'filter' => $filter,
	'order' => $arResult['SORT'],
	'offset' => $nav->getOffset(),
	'limit' => $nav->getLimit(),
]);
while ($data = $logList->fetch())
{
	$url = str_replace('#ID#', urlencode($data['KEY_ID']), $arParams['KEY_EDIT_URL']);
	if (isset($data['ACCESS_KEY']))
	{
		$data['KEY_ID'] = '<a href="javascript:BX.SidePanel.Instance.open(\'' . CUtil::JSEscape($url) . '\')">' . $data['KEY_ID'] . '</a>';
	}

	$service = Loc::getMessage('CC_BBUS_SERVICE_' . mb_strtoupper($data['SERVICE_ID']));
	if ($service)
	{
		$data['SERVICE_ID'] = $service;
	}

	if (isset($tables[$data['SOURCE_ID']]))
	{
		$data['SOURCE_ID'] = $tables[$data['SOURCE_ID']];
	}

	if ($data['ROW_NUM'] === null)
	{
		$data['ROW_NUM'] = Loc::getMessage('CC_BBUS_ROWS_NO_DATA');
	}
	else
	{
		$str = number_format($data['ROW_NUM'], 0, '.', ' ');
		$str = str_replace(' ', '<span></span>', $str);
		$data['ROW_NUM'] = '<span class="biconnector-usage-stat-number">' . $str . '</span>';
	}

	if ($data['DATA_SIZE'])
	{
		$str = number_format($data['DATA_SIZE'], 0, '.', ' ');
		$str = str_replace(' ', '<span></span>', $str);
		$data['DATA_SIZE'] = '<span class="biconnector-usage-stat-number">' . $str . '</span>';
	}

	if ($data['REAL_TIME'])
	{
		$str = number_format($data['REAL_TIME'], 2, '.', ' ');
		$str = str_replace(' ', '<span></span>', $str);
		$data['REAL_TIME'] = '<span class="biconnector-usage-stat-number">' . $str . '</span>';
	}

	if ($data['FIELDS'])
	{
		$fields = explode(', ', $data['FIELDS']);
		if (count($fields) > $maxFieldsCount)
		{
			$html = implode(', ', array_slice($fields, 0, $maxFieldsCount));
			$html .= ', <a class="biconnector-usage-stat-action-link" onclick="return showMore(this, \'' . implode(', ', array_slice($fields, $maxFieldsCount)) . '\');">' . Loc::getMessage('CC_BBUS_SHOW_MORE', [
				'#N#' => (count($fields) - $maxFieldsCount)
			]) . '</a>';
			$data['FIELDS'] = $html;
		}
	}

	$arResult['ROWS'][] = [
		'id' => $data['ID'],
		'data' => $data,
	];
}

$arResult['NAV'] = $nav;
$arResult['CURRENT_PAGE'] = $nav->getCurrentPage();
$arResult['ENABLE_NEXT_PAGE'] = count($arResult['ROWS']) == $nav->getPageSize();

if (isset($_GET['over_limit']) && $_GET['over_limit'] === 'Y')
{
	$emptyStateTitle = Loc::getMessage('CC_BBUS_EMPTYSTATE_OVER_LIMIT_TITLE');
	$emptyStateDescription = Loader::includeModule('bitrix24')
		? Loc::getMessage('CC_BBUS_EMPTYSTATE_OVER_LIMIT_DESCRIPTION_B24')
		: Loc::getMessage('CC_BBUS_EMPTYSTATE_OVER_LIMIT_DESCRIPTION');
}
else
{
	$emptyStateTitle = Loc::getMessage('CC_BBUS_EMPTYSTATE_TITLE');
	$emptyStateDescription = Loc::getMessage('CC_BBUS_EMPTYSTATE_DESCRIPTION');
}
$arResult['STUB'] = "
	<div class=\"biconnector-empty\">
		<div class=\"biconnector-empty__icon --statistic\"></div>
		<div class=\"biconnector-empty__title\">{$emptyStateTitle}</div>
		<div class=\"biconnector-empty__title-sub\">{$emptyStateDescription}</div>
	</div>
";

$limitManager = \Bitrix\BIConnector\LimitManager::getInstance();
if (isset($arParams['BI_ANALYTIC']) && $arParams['BI_ANALYTIC'] === 'Y')
{
	$limitManager->setIsSuperset();
}
$arResult['BICONNECTOR_LIMIT'] = $limitManager->getLimit();

$this->includeComponentTemplate();
