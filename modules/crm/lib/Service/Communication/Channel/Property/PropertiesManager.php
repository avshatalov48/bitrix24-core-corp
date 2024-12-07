<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property;

use Bitrix\Crm\Service\Communication\Channel\Property\Common\BaseType;
use Bitrix\Crm\Service\Communication\Channel\Property\Common\ClientType;
use Bitrix\Crm\Service\Communication\Channel\Property\Type\Base;
use Bitrix\Crm\Service\Communication\Channel\Property\Type\ClientPhone;
use Bitrix\Crm\Service\Communication\Channel\Property\Type\EnumerationType;
use Bitrix\Crm\Service\Communication\Channel\Property\Type\StringType;
use Bitrix\Crm\Traits\Singleton;

final class PropertiesManager
{
	use Singleton;

	public const TYPE_STRING = 'string';

	public const TYPE_CLIENT_PHONE = 'client_phone';
	public const TYPE_ENUMERATION = 'enumeration';

	public static function getTypeInstance(string $type, mixed $value): ?Base
	{
		return match ($type)
		{
			self::TYPE_STRING => new StringType($value),
			self::TYPE_CLIENT_PHONE => new ClientPhone($value),
			self::TYPE_ENUMERATION => new EnumerationType($value),
			default => null,
		};
	}

	public static function getCommonPropertyInstance(string $code, array $bindings): ?BaseType
	{
		return match ($code)
		{
			ClientType::CODE => new ClientType($bindings),
			default => null,
		};
	}

	public function getCommonProperties(): PropertiesCollection
	{
		return new PropertiesCollection([
			$this->getClientTypeProperty(),
		]);
	}

	private function getClientTypeProperty(): Property
	{
		return (self::getCommonPropertyInstance(ClientType::CODE, []))->createProperty();
	}
}
