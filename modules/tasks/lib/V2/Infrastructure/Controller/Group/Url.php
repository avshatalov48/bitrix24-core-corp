<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Group;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Url\UrlManager;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Access\Group\Permission;
use Bitrix\Tasks\V2\Internal\Entity;

class Url extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Group.Url.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read]
		Entity\Group $group,
	): string
	{
		return UrlManager::getUrlByType($group->id, $group->type);
	}
}