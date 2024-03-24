<?php

namespace Bitrix\Tasks\Internals\Notification\UseCase;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Notification\EntityCode;
use Bitrix\Tasks\Internals\Notification\EntityOperation;
use Bitrix\Tasks\Internals\Notification\Metadata;

class TaskExpiresSoon extends AbstractCase
{
	use ByRoleTrait;

	private function getMetadata(array $params = []): Metadata
	{
		return new Metadata(
			EntityCode::CODE_TASK,
			EntityOperation::EXPIRES_SOON,
			$params
		);
	}

	private function getSupportedRoles(): array
	{
		return [RoleDictionary::ROLE_RESPONSIBLE, RoleDictionary::ROLE_ACCOMPLICE];
	}
}