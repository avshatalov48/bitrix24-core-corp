<?php

namespace Bitrix\BIConnector\UserField\Bypass;

use Bitrix\Iblock\UserField\Types\ElementType;
use Bitrix\Main\UserField\Types\BaseType;

final class IBlockElement extends IBlock
{

	protected function getEmptyCaption(array $userField): string
	{
		return ElementType::getEmptyCaption($userField);
	}

	protected function getEnumList(array &$userField, array $value): void
	{
		ElementType::getEnumList(
			$userField,
			[
				'mode' => BaseType::MODE_VIEW,
				'VALUE' => $value,
				'SKIP_CHECK_PERMISSIONS' => true,
			]
		);
	}
}
