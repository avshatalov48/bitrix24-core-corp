<?php

namespace Bitrix\BIConnector\UserField\Bypass;

use Bitrix\Iblock\UserField\Types\SectionType;
use Bitrix\Main\UserField\Types\BaseType;

final class IBlockSection extends IBlock
{

	protected function getEmptyCaption(array $userField): string
	{
		return SectionType::getEmptyCaption($userField);
	}

	protected function getEnumList(array &$userField, array $value): void
	{
		SectionType::getEnumList(
			$userField,
			[
				'mode' => BaseType::MODE_VIEW,
				'VALUE' => $value,
				'SKIP_CHECK_PERMISSIONS' => true,
			]
		);
	}
}
