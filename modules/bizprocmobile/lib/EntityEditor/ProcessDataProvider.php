<?php

namespace Bitrix\BizprocMobile\EntityEditor;

use Bitrix\BizprocMobile\UI\WorkflowUserDetailView;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('ui');

class ProcessDataProvider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	private array $data = [];
	private WorkflowUserDetailView $workflowView;

	public function __construct(WorkflowUserDetailView $workflowView)
	{
		$this->workflowView = $workflowView;
		$this->fillData();
	}

	private function fillData(): void
	{
		$this->data = [
			'modified' => $this->workflowView->getModifiedTimestamp(),
			'status' => $this->workflowView->getStatusText(),
		];

		if ($this->workflowView->getIsCompleted())
		{
			$result = $this->workflowView->getWorkflowResult();
			$this->data['result'] = $result ? $result['text'] : '';
		}
	}

	public function getGUID(): string
	{
		return 'BIZPROC_PROCESS_DATA';
	}

	public function getEntityId(): ?int
	{
		return null;
	}

	public function getEntityTypeName(): string
	{
		return 'bizproc_process_data';
	}

	public function getEntityFields(): array
	{
		$fields = [
			'modified' => [
				'name' => 'modified',
				'type' => 'datetime',
				'title' => Loc::getMessage('BPMOBILE_LIB_ENTITY_EDITOR_PROCESS_DATA_PROVIDER_MODIFIED'),
				'editable' => false,
				'required' => false,
				'multiple' => false,
				'showAlways' => true,
				'showNew' => true,
			],
			'status' => [
				'name' => 'status',
				'type' => 'string',
				'title' => Loc::getMessage('BPMOBILE_LIB_ENTITY_EDITOR_PROCESS_DATA_PROVIDER_STATUS'),
				'editable' => false,
				'required' => false,
				'multiple' => false,
				'showAlways' => true,
				'showNew' => true,
			],
		];

		if ($this->workflowView->getIsCompleted())
		{
			$fields['result'] = [
				'name' => 'result',
				'type' => 'textarea',
				'title' => Loc::getMessage('BPMOBILE_LIB_ENTITY_EDITOR_PROCESS_DATA_PROVIDER_RESULT'),
				'editable' => false,
				'required' => false,
				'multiple' => false,
				'showAlways' => true,
				'showNew' => true,
			];
		}

		return $fields;
	}

	public function getEntityConfig(): array
	{
		return [
			[
				'name' => 'default_column',
				'type' => 'column',
				'elements' => [
					[
						'name' => 'main',
						'title' => Loc::getMessage('BPMOBILE_LIB_ENTITY_EDITOR_PROCESS_DATA_PROVIDER_TITLE'),
						'type' => 'section',
						'elements' => $this->getEntityFields(),
						'data' => [
							'showBorder' => true,
						],
					],
				],
			],
		];
	}

	public function getEntityData(): array
	{
		return $this->data;
	}

	public function isReadOnly(): bool
	{
		return true;
	}
}
