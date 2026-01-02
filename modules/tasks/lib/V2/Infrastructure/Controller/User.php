<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Enum\Type;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Public\Provider\Params\UserParams;
use Bitrix\Tasks\V2\Public\Provider\UserProvider;

class User extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.User.list
	 */
	#[CloseSession]
	public function listAction(
		#[NotEmpty]
		#[ElementsType(typeEnum: Type::Numeric)]
		array $ids,
		UserProvider $userProvider,
	): Entity\UserCollection
	{
		return $userProvider->getByIds(
			new UserParams(userId: $this->userId, targetUserIds: $ids)
		);
	}
}
