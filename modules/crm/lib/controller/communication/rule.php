<?php

namespace Bitrix\Crm\Controller\Communication;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Communication\Controller\RuleController;
use Bitrix\Crm\Service\Communication\Entity\CommunicationChannelRuleTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ResponsibleQueue\Controller\QueueConfigController;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Result;

final class Rule extends Base
{
	public function saveAction(
		string $title,
		int $channelId,
		array $properties,
		array $actions,
		array $queueConfig,
		?array $searchTargets = null,
		?int $id = null,
		array $settings = [],
	): Result
	{
		$result = new Result();

		if (!$this->isAdmin())
		{
			return $result->addError(ErrorCode::getAccessDeniedError());
		}

		if (!$this->isQueueConfigValid($queueConfig))
		{
			return $result->addError(
				new Error(
					'Responsible queue config has invalid structure',
					ErrorCode::INVALID_ARG_VALUE
				)
			);
		}

		$applyQueueResult = $this->fillQueueConfig(
			$queueConfig,
			sprintf('Queue for route "%s"',  $title),
			$id
		);
		if (!$applyQueueResult->isSuccess())
		{
			foreach ($applyQueueResult->getErrors() as $error)
			{
				$result->addError(new Error($error->getMessage()));
			}

			return $result;
		}

		$queueConfigId = $applyQueueResult->getId();

		$ruleController = RuleController::getInstance();
		if ($id)
		{
			$actionResult = $ruleController->update(
				$id,
				$title,
				$channelId,
				$queueConfigId,
				$searchTargets,
				$properties,
				$actions,
				$settings
			);
		}
		else
		{
			$actionResult =  $ruleController->add(
				$title,
				$channelId,
				$queueConfigId,
				$searchTargets,
				$properties,
				$actions,
				$settings
			);
		}

		if ($actionResult->isSuccess())
		{
			return $result;
		}

		foreach ($actionResult->getErrors() as $error)
		{
			$result->addError(new Error($error->getMessage()));
		}

		return $result;
	}

	public function deleteAction(int $id, bool $withQueue = false): Result
	{
		$result = new Result();

		if (!$this->isAdmin())
		{
			return $result->addError(ErrorCode::getAccessDeniedError());
		}

		if ($id <= 0)
		{
			return $result->addError(ErrorCode::getNotFoundError());
		}

		$ruleData = CommunicationChannelRuleTable::getById($id)->fetch();
		$queueConfigId = $ruleData ? (int)$ruleData['QUEUE_CONFIG_ID'] : null;

		$result = RuleController::getInstance()->deleteById($id);
		if ($result->isSuccess() && $withQueue && isset($queueConfigId))
		{
			$queueResult = QueueConfigController::getInstance()->deleteById($queueConfigId);
			if (!$queueResult->isSuccess())
			{
				$result->addErrors($queueResult->getErrors());
			}
		}

		return $result;
	}

	private function isAdmin(): bool
	{
		return Container::getInstance()->getUserPermissions($this->getCurrentUser()?->getId())->isCrmAdmin();
	}

	private function isQueueConfigValid(array $queueConfig): bool
	{
		if (empty($queueConfig))
		{
			return false;
		}

		$isMembersSet = isset($queueConfig['members']) && is_array($queueConfig['members']);
		$isPropertiesSet = isset($queueConfig['properties']) && is_array($queueConfig['properties']);

		return $isMembersSet && $isPropertiesSet;
	}
	
	private function fillQueueConfig(array $queueConfig, string $title, ?int $ruleId = null): AddResult|UpdateResult
	{
		$queueConfigId = null;
		if (isset($ruleId))
		{
			$ruleData = CommunicationChannelRuleTable::getById($ruleId)->fetch();
			$queueConfigId = $ruleData ? (int)$ruleData['QUEUE_CONFIG_ID'] : null;
		}

		$queueController = QueueConfigController::getInstance();
		if ($queueConfigId)
		{
			return $queueController->update(
				$queueConfigId,
				$queueConfig['members'] ?? [],
				$queueConfig['properties'] ?? []
			);
		}

		return $queueController->add(
			$title,
			QueueConfigController::TYPE_COMMUNICATION_CHANNEL_ROUTING,
			$queueConfig['members'] ?? [],
			$queueConfig['properties'] ?? []
		);
	}
}
