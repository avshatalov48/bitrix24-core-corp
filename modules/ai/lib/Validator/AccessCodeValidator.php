<?php declare(strict_types=1);

namespace Bitrix\AI\Validator;

use Bitrix\AI\Exception\ValidateException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\EntitySelector\Converter;

class AccessCodeValidator
{
	/**
	 * @throws ValidateException
	 */
	public function checkAccessCodes(array $list, string $fieldName): void
	{
		if (empty($list))
		{
			return;
		}

		$hasInList = false;
		foreach ($list as $item)
		{
			if (empty($item[0]) || !isset($item[1]))
			{
				throw new ValidateException(
					$fieldName,
					Loc::getMessage('AI_VALIDATOR_VALUES_SHOULD_BE_ARRAY')
				);
			}

			if (!$hasInList && in_array($item[0], $this->getItems(), true))
			{
				$hasInList = true;
			}
		}

		if (!$hasInList)
		{
			throw new ValidateException(
				$fieldName, Loc::getMessage('AI_VALIDATOR_NOT_MAP_CODE')
			);
		}
	}

	protected function getItems()
	{
		static $items;
		if (empty($items))
		{
			$items = array_unique(
				array_merge(
					Converter::$sampleSortPriority,
					array_keys(Converter::getCompatEntities())
				)
			);
		}

		return $items;
	}
}
