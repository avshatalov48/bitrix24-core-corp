<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::includeModule('biconnector');

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceSettingsTable;
use Bitrix\BIConnector\ExternalSource\Internal\ExternalSourceTable;
use Bitrix\BiConnector\Settings;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ExternalConnectionComponent extends CBitrixComponent implements Controllerable, Errorable
{
	use \Bitrix\Main\ErrorableImplementation;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	public function executeComponent($component = null)
	{
		$checkAccessResult = $this->checkAccess();
		if (!$checkAccessResult->isSuccess())
		{
			$this->arResult['ERROR_MESSAGES'] = $checkAccessResult->getErrorMessages();
			$this->includeComponentTemplate();
			Application::getInstance()->terminate();
		}

		$this->arResult['SOURCE_FIELDS'] = [];
		if ($this->arParams['SOURCE_ID'])
		{
			$source = ExternalSourceTable::getById((int)$this->arParams['SOURCE_ID'])->fetchObject();
			if ($source)
			{
				$this->arResult['SOURCE_FIELDS'] = [
					'id' => $source->getId(),
					'title' => htmlspecialcharsbx($source->getTitle()),
					'type' => $source->getType(),
				];
				$settings = ExternalSourceSettingsTable::getList([
					'filter' => [
						'SOURCE_ID' => $source->getId(),
					],
				])->fetchAll();
				foreach ($settings as $setting)
				{
					if ($setting['CODE'] !== 'password')
					{
						$this->arResult['SOURCE_FIELDS'][$setting['CODE']] = htmlspecialcharsbx($setting['VALUE']);
					}
				}
			}
		}
		$this->arResult['SIGNED_PARAMETERS'] = $this->getSignedParameters();

		$supportedDatabases = ExternalSource\SourceManager::getSupportedDatabases();
		if (!$supportedDatabases)
		{
			$this->arResult['ERROR_MESSAGES'] = ['No databases available'];
			$this->includeComponentTemplate();
			Application::getInstance()->terminate();
		}
		$this->arResult['SUPPORTED_DATABASES'] = $supportedDatabases;
		$this->arResult['FIELDS_CONFIG'] = ExternalSource\SourceManager::getFieldsConfig();
		$this->includeComponentTemplate();
	}

	public function checkConnectionAction(array $data): ?bool
	{
		$checkAccessResult = $this->checkAccess();
		if (!$checkAccessResult->isSuccess())
		{
			$this->errorCollection[] = $checkAccessResult->getError();

			return null;
		}

		if (
			!isset($data['type'], $data['host'], $data['username'], $data['password'])
			|| !$data['type']
			|| !$data['host']
			|| !$data['username']
			|| !$data['password']
		)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_FIELDS_INCOMPLETE'));

			return null;
		}

		$type = ExternalSource\Type::tryFrom($data['type']);
		if (!$type)
		{
			$this->errorCollection[] = new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_UNKNOWN_TYPE', [
				'#CONNECTION_TYPE#' => htmlspecialcharsbx($data['type']),
			]));

			return null;
		}

		$source = ExternalSource\Source\Factory::getSource($type, 0);
		$connectionResult = $source->connect(trim($data['host']), $data['username'], $data['password']);
		if (!$connectionResult->isSuccess())
		{
			$this->errorCollection[] = $connectionResult->getError();

			return null;
		}

		return true;
	}

	private function checkAccess(): Result
	{
		$result = new Result();
		if (!Bitrix\BIConnector\Configuration\Feature::isExternalEntitiesEnabled())
		{
			$result->addError(new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_FEATURE')));

			return $result;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			$result->addError(new Error(Loc::getMessage('EXTERNAL_CONNECTION_ERROR_ACCESS')));

			return $result;
		}

		return $result;
	}

	public function configureActions()
	{
		return [];
	}
}
