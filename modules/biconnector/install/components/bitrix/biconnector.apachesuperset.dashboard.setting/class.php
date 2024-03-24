<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\Integration\Superset\Model\Dashboard;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\IconController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Section\EntityEditorSection;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\EntityEditorController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\SettingsComponentController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Field\DashboardPeriodFilterField;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Field\PeriodFilterField;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\SettingsPanel;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorableImplementation;
use Bitrix\Main\ErrorCollection;
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
	implements Controllerable, Errorable
{
	use ErrorableImplementation;

	private ?Dashboard $dashboard;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

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

		return parent::onPrepareComponentParams($arParams);
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
		if ($this->dashboard === null)
		{
			$this->arResult['ERROR_MESSAGES'][] = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS_DASHBOARD_NOT_FOUND');
			$this->includeComponentTemplate();

			return;
		}

		$this->initSettingsPanel();
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

	private function initSettingsPanel(): void
	{
		$ajaxData = [
			'COMPONENT_NAME' => $this->getName(),
			'ACTION_NAME' => 'save',
			'SIGNED_PARAMETERS' => $this->getSignedParameters(),
		];

		$settingsPanel =
			(new SettingsPanel('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS'))
			->addController(
				$this->getController(),
				new IconController('ICON_CONTROLLER')
			)
			->addSection($this->getFilterSection())
			->setAjaxData($ajaxData)
		;

		if (isset($this->arParams['DASHBOARD_ID']))
		{
			$settingsPanel->setEntityId($this->arParams['DASHBOARD_ID']);
		}

		$this->arResult['SETTINGS_PANEL'] = $settingsPanel;
	}

	private function getController(): EntityEditorController
	{
		return
			(new SettingsComponentController('SETTING_COMPONENT_CONTROLLER'))
			->setConfig([
				'dashboardAnalyticInfo' => $this->getDashboardInfo(),
			])
		;
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

		return $result;
	}

	private function initDashboard(): void
	{
		$superset = new SupersetController(ProxyIntegrator::getInstance());
		$this->dashboard = $superset->getDashboardRepository()->getById((int)$this->arParams['DASHBOARD_ID']);
	}

	private function getFilterSection(): EntityEditorSection
	{
		$dateFilterSection = new EntityEditorSection('DASHBOARD_FILTER');
		if ($this->dashboard !== null)
		{
			$dateFilterSection->addField(new DashboardPeriodFilterField(
				id: 'DASHBOARD_FILTER',
				dashboard: $this->dashboard,
			));
		}
		else
		{
			$dateFilterSection->addField(new PeriodFilterField('DASHBOARD_FILTER'));
		}

		return $dateFilterSection;
	}

	private function getTitle(): string
	{
		return Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_SETTINGS_TITLE')
			. ': '
			. $this->dashboard->getTitle()
		;
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

	public function saveAction(array $data): ?array
	{
		$checkingResult = $this->checkAccess();
		if (!$checkingResult->isSuccess())
		{
			$error = new Error(Loc::getMessage('BICONNECTOR_SUPERSET_ACTION_SETTINGS_SAVE_ERROR_NO_RIGHTS'));
			$this->errorCollection->setError($error);

			return null;
		}

		$dashboardId = (int)$this->arParams['DASHBOARD_ID'];
		if ($dashboardId === 0)
		{
			$error = new Error(Loc::getMessage('BICONNECTOR_SUPERSET_ACTION_SETTINGS_SAVE_ERROR_INVALID_DASHBOARD'));
			$this->errorCollection->setError($error);

			return null;
		}

		$this->initDashboard();
		if (!$this->dashboard)
		{
			$error = new Error(Loc::getMessage('BICONNECTOR_SUPERSET_ACTION_SETTINGS_SAVE_ERROR_INVALID_DASHBOARD'));
			$this->errorCollection->setError($error);

			return null;
		}

		$dashboardObject = $this->dashboard->getOrmObject();

		if ($data['FILTER_PERIOD'] === EmbeddedFilter\DateTime::PERIOD_DEFAULT)
		{
			$dashboardObject->setDateFilterStart(null);
			$dashboardObject->setDateFilterEnd(null);
			$dashboardObject->setFilterPeriod(null);
			$dashboardObject->save();

			return ['FILTER_PERIOD' => EmbeddedFilter\DateTime::PERIOD_DEFAULT];
		}

		$startTime = null;
		$endTime = null;
		if ($data['FILTER_PERIOD'] === EmbeddedFilter\DateTime::PERIOD_RANGE)
		{
			try
			{
				$startTime = new Date($data['DATE_FILTER_START']);
				$endTime = new Date($data['DATE_FILTER_END']);
			}
			catch (\Bitrix\Main\ObjectException)
			{
				$error = new Error(Loc::getMessage('BICONNECTOR_SUPERSET_ACTION_SETTINGS_SAVE_ERROR_INVALID_RANGE'));
				$this->errorCollection->setError($error);

				return null;
			}
		}

		$period = EmbeddedFilter\DateTime::getDefaultPeriod();
		$innerPeriod = $data['FILTER_PERIOD'] ?? '';
		if (is_string($innerPeriod) && EmbeddedFilter\DateTime::isAvailablePeriod($innerPeriod))
		{
			$period = $innerPeriod;
		}

		$dashboardObject->setDateFilterStart($startTime);
		$dashboardObject->setDateFilterEnd($endTime);
		$dashboardObject->setFilterPeriod($period);
		$dashboardObject->save();

		return [
			'FILTER_PERIOD' => $period,
			'DATE_FILTER_START' => $startTime,
			'DATE_FILTER_END' => $endTime,
		];
	}
}
