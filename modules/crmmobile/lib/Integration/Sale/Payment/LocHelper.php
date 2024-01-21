<?php

namespace Bitrix\CrmMobile\Integration\Sale\Payment;

use Bitrix\Main;

final class LocHelper
{
	private const PATH = '/bitrix/modules/crmmobile/lib/Integration/Sale/Payment/Payment.php';

	public static function loadMessages()
	{
		Main\Localization\Loc::loadMessages(Main\Application::getDocumentRoot() . self::PATH);
	}
}
