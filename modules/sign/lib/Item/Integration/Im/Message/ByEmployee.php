<?php

namespace Bitrix\Sign\Item\Integration\Im\Message;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\Integration\Im\Message;

abstract class ByEmployee extends Message
{
	protected function getLocalizedFallbackMessage(string $id, array $replace = null, ?string $lang = null): ?string
	{
		$lang = $lang ?? $this->lang;
		return
			Loc::getMessage($id, $replace, $lang)
			?? parent::getLocalizedFallbackMessage($id, $replace, $lang)
		;
	}
}
