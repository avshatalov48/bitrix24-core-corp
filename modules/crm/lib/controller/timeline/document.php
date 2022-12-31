<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Timeline\TimelineType;

class Document extends \Bitrix\Crm\Controller\Base
{
	public function deleteAction(int $id, int $ownerTypeId, int $ownerId): ?bool
	{
		if ($id <= 0)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		$resultBind = \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getList(
			[
				'filter' => ['=OWNER_ID' => $id],
			]
		);

		$isExistBinding = false;
		$bindings = [];
		while ($bindData = $resultBind->fetch())
		{
			if ((int)$bindData['ENTITY_TYPE_ID'] === $ownerTypeId && (int)$bindData['ENTITY_ID'] === $ownerId)
			{
				$isExistBinding = true;
			}
			$bindings[] = $bindData;
		}

		if (!$isExistBinding)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if (!\Bitrix\Crm\Security\EntityAuthorization::checkUpdatePermission($ownerTypeId, $ownerId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}
		$entry = \Bitrix\Crm\Timeline\DocumentEntry::getByID($id);
		if ($entry['TYPE_ID'] != TimelineType::DOCUMENT)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		if (count($bindings) > 1)
		{
			\Bitrix\Crm\Timeline\Entity\TimelineBindingTable::delete([
				'OWNER_ID' => $id,
				'ENTITY_ID' => $ownerId,
				'ENTITY_TYPE_ID' => $ownerTypeId,
			]);
		}
		else
		{
			$result = new \Bitrix\Main\Result();
			if (\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled())
			{
				if (is_array($entry) && isset($entry['SETTINGS']) && isset($entry['SETTINGS']['DOCUMENT_ID']))
				{
					$documentId = $entry['SETTINGS']['DOCUMENT_ID'];
					if (\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyDocument($documentId))
					{
						$result = \Bitrix\DocumentGenerator\Model\DocumentTable::delete($entry['SETTINGS']['DOCUMENT_ID']);
					}
					else
					{
						$this->addError(ErrorCode::getAccessDeniedError());
					}
				}
			}
			if ($result->isSuccess())
			{
				\Bitrix\Crm\Timeline\DocumentEntry::delete($id);
				\Bitrix\Crm\Timeline\DocumentController::getInstance()->onDelete(
					$id, [
						'ENTITY_TYPE_ID' => $ownerTypeId,
						'ENTITY_ID' => $ownerId,
					]
				);

				return true;
			}
			else
			{
				$this->addErrors($result->getErrors());
			}
		}

		return null;
	}
}
