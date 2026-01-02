<?php

namespace Bitrix\Crm\Service\EditorAdapter\SchemeDecorator\MartaAI;

use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use Bitrix\Crm\Integration\UI\EntityEditor\MartaAIMarksRepository;
use Bitrix\Crm\Service\EditorAdapter\SchemeDecorator;
use Bitrix\Main\Localization\Loc;

final class ConfiguredField implements SchemeDecorator
{
	private readonly array $targetFields;

	public function __construct(
		private readonly MartaAIMarksRepository $repository,
	)
	{
		$this->targetFields = $this->repository->get(MarkTarget::Field) ?? [];
	}

	public function decorate(array $scheme): array
	{
		if (empty($this->targetFields))
		{
			return $scheme;
		}

		foreach ($scheme as &$column)
		{
			foreach ($column['elements'] as &$section)
			{
				foreach ($section['elements'] as &$field)
				{
					if (!$this->isMarkedField($field))
					{
						continue;
					}

					$field['immutableOptions']['titleOptions']['rightIconOptions'] = self::icon();
				}
			}
		}
		unset($column, $section, $field);

		$this->repository->delete(MarkTarget::Field);

		return $scheme;
	}

	private function isMarkedField(array $field): bool
	{
		return in_array($field['name'] ?? null, $this->targetFields, true);
	}

	private static function icon(): array
	{
		return [
			'icon' => 'o-ai-stars',
			'color' => 'conic-gradient(from 0deg at 50% 51.19%, #0075FF 0deg, #1FF4FB 180deg, #0075FF 360deg)',
			'size' => 16,
			'title' => Loc::getMessage('CRM_SERVICE_EDITOR_ADAPTER_SCHEME_DECORATOR_CREATED_BY_MARTA_AI_HINT'),
		];
	}
}
