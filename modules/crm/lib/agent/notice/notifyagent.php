<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2018 Bitrix
 */

namespace Bitrix\Crm\Agent\Notice;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

/**
 * Class NotifyAgent
 *
 * @package Bitrix\Crm\Agent\Notice
 */
class NotifyAgent
{
	/**
	 * Notify about "repeat lead" functionality is enabled.
	 *
	 * @return void
	 */
	public static function notifyAboutRepeatLead()
	{
		Notification::create()
			->withMessage(self::getNotifyMessage())
			->toList(UserList::getPortalAdmins())
			->toList(UserList::getLeadAssignedUsers())
			->toList(UserList::getContactAssignedUsers())
			->toList(UserList::getCompanyAssignedUsers())
			->send();
	}

	protected static function getNotifyMessage(): callable
	{
		return static fn (?string $languageId = null) =>
			Loc::getMessage('CRM_AGENT_NOTICE_NOTIFY_AGENT_ABOUT_REPEAT_LEAD', ['#HELPDESK_URL#' => Loader::includeModule('ui') ? (new \Bitrix\UI\Helpdesk\Url())->getDomain()->get() : ''], $languageId)
		;
	}
}
