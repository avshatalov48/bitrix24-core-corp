<?php

namespace Bitrix\Sign\Config;

use Bitrix\Main\Config\Option;

final class Feature
{
	private static ?self $instance = null;

	public static function instance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function isSendDocumentByEmployeeEnabled(): bool
	{
		return
			(bool)Option::get('sign', 'SIGN_SEND_DOCUMENT_BY_EMPLOYEE_ENABLED', false)
			&& Storage::instance()->isB2eAvailable()
		;
	}
}