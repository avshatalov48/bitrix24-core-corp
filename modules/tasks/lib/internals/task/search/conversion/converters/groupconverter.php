<?php

namespace Bitrix\Tasks\Internals\Task\Search\Conversion\Converters;

use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Task\Search\Conversion\AbstractConverter;

class GroupConverter extends AbstractConverter
{
	public function convert(): string
	{
		$groupId = $this->getFieldValue();
		$groups = Group::getData([$groupId]);

		return $groups[$groupId]['NAME'] ?? '';
	}

	public static function getFieldName(): string
	{
		return 'GROUP_ID';
	}
}