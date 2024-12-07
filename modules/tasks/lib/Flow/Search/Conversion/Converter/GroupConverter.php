<?php

namespace Bitrix\Tasks\Flow\Search\Conversion\Converter;

use Bitrix\Tasks\Flow\Search\Conversion\AbstractConverter;
use Bitrix\Tasks\Integration\SocialNetwork\Group;

class GroupConverter extends AbstractConverter
{
	public function convert()
	{
		$groupId = $this->getFieldValue();
		$group = Group::getData([$groupId]);

		return !empty($group[$groupId]) ? $group[$groupId]['NAME'] : '';
	}

	public static function getFieldName(): string
	{
		return 'groupId';
	}
}