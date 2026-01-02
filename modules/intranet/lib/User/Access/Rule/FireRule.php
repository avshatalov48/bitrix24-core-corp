<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Rule;

use Bitrix\Intranet\Integration\HumanResources\Permissions as HRPermissions;
use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Intranet\User\Access\Model\UserModel;
use Bitrix\Intranet\User\Access\Trait\SelfRuleTrait;
use Bitrix\Intranet\User\Access\Trait\ValidationTrait;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;

class FireRule extends AbstractRule
{
	use ValidationTrait;
	use SelfRuleTrait;

	/* @var UserModel $user */
	protected $user;

	/**
	 * @param TargetUserModel|null $item
	 * @param $params
	 * @return bool
	 */
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (isset($item))
		{
			if (!$this->checkModel($item))
			{
				return false;
			}

			if ($this->isSelfAction($item))
			{
				return false;
			}

			if (!$this->checkAdminTargetUser($item))
			{
				return false;
			}
		}

		if ($this->user->isAdmin())
		{
			return true;
		}

		return (new HRPermissions($this->user))
			->canFireUser($item);
	}

	private function checkAdminTargetUser(TargetUserModel $item): bool
	{
		if (!$item->isAdmin() || $item->isIntegrator())
		{
			return true;
		}

		if ($this->user->isIntegrator())
		{
			return false;
		}

		if ($item->isAdmin() && !$this->user->isAdmin())
		{
			return false;
		}

		return true;
	}
}
