<?php

namespace Bitrix\Sign\Service\Sign\Document\Template;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelation;
use Bitrix\Sign\Item\Document\Template\TemplateFolderRelationCollection;
use Bitrix\Sign\Type\Template\EntityType;

class TemplateFolderRelationService
{
	/**
	 * @param array<array{entityType: string, id: int}> $entities
	 * @return TemplateFolderRelationCollection
	 */
	public function getPrepareTemplateFolderRelations(array $entities): TemplateFolderRelationCollection
	{
		$templateFolderRelations = new TemplateFolderRelationCollection();
		$currentUserId = (int)CurrentUser::get()->getId();
		if (!$currentUserId)
		{
			return $templateFolderRelations;
		}

		$filteredItems = array_filter($entities, function ($item) {
			return isset($item['id'], $item['entityType']);
		});
		foreach ($filteredItems as $item)
		{
			$itemId = (int)($item['id'] ?? null);
			$entityType = EntityType::tryFrom($item['entityType'] ?? '');
			if ($itemId < 1 || $entityType === null)
			{
				continue;
			}

			$relation = new TemplateFolderRelation(
				entityId: $itemId,
				entityType: $entityType,
				createdById: $currentUserId,
			);

			$templateFolderRelations->add($relation);
		}

		return $templateFolderRelations;
	}
}