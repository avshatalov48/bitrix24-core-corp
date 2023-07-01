<?php

namespace Bitrix\CrmMobile\Terminal;

use Bitrix\Main;

final class LocHelper
{
	private const PATH = '/bitrix/modules/crmmobile/lib/Terminal/Terminal.php';

	public static function loadMessages()
	{
		Main\Localization\Loc::loadMessages(Main\Application::getDocumentRoot() . self::PATH);
	}
}
