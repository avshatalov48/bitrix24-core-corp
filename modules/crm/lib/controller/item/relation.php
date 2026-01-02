<?php

namespace Bitrix\Crm\Controller\Item;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;

class Relation extends Base
{
	public function updateAction(
		int $parentEntityTypeId,
		int $parentEntityId,
		int $childEntityTypeId,
		array $selectedIds,
	): ?array
	{
		$parentFactory = Container::getInstance()->getFactory($parentEntityTypeId);
		if (!$parentFactory)
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_RELATION_PARENT_ENTITY_TYPE_NOT_FOUND', [
						'#ENTITY_TYPE_ID#' => $parentEntityTypeId,
					]),
					ErrorCode::NOT_FOUND)
			);

			return null;
		}

		$parentItem = $parentFactory->getItem($parentEntityId, ['ID']);
		if (!$parentItem)
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_RELATION_PARENT_ENTITY_NOT_FOUND', [
						'#ENTITY_TYPE_ID#' => $parentEntityTypeId,
						'#ENTITY_ID#' => $parentEntityId,
					]),
					ErrorCode::NOT_FOUND)
			);

			return null;
		}

		$childFactory = Container::getInstance()->getFactory($childEntityTypeId);
		if (!$childFactory)
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_RELATION_CHILD_ENTITY_TYPE_NOT_FOUND', [
						'#ENTITY_TYPE_ID#' => $childEntityTypeId,
					]),
					ErrorCode::NOT_FOUND)
			);

			return null;
		}

		$data = [
			'PARENT_TYPE_ID' => $parentEntityTypeId,
			'PARENT_ID' => $parentEntityId,
		];

		if (!EditorAdapter::fillParentFieldFromContextEnrichedData($data))
		{
			$this->addError(new Error(
					Loc::getMessage('CRM_RELATION_PARENT_ENTITY_NOT_FOUND', [
						'#ENTITY_TYPE_ID#' => $parentEntityTypeId,
						'#ENTITY_ID#' => $parentEntityId,
					]),
					ErrorCode::NOT_FOUND)
			);

			return null;
		}

		$response = [];
		foreach ($selectedIds as $childEntityId)
		{
			try
			{
				$item = $childFactory->getItem($childEntityId);
			}
			catch (\Throwable $e)
			{
				$this->addError(new Error(
					$e->getMessage(),
					ErrorCode::INVALID_ARG_VALUE
				));

				break;
			}

			if (!$item)
			{
				$this->addError(new Error(
					Loc::getMessage('CRM_RELATION_CHILD_ENTITY_NOT_FOUND', [
						'#ENTITY_TYPE_ID#' => \CCrmOwnerType::ResolveName($childEntityTypeId) ?? (string)$childEntityTypeId,
						'#ENTITY_ID#' => $childEntityId,
					]),
					ErrorCode::NOT_FOUND
				));

				continue;
			}

			$entityTitle = \CCrmOwnerType::Contact === $item->getEntityTypeId() ? $item->getFullName() : $item->getTitle();

			if ($this->alreadyBound($item, $parentEntityTypeId))
			{
				$this->addError(new Error(
						Loc::getMessage('CRM_RELATION_BINDING_ALREADY_EXISTS', [
							'#ENTITY_TYPE_NAME#' => \CCrmOwnerType::ResolveName($item->getEntityTypeId()),
							'#ENTITY_TITLE#' => $entityTitle,
						]),
						ErrorCode::INVALID_ARG_VALUE)
				);

				continue;
			}

			$item->setFromCompatibleData($data);
			$updateResult = $childFactory->getUpdateOperation($item)->launch();

			if (!$updateResult->isSuccess())
			{
				$this->addErrors($updateResult->getErrorCollection()->getValues());
			}

			$response['success'][] = Loc::getMessage('CRM_RELATION_CHILD_ENTITY_LINKED',
				[
					'#ENTITY_TYPE_NAME#' => \CCrmOwnerType::ResolveName($item->getEntityTypeId()),
					'#ENTITY_TITLE#' => $entityTitle,
				]
			);
		}

		return $response;
	}

	private function alreadyBound(Item $item, int $parentEntityTypeId): bool
	{
		$relationManager = Container::getInstance()->getRelationManager();
		$itemIdentifier = new ItemIdentifier($item->getEntityTypeId(), $item->getId());
		$parents = $relationManager->getParentElements($itemIdentifier);

		foreach ($parents as $parentIdentifier)
		{
			if ($parentIdentifier->getEntityTypeId() === $parentEntityTypeId)
			{
				return true;
			}
		}

		return false;
	}
}