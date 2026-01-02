<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Automation\Helper;
use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Integration\BizProc\Starter\CrmStarter;
use Bitrix\Crm\Integration\BizProc\Starter\Dto\DocumentDto;
use Bitrix\Crm\Integration\BizProc\Starter\Dto\RunDataDto;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\EventManager;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;
use CCrmBizProcEventType;

final class TransactionWrapper
{
	private readonly Connection $connection;

	public function __construct(
		private readonly Operation $operation,
	)
	{
		$this->connection = Application::getConnection();
	}

	/**
	 * Launches the operation, properly wrapped in transaction.
	 *
	 * @return Result
	 */
	public function launch(): Result
	{
		if ($this->operation instanceof Operation\Delete)
		{
			return $this->launchWholeOperationInTransaction();
		}

		return $this->launchOperationInTransactionAndAutomationAfterIt();
	}

	private function launchWholeOperationInTransaction(): Result
	{
		$this->connection->startTransaction();

		$result = $this->operation->launch();
		if ($result->isSuccess())
		{
			$this->connection->commitTransaction();
		}
		else
		{
			$this->connection->rollbackTransaction();
		}

		return $result;
	}

	private function launchOperationInTransactionAndAutomationAfterIt(): Result
	{
		$isBizProcEnabled = $this->operation->isBizProcEnabled();
		$isAutomationEnabled = $this->operation->isAutomationEnabled();

		$this->operation
			->disableBizProc()
			->disableAutomation()
		;

		$this->connection->startTransaction();

		$result = $this->operation->launch();
		if (!$result->isSuccess())
		{
			$this->connection->rollbackTransaction();

			return $result;
		}

		$this->connection->commitTransaction();

		if ($isBizProcEnabled)
		{
			$this->runBizProc();
		}
		if ($isAutomationEnabled)
		{
			$this->runAutomation();
		}

		return $result;
	}

	/**
	 * @see Operation::runBizProc() - copy-paste
	 */
	private function runBizProc(): void
	{
		$bizProcEventType = null;
		if ($this->operation instanceof Operation\Add)
		{
			$bizProcEventType = \CCrmBizProcEventType::Create;
		}
		elseif ($this->operation instanceof Operation\Update)
		{
			$bizProcEventType = \CCrmBizProcEventType::Edit;
		}

		if ($bizProcEventType === null)
		{
			return;
		}

		$request = Application::getInstance()->getContext()->getRequest();
		$data = $request->getPost('data');
		$workflowParameters = $data['bizproc_parameters'] ?? null;

		$starter = null;
		try
		{
			$starter = new CrmStarter(new DocumentDto(
				$this->operation->getItem()->getEntityTypeId(),
				$this->operation->getItem()->getId()
			));
		}
		catch (ArgumentException $exception)
		{}

		if ($starter)
		{
			$scope = (
				$this->operation->getContext()->getScope() === Context::SCOPE_AUTOMATION
					? CrmStarter::AUTOMATION_SCOPE
					: '')
			;
			if ($this->operation->getContext()->getScope() === Context::SCOPE_REST)
			{
				$scope = CrmStarter::REST_SCOPE;
			}

			$actualFields = null;
			$previousFields = null;
			if ($this->operation->getItemBeforeSave())
			{
				$actualFields = Helper::prepareCompatibleData(
					$this->operation->getItemBeforeSave()->getEntityTypeId(),
					$this->operation->getItemBeforeSave()->getCompatibleData(Values::CURRENT)
				);
				$previousFields = Helper::prepareCompatibleData(
					$this->operation->getItemBeforeSave()->getEntityTypeId(),
					$this->operation->getItemBeforeSave()->getCompatibleData(Values::ACTUAL)
				);
			}

			$starter->runProcess(
				new RunDataDto(
					actualFields: $actualFields,
					previousFields: $previousFields,
					userId: (
						$this->operation->getContext()->getScope() === Context::SCOPE_AUTOMATION
							? 0
							: $this->operation->getContext()->getUserId()
					),
					parameters: is_array($workflowParameters) || is_string($workflowParameters) ? $workflowParameters : null,
					scope: $scope,
				),
				$bizProcEventType,
			);
		}
	}

	/**
	 * @see Operation::runAutomation() - copy-paste
	 */
	private function runAutomation(): void
	{
		try
		{
			$starter = new CrmStarter(new DocumentDto(
				$this->operation->getItem()->getEntityTypeId(),
				$this->operation->getItem()->getId()
			));
		}
		catch (ArgumentException $exception)
		{
			return;
		}

		$scope = (
			$this->operation->getContext()->getScope() === Context::SCOPE_AUTOMATION
				? CrmStarter::AUTOMATION_SCOPE
				: ''
		);
		if ($this->operation->getContext()->getScope() === Context::SCOPE_REST)
		{
			$scope = CrmStarter::REST_SCOPE;
		}

		$userId = (
			in_array($this->operation->getContext()->getScope(), [Context::SCOPE_AUTOMATION, Context::SCOPE_REST], true)
				? 0
				: $this->operation->getContext()->getUserId()
		);

		$eventType = $this->operation->getItem()->getEntityEventName('OnAfterUpdate');
		$eventId = EventManager::getInstance()->addEventHandler(
			'crm',
			$eventType,
			[$this->operation, 'updateItemFromUpdateEvent']
		);

		if ($this->operation instanceof Operation\Add)
		{
			$starter->runAutomation(new RunDataDto(userId: $userId, scope: $scope), CCrmBizProcEventType::Create);
		}
		elseif (
			$this->operation instanceof Operation\Update
			// maybe the item wasn't changed and the operation was aborted
			&& $this->operation->getItemBeforeSave()
		)
		{
			$starter->runAutomation(
				new RunDataDto(
					actualFields: Helper::prepareCompatibleData(
						$this->operation->getItemBeforeSave()->getEntityTypeId(),
						$this->operation->getItemBeforeSave()->getCompatibleData(Values::CURRENT)
					),
					previousFields: Helper::prepareCompatibleData(
						$this->operation->getItemBeforeSave()->getEntityTypeId(),
						$this->operation->getItemBeforeSave()->getCompatibleData(Values::ACTUAL)
					),
					userId: $userId,
					scope: $scope
				),
				CCrmBizProcEventType::Edit
			);
		}

		EventManager::getInstance()->removeEventHandler('crm', $eventType, $eventId);
	}
}
