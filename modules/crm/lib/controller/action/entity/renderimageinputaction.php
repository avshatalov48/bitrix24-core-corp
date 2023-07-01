<?php

namespace Bitrix\Crm\Controller\Action\Entity;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Security\PermissionToken;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\Response\Component;

class RenderImageInputAction extends \Bitrix\Main\Engine\Action
{
	protected $requisites = [];

	public function run(string $fieldName, string $entityTypeName, int $entityId = 0, string $fieldValue = '', array $context = []): ?Component
	{
		$entityTypeId = \CCrmOwnerType::ResolveId($entityTypeName);
		$fileControlId = mb_strtolower($fieldName) . '_uploader';
		$isRequisiteEntity = ($entityTypeId === \CCrmOwnerType::Requisite);

		if ($isRequisiteEntity)
		{
			$hasAccess = $this->checkRequisiteAccess($entityId, $context);
		}
		else
		{
			$userPermissions = Container::getInstance()->getUserPermissions();
			$hasAccess =
				$entityId > 0
					? $userPermissions->checkUpdatePermissions($entityTypeId, $entityId)
					: $userPermissions->checkAddPermissions($entityTypeId);
		}

		if (!$hasAccess)
		{
			Container::getInstance()->getLocalization()->loadMessages();
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());
			return null;
		}

		$value = 0;

		if ($fieldValue !== '')
		{
			$allowedFileIds = \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles($fileControlId, [$fieldValue]);
			if (in_array($fieldValue, $allowedFileIds, false))
			{
				$value = $fieldValue;
			}
		}
		if (!$value && $entityId > 0)
		{
			if ($isRequisiteEntity)
			{
				$value = $this->getRequisite($entityId)[$fieldName] ?? 0;
			}
			else
			{
				$factory = Container::getInstance()->getFactory($entityTypeId);
				if ($factory)
				{
					$item = $factory->getItem($entityId);
					if ($item && $item->hasField($fieldName))
					{
						$value = $item->get($fieldName);
					}
				}
			}
		}

		return new Component(
			'bitrix:main.file.input',
			'',
			[
				'MODULE_ID' => 'crm',
				'MAX_FILE_SIZE' => 3145728,
				'MULTIPLE' => 'N',
				'ALLOW_UPLOAD' => 'I',
				'SHOW_AVATAR_EDITOR' => 'Y',
				'CONTROL_ID' => $fileControlId,
				'INPUT_NAME' => $fieldName,
				'INPUT_VALUE' => (int)$value,
			]
		);
	}

	protected function checkRequisiteAccess(int $entityId, array $context = []): bool
	{
		if ($entityId > 0)
		{
			$requisite = $this->getRequisite($entityId);
			if (!$requisite)
			{
				return false;
			}

			$ownerEntityTypeId = (int)$requisite['ENTITY_TYPE_ID'];
			$ownerEntityId = (int)$requisite['ENTITY_ID'];

			return \Bitrix\Crm\EntityRequisite::checkReadPermissionOwnerEntity($ownerEntityTypeId, $ownerEntityId);
		}

		$ownerEntityTypeId = (int)($context['ownerEntityTypeId'] ?? 0);
		$ownerEntityId = (int)($context['ownerEntityId'] ?? 0);
		$ownerCategoryId = (int)($context['ownerEntityCategoryId'] ?? 0);

		$canReadOwnerEntity = \Bitrix\Crm\EntityRequisite::checkReadPermissionOwnerEntity($ownerEntityTypeId, $ownerEntityId, $ownerCategoryId);
		if ($canReadOwnerEntity)
		{
			return true;
		}

		return PermissionToken::canEditRequisites($context['permissionToken'] ?? '', $ownerEntityTypeId, $ownerEntityId);
	}

	protected function getRequisite(int $requisiteId): ?array
	{
		if (!array_key_exists($requisiteId, $this->requisites))
		{
			$this->requisites[$requisiteId] = EntityRequisite::getSingleInstance()->getById($requisiteId);
		}

		return $this->requisites[$requisiteId];
	}
}
