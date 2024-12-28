<?php

namespace Bitrix\Tasks\Integration\Bizproc\Flow;

use Bitrix\Bizproc\Script\Manager as BizProcManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Integration\Bizproc\Flow\Robot\Factory;
use Bitrix\Tasks\Integration\Bizproc\Exception\SmartProcessException;
use Bitrix\Tasks\Internals\Log\LogFacade;

class Manager
{
	public function runProc(int $procId, array $docIds = []): void
	{
		if (!$procId)
		{
			return;
		}

		if (!Loader::includeModule('bizproc'))
		{
			return;
		}

		$userId = 1;
		$parameters = [];
		$result = BizProcManager::startScript($procId, $userId, $docIds, $parameters);

		if (!$result->isSuccess())
		{
			LogFacade::log(
				'Flow: Failed running smart proc: ' . Json::encode($result->getErrorMessages()),
				'TASKS_FLOW_BIZPROC_RUN'
			);
		}
	}

	public function deleteSmartProcess(int $procId): void
	{
		if (!Loader::includeModule('bizproc'))
		{
			return;
		}

		if ($procId > 0)
		{
			BizProcManager::deleteScript($procId);
		}
	}

	/**
	 * @throws SmartProcessException
	 */
	public function addSmartProcess(Item $item): int
	{
		if (!Loader::includeModule('bizproc'))
		{
			return 0;
		}

		$userId = 1;
		$procId = 0;
		$documentType = Factory::getDocumentType($item);
		$robots = Factory::buildRobots($item);

		if (empty($robots))
		{
			LogFacade::log(
				'Flow: Unknown robot type: ' . $item->getChannel(),
				'TASKS_FLOW_BIZPROC_RUN_ADD'
			);

			return 0;
		}

		$fields = [
			'script' => [
				'ID' => $procId,
				'NAME' => $item->getCaption()->getValue(),
				'DESCRIPTION' => 'This script was automatically created by Tasks:SyncAgent',
			],
			'robotsTemplate' => [
				'ID' => 0,
				'DOCUMENT_TYPE' => $documentType,
				'DOCUMENT_STATUS' => 'SCRIPT',
				'PARAMETERS' => [],
				'CONSTANTS' => [],
				'VARIABLES' => [],
				'IS_EXTERNAL_MODIFIED' => 0,
				'ROBOTS' => $robots,
			],
		];

		$result = BizProcManager::saveScript(
			$procId,
			$documentType,
			$fields,
			$userId
		);

		if (!$result->isSuccess())
		{
			$message = 'Flow: Failed creating new smart proc: ' . Json::encode($result->getErrorMessages());

			throw new SmartProcessException($message, $result->getData());
		}

		return $result->getId();
	}
}