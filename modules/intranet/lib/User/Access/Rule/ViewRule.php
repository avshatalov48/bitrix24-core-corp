<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access\Rule;

use Bitrix\Intranet\Internal\Integration\Socialnetwork\UserPermissions;
use Bitrix\Intranet\User\Access\Model\TargetUserModel;
use Bitrix\Intranet\User\Access\Model\UserModel;
use Bitrix\Intranet\User\Access\Trait\SelfRuleTrait;
use Bitrix\Intranet\User\Access\Trait\ValidationTrait;
use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\Rule\AbstractRule;

class ViewRule extends AbstractRule
{
	use ValidationTrait;
	use SelfRuleTrait;

	/* @var UserModel $user */
	protected $user;

	/**
	 * @param TargetUserModel|null $item
	 */
	public function execute(AccessibleItem $item = null, $params = null): bool
	{
		if (
			!(isset($item) && $this->checkModel($item))
		)
		{
			return false;
		}

		if ($this->isSelfAction($item))
		{
			return true;
		}

		return (new UserPermissions())->canUserViewUserProfile($this->user->getUserEntity(), $item->getUserEntity());
	}
}
