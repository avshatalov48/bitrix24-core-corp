<?php

namespace Bitrix\BizprocMobile\EntityEditor;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::requireModule('ui');

class TaskProvider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	private int $taskId;
	private array $fields = [];
	private array $data = [];

	public function __construct(int $taskId, array $fields)
	{
		$this->taskId = $taskId;

		foreach ($fields as $name => $property)
		{
			$id = $property['Id'] ?? $name;

			$this->fields[$id] = array_merge(
				$property,
				[
					'showAlways' => true,
					'showNew' => true,
				],
			);
			$this->data[$id] = $property['custom']['default'] ?? null;
		}
	}

	public function getGUID(): string
	{
		return 'BIZPROC_TASK_FIELDS';
	}

	public function getEntityId(): ?int
	{
		return null;
	}

	public function getEntityTypeName(): string
	{
		return 'bizproc_task';
	}

	public function getEntityFields(): array
	{
		return $this->fields;
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
						'title' => Loc::getMessage('BIZPROCMOBILE_LIB_ENTITY_EDITOR_TASK_PROVIDER_FIELDS_SECTION_TITLE_1'),
						'type' => 'section',
						'elements' => $this->getEntityFields(),
						'data' => [
							'showBorder' => true,
						],
					]
				],
			],
		];
	}

	public function getEntityData(): array
	{
		$data = $this->data;
		$data['TASK_ID'] = $this->taskId;

		return $data;
	}
}
