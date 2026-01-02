<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Model;

use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\Enum\InvitationStatus;
use Bitrix\Intranet\Service\ServiceContainer;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Exception\AccessException;

final class TargetUserModel implements AccessibleItem
{
	private static array $cachedTargetUserModel = [];

	private function __construct(
		private User $user,
	)
	{}

	public static function createFromId(int $itemId): TargetUserModel
	{
		if (isset(self::$cachedTargetUserModel[$itemId]))
		{
			return self::$cachedTargetUserModel[$itemId];
		}

		$userRepository = ServiceContainer::getInstance()->userRepository();
		$model = new TargetUserModel(
			$userRepository->getUserById($itemId)
		);
		self::$cachedTargetUserModel[$itemId] = $model;

		return $model;
	}

	public static function createFromArray(array $user): TargetUserModel
	{
		if (!isset($user['ID']))
		{
			throw new AccessException('user id is not set');
		}

		if (isset(self::$cachedTargetUserModel[$user['ID']]))
		{
			return self::$cachedTargetUserModel[$user['ID']];
		}

		$model = new TargetUserModel(
			User::initByArray($user)
		);
		self::$cachedTargetUserModel[$user['ID']] = $model;

		return $model;
	}

	public static function createFromUserEntity(User $user): TargetUserModel
	{
		if (!$user->getId())
		{
			throw new AccessException('user id is not set');
		}

		if (isset(self::$cachedTargetUserModel[$user->getId()]))
		{
			return self::$cachedTargetUserModel[$user->getId()];
		}

		$model = new TargetUserModel($user);
		self::$cachedTargetUserModel[$user->getId()] = $model;

		return $model;
	}

	public function getId(): int
	{
		return $this->user->getId() ?? 0;
	}

	public function isIntegrator(): bool
	{
		return $this->user->isIntegrator() ?? false;
	}

	public function isAdmin(): bool
	{
		return $this->user->isAdmin() ?? false;
	}

	public function getInviteStatus(): ?InvitationStatus
	{
		return $this->user->getInviteStatus();
	}

	public function getUserEntity(): User
	{
		return $this->user;
	}
}
