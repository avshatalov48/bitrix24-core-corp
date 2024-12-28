<?php

namespace Bitrix\Crm\Timeline\Bizproc\Data;

use Bitrix\Bizproc\Api\Request\WorkflowFacesService\GetDataRequest;
use Bitrix\Bizproc\Api\Service\WorkflowAccessService;
use Bitrix\Bizproc\Api\Service\WorkflowFacesService;
use Bitrix\Bizproc\Workflow\Entity\WorkflowStateTable;
use Bitrix\Bizproc\Api\Enum\Template\WorkflowTemplateType;
use Bitrix\Main\Loader;

final class Workflow
{
	public readonly string $id;
	private array $state;

	private static $cache = [];

	public function __construct(string $workflowId)
	{
		$this->id = $workflowId;
	}

	public function getTemplateName(): ?string
	{
		$this->loadWorkflowState();

		return $this->state['WORKFLOW_TEMPLATE_NAME'] ?? null;
	}

	private function loadWorkflowState(): void
	{
		if (!isset($this->state))
		{
			$this->state = [];
			if (Loader::includeModule('bizproc'))
			{
				$state = \CBPStateService::getWorkflowStateInfo($this->id);
				if ($state)
				{
					$this->state = $state;
				}
			}
		}
	}

	public function getFaces(int $runningTaskId = null): ?array
	{
		if (
			!Loader::includeModule('bizproc')
			|| !class_exists('\Bitrix\Bizproc\Api\Service\WorkflowFacesService')
		)
		{
			return null;
		}

		$workflowFacesService = new WorkflowFacesService(
			new WorkflowAccessService(),
		);

		$request = new GetDataRequest(
			workflowId: $this->id,
			runningTaskId: (int)$runningTaskId,
			skipAccessCheck: true,
		);

		$data = $workflowFacesService->getDataBySteps($request);
		if (!$data->isSuccess())
		{
			return null;
		}

		$faces = ['steps' => []];
		foreach ($data->getSteps() as $step)
		{
			if ($step)
			{
				$faces['steps'][] = [
					'id' => $step->getId(),
					'avatars' => $step->getAvatars(),
					'duration' => $step->getDuration(),
					'success' => $step->getSuccess(),
				];
			}
		}

		$progressBox = $data->getProgressBox();
		if ($progressBox)
		{
			$faces['progressTasksCount'] = $progressBox->getProgressTasksCount();
		}

		return $faces;
	}

	public function isWorkflowShowInTimeline(): bool
	{
		if (isset(self::$cache[$this->id]))
		{
			return self::$cache[$this->id];
		}

		$templateId = $this->getWorkflowTemplateId();

		if ($templateId)
		{
			$tpl = $this->getWorkflowTemplateById($templateId);

			if ($tpl)
			{
				if (!empty($tpl['SETTINGS_ID']))
				{
					self::$cache[$this->id] = $tpl['SETTINGS_VALUE'] !== 'N';

					return self::$cache[$this->id];
				}

				$documentEventType = (int)$tpl['AUTO_EXECUTE'];
				$isDefaultType = !empty($tpl['TYPE']) && $tpl['TYPE'] === WorkflowTemplateType::Default->value;
				$isNoneEventType = $documentEventType === \CBPDocumentEventType::None;
				$isCreateEventType = $documentEventType === \CBPDocumentEventType::Create;
				$isShownByDefault = $isDefaultType && ($isNoneEventType || $isCreateEventType);

				if ($isDefaultType && $isShownByDefault)
				{
					self::$cache[$this->id] = true;

					return true;
				}

				self::$cache[$this->id] = false;

				return false;
			}
		}

		self::$cache[$this->id] = false;

		return false;
	}

	private function getWorkflowTemplateId(): ?int
	{
		$workflowState = WorkflowStateTable::getList([
			'filter' => ['=ID' => $this->id],
			'select' => ['WORKFLOW_TEMPLATE_ID'],
		])->fetch();

		if ($workflowState)
		{
			return (int)$workflowState['WORKFLOW_TEMPLATE_ID'];
		}

		return null;
	}

	private function getWorkflowTemplateById(int $templateId): ?array
	{
		return \Bitrix\Bizproc\WorkflowTemplateTable::getRow([
			'filter' => ['ID' => $templateId],
			'select' => ['TYPE', 'AUTO_EXECUTE', 'SETTINGS_VALUE' => 'SETTINGS.VALUE', 'SETTINGS_ID' => 'SETTINGS.ID'],
			'runtime' => [
				new \Bitrix\Main\Entity\ReferenceField(
					'SETTINGS',
					\Bitrix\Bizproc\Workflow\Template\WorkflowTemplateSettingsTable::class,
					\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.TEMPLATE_ID')
						->where('ref.NAME', 'SHOW_IN_TIMELINE')
					,
					['join_type' => 'LEFT']
				)
			]
		]);
	}
}
