<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<Template>
 */
class TemplateCollection extends Collection
{
	/**
	 * @return list<?int>
	 */
	public function getIds(): array
	{
		return array_map(
			static fn(Template $template): int => $template->id,
			$this->toArray(),
		);
	}

	/**
	 * @return list<int>
	 */
	public function getIdsWithoutNull(): array
	{
		return array_filter($this->getIds(), static fn(?int $id): bool => $id !== null);
	}

	/**
	 * Get array with keys - values of id field
	 *
	 * @return array<int, Template>
	 */
	public function mapWithIdKeys(): array
	{
		$templates = [];
		foreach ($this->toArray() as $template)
		{
			$templates[$template->id] = $template;
		}

		return $templates;
	}

	public function findById(int $id): ?Template
	{
		return $this->findByRule(static fn(Template $template): bool => $template->id === $id);
	}

	protected function getItemClassName(): string
	{
		return Template::class;
	}
}
