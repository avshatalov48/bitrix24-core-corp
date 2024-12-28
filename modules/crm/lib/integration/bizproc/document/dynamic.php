<?php

namespace Bitrix\Crm\Integration\BizProc\Document;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

if(!Loader::includeModule('bizproc'))
{
	return;
}

class Dynamic extends Item
{
	public static function getEntityName($entity)
	{
		return Loc::getMessage('CRM_BP_DOCUMENT_DYNAMIC_ENTITY_NAME');
	}

	protected static function GetDocumentInfo($documentId)
	{
		$documentIdParts = explode('_', $documentId);
		$typePrefix = $documentIdParts[0] ?? null;
		$entityTypeId = $documentIdParts[1] ?? null;
		$entityId = $documentIdParts[2] ?? null;

		if (is_null($entityTypeId))
		{
			return false;
		}

		return [
			'TYPE' => $typePrefix . '_' . $entityTypeId,
			'TYPE_ID' => (int)$entityTypeId,
			'ID' => (int)$entityId,
			'DOCUMENT_TYPE' => ['crm', static::class, $typePrefix . '_' . $entityTypeId],
		];
	}

	public static function getDocumentCategories($documentType)
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($documentType);
		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		$categories = null;

		if ($factory && $factory?->isCategoriesSupported() && $factory?->isCategoriesEnabled())
		{
			$categories = [];
			foreach ($factory->getCategories() as $category)
			{
				$categoryId = $category->getId();
				$categories[$categoryId] = [
					'id' => $categoryId,
					'name' => $category->getName(),
				];
			}
		}

		return $categories;
	}

	public static function getDocumentCategoryId(string $documentId): ?int
	{
		$documentInfo = self::GetDocumentInfo($documentId);
		$entityTypeId = \CCrmOwnerType::ResolveID($documentInfo['TYPE']);
		$factory = Crm\Service\Container::getInstance()->getFactory($entityTypeId);

		if ($factory && $factory?->isCategoriesSupported() && $factory?->isCategoriesEnabled())
		{
			$entity = $factory->getItem($documentInfo['ID'], ['CATEGORY_ID']);
			if ($entity)
			{
				return $entity->getCategoryId();
			}
		}

		return null;
	}
}
