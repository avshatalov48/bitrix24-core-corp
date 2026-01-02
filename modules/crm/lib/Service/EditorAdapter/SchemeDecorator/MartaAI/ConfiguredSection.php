<?php

namespace Bitrix\Crm\Service\EditorAdapter\SchemeDecorator\MartaAI;

use Bitrix\Crm\Integration\UI\EntityEditor\Enum\MarkTarget;
use Bitrix\Crm\Integration\UI\EntityEditor\MartaAIMarksRepository;
use Bitrix\Crm\Service\EditorAdapter\SchemeDecorator;

final class ConfiguredSection implements SchemeDecorator
{
	private readonly array $targetSections;

	public function __construct(
		private readonly MartaAIMarksRepository $repository,
	)
	{
		$this->targetSections = $this->repository->get(MarkTarget::Section) ?? [];
	}

	public function decorate(array $scheme): array
	{
		if (empty($this->targetSections))
		{
			return $scheme;
		}

		foreach ($scheme as &$column)
		{
			foreach ($column['elements'] as &$section)
			{
				if (!$this->isMarkedSection($section))
				{
					continue;
				}

				/** @see \CCrmEntityEditorComponent css styles */
				$section['immutableOptions']['wrapperClassList'] = [
					'--marta-ai-configured-section',
				];
			}
		}
		unset($column, $section);

		$this->repository->delete(MarkTarget::Section);

		return $scheme;
	}

	private function isMarkedSection(array $section): bool
	{
		return in_array($section['name'] ?? null, $this->targetSections, true);
	}
}
