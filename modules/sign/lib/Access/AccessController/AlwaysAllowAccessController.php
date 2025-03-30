<?php

namespace Bitrix\Sign\Access\AccessController;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\Model\UserModelRepository;

final class AlwaysAllowAccessController extends AccessController
{
	public function __construct($userId = 1, ?UserModelRepository $userModelRepository = null)
	{
		parent::__construct($userId, $userModelRepository);
	}

	public function check(string $action, ?AccessibleItem $item = null, $params = null): bool
	{
		return true;
	}
}