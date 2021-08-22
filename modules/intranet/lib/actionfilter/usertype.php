<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Intranet\UserTable;
use Bitrix\Main\Context;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class UserType extends Engine\ActionFilter\Base
{
	/** @see \Bitrix\Main\UserTable::getExternalUserTypes */

	public const TYPE_CONTROLLER = '__controller';
	public const TYPE_BOT = 'bot';
	public const TYPE_CALL = 'call';
	public const TYPE_DOCUMENT_EDITOR = 'document_editor';
	public const TYPE_EMAIL = 'email';
	public const TYPE_EMPLOYEE = 'employee';
	public const TYPE_EXTRANET = 'extranet';
	public const TYPE_IMCONNECTOR = 'imconnector';
	public const TYPE_REPLICA = 'replica';
	public const TYPE_SALE = 'sale';
	public const TYPE_SALE_ANONYMOUS = 'saleanonymous';
	public const TYPE_SHOP = 'shop';

	public const ERROR_RESTRICTED_BY_USER_TYPE = 'restricted_by_user_type';

	/** @var array */
	private $allowedUserTypes;

	public function __construct(array $allowedUserTypes)
	{
		$this->allowedUserTypes = $allowedUserTypes;
		parent::__construct();
	}

	public function onBeforeAction(Event $event)
	{
		if (!$this->belongsCurrentUserToAllowedTypes())
		{
			Context::getCurrent()->getResponse()->setStatus(403);
			$this->addError(new Error(
				'Access restricted by user type',
				self::ERROR_RESTRICTED_BY_USER_TYPE
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	protected function belongsCurrentUserToAllowedTypes(): bool
	{
		global $USER;
		if (!($USER instanceof \CAllUser) || !$USER->getId())
		{
			return false;
		}

		if ($USER->IsAdmin())
		{
			return true;
		}

		$userData = UserTable::getRow([
			'filter' => [
				'=ID' => $USER->getId()
			],
			'select' => ['ID', 'USER_TYPE']
		]);

		if (empty($userData['USER_TYPE']))
		{
			return false;
		}

		return in_array($userData['USER_TYPE'], $this->allowedUserTypes, true);
	}
}