<?php

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Disk\Uf;
use Bitrix\Main\Loader;

class QuoteConnector extends Uf\StubConnector
{
	public function __construct($entityId)
	{
		Loader::requireModule('disk');

		parent::__construct($entityId);
	}

	public function canRead($userId): bool
	{
		$userPermissions = $this->getUserPermissions($userId);

		return $userPermissions->checkReadPermissions(\CCrmOwnerType::Quote, $this->entityId);
	}

	public function canUpdate($userId): bool
	{
		$userPermissions = $this->getUserPermissions($userId);

		return $userPermissions->checkUpdatePermissions(\CCrmOwnerType::Quote, $this->entityId);
	}

	public function canConfidenceReadInOperableEntity(): bool
	{
		return true;
	}

	public function canConfidenceUpdateInOperableEntity(): bool
	{
		return true;
	}

	private function getUserPermissions(int $userId): UserPermissions
	{
		return Container::getInstance()->getUserPermissions($userId);
	}
}