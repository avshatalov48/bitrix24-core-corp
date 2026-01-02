<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

interface TaskMemberRepositoryInterface
{
	public function get(int $taskId): Entity\UserCollection;

	public function getCreator(int $taskId): ?Entity\User;

	public function getResponsible(int $taskId): ?Entity\User;

	public function getAccomplices(int $taskId): Entity\UserCollection;

	public function getAuditors(int $taskId): Entity\UserCollection;
}