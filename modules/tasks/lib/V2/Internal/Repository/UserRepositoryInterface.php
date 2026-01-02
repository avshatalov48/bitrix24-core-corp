<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;
use Bitrix\Tasks\V2\Internal\Entity;

interface UserRepositoryInterface
{
	public function getByIds(array $userIds): Entity\UserCollection;
	public function getAdmins(): Entity\UserCollection;
}