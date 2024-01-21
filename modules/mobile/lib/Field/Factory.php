<?php

namespace Bitrix\Mobile\Field;

use Bitrix\Mobile\Field\Type\AddressField;
use Bitrix\Mobile\Field\Type\BaseField;
use Bitrix\Mobile\Field\Type\BooleanField;
use Bitrix\Mobile\Field\Type\CrmField;
use Bitrix\Mobile\Field\Type\CrmStatusField;
use Bitrix\Mobile\Field\Type\DateField;
use Bitrix\Mobile\Field\Type\DateTimeField;
use Bitrix\Mobile\Field\Type\EnumerationField;
use Bitrix\Mobile\Field\Type\FileField;
use Bitrix\Mobile\Field\Type\IblockElementField;
use Bitrix\Mobile\Field\Type\IblockSectionField;
use Bitrix\Mobile\Field\Type\MoneyField;
use Bitrix\Mobile\Field\Type\NumberField;
use Bitrix\Mobile\Field\Type\StringField;
use Bitrix\Mobile\Field\Type\UrlField;
use Bitrix\Mobile\Field\Type\UserField;

class Factory
{
	/**
	 * @param string $type
	 * @param string $id
	 * @param $value
	 * @return ?BaseField
	 */
	public static function getField(string $type, string $id, $value): ?BaseField
	{
		if ($type === StringField::TYPE)
		{
			return new StringField($id, $value);
		}

		if ($type === EnumerationField::TYPE)
		{
			return new EnumerationField($id, $value);
		}

		if ($type === DateField::TYPE)
		{
			return new DateField($id, $value);
		}

		if ($type === DateTimeField::TYPE)
		{
			return new DateTimeField($id, $value);
		}

		if ($type === AddressField::TYPE)
		{
			return new AddressField($id, $value);
		}

		if ($type === UrlField::TYPE)
		{
			return new UrlField($id, $value);
		}

		if ($type === FileField::TYPE)
		{
			return new FileField($id, $value);
		}

		if ($type === MoneyField::TYPE)
		{
			return new MoneyField($id, $value);
		}

		if ($type === BooleanField::TYPE)
		{
			return new BooleanField($id, $value);
		}

		if ($type === NumberField::TYPE || $type === 'double' || $type === 'integer')
		{
			return new NumberField($id, $value);
		}

		if ($type === CrmField::TYPE)
		{
			return new CrmField($id, $value);
		}

		if ($type === CrmStatusField::TYPE)
		{
			return new CrmStatusField($id, $value);
		}

		if ($type === IblockElementField::TYPE)
		{
			return new IblockElementField($id, $value);
		}

		if ($type === IblockSectionField::TYPE)
		{
			return new IblockSectionField($id, $value);
		}

		if ($type === UserField::TYPE)
		{
			return new UserField($id, $value);
		}

		return null;
	}
}
