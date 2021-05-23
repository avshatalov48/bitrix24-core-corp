<?php

namespace Bitrix\ImOpenLines\Controller\Widget\Filter;

use Bitrix\Imopenlines\Widget\User;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;

use Bitrix\Main\EventResult;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Cookie;
use CUser;

class Authorization extends Base
{
	private const AUTH_TYPE = 'livechat';

	public function onBeforeAction(Event $event)
	{
		$request = $this->action->getController()->getRequest();
		if (!$request)
		{
			return null;
		}

		//for preflight CORS request
		$requestMethod = $request->getRequestMethod();
		if ($requestMethod === 'OPTIONS')
		{
			return null;
		}

		$authCode = $request->getHeader('livechat-auth-id');
		if ($authCode === null)
		{
			$this->addError(new Error('LiveChat: user auth failed [code is empty'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			$USER->LoginByCookies();
		}

		if (!preg_match("/^[a-fA-F0-9]{32}$/i", $authCode))
		{
			$this->addError(new Error('LiveChat: user auth failed [code is not correct]'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$session = Application::getInstance()->getSession();
		if ($session['LIVECHAT']['AUTH_ERROR_COUNTER'] > 3)
		{
			$this->addError(new Error('LiveChat: user auth blocked'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$xmlId = self::AUTH_TYPE."|".$authCode;

		if ($USER->IsAuthorized())
		{
			if ($USER->GetParam('EXTERNAL_AUTH_ID') === User::EXTERNAL_AUTH_ID)
			{
				if ($USER->GetParam('XML_ID') === $xmlId)
				{
					CUser::SetLastActivityDate($USER->GetID(), true);
				}

				return null;
			}

			return null;
		}

		$userData = UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ['=XML_ID' => $xmlId]
		])->fetch();

		if($userData && $userData['EXTERNAL_AUTH_ID'] === User::EXTERNAL_AUTH_ID)
		{
			self::authorizeById($userData['ID']);
			CUser::SetLastActivityDate($USER->GetID(), true);
		}

		if (
			$session['LIVECHAT']['AUTH_CODE']
			&& $session['LIVECHAT']['AUTH_CODE'] !== $xmlId
		)
		{
			$session['LIVECHAT']['AUTH_ERROR_COUNTER'] += 1;
		}

		$session['LIVECHAT']['AUTH_CODE'] = $xmlId;

		return null;
	}

	public static function authorizeById($userId, $skipAuthorizeCheck = false): bool
	{
		global $USER;

		if (!$skipAuthorizeCheck && $USER->IsAuthorized())
		{
			return false;
		}

		$context = Context::getCurrent();

		if ($USER->GetID() != $userId)
		{
			$USER->Authorize($userId, false, false, 'public');
		}

		$authCode = str_replace(self::AUTH_TYPE.'|', '', $USER->GetParam("XML_ID"));

		$cookie = new Cookie('LIVECHAT_HASH', $authCode, null, false);
		$cookie->setHttpOnly(false);
		$context->getResponse()->addCookie($cookie);

		return true;
	}
}