<?php

namespace Bitrix\BIConnector\UserField\Bypass;

use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\Collection;

abstract class IBlock implements Bypass
{
	abstract protected function getEmptyCaption(array $userField): string;
	abstract protected function getEnumList(array &$userField, array $value): void;

	public function getText(array $userField): string
	{
		if (!Loader::includeModule('iblock'))
		{
			return '';
		}

		$value = self::getValueFromUserField($userField);
		Collection::normalizeArrayValuesByInt($value, false);
		if (!empty($value))
		{
			$this->getEnumList($userField, $value);
			$result = $userField['USER_TYPE']['FIELDS'] ?? [];
			return
				!empty($result)
					? HtmlFilter::encode(implode(', ', $result))
					: $this->getEmptyCaption($userField)
				;
		}

		return $this->getEmptyCaption($userField);
	}

	private static function getValueFromUserField(array $userField): array
	{
		if (isset($userField['ENTITY_VALUE_ID']) && $userField['ENTITY_VALUE_ID'] <= 0)
		{
			$value = $userField['SETTINGS']['DEFAULT_VALUE'] ?? [];
		}
		else
		{
			$value = $userField['VALUE'] ?? [];
		}

		if(!is_array($value))
		{
			$value = [$value];
		}
		if(empty($value))
		{
			$value = [null];
		}

		return $value;
	}
}
