<?php
namespace Bitrix\Crm\Automation\Engine;

use Bitrix\Bizproc\WorkflowInstanceTable;
use Bitrix\Crm\Activity\AutocompleteRule;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Automation\Target\BaseTarget;
use Bitrix\Crm\Automation;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Runtime
 * @package Bitrix\Crm\Automation\Engine
 * @deprecated
 * @see \Bitrix\Bizproc\Automation\Engine\Runtime
 */

class Runtime
{
	protected $target;
	protected static $startedTemplates = array();

	public function setTarget(BaseTarget $target)
	{
		$this->target = $target;
		return $this;
	}

	/**
	 * @return BaseTarget
	 * @throws InvalidOperationException
	 */
	public function getTarget()
	{
		if ($this->target === null)
			throw new InvalidOperationException('Target must be set by setTarget method.');

		return $this->target;
	}

	protected function getBizprocDocumentType()
	{
		$entityTypeId = $this->getTarget()->getEntityTypeId();

		return array(
			'crm',
			\CCrmBizProcHelper::ResolveDocumentName($entityTypeId),
			\CCrmOwnerType::ResolveName($entityTypeId)
		);
	}

	protected function getBizprocDocumentId()
	{
		$entityTypeId = $this->getTarget()->getEntityTypeId();
		$entityId = $this->getTarget()->getEntityId();

		return array(
			'crm',
			\CCrmBizProcHelper::ResolveDocumentName($entityTypeId),
			\CCrmOwnerType::ResolveName($entityTypeId).'_'.$entityId
		);
	}

	protected function getBizprocInstanceIds(array $documentId)
	{
		$iterator = WorkflowInstanceTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=STATE.MODULE_ID'             => $documentId[0],
				'=STATE.ENTITY'                => $documentId[1],
				'=STATE.DOCUMENT_ID'           => $documentId[2],
				'=STATE.TEMPLATE.AUTO_EXECUTE' => \CBPDocumentEventType::Automation
			)
		));

		$ids = array();
		while ($row = $iterator->fetch())
		{
			$ids[] = $row['ID'];
		}

		return $ids;
	}

	protected function runTemplates($documentStatus)
	{
		if (!Automation\Helper::isBizprocEnabled())
			return false;

		$iterator = Entity\TemplateTable::getList(array(
			'filter' => array(
				'=ENTITY_TYPE_ID' => $this->getTarget()->getEntityTypeId(),
				'=ENTITY_STATUS'  => $documentStatus
			)
		));
		$templateData = $iterator->fetch(); // single, yet (?)

		if ($templateData)
		{
			$template = new Template($templateData);

			$errors = array();
			$trigger = $this->getTarget()->getAppliedTrigger();

			if (!$template->isExternalModified() && !$trigger && !$template->getRobots())
			{
				return false;
			}

			$workflowId = \CBPDocument::StartWorkflow(
				$templateData['TEMPLATE_ID'],
				$this->getBizprocDocumentId(),
				array(
					\CBPDocument::PARAM_USE_FORCED_TRACKING => !$template->isExternalModified(),
					\CBPDocument::PARAM_IGNORE_SIMULTANEOUS_PROCESSES_LIMIT => true
				),
				$errors
			);

			if (!$errors && $trigger && $workflowId)
			{
				$this->writeTriggerTracking($workflowId, $trigger);
			}

			$this->setStarted($this->getTarget()->getEntityId(), $documentStatus);
		}
		return true;
	}

	protected function writeTriggerTracking($workflowId, $trigger)
	{
		$runtime = \CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		/** @var \CBPTrackingService $trackingService */
		$trackingService = $runtime->GetService('TrackingService');

		$trackingService->Write(
			$workflowId,
			\CBPTrackingType::Trigger,
			'APPLIED_TRIGGER',
			\CBPActivityExecutionStatus::Closed,
			\CBPActivityExecutionResult::Succeeded,
			'',
			$trigger['ID']
		);
	}

	protected function stopTemplates()
	{
		if (!Automation\Helper::isBizprocEnabled())
			return;

		$errors = array();
		$documentId = $this->getBizprocDocumentId();
		$instanceIds = $this->getBizprocInstanceIds($documentId);
		foreach ($instanceIds as $instanceId)
		{
			\CBPDocument::TerminateWorkflow(
				$instanceId,
				$documentId,
				$errors,
				Loc::getMessage('CRM_AUTOMATION_TEMPLATE_TERMINATED_MSGVER_1')
			);
		}
	}

	protected function doAutocompleteActivities()
	{
		$entityTypeId = $this->getTarget()->getEntityTypeId();
		$entityId = $this->getTarget()->getEntityId();

		$result = ActivityTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=COMPLETED' => 'N',
				'=AUTOCOMPLETE_RULE' => AutocompleteRule::AUTOMATION_ON_STATUS_CHANGED,
				'=BINDINGS.OWNER_TYPE_ID' => $entityTypeId,
				'=BINDINGS.OWNER_ID' => $entityId,
			),
			'order' => array('ID' => 'ASC')
		));

		while ($row = $result->fetch())
		{
			\CCrmActivity::SetAutoCompleted($row['ID']);
		}
	}

	public function onEntityAdd()
	{
		$entityStatus = $this->getTarget()->getEntityStatus();
		if ($entityStatus && !$this->isStarted($this->getTarget()->getEntityId(), $entityStatus))
		{
			$this->runTemplates($entityStatus);
		}
	}

	public function onEntityStatusChanged()
	{
		$entityStatus = $this->getTarget()->getEntityStatus();
		if ($entityStatus && !$this->isStarted($this->getTarget()->getEntityId(), $entityStatus))
		{
			$this->stopTemplates();
			$this->doAutocompleteActivities();
			$this->runTemplates($entityStatus);
		}
	}

	private function setStarted($entityId, $status)
	{
		$type = $this->getTarget()->getEntityTypeId();
		if (!isset(static::$startedTemplates[$type]))
			static::$startedTemplates[$type] = array();

		static::$startedTemplates[$type][$entityId] = $status;

		return $this;
	}

	private function isStarted($id, $status)
	{
		$type = $this->getTarget()->getEntityTypeId();
		return (
			isset(static::$startedTemplates[$type])
			&& isset(static::$startedTemplates[$type][$id])
			&& $status == static::$startedTemplates[$type][$id]
		);
	}
}