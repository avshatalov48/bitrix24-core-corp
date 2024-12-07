<?php

namespace Bitrix\Crm\FieldSynchronizer;

class Email extends MultiFieldBase
{
	protected static string $typeId = \CCrmFieldMulti::EMAIL;
	protected static array $availableTypeList = [
		\Bitrix\Crm\Multifield\Type\Email::VALUE_TYPE_WORK,
		\Bitrix\Crm\Multifield\Type\Email::VALUE_TYPE_HOME,
		\Bitrix\Crm\Multifield\Type\Email::VALUE_TYPE_OTHER,
	];
}
