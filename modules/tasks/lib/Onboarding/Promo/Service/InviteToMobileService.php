<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Promo\Service;

use Bitrix\Main\Loader;
use Bitrix\Mobile\Deeplink;
use Bitrix\Tasks\Onboarding\Command\Trait\ContainerTrait;
use Bitrix\Tasks\Onboarding\Promo\Repository\InviteToMobileRepositoryInterface;

class InviteToMobileService
{
	use ContainerTrait;

	private const PRESET_TASK = 'preset_task';

	private InviteToMobileRepositoryInterface $repository;

	public function __construct()
	{
		$this->repository = $this->getContainer()->getInviteToMobileRepository();
	}

	public function getInviteLink(int $userId): string
	{
		if (!Loader::includeModule('mobile'))
		{
			return '';
		}

		return Deeplink::getAuthLink(self::PRESET_TASK, $userId);
	}

	public function needToShow(int $userId): bool
	{
		return $this->repository->needToShow($userId);
	}

	public function setNeedToShow(int $userId): void
	{
		$this->repository->setNeedToShow($userId);
	}

	public function setShown(int $userId): void
	{
		$this->repository->setShown($userId);
	}
}
