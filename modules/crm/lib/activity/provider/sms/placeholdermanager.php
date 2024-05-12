<?php

namespace Bitrix\Crm\Activity\Provider\Sms;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class PlaceholderManager
{
	public function getPlaceholders(array $templateIds, PlaceholderContext $context): array
	{
		if (empty($templateIds))
		{
			return [];
		}

		$placeholders = SmsPlaceholderTable::getList([
			'select' => [
				'ENTITY_TYPE_ID',
				'ENTITY_CATEGORY_ID',
				'TEMPLATE_ID',
				'PLACEHOLDER_ID',
				'FIELD_NAME',
				'FIELD_ENTITY_TYPE',
				'FIELD_VALUE',
			],
			'filter' => [
				'ENTITY_TYPE_ID' => $context->getEntityTypeId(),
				'ENTITY_CATEGORY_ID' => $context->getEntityCategoryId(),
				'@TEMPLATE_ID' => $templateIds,
			],
		])->fetchAll();

		$this->preparePlaceholders($placeholders, $context);

		return $placeholders;
	}

	private function preparePlaceholders(array &$placeholders, PlaceholderContext $context): void
	{
		if (empty($placeholders) || !Loader::includeModule('documentgenerator'))
		{
			return;
		}

		$documentGeneratorManager = DocumentGeneratorManager::getInstance();
		$providerClass = $documentGeneratorManager->getCrmOwnerTypeProvider($context->getEntityTypeId(), false);
		$providerPlaceholders = DataProviderManager::getInstance()->getProviderPlaceholders($providerClass);

		$entityTypeDescription = \CCrmOwnerType::GetDescription($context->getEntityTypeId());
		foreach ($placeholders as &$placeholder)
		{
			if (isset($providerPlaceholders[$placeholder['FIELD_NAME']]))
			{
				$title = $providerPlaceholders[$placeholder['FIELD_NAME']]['TITLE'];
				$parentTitle = $providerPlaceholders[$placeholder['FIELD_NAME']]['GROUP'][0] ?? '';

				$placeholder['TITLE'] = $title;
				$placeholder['PARENT_TITLE'] = ($parentTitle === $title ? $entityTypeDescription : $parentTitle);
			}
		}
		unset($placeholder);
	}

	public function createOrUpdate(
		int $templateId,
		string $placeholderId,
		?string $fieldName,
		?string $fieldValue,
		?string $entityType,
		PlaceholderContext $context
	): Result
	{
		$item = $this->getPlaceholderItem($templateId, $placeholderId, $context);

		if ($item === null)
		{
			return SmsPlaceholderTable::add([
				'ENTITY_TYPE_ID' => $context->getEntityTypeId(),
				'ENTITY_CATEGORY_ID' => $context->getEntityCategoryId(),
				'TEMPLATE_ID' => $templateId,
				'PLACEHOLDER_ID' => $placeholderId,
				'FIELD_NAME' => $fieldName,
				'FIELD_ENTITY_TYPE' => $entityType,
				'FIELD_VALUE' => $fieldValue
			]);
		}

		if (
			$item['FIELD_NAME'] !== $fieldName
			|| $item['FIELD_VALUE'] !== $fieldValue
		)
		{
			return SmsPlaceholderTable::update(
				$item['ID'],
				[
					'FIELD_NAME' => $fieldName,
					'FIELD_ENTITY_TYPE' => $entityType,
					'FIELD_VALUE' => $fieldValue
				]
			);
		}

		return (new Result())->addError(ErrorCode::getNotFoundError());
	}

	public function delete(int $templateId, string $placeholderId, PlaceholderContext $context): Result
	{
		$item = $this->getPlaceholderItem($templateId, $placeholderId, $context);

		if (empty($item))
		{
			return new Result();
		}

		return SmsPlaceholderTable::delete($item['ID']);
	}

	private function getPlaceholderItem(
		int $templateId,
		string $placeholderId,
		PlaceholderContext $context,
	): ?array
	{
		return SmsPlaceholderTable::getRow([
			'select' => [
				'ID',
				'FIELD_NAME',
				'FIELD_ENTITY_TYPE',
				'FIELD_VALUE',
			],
			'filter' => [
				'=ENTITY_TYPE_ID' => $context->getEntityTypeId(),
				'=ENTITY_CATEGORY_ID' => $context->getEntityCategoryId(),
				'=TEMPLATE_ID' => $templateId,
				'=PLACEHOLDER_ID' => $placeholderId,
			],
		]);
	}
}
