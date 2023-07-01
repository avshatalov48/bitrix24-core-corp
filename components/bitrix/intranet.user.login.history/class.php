<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Authentication\Internal\UserDeviceLoginTable;
use Bitrix\Main\Authentication\Internal\UserDeviceTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Service\GeoIp\Internal\GeonameTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Grid;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Web\UserAgent\DeviceType;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\CreateButton;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Toolbar\Facade\Toolbar;

class IntranetUserLoginHistoryComponent extends CBitrixComponent implements Controllerable
{
	private const N_PAGE_SIZE = 50;
	private const DEVICES_KEYS = [
		'unknown' => DeviceType::UNKNOWN,
		'desktop' => DeviceType::DESKTOP,
		'mobile' => DeviceType::MOBILE_PHONE,
		'tablet' => DeviceType::TABLET,
		'tv' => DeviceType::TV,
	];
	private ?Filter\Options $filterOptions;
	private ?Grid\Options $gridOptions;
	private ?array $requestFilter;

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function executeComponent()
	{
		$this->prepareToolbar();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	private function prepareToolbar(): void
	{
		Toolbar::addFilter($this->getFilterConfig());

		if (!isset($this->arParams['USER_ID']) || $this->arParams['USER_ID'] === (int)\Bitrix\Main\Engine\CurrentUser::get()->getId())
		{
			Toolbar::addButton($this->getSecurityButton());
		}
		else
		{
			Toolbar::addButton($this->getLogoutButton());
		}
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function prepareResult(): void
	{
		$this->arResult['GRID_ID'] = $this->getGridID();
		$this->arResult['GRID_COLUMNS'] = $this->getGridColumns();
		$nav = $this->getPageNavigationGrid();
		$queryParams = $this->getQueryParams($nav);
		$this->arResult['ROWS'] = $this->getDataForGrid($queryParams);
		$sizeList = $this->getSizeHistoryList($queryParams);
		$nav->setRecordCount($sizeList);
		$this->arResult['NAV_OBJECT'] = $nav;
		$this->arResult['isConfiguredPortal'] = Option::get('main', 'user_device_history', 'N') === 'Y';
		$this->arResult['isCloud'] = ModuleManager::isModuleInstalled('bitrix24');
	}

	public function onPrepareComponentParams($arParams)
	{
		if (isset($arParams['USER_ID']))
		{
			$arParams['USER_ID'] = (int)$arParams['USER_ID'];
		}

		return $arParams;
	}

	/**
	 * @return PageNavigation
	 */
	private function getPageNavigationGrid(): PageNavigation
	{
		$navigationData = $this->getGridOptions()->getNavParams(['nPageSize' => self::N_PAGE_SIZE]);
		$navigation = new PageNavigation($this->getGridID());
		$navigation->allowAllRecords(true)
			->setPageSize($navigationData['nPageSize'])
			->initFromUri();

		return $navigation;
	}

	/**
	 * @param PageNavigation $navigation
	 * @return array
	 */
	private function getQueryParams(PageNavigation $navigation): array
	{
		$userId = $this->arParams['USER_ID'];

		if (!is_numeric($userId))
		{
			$userId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		}

		$request = Context::getCurrent()->getRequest();

		if ($request->get('grid_action') === 'more')
		{
			$navigation->setCurrentPage($request->get($navigation->getId()));
		}

		return [
			'filter' => [
				$this->getDateFilter(),
				$this->getTextFilter(),
			],
			'deviceType' => $this->getFilterForDeviceName(),
			'offset' => $navigation->getOffset(),
			'limit' => $navigation->getLimit(),
			'order' => $this->getGridOrder(),
			'userId' => $userId,
		];
	}

	private function getDateFilter(): array
	{
		$requestFilter = $this->getRequestFilter();
		$filter = [];

		if (!empty($requestFilter['DATE_from']))
		{
			$filter['><LOGIN_DATE'] = [$requestFilter['DATE_from'], $requestFilter['DATE_to']];
		}

		return $filter;
	}

	private function getTextFilter(): array
	{
		$requestFilter = $this->getRequestFilter();
		$searchString = $this->getFilterOptions()->getSearchString();
		$filter = [];
		$isBrowserFilter = false;
		$isIpFilter = false;

		if (!empty($requestFilter['BROWSER']))
		{
			$filter['DEVICE.BROWSER'] = '%' . $requestFilter['BROWSER'] . '%';
			$isBrowserFilter = true;
		}
		elseif ($searchString)
		{
			$filter['DEVICE.BROWSER'] = '%' . $searchString . '%';
		}

		if (!empty($requestFilter['IP']))
		{
			$filter['IP'] = '%' . $requestFilter['IP'] . '%';
			$isIpFilter = true;
		}
		elseif ($searchString)
		{
			$filter['IP'] = '%' . $searchString . '%';
		}

		if (!empty($requestFilter['DEVICE_PLATFORM']))
		{
			$filter['DEVICE.PLATFORM'] = '%' . $requestFilter['DEVICE_PLATFORM'] . '%';
			$isIpFilter = true;
		}
		elseif ($searchString)
		{
			$filter['DEVICE.PLATFORM'] = '%' . $searchString . '%';
		}

		if (!$isBrowserFilter && !$isIpFilter && $searchString)
		{
			$filter['LOGIC'] = 'OR';
		}

		return $filter;
	}

	private function getFilterForDeviceName(): array
	{
		$requestFilter = $this->getRequestFilter();
		$listDeviceID = [];

		if (!empty($requestFilter['DEVICE_TYPE']))
		{
			$listDeviceID = $requestFilter['DEVICE_TYPE'];
		}

		return $listDeviceID;
	}

	private function getGridOrder(): array
	{
		$sorting = $this->getGridOptions()->getSorting(['sort' => $this->getDefaultSort()]);
		$by = key($sorting['sort']);
		$order = mb_strtoupper(current($sorting['sort'])) === 'ASC' ? 'ASC' : 'DESC';
		$list = array_column($this->getGridColumns(), 'sort');

		if (!in_array($by, $list, true))
		{
			return $this->getDefaultSort();
		}

		return [$by => $order];
	}

	private function getFilterConfig(): array
	{
		return [
			'FILTER_ID' => $this->getFilterID(),
			'GRID_ID' => $this->getGridID(),
			'FILTER' => [
				[
					'id' => 'DATE',
					'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_WHEN'),
					'type' => 'date',
					'default' => true,
					'exclude' => [
						DateType::NEXT_WEEK,
						DateType::NEXT_MONTH,
						DateType::NEXT_DAYS,
						DateType::TOMORROW,
						DateType::TOMORROW,
					]
				],
				[
					'id' => 'DEVICE_TYPE',
					'name' =>  Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_DEVICE_TYPE'),
					'type' => 'list',
					'default' => true,
					'params' => [
						'multiple' => 'Y',
					],
					'items' => $this->getDevices(),
				],
				[
					'id' => 'DEVICE_PLATFORM',
					'default' => true,
					'name' =>  Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_DEVICE_PLATFORM'),
				],
				[
					'id' => 'BROWSER',
					'default' => true,
					'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_BROWSER')
				],
				[
					'id' => 'IP',
					'default' => true,
					'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_IP')
				],
			],
			'FILTER_PRESETS' => $this->getFilterPresets(),
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => true,
			'THEME' => Filter\Theme::LIGHT,
		];
	}

	/**
	 * @return CreateButton
	 */
	private function getLogoutButton(): CreateButton
	{
		return CreateButton::create([
			'text' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_SIGN_OUT_ALL_USER'),
			'color' => Color::LIGHT_BORDER,
			'dataset' => [
				'toolbar-collapsed-icon' => Icon::REMOVE,
			],
			'classList' => ['add-document-button'],
			'onclick' => 'showLogoutBox',
			'id' => 'add-document-button-id'
		]);
	}

	/**
	 * @return CreateButton
	 */
	private function getSecurityButton(): CreateButton
	{
		return CreateButton::create([
			'text' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_SECURITY_BUTTON'),
			"icon" => Icon::SETTINGS,
			'color' => Color::LIGHT_BORDER,
			'dataset' => [
				'toolbar-collapsed-icon' => Icon::REMOVE,
			],
			'classList' => ['add-document-button'],
			'onclick' => 'openSecuritySlider',
			'id' => 'add-document-button-id',
		]);
	}

	private function getGridID(): string
	{
		return 'login_history_grid';
	}

	private function getFilterID(): string
	{
		return 'login_history_filter';
	}

	private function getDevices(): array
	{
		return DeviceType::getDescription();
	}

	/**
	 * @return Filter\Options
	 */
	private function getFilterOptions(): Filter\Options
	{
		if (!empty($this->filterOptions))
		{
			return $this->filterOptions;
		}
		$this->filterOptions = new Filter\Options($this->getFilterID());

		return $this->filterOptions;
	}

	/**
	 * @return Grid\Options
	 */
	private function getGridOptions(): Grid\Options
	{
		if (!empty($this->gridOptions))
		{
			return $this->gridOptions;
		}
		$this->gridOptions = new Grid\Options($this->getGridID());

		return $this->gridOptions;
	}

	/**
	 * @return array
	 */
	private function getRequestFilter(): array
	{
		if (!empty($thos->requestFilter))
		{
			return $this->requestFilter;
		}
		$this->requestFilter = $this->getFilterOptions()->getFilter($this->getFilterConfig()['FILTER']);

		return $this->requestFilter;
	}

	private function getGridColumns(): array
	{
		return [
			[
				'id' => 'DATE',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_WHEN'),
				'sort' => 'LOGIN_DATE',
				'default' => true,
			],
			[
				'id' => 'TIME',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_TIME'),
				'default' => true,
			],
			[
				'id' => 'GEOLOCATION',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_GEOLOCATION'),
				'default' => true,
			],
			[
				'id' => 'DEVICE_TYPE',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_DEVICE_TYPE'),
				'sort' => 'DEVICE_TYPE',
				'default' => true,
			],
			[
				'id' => 'DEVICE_PLATFORM',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_DEVICE_PLATFORM'),
				'default' => true,
			],
			[
				'id' => 'BROWSER',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_BROWSER'),
				'default' => true,
			],
			[
				'id' => 'IP',
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_GRID_COLUMN_NAME_IP'),
				'default' => true,
			],
		];
	}

	private function getDefaultSort(): array
	{
		return ['LOGIN_DATE' => 'DESC'];
	}

	private function getFilterPresets(): array
	{
		return [
			'filter_today_history' => [
				'name' => Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_FILTER_DEFAULT_PRESET_TODAY'),
				'fields' => ['DATE_datesel' => 'CURRENT_DAY'],
				'default' => false,
			],
		];
	}

	/**
	 * @param array $queryParams
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getDataForGrid(array $queryParams): array
	{
		$list = $this->getDataForHistoryList($queryParams);
		$data = $this->getPreparedHistoryList($list);
		$rows = [];
		$i = 0;

		foreach ($data as $row)
		{
			$i++;
			$rows[] = [
				'data' => [
					'ID' => $i,
					'DATE' => $row['LOGIN_DATE'],
					'DEVICE_TYPE' => $this->prepareDeviceTypeIconForGrid($row['DEVICE_TYPE']),
					'DEVICE_PLATFORM' => $row['DEVICE_PLATFORM'],
					'BROWSER' => $row['BROWSER'],
					'GEOLOCATION' => $row['GEOLOCATION'],
					'IP' => $row['IP'],
					'TIME' => $row['LOGIN_TIME'],
				],
			];
		}

		return $rows;
	}

	private function prepareDeviceTypeIconForGrid(string $deviceType): string
	{
		$devices = $this->getDevices();
		$deviceName = $devices[self::DEVICES_KEYS[$deviceType]];

		return "
			<div data-hint='$deviceName' class='bx-user-login-history-device-type-icon --$deviceType' data-hint-no-icon></div>
			<script>
				BX.UI.Hint.init(BX('.bx-user-login-history-device-type-icon'));
			</script>
		";
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getSizeHistoryList(array $paramsQuery): int
	{
		$query = $this->getDefaultQueryObject($paramsQuery)
			->setSelect(['CNT' => Query::expr()->count('DEVICE.USER_ID')])
			->setGroup('DEVICE.USER_ID');

		return (int)($query->exec()->fetch()['CNT'] ?? 0);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getPreparedHistoryList(array $list): array
	{
		$preparedList = [];

		foreach ($list as $item)
		{
			$item['LOGIN_DATE']->toUserTime();
			$preparedList[] = [
				'LOGIN_DATE' => $this->prepareDateHistoryList($item['LOGIN_DATE']),
				'DEVICE_TYPE' => $this->prepareDeviceType($item['DEVICE_TYPE']),
				'DEVICE_PLATFORM' => $item['DEVICE_PLATFORM'] ?? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED'),
				'BROWSER' => $item['BROWSER'] ?? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED'),
				'GEOLOCATION' => $this->prepareGeolocationName($item['CITY_GEOID'], $item['REGION_GEOID'],
					$item['COUNTRY_ISO_CODE']),
				'IP' => $item['IP'] ?? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED'),
				'LOGIN_TIME' => $this->prepareTimeHistoryList($item['LOGIN_DATE']),
			];
		}

		return $preparedList;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getDataForHistoryList(array $paramsQuery): array
	{
		$query = $this->getDefaultQueryObject($paramsQuery)
			->setSelect([
				'*',
				'DEVICE_TYPE' => 'DEVICE.DEVICE_TYPE',
				'USER_ID' => 'DEVICE.USER_ID',
				'BROWSER' => 'DEVICE.BROWSER',
				'DEVICE_PLATFORM' => 'DEVICE.PLATFORM',
			])
			->setOrder($paramsQuery['order'])
			->setOffset($paramsQuery['offset'])
			->setLimit($paramsQuery['limit']);

		return $query->exec()->fetchAll();
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	private function getDefaultQueryObject(array $paramsQuery): Query
	{
		return UserDeviceLoginTable::query()
			->whereIn('DEVICE.DEVICE_TYPE', $paramsQuery['deviceType'])
			->where('DEVICE.USER_ID', $paramsQuery['userId'])
			->setFilter($paramsQuery['filter'])
			->registerRuntimeField(
				'DEVICE',
				new Reference(
					'DEVICE',
					UserDeviceTable::class,
					Join::on('this.DEVICE_ID', 'ref.ID'),
					['join_type' => Join::TYPE_INNER]
				)
			);
	}

	/**
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getListLastLoginAction(int $limit): array
	{
		$query = UserDeviceLoginTable::query()
			->setSelect([
				'*',
				'DEVICE_TYPE' => 'DEVICE.DEVICE_TYPE',
				'USER_ID' => 'DEVICE.USER_ID',
				'BROWSER' => 'DEVICE.BROWSER',
				'DEVICE_PLATFORM' => 'DEVICE.PLATFORM',
			])
			->where('DEVICE.USER_ID', \Bitrix\Main\Engine\CurrentUser::get()->getId())
			->setOrder(['LOGIN_DATE' => 'DESC'])
			->setLimit($limit)
			->registerRuntimeField(
				'DEVICE',
				new Reference(
					'DEVICE',
					UserDeviceTable::class,
					Join::on('this.DEVICE_ID', 'ref.ID'),
					['join_type' => Join::TYPE_INNER]
				)
			);
		$list = $query->exec()->fetchAll();

		return $this->getPreparedHistoryListForWidget($list);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function getPreparedHistoryListForWidget(array $list): array
	{
		$preparedList = [];

		foreach ($list as $item)
		{
			$preparedList[] = [
				'LOGIN_DATE' => $item['LOGIN_DATE'],
				'DEVICE_TYPE' => $this->prepareDeviceType($item['DEVICE_TYPE'] ?? 0),
				'DEVICE_PLATFORM' => $item['DEVICE_PLATFORM'] ?? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED'),
				'BROWSER' => $item['BROWSER'] ?? Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED'),
				'GEOLOCATION' => $this->prepareGeolocationName($item['CITY_GEOID'], null,
					$item['COUNTRY_ISO_CODE']),
			];
		}

		return $preparedList;
	}

	private function prepareDateHistoryList(?DateTime $dateTime): string
	{
		if(!$dateTime)
		{
			return Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED');
		}

		return FormatDate($this->getCultureUser()->getLongDateFormat(), $dateTime->getTimestamp());
	}

	private function prepareDeviceType(int $deviceType): string
	{
		foreach (self::DEVICES_KEYS as $type => $id)
		{
			if ($deviceType === $id)
			{
				return $type;
			}
		}

		return 'unknown';

	}

	private function prepareTimeHistoryList(?DateTime $dateTime): string
	{
		if(!$dateTime)
		{
			return Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED');
		}

		return $dateTime->format($this->getCultureUser()->getLongTimeFormat());
	}

	private function getCultureUser(): Context\Culture
	{
		return Context::getCurrent()->getCulture();
	}

	private function prepareGeolocationName(?int $cityID, ?int $regionID, ?string $countryCode): string
	{
		$geoID = [];
		$result = [];

		if ($cityID && $cityID > 0)
		{
			$geoID[$cityID] = $cityID;
		}

		if ($regionID && $regionID > 0)
		{
			$geoID[$regionID] = $regionID;
		}

		$countries = GetCountries();
		$geonames = GeonameTable::get($geoID);
		$currentLang = Context::getCurrent()->getLanguageObject()->getCode();

		if ($cityID && $cityID > 0)
		{
			$city = $geonames[$cityID][$currentLang] ?? $geonames[$cityID]['en'] ?? '';

			if($city)
			{
				$result[] = $city;
			}
		}

		if ($regionID && $regionID > 0)
		{
			$region = $geonames[$regionID][$currentLang] ?? $geonames[$regionID]['en'] ?? '';

			if($region)
			{
				$result[] = $region;
			}
		}

		if ($countryCode)
		{
			$country = $countries[$countryCode]['NAME'] ?? '';

			if($country)
			{
				$result[] = $country;
			}
		}

		if (!$result)
		{
			return Loc::getMessage('INTRANET_USER_LOGIN_HISTORY_UNDEFINED');
		}

		return implode('/', $result);
	}

	public function configureActions()
	{
		return [];
	}
}