<?php

namespace Bitrix\Crm\Integration\BizProc\Starter;

use Bitrix\Bizproc\Starter\Dto\ContextDto;
use Bitrix\Bizproc\Starter\Dto\StarterConfigDto;
use Bitrix\Bizproc\Starter\Dto\StarterDto;
use Bitrix\Bizproc\Starter\Enum\Face;
use Bitrix\Bizproc\Starter\Enum\Scenario;
use Bitrix\Bizproc\Starter\Starter;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Automation\Trigger\BaseTrigger;
use Bitrix\Crm\Automation\Trigger\FieldChangedTrigger;
use Bitrix\Crm\Automation\Trigger\ResponsibleChangedTrigger;
use Bitrix\Crm\Integration\BizProc\Starter\Dto\DocumentDto;
use Bitrix\Crm\Integration\BizProc\Starter\Dto\EventDto;
use Bitrix\Crm\Integration\BizProc\Starter\Dto\RunDataDto;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use CCrmBizProcHelper;

final class CrmStarter
{
	public const AUTOMATION_SCOPE = 'automation';
	public const REST_SCOPE = 'rest';
	private array $complexId;
	private DocumentDto $document;

	public function __construct(DocumentDto $document)
	{
		$complexId = \CCrmBizProcHelper::ResolveDocumentId($document->entityTypeId, $document->entityId);
		if (!$complexId)
		{
			throw new ArgumentException('Invalid value for entityTypeId', 'entityTypeId');
		}

		$this->complexId = $complexId;
		$this->document = $document;
	}

	public function runOnDocumentAdd(RunDataDto $dto): void
	{
		$starter = $this->getStarter(true, $dto);
		if ($starter)
		{
			$starter->setValidateParameters(false);
			$starter->start();
		}
		else
		{
			$this->runProcess($dto, \CCrmBizProcEventType::Create);

			// region automation
			foreach ($dto->events as $event)
			{
				$this->executeTrigger($event);
			}

			$this->runAutomation($dto, \CCrmBizProcEventType::Create);
		}
	}

	public function runOnDocumentUpdate(RunDataDto $dto): void
	{
		$starter = $this->getStarter(false, $dto);
		if ($starter)
		{
			$starter->setValidateParameters(false);
			$starter->start();
		}
		else
		{
			$this->runProcess($dto, \CCrmBizProcEventType::Edit);

			foreach ($dto->events as $event)
			{
				$this->executeTrigger($event);
			}

			$this->runAutomation($dto, \CCrmBizProcEventType::Edit);
		}
	}

	// todo: runOnEvents

	public function runProcess(RunDataDto $dto, int $eventType): Result
	{
		$result = new Result();

		if ($this->isStarterEnabled())
		{
			$starter =
				(new Starter(
					new StarterDto(
						process: new StarterConfigDto(
							scenario: $this->getScenarioByScope($dto->scope, $eventType === \CCrmBizProcEventType::Create),
							validateParameters: false,
						),
					)
				))
			;
			$this->fillStarterByRunDto($dto, $starter);
			if ($eventType !== \CCrmBizProcEventType::Create)
			{
				$this->fillStarterWithCommonTriggers($dto, $starter);
			}

			$startResult = $starter->start();
			$result->addErrors($startResult->getErrors());
		}
		else
		{
			$errors = [];
			\CCrmBizProcHelper::AutoStartWorkflows(
				$this->document->entityTypeId,
				$this->document->entityId,
				$eventType,
				$errors,
				$dto->parameters,
			);
			foreach ($errors as $error)
			{
				$customData = array_diff_key($errors, ['message' => '', 'code' => '']);

				$result->addError(
					new Error($error['message'], $error['code'], $customData)
				);
			}
		}

		return $result;
	}

	public function runAutomation(RunDataDto $dto, int $eventType): Result
	{
		$result = new Result();

		if ($this->isStarterEnabled())
		{
			$starter =
				(new Starter(
					new StarterDto(
						automation: new StarterConfigDto(
							scenario: $this->getScenarioByScope($dto->scope, $eventType === \CCrmBizProcEventType::Create),
							validateParameters: false,
							checkConstants: false,
						),
					)
				))
			;
			$this->fillStarterByRunDto($dto, $starter);
			if ($eventType !== \CCrmBizProcEventType::Create)
			{
				$this->fillStarterWithCommonTriggers($dto, $starter);
			}

			$startResult = $starter->start();
			$result->addErrors($startResult->getErrors());
		}
		elseif (Factory::isAutomationAvailable($this->document->entityTypeId))
		{
			$starter = new \Bitrix\Crm\Automation\Starter($this->document->entityTypeId, $this->document->entityId);
			if ($dto->userId > 0)
			{
				$starter->setUserId($dto->userId);
			}

			if ($dto->scope === self::AUTOMATION_SCOPE)
			{
				$starter->setContextToBizproc();
			}
			elseif ($dto->scope === self::REST_SCOPE)
			{
				$starter->setContextToRest();
			}

			if ($eventType === \CCrmBizProcEventType::Create)
			{
				$runResult = $starter->runOnAdd();
			}
			else
			{
				$runResult = $starter->runOnUpdate($dto->actualFields ?? [], $dto->previousFields ?? []);
			}

			$result->addErrors($runResult->getErrors());
			$result->setData($runResult->getData());
		}

		return $result;
	}

	private function getScenarioByScope(string $scope, bool $isNew = false): Scenario
	{
		if (in_array($scope, [self::REST_SCOPE, self::AUTOMATION_SCOPE], true))
		{
			return $isNew ? Scenario::onDocumentInnerAdd : Scenario::onDocumentInnerUpdate;
		}

		return $isNew ? Scenario::onDocumentAdd : Scenario::onDocumentUpdate;
	}

	private function getStarter(bool $isNew, RunDataDto $dto): ?Starter
	{
		if ($this->isStarterEnabled())
		{
			$starter = Starter::getByScenario($isNew ? Scenario::onDocumentAdd : Scenario::onDocumentUpdate);
			$this->fillStarterByRunDto($dto, $starter);

			foreach ($dto->events as $event)
			{
				$starter->addEvent(
					$event->triggerCode,
					$this->convertEventDocumentsToDocumentDto($event->documents),
					$event->parameters
				);
			}

			if (!$isNew)
			{
				$this->fillStarterWithCommonTriggers($dto, $starter);
			}

			return $starter;
		}

		return null;
	}

	private function fillStarterWithCommonTriggers(RunDataDto $dto, Starter $starter): void
	{
		$changedFields = $this->computeChangedFields($dto->actualFields ?? [], $dto->previousFields ?? []);
		if (!$changedFields)
		{
			return;
		}

		$events = [
			new EventDto(FieldChangedTrigger::getCode(), [$this->document], ['CHANGED_FIELDS' => $changedFields]),
		];

		$responsibleKey = (
			$this->document->entityTypeId === \CCrmOwnerType::Order
				? 'RESPONSIBLE_ID'
				: 'ASSIGNED_BY_ID'
		);
		if (in_array($responsibleKey, $changedFields, true))
		{
			$events[] = new EventDto(ResponsibleChangedTrigger::getCode(), [$this->document]);
		}

		foreach ($events as $event)
		{
			$starter->addEvent(
				$event->triggerCode,
				$this->convertEventDocumentsToDocumentDto($event->documents),
				$event->parameters
			);
		}
	}

	private function fillStarterByRunDto(RunDataDto $dto, Starter $starter): void
	{
		$face = $dto->scope === self::REST_SCOPE ? Face::REST : Face::WEB;

		$starter
			->setDocument(new \Bitrix\Bizproc\Starter\Dto\DocumentDto(
				complexDocumentId: $this->complexId,
				complexDocumentType: CCrmBizProcHelper::ResolveDocumentType($this->document->entityTypeId),
				changedFieldNames: $this->computeChangedFields(
					$dto->actualFields ?? [],
					$dto->previousFields ?? []
				)
			))
			->setContext(new ContextDto('crm', $face))
			->setParameters($dto->parameters ?? [])
			->setUser($dto->userId)
		;
	}

	private function computeChangedFields(array $actualFields, array $previousFields): array
	{
		return (new DocumentFieldComparator(
			$this->document->entityTypeId, $actualFields, $previousFields
		))->compare();
	}

	private function convertEventDocumentsToDocumentDto(array $eventDocuments): array
	{
		$documents = [];
		/** @var DocumentDto[] $eventDocuments */
		foreach ($eventDocuments as $document)
		{
			$complexId = CCrmBizProcHelper::resolveDocumentId($document->entityTypeId, $document->entityId);
			if ($complexId)
			{
				$documents[] = new \Bitrix\Bizproc\Starter\Dto\DocumentDto(
					$complexId,
					CCrmBizProcHelper::ResolveDocumentType($document->entityTypeId)
				);
			}
		}

		return $documents;
	}

	private function executeTrigger(EventDto $event): void
	{
		$bindings = [];

		/** @var BaseTrigger $trigger */
		$trigger = null;
		foreach ($event->documents as $document)
		{
			$supportedTrigger = \CCrmDocument::getTriggerByCode(
				$event->triggerCode,
				CCrmBizProcHelper::ResolveDocumentType($document->entityTypeId),
			);
			if ($supportedTrigger)
			{
				$bindings[] = [
					'OWNER_TYPE_ID' => $document->entityTypeId,
					'OWNER_ID' => $document->entityId,
				];
				$trigger = $supportedTrigger;
			}
		}

		if ($trigger)
		{
			$trigger::execute($bindings, $event->parameters);
		}
	}

	private function isStarterEnabled(): bool
	{
		return Loader::includeModule('bizproc') && class_exists(Starter::class) && Starter::isEnabled();
	}
}
