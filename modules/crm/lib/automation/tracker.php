<?php
namespace Bitrix\Crm\Automation;

use Bitrix\Bizproc\WorkflowStateTable;

/**
 * Class Tracker
 * @package Bitrix\Crm\Automation
 * @deprecated
 * @see \Bitrix\Bizproc\Automation\Tracker
 */

class Tracker
{
	const STATUS_WAITING = 0;
	const STATUS_RUNNING = 1;
	const STATUS_COMPLETED = 2;
	const STATUS_AUTOCOMPLETED = 3;

	protected $entityTypeId;
	protected $entityId;
	protected $target;

	public function __construct($entityTypeId, $entityId)
	{
		$this->entityTypeId = (int)$entityTypeId;
		$this->entityId = (int)$entityId;

		$this->target = Factory::createTarget($entityTypeId);
		$this->target->setEntityId($entityId);
		$documentId = \CCrmOwnerType::ResolveName($this->target->getEntityTypeId()) . '_' . $entityId;
		$this->target->setDocumentId($documentId);
	}

	public function getLog()
	{
		$log = array();

		if (!Helper::isBizprocEnabled())
			return $log;

		$currentStatus = $this->target->getEntityStatus();
		$allStatuses = $this->target->getEntityStatuses();

		$needleKey = array_search($currentStatus, $allStatuses);

		if ($needleKey === false)
			return $log;

		$logStatuses = array_slice($allStatuses, 0, $needleKey + 1);

		return $this->getBizprocTrackingEntries($logStatuses);
	}

	private function getBizprocTrackingEntries($statuses)
	{
		$entries = array();

		$states = $this->getStatusesStates($statuses);

		if ($states)
		{
			$trackIterator = \CBPTrackingService::GetList(array('ID' => 'DESC'), array(
				'@WORKFLOW_ID' => array_keys($states)
			));

			while ($row = $trackIterator->fetch())
			{
				$status = $states[$row['WORKFLOW_ID']];
				$entries[$status][] = $row;
			}
		}

		return $entries;
	}

	private function getStatusesStates($statuses)
	{
		$states = array();
		$templateIds = $this->getBizprocTemplateIds($statuses);

		if (!$templateIds)
			return $states;

		$stateIterator = WorkflowStateTable::getList(array(
			'select' => array('ID', 'WORKFLOW_TEMPLATE_ID'),
			'filter' => array(
				'=DOCUMENT_ID' => \CCrmOwnerType::ResolveName($this->entityTypeId).'_'.$this->entityId,
				'@WORKFLOW_TEMPLATE_ID' => array_keys($templateIds)
			),
			'order' => array('STARTED' => 'DESC')
		));

		while ($row = $stateIterator->fetch())
		{
			$status = $templateIds[$row['WORKFLOW_TEMPLATE_ID']];
			if (!in_array($status, $states))
				$states[$row['ID']] = $status;
		}

		return $states;
	}

	private function getBizprocTemplateIds($statuses)
	{
		$ids = array();

		$iterator = Engine\Entity\TemplateTable::getList(array(
			'select' => array('TEMPLATE_ID', 'ENTITY_STATUS'),
			'filter' => array(
				'=ENTITY_TYPE_ID' => $this->entityTypeId,
				'@ENTITY_STATUS' => $statuses
			)
		));

		while ($row = $iterator->fetch())
		{
			if ($row['TEMPLATE_ID'] > 0)
				$ids[$row['TEMPLATE_ID']] = $row['ENTITY_STATUS'];
		}

		return $ids;
	}
}