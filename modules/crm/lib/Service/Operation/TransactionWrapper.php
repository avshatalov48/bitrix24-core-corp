<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Automation\Helper;
use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\EventManager;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\Result;

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
		$workflowParameters = [];
		if (is_array($data) && isset($data['bizproc_parameters']))
		{
			$workflowParameters = $data['bizproc_parameters'];
		}

		$errors = [];
		\CCrmBizProcHelper::AutoStartWorkflows(
			$this->operation->getItem()->getEntityTypeId(),
			$this->operation->getItem()->getId(),
			$bizProcEventType,
			$errors,
			$workflowParameters,
		);
	}

	/**
	 * @see Operation::runAutomation() - copy-paste
	 */
	private function runAutomation(): void
	{
		$starter = new Starter($this->operation->getItem()->getEntityTypeId(), $this->operation->getItem()->getId());

		switch ($this->operation->getContext()->getScope())
		{
			case Context::SCOPE_AUTOMATION:
				$starter->setContextToBizproc();
				break;
			case Context::SCOPE_REST:
				$starter->setContextToRest();
				break;
			default:
				$starter->setUserId($this->operation->getContext()->getUserId());
				break;
		}

		$eventType = $this->operation->getItem()->getEntityEventName('OnAfterUpdate');
		$eventId = EventManager::getInstance()->addEventHandler(
			'crm',
			$eventType,
			[$this->operation, 'updateItemFromUpdateEvent']
		);

		if ($this->operation instanceof Operation\Add)
		{
			$starter->runOnAdd();
		}
		elseif (
			$this->operation instanceof Operation\Update
			// maybe the item wasn't changed and the operation was aborted
			&& $this->operation->getItemBeforeSave()
		)
		{
			$starter->runOnUpdate(
				Helper::prepareCompatibleData(
					$this->operation->getItemBeforeSave()->getEntityTypeId(),
					$this->operation->getItemBeforeSave()->getCompatibleData(Values::CURRENT)
				),
				Helper::prepareCompatibleData(
					$this->operation->getItemBeforeSave()->getEntityTypeId(),
					$this->operation->getItemBeforeSave()->getCompatibleData(Values::ACTUAL)
				)
			);
		}

		EventManager::getInstance()->removeEventHandler('crm', $eventType, $eventId);
	}
}
