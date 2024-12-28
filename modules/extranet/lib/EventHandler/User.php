<?php

namespace Bitrix\Extranet\EventHandler;

use Bitrix\Extranet\Enum\User\ExtranetRole;
use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Control\Event\BeforeCollabUpdateEvent;
use Bitrix\HumanResources;

class User
{
	public static function onAfterUserAdd($fields): void
	{
		if (
			!empty($fields['ID'])
			&& Loader::includeModule('humanresources')
			&& !HumanResources\Service\Container::getUserService()->isEmployee((int)$fields['ID'])
		)
		{
			$userId = (int)$fields['ID'];
			$userService = ServiceContainer::getInstance()->getUserService();
			$userService->setRoleById($userId, ExtranetRole::Extranet);

			if (Loader::includeModule('socialnetwork'))
			{
				EventManager::getInstance()->addEventHandler(
					'socialnetwork',
					'OnBeforeCollabUpdate',
					static function (BeforeCollabUpdateEvent $event) use ($userService, $userId): void
					{
						$invitedMembers = $event->getCommand()->getAddInvitedMembers() ?? [];
						$addMembers = $event->getCommand()->getAddMembers() ?? [];
						$allMembers = array_merge($invitedMembers, $addMembers);

						if ($allMembers && in_array('U' . $userId, $allMembers, true))
						{
							$userService->setRoleById($userId, ExtranetRole::Collaber);
						}
					},
				);
			}
		}
	}

	public static function onAfterUserUpdate($fields): void
	{
		if (
			!empty($fields['ID'])
			&& Loader::includeModule('humanresources')
			&& HumanResources\Service\Container::getUserService()->isEmployee((int)$fields['ID'])
		)
		{
			$serviceContainer = ServiceContainer::getInstance();
			$userService = $serviceContainer->getUserService();
			$collaberService = $serviceContainer->getCollaberService();

			if ($userService->isCurrentExtranetUserById((int)$fields['ID']))
			{
				if ($collaberService->isCollaberById((int)$fields['ID']))
				{
					$collaberService->removeCollaberRoleByUserId((int)$fields['ID']);
				}
				else
				{
					$userService->setRoleById((int)$fields['ID'], ExtranetRole::FormerExtranet);
				}
			}
		}
	}

	public static function onAfterUserDelete($id): void
	{
		$userService = ServiceContainer::getInstance()->getUserService();
		$id = (int)$id;

		if ($userService->isCurrentExtranetUserById($id) || $userService->isFormerExtranetUserById($id))
		{
			$userService->deleteById($id);
		}
	}

	public static function OnAfterTransferEmailUser($arFields): void
	{
		self::onAfterUserAdd($arFields);
	}
}
