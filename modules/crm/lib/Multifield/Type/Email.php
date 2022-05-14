<?php

namespace Bitrix\Crm\Multifield\Type;

use Bitrix\Crm\Multifield\Type;

final class Email extends Type
{
	public const ID = 'EMAIL';

	public const VALUE_TYPE_WORK = 'WORK';
	public const VALUE_TYPE_HOME = 'HOME';
	public const VALUE_TYPE_MAILING = 'MAILING';
	public const VALUE_TYPE_OTHER = 'OTHER';
}
