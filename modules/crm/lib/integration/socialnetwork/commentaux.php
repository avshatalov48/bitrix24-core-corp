<?php
namespace Bitrix\Crm\Integration\Socialnetwork;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class CommentAux
{
	public static function initJs()
	{
		\CJSCore::Init(array('crm_sonet_commentaux'));
	}
}