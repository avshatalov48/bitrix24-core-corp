<?php

namespace Bitrix\Sign\Item\Document\Template;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<TemplateFolderRelation>
 */
class TemplateFolderRelationCollection extends Collection
{
	/**
	 * @return list<?int>
	 */
	public function getEntityIds(): array
	{
		return array_map(
			static fn(TemplateFolderRelation $relation): int => $relation->entityId,
			$this->toArray(),
		);
	}

	protected function getItemClassName(): string
	{
		return TemplateFolderRelation::class;
	}
}
