<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Main;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\SalesCenter\Integration\CrmManager;

class AutomationStage extends Base
{
	protected function processBeforeAction(Action $action): bool
	{
		if (!$this->checkModules())
		{
			return false;
		}

		return parent::processBeforeAction($action);
	}

	private function checkModules(): bool
	{
		if (!Main\Loader::includeModule('crm'))
		{
			$this->addError(new Main\Error('module "crm" is not installed.'));
			return false;
		}

		return true;
	}

	public function configureActions()
	{
		return [
			'getStages' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getStagesAction(int $entityId, int $entityTypeId): ?array
	{
		if (Factory::isAutomationAvailable($entityTypeId))
		{
			$stageOnOrderPaid = CrmManager::getInstance()->getStageWithOrderPaidTrigger(
				$entityId,
				$entityTypeId
			);

			$stageOnDeliveryFinished = CrmManager::getInstance()->getStageWithDeliveryFinishedTrigger(
				$entityId,
				$entityTypeId
			);

			return [
				'stageOnOrderPaid' => $stageOnOrderPaid,
				'stageOnDeliveryFinished' => $stageOnDeliveryFinished,
			];
		}

		return null;
	}

	public function saveStagesAction(int $entityId, int $entityTypeId, array $stages): void
	{
		if (isset($stages['stageOnOrderPaid']))
		{
			CrmManager::getInstance()->saveTriggerOnOrderPaid(
				$entityId,
				$entityTypeId,
				$stages['stageOnOrderPaid']
			);
		}

		if (isset($stages['stageOnDeliveryFinished']))
		{
			CrmManager::getInstance()->saveTriggerOnDeliveryFinished(
				$entityId,
				$entityTypeId,
				$stages['stageOnDeliveryFinished']
			);
		}
	}
}