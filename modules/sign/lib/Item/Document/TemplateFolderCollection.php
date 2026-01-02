<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<TemplateFolder>
 */
class TemplateFolderCollection extends Collection
{
	/**
	 * @return list<?int>
	 */
	public function getIds(): array
	{
		return array_map(
			static fn(TemplateFolder $template): int => $template->id,
			$this->toArray(),
		);
	}

	public function findById(int $id): ?TemplateFolder
	{
		return $this->findByRule(static fn(TemplateFolder $template): bool => $template->id === $id);
	}

	protected function getItemClassName(): string
	{
		return TemplateFolder::class;
	}
}