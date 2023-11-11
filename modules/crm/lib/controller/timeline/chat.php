<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Im;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class Chat extends Base
{
	public function getAction(int $entityId, int $entityTypeId) : ?array
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			$this->addError(new Error('im module not installed'));

			return null;
		}

		if (!CCrmOwnerType::IsDefined($entityTypeId) || $entityId < 0)
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError());

			return null;
		}

		$userPermissions = Container::getInstance()->getUserPermissions();
		if (!$userPermissions->checkReadPermissions($entityTypeId, $entityId))
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$userId = Container::getInstance()->getContext()->getUserId();
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);

		$chatID = Im\Chat::joinChat([
			'ENTITY_TYPE' => $entityTypeName,
			'ENTITY_ID' => $entityId,
			'USER_ID' => $userId,
		]);

		if ($chatID === false)
		{
			$this->addError(new Error(Loc::getMessage('CRM_CONTROLLER_TIMELINE_CHAT_ERROR_AT_JOIN_CHAT')));

			return null;
		}

		return ['chatId' => $chatID];
	}
}
