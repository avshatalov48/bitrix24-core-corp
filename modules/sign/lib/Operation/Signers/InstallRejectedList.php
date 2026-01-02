<?php

namespace Bitrix\Sign\Operation\Signers;

use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Sign\Config;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Service\SignersListService;
use Bitrix\Sign\Service\Container;

final class InstallRejectedList implements Contract\Operation
{
	private readonly SignersListService $signersListService;
	private readonly Config\Storage $config;

	public function __construct(
		private readonly ?int $createdByUserId = null,
		?SignersListService $signersListService = null,
		?Config\Storage $config = null,
	)
	{
		$this->config = $config ?? Config\Storage::instance();
		$this->signersListService = $signersListService ?? Container::instance()->getSignersListService();
	}

	public function launch(): Main\Result
	{
		$currentRejectedListId = $this->config->getSignersListRejectedId();

		if ($currentRejectedListId !== null)
		{
			return new Main\Result();
		}

		return $this->signersListService->installRejectedList(
			$this->getCreatedByUserId(),
			Main\Localization\Loc::getMessage('SIGN_B2E_SIGNERS_LIST_REJECTED_LIST_TITLE'),
		);
	}

	private function getCreatedByUserId(): int
	{
		return $this->createdByUserId ?? self::getAdminUserId();
	}

	private static function getAdminUserId(): int
	{
		$admins = [];
		if (Main\Loader::includeModule('intranet'))
		{
			$admins = Intranet\Service\ServiceContainer::getInstance()->getUserService()->getAdminUserIds();
		}

		return !empty($admins) ? (int)reset($admins) : 1;
	}
}
