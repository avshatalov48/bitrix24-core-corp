<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

class SmartInvoice extends Dynamic
{
	public static function getEntityTypeId(): int
	{
		return \CCrmOwnerType::SmartInvoice;
	}

	public static function getLangName(): string
	{
		return \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice);
	}

	public static function getExtendedList(): array
	{
		$result = [];

		$type = Container::getInstance()->getTypeByEntityTypeId(\CCrmOwnerType::SmartInvoice);
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
		if (!$type || !$factory)
		{
			return $result;
		}

		static::extendProvidersListForType($result, $type, $factory->getCategories());

		return $result;
	}

	public static function getProviderCode(int $entityTypeId, int $categoryId = 0): string
	{
		$code = static::class;

		if($categoryId > 0)
		{
			$code .= '_' . $categoryId;
		}

		return mb_strtolower($code);
	}

	protected function getFieldsAliases(): array
	{
		return [
			'DATE_BILL' => Item::FIELD_NAME_BEGIN_DATE,
			'DATE_PAY_BEFORE' => Item::FIELD_NAME_CLOSE_DATE,
		];
	}

	public function getFields(): array
	{
		if($this->fields === null)
		{
			$fields = parent::getFields();

			$fields[Item::FIELD_NAME_BEGIN_DATE]['TITLE'] = $this->getFactory()->getFieldCaption(Item::FIELD_NAME_BEGIN_DATE);
			$fields[Item::FIELD_NAME_CLOSE_DATE]['TITLE'] = $this->getFactory()->getFieldCaption(Item::FIELD_NAME_CLOSE_DATE);

			foreach ($this->getFieldsAliases() as $alias => $fieldName)
			{
				if (isset($fields[$fieldName]))
				{
					$fields[$alias] = [
						'TITLE' => $fields[$fieldName]['TITLE'],
						'VALUE' => $fieldName,
						'OPTIONS' => [
							'COPY' => $fieldName,
						]
					];
				}
			}

			$this->fields = $fields;
		}

		return $this->fields;
	}
}
