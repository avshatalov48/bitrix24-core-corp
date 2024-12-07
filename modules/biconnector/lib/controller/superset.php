<?php

namespace Bitrix\BIConnector\Controller;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\SupersetInitializer;
use Bitrix\BIConnector\Superset\Cache\CacheManager;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\BIConnector;

class Superset extends Controller
{
	public function getDefaultPreFilters()
	{
		$prefilters = parent::getDefaultPreFilters();
		if (Loader::includeModule('intranet'))
		{
			$prefilters[] = new \Bitrix\Intranet\ActionFilter\IntranetUser();
		}

		return $prefilters;
	}

	public function onStartupMetricSendAction()
	{
		\Bitrix\Main\Config\Option::set('biconnector', 'superset_startup_metric_send', true);
	}

	/**
	 * Clean action from user to delete superset instance
	 *
	 * @return bool|null
	 */
	public function cleanAction(): ?bool
	{
		if (!BIConnector\Manager::isAdmin())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SUPERSET_DELETE_ERROR_RIGHTS')));

			return null;
		}

		if (!SupersetInitializer::isSupersetExist())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SUPERSET_ALREADY_DELETED')));

			return null;
		}

		$result = SupersetInitializer::deleteInstance();
		if (!$result->isSuccess())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SUPERSET_DELETE_ERROR')));

			return null;
		}

		SupersetInitializer::setSupersetStatus(SupersetInitializer::SUPERSET_STATUS_DELETED);

		return true;
	}

	/**
	 * Enable superset action from user
	 *
	 * @return bool|null
	 */
	public function enableAction(): ?bool
	{
		if (!BIConnector\Manager::isAdmin())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SUPERSET_START_ERROR_RIGHTS')));

			return null;
		}

		if (SupersetInitializer::getAvailableToEnableSupersetTimestamp() > time())
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SUPERSET_START_ERROR_START_TIMESTAMP')));

			return null;
		}

		SupersetInitializer::setSupersetStatus(SupersetInitializer::SUPERSET_STATUS_DOESNT_EXISTS);
		// SupersetInitializer::startupSuperset();

		return true;
	}

	public function clearCacheAction(): ?array
	{
		if (!AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_ACCESS))
		{
			$this->addError(new Error(Loc::getMessage('BICONNECTOR_CONTROLLER_SUPERSET_CACHE_RIGHTS_ERROR')));

			return null;
		}

		$cacheManager = CacheManager::getInstance();
		if (!$cacheManager->canClearCache())
		{
			$time = $cacheManager->getNextClearTimeout();
			$errorMessage = Loc::getMessagePlural(
				'BICONNECTOR_CONTROLLER_SUPERSET_CACHE_TIMEOUT',
				ceil($time / 60),
				['#COUNT#' => ceil($time / 60)],
			);
			$this->addError(new Error($errorMessage));

			return null;
		}

		$clearResult = $cacheManager->clear();
		if (!$clearResult->isSuccess())
		{
			$this->addErrors($clearResult->getErrors());

			return null;
		}

		return [
			'timeoutToNextClearCache' => $cacheManager->getNextClearTimeout(),
		];
	}
}
