<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Activity\Provider\Sms\PlaceholderContext;
use Bitrix\Crm\Activity\Provider\Sms\PlaceholderManager;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class SmsPlaceholder extends Base
{
	public function createOrUpdatePlaceholderAction(
		int $templateId,
		string $placeholderId,
		int $entityTypeId,
		?string $fieldName = null,
		?string $fieldValue = null,
		string $entityType = null,
		?int $entityCategoryId = null
	): Result
	{
		$result = new Result();

		if ($templateId <= 0)
		{
			$this->addError(new Error('TemplateId must be greater than zero'));
		}

		if ($placeholderId === '')
		{
			$this->addError(new Error('PlaceholderId must be not empty string'));
		}

		if ($fieldName === null && $fieldValue === null)
		{
			$this->addError(new Error('FieldName or fieldValue must be filled'));
		}

		if ($entityType === '' && $fieldValue === '')
		{
			$this->addError(new Error('EntityType must be not empty string'));
		}

		if (!empty($this->getErrors()))
		{
			return $result;
		}

		if (!\CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return $result;
		}

		if (!$this->checkPermissions($entityTypeId, $entityCategoryId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return $result;
		}

		$context = PlaceholderContext::createInstance($entityTypeId, $entityCategoryId);

		return (new PlaceholderManager())->createOrUpdate(
			$templateId,
			$placeholderId,
			$fieldName,
			$fieldValue,
			$entityType,
			$context
		);
	}

	public function deletePlaceholderAction(
		int $templateId,
		string $placeholderId,
		int $entityTypeId,
		?int $entityCategoryId = null
	): Result
	{
		$result = new Result();

		if (!\CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return $result;
		}

		if (!$this->checkPermissions($entityTypeId, $entityCategoryId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return $result;
		}

		$context = PlaceholderContext::createInstance($entityTypeId, $entityCategoryId);

		return (new PlaceholderManager())->delete($templateId, $placeholderId, $context);
	}

	private function checkPermissions(int $entityTypeId, ?int $entityCategoryId = null): bool
	{
		$userPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()?->getId());
		if ($userPermissions->checkUpdatePermissions($entityTypeId, 0, $entityCategoryId))
		{
			return true;
		}

		return false;
	}
}
