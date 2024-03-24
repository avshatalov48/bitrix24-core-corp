<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\IconController;
use Bitrix\BIConnector\Integration\Superset\Integrator\ProxyIntegrator;
use Bitrix\BIConnector\KeyTable;
use Bitrix\BIConnector\Services\ApacheSuperset;
use Bitrix\BIConnector\Superset\KeyManager;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Field\KeyInfoField;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Section\EntityEditorSection;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\EntityEditorController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Controller\SettingsComponentController;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\Field\PeriodFilterField;
use Bitrix\BIConnector\Superset\UI\SettingsPanel\SettingsPanel;
use Bitrix\BIConnector\Superset\Dashboard\EmbeddedFilter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
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

class ApacheSupersetSettingComponent
	extends CBitrixComponent
	implements Controllerable, Errorable
{
	use ErrorableImplementation;

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
		return [];
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

		$this->arResult['TITLE'] = $this->getTitle();

		$this->initSettingsPanel();

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
			(new SettingsPanel('BICONNECTOR_SUPERSET_SETTINGS'))
			->addController(
				$this->getController(),
				new IconController('ICON_CONTROLLER')
			)
			->addSection($this->getFilterSection())
			->setAjaxData($ajaxData)
		;

		$user = CurrentUser::get();
		if (KeyManager::canManageKey($user))
		{
			$settingsPanel->addSection($this->getSupersetKeySection());
		}

		$this->arResult['SETTINGS_PANEL'] = $settingsPanel;
	}

	private function getController(): EntityEditorController
	{
		return new SettingsComponentController('SETTING_COMPONENT_CONTROLLER');
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

	private function getFilterSection(): EntityEditorSection
	{
		$dateFilterSection = new EntityEditorSection(
			name: 'DEFAULT_RANGE_SETTINGS',
			title: Loc::getMessage('BICONNECTOR_SUPERSET_SETTINGS_RANGE_FILTER_SECTION'),
		);
		$dateFilterSection->setIconClass('--calendar-1');
		$dateFilterSection->addField(new PeriodFilterField('DASHBOARD_FILTER'));

		return $dateFilterSection;
	}

	private function getSupersetKeySection(): EntityEditorSection
	{
		return
			(new EntityEditorSection(
				name: 'KEY_INFO',
				title: Loc::getMessage('BICONNECTOR_SUPERSET_SETTINGS_KEY_INFO_SECTION'),
			))
				->setIconClass('--key')
				->addField(new KeyInfoField('KEY_VALUE'))
		;
	}

	private function getTitle(): string
	{
		return Loc::getMessage('BICONNECTOR_SUPERSET_SETTINGS_TITLE');
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

		return [
			'FILTER_PERIOD' => $period,
			'DATE_FILTER_START' => $startTime,
			'DATE_FILTER_END' => $endTime,
		];
	}

	/**
	 * @return string|null
	 */
	public function changeBiTokenAction(): ?string
	{
		$user = CurrentUser::get();
		if (!KeyManager::canManageKey($user))
		{
			return null;
		}

		$key = KeyTable::getList([
				'select' => [
					'ID',
				],
				'filter' => [
					'=SERVICE_ID' => ApacheSuperset::getServiceId(),
					'=ACTIVE' => 'Y',
					'=APP_ID' => false,
				],
				'limit' => 1,
			])
			->fetchObject()
		;

		$result = KeyManager::createAccessKey($user);
		if (!$result->isSuccess())
		{
			return null;
		}

		$accessKey = $result->getData()['ACCESS_KEY'] ?? null;
		if (empty($accessKey))
		{
			return null;
		}

		$proxyIntegrator = ProxyIntegrator::getInstance();
		$response = $proxyIntegrator->changeBiconnectorToken($accessKey);

		if ($response->hasErrors())
		{
			return null;
		}

		if ($key)
		{
			$key->delete();
		}
		else
		{
			Option::delete('biconnector', ['name' => KeyManager::SUPERSET_KEY_OPTION_NAME]);
		}

		return $accessKey;
	}
}
