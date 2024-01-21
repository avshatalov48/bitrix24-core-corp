<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\Date;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons;
use Bitrix\Bitrix24\Feature;

Loader::includeModule("biconnector");

class ApacheSupersetDashboardSettingComponent
	extends CBitrixComponent
	implements Controllerable
{
	private ?Dashboard $dashboard;

	public function configureActions()
	{
		return [];
	}

	protected function listKeysSignedParameters()
	{
		return ['DASHBOARD_ID'];
	}

	public function onPrepareComponentParams($arParams)
	{
		$arParams['DASHBOARD_ID'] = (int)($arParams['DASHBOARD_ID'] ?? 0);
		$arParams['OPEN_LOGIN_POPUP'] = (bool)$arParams['OPEN_LOGIN_POPUP'] ?? false;
		$arParams['CODE'] = $arParams['CODE'] ?? '';

		return parent::onPrepareComponentParams($arParams);
	}

	public function saveAction(array $data): Result
	{
		$result = new Result();
		$checkingResult = $this->checkAccess();
		if (!$checkingResult->isSuccess())
		{
			return $checkingResult;
		}

		$startTime = null;
		$endTime = null;
		if ($data['FILTER_PERIOD'] === EmbeddedFilter\DateTime::PERIOD_RANGE)
		{
			$startTime = new Date($data['DATE_FILTER_START']);
			$endTime = new Date($data['DATE_FILTER_END']);
		}

		$period = EmbeddedFilter\DateTime::getDefaultPeriod();
		if (EmbeddedFilter\DateTime::isAvailablePeriod($data['FILTER_PERIOD']))
		{
			$period = $data['FILTER_PERIOD'];
		}

		$dashboardId = (int)$this->arParams['DASHBOARD_ID'];
		if ($dashboardId > 0)
		{
			$this->initDashboard();
			if (!$this->dashboard)
			{
				$result->addError(new Error('Dashboard was not found'));

				return $result;
			}

			$dashboardObject = $this->dashboard->getOrmObject();

			$dashboardObject->setDateFilterStart($startTime);
			$dashboardObject->setDateFilterEnd($endTime);
			$dashboardObject->setFilterPeriod($period);

			return $dashboardObject->save();
		}

		Option::set('biconnector', EmbeddedFilter\DateTime::CONFIG_PERIOD_OPTION_NAME, $period);
		if ($startTime !== null)
		{
			Option::set('biconnector', EmbeddedFilter\DateTime::CONFIG_DATE_START_OPTION_NAME, $startTime->toString());
		}
		else
		{
			Option::delete('biconnector', ['name' => EmbeddedFilter\DateTime::CONFIG_DATE_START_OPTION_NAME]);
		}

		if ($endTime !== null)
		{
			Option::set('biconnector', EmbeddedFilter\DateTime::CONFIG_DATE_END_OPTION_NAME, $endTime->toString());
		}
		else
		{
			Option::delete('biconnector', ['name' => EmbeddedFilter\DateTime::CONFIG_DATE_END_OPTION_NAME]);
		}

		return $result;
	}

	public function executeComponent()
	{
		$checkingResult = $this->checkAccess();
		if (!$checkingResult->isSuccess())
		{
			foreach ($checkingResult->getErrorMessages() as $message)
			{
				$this->arResult['ERROR_MESSAGES'][] = $message;
			}

			$this->includeComponentTemplate();

			return;
		}

		$this->initDashboard();
		$this->arResult['FORM_PARAMETERS'] = $this->getFormParameters();
		$this->arResult['TITLE'] = $this->getTitle();

		Toolbar::addButton(
			new Buttons\Button(
				[
					"color" => Buttons\Color::LIGHT_BORDER,
					"size"  => Buttons\Size::MEDIUM,
					"click" => new Buttons\JsCode(
						"top.BX.Helper.show('redirect=detail&code=19123608');"
					),
					"text" => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS_DASHBOARD_HELP')
				]
			)
		);

		$this->includeComponentTemplate();
	}

	private function checkAccess(): Result
	{
		$result = new Result();

		if (
			!Loader::includeModule('bitrix24')
			|| !Feature::isFeatureEnabled('bi_constructor')
		)
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS_FEATURE_UNAVAILABLE')));

			return $result;
		}

		if (Option::get('biconnector', 'release_bi_superset', 'N') !== 'Y')
		{
			$result->addError(new Error(Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS_FEATURE_UNAVAILABLE')));

			return $result;
		}

		return $result;
	}

	private function initDashboard(): void
	{
		$superset = new SupersetController(ProxyIntegrator::getInstance());
		$this->dashboard = $superset->getDashboardRepository()->getById((int)$this->arParams['DASHBOARD_ID']);
	}

	private function getFormParameters(): array
	{
		return [
			'GUID' => 'BICONNECTOR_SUPERSET_SETTINGS',
			'INITIAL_MODE' => 'edit',
			'ENTITY_ID' => $this->arParams['ID'] ?? null,
			'ENTITY_TYPE_NAME' => 'dashboardSettings',
			'ENTITY_FIELDS' => $this->getEntityFields(),
			'ENTITY_CONFIG' => $this->getEntityConfig(),
			'ENTITY_DATA' => $this->getEntityData(),
			'ENTITY_CONTROLLERS' => $this->getEntityControllers(),
			'ENABLE_PAGE_TITLE_CONTROLS' => true,
			'ENABLE_COMMON_CONFIGURATION_UPDATE' => true,
			'ENABLE_PERSONAL_CONFIGURATION_UPDATE' => true,
			'ENABLE_SECTION_DRAG_DROP' => false,
			'ENABLE_CONFIG_CONTROL' => false,
			'ENABLE_FIELD_DRAG_DROP' => false,
			'ENABLE_FIELDS_CONTEXT_MENU' => false,
			'IS_IDENTIFIABLE_ENTITY' => false,
			'ENABLE_MODE_TOGGLE' => false,
			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $this->getName(),
				'ACTION_NAME' => 'save',
				'SIGNED_PARAMETERS' => $this->getSignedParameters(),
			],
		];
	}

	private function getTitle(): string
	{
		return Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS_TITLE');
	}

	private function getEntityConfig(): array
	{
		return [
			[
				'title' => Loc::getMessage('SUPERSET_DASHBOARD_SETTINGS_TITLE_DATE_FILTER'),
				'name' => 'filter',
				'type' => 'section',
				'enableTitle' => false,
				'elements' => [
					['name' => 'FILTER_PERIOD'],
				],
				'data' => [
					'isChangeable' => false,
					'isRemovable' => false,
				],
			],
		];
	}

	private function getEntityFields(): array
	{
		return [
			$this->getDateFilterField(),
		];
	}

	private function getDateFilterField(): array
	{
		$periods = [
			EmbeddedFilter\DateTime::PERIOD_WEEK,
			EmbeddedFilter\DateTime::PERIOD_MONTH,
			EmbeddedFilter\DateTime::PERIOD_QUARTER,
			EmbeddedFilter\DateTime::PERIOD_HALF_YEAR,
			EmbeddedFilter\DateTime::PERIOD_YEAR,
			EmbeddedFilter\DateTime::PERIOD_RANGE,
		];

		$items = [];
		foreach ($periods as $period)
		{
			$items[] = [
				'NAME' => EmbeddedFilter\DateTime::getPeriodName($period),
				'VALUE' => $period,
			];
		}

		return [
			'id' => 'FILTER_PERIOD',
			'title' => '',
			'name' => 'FILTER_PERIOD',
			'type' => 'timePeriod',
			'data' => [
				'items' => $items,
				'dateStartFieldName' => 'DATE_FILTER_START',
				'dateEndFieldName' => 'DATE_FILTER_END',
			],
			'isDragEnabled' => false,
		];
	}

	private function getEntityData(): array
	{
		if ($this->dashboard)
		{
			$filter = new EmbeddedFilter\DateTime($this->dashboard);

			$dateStart = $filter->getDateStart();
			$dateEnd = $filter->getDateEnd();
			$filterPeriod = $filter->getPeriod();
		}

		$dateStart ??= EmbeddedFilter\DateTime::getDefaultDateStart();
		$dateEnd ??= EmbeddedFilter\DateTime::getDefaultDateEnd();
		$filterPeriod ??= EmbeddedFilter\DateTime::getDefaultPeriod();

		return [
			'DATE_FILTER_START' => $dateStart,
			'DATE_FILTER_END' => $dateEnd,
			'FILTER_PERIOD' => $filterPeriod,
		];
	}

	private function getDashboardInfo(): ?array
	{
		if ($this->dashboard === null)
		{
			return null;
		}

		return [
			'id' => $this->dashboard->getId(),
			'appId' => $this->dashboard->getAppId(),
			'type' => $this->dashboard->getType(),
		];
	}

	private function getEntityControllers(): array
	{
		return [
			[
				'name' => 'SETTING_COMPONENT_CONTROLLER',
				'type' => 'settingComponentController',
				'config' => [
					'dashboardAnalyticInfo' => $this->getDashboardInfo(),
				],
			],
		];
	}
}
