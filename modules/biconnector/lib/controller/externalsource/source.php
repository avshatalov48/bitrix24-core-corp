<?php

namespace Bitrix\BIConnector\Controller\ExternalSource;

use Bitrix\BIConnector;
use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\ExternalSource;
use Bitrix\BIConnector\ExternalSource\SourceManager;
use Bitrix\BIConnector\Superset\ActionFilter\BIConstructorAccess;
use Bitrix\Crm;
use Bitrix\Intranet\ActionFilter\IntranetUser;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Source extends Controller
{
	protected function getDefaultPreFilters(): array
	{
		$additionalFilters = [
			new BIConstructorAccess(),
		];

		if (Loader::includeModule('intranet'))
		{
			$additionalFilters[] = new IntranetUser();
		}

		return [
			...parent::getDefaultPreFilters(),
			...$additionalFilters,
		];
	}

	protected function processBeforeAction(Action $action): bool
	{
		if (!BIConnector\Configuration\Feature::isExternalEntitiesEnabled())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_FEATURE')));

			return false;
		}

		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG))
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_ACCESS')));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function changeActivityAction(int $id, string $moduleId): ?bool
	{
		$source = null;
		if ($moduleId === 'BI')
		{
			$source = BIConnector\ExternalSource\Internal\ExternalSourceTable::getById($id)->fetchObject();
		}
		elseif ($moduleId === 'CRM')
		{
			if (!Loader::includeModule('crm'))
			{
				$this->addError(new Error('Module crm is not installed'));

				return null;
			}
			$source = Crm\Tracking\Internals\SourceTable::getById($id)->fetchObject();
		}

		if (!$source)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_NOT_FOUND')));

			return null;
		}

		if ($source->getActive())
		{
			$source->setActive(false);
		}
		else
		{
			$source->setActive(true);
		}

		$saveResult = $source->save();
		if (!$saveResult->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_NOT_SAVED')));

			return null;
		}

		return true;
	}

	public function deleteAction(int $id, string $moduleId): ?bool
	{
		if ($moduleId !== 'BI')
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_DELETE_ERROR_WRONG_MODULE')));

			return null;
		}

		$source = BIConnector\ExternalSource\Internal\ExternalSourceTable::getById($id)->fetchObject();
		if (!$source)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_NOT_FOUND')));

			return null;
		}

		$deleteResult = $source->delete();
		if (!$deleteResult->isSuccess())
		{
			$this->addErrors($deleteResult->getErrors());

			return null;
		}

		return true;
	}

	public function checkConnectionAction(int $sourceId): ?bool
	{
		$sourceEntity = BIConnector\ExternalSource\Internal\ExternalSourceTable::getById($sourceId)->fetchObject();
		if (!$sourceEntity)
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SOURCE_ERROR_NOT_FOUND')));

			return null;
		}

		$source = ExternalSource\Source\Factory::getSource($sourceEntity->getEnumType(), $sourceEntity->getId());
		$settings = $sourceEntity->getSettings();

		$connectResult = $source->connect(
			$settings->getValueByCode('host') ?? '',
			$settings->getValueByCode('username') ?? '',
			$settings->getValueByCode('password') ?? '',
		);

		if (!$connectResult->isSuccess())
		{
			$this->addErrors($connectResult->getErrors());

			return null;
		}

		return true;
	}

	public function saveAction(array $data): ?array
	{
		$sourceId = null;
		if (isset($data['id']))
		{
			$sourceId = (int)$data['id'];
		}

		if ($sourceId)
		{
			$saveResult = SourceManager::updateConnection($sourceId, $data);
		}
		else
		{
			$saveResult = SourceManager::addConnection($data);
		}

		if (!$saveResult->isSuccess())
		{
			$this->addErrors($saveResult->getErrors());

			return null;
		}

		return $saveResult->getData();
	}
}
