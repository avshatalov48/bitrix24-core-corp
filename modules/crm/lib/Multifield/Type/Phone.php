<?php

namespace Bitrix\Crm\Multifield\Type;

use Bitrix\Crm\Multifield\Type;

final class Phone extends Type
{
	public const ID = 'PHONE';

	public const VALUE_TYPE_WORK = 'WORK';
	public const VALUE_TYPE_MOBILE = 'MOBILE';
	public const VALUE_TYPE_FAX = 'FAX';
	public const VALUE_TYPE_HOME = 'HOME';
	public const VALUE_TYPE_PAGER = 'PAGER';
	public const VALUE_TYPE_MAILING = 'MAILING';
	public const VALUE_TYPE_OTHER = 'OTHER';
}
