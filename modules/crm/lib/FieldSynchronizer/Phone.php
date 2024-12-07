<?php

namespace Bitrix\Crm\FieldSynchronizer;

class Phone extends MultiFieldBase
{
	protected static string $typeId = \CCrmFieldMulti::PHONE;
	protected static array $availableTypeList = [
		\Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_WORK,
		\Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_MOBILE,
		\Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_HOME,
		\Bitrix\Crm\Multifield\Type\Phone::VALUE_TYPE_OTHER,
	];
}
