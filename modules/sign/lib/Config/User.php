<?php

namespace Bitrix\Sign\Config;

use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;

final class User
{
	private static ?self $instance = null;

	public static function instance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function canUserParticipateInSigning(int $userId): bool
	{
		if (!Main\Loader::includeModule('intranet'))
		{
			return false;
		}

		return \Bitrix\Intranet\Util::isIntranetUser($userId);
	}


	public function isB2bCreateDocumentAvailableForCurrentUser(): bool
	{
		$userId = (int)CurrentUser::get()->getId();

		return $this->isB2bCreateDocumentAvailableByUserId($userId);
	}

	public function isB2bCreateDocumentAvailableByUserId(int $userId): bool
	{
		if (!Feature::instance()->isCollabIntegrationEnabled())
		{
			return false;
		}

		if ($userId < 1)
		{
			return false;
		}

		$accessController = new AccessController($userId);
		$permission = ActionDictionary::ACTION_DOCUMENT_ADD;

		try
		{
			return $accessController->check($permission);
		}
		catch (\Throwable)
		{
		}

		return false;
	}
}
