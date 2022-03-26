<?php
namespace Bitrix\Intranet;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * @deprecated implementation moved to \Bitrix\Intranet\Secretary
 * @see \Bitrix\Intranet\Secretary
 * @see \Bitrix\Intranet\Controller\ControlButton
 * @see \Bitrix\Mail\Controller\Secretary
 */
class ControlButton extends Secretary
{
	/**
	 * @deprecated Method for backward compatibility
	 * @see \Bitrix\Intranet\Secretary::updateChatUsers()
	 */
	public static function udpateChatUsers($chatId, $addedUsers, $deletedUsers): void
	{
		parent::updateChatUsers($chatId, $addedUsers, $deletedUsers);
	}
}
