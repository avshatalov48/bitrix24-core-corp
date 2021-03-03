<?php

namespace Bitrix\ImOpenLines\Controller\Widget\Filter;

use Bitrix\Imopenlines\Widget\User;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;

use Bitrix\Main\EventResult;

class Authorization extends Base
{
	private const AUTH_TYPE = 'livechat';

	public function onBeforeAction(Event $event)
	{
		//for preflight CORS request
		$requestMethod = $this->action->getController()->getRequest()->getRequestMethod();
		if ($requestMethod === 'OPTIONS')
		{
			return null;
		}

		$authCode = Context::getCurrent()->getRequest()->getHeader('livechat-auth-id');

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

		$session = \Bitrix\Main\Application::getInstance()->getSession();
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
					\CUser::SetLastActivityDate($USER->GetID(), true);
				}

				return null;
			}

			return null;
		}

		$userData = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ['=XML_ID' => $xmlId]
		])->fetch();

		if($userData && $userData['EXTERNAL_AUTH_ID'] === User::EXTERNAL_AUTH_ID)
		{
			self::authorizeById($userData['ID']);
			\CUser::SetLastActivityDate($USER->GetID(), true);
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

		$context = \Bitrix\Main\Context::getCurrent();

		if ($USER->GetID() != $userId)
		{
			$USER->Authorize($userId, false, false, 'public');
		}

		$authCode = str_replace(self::AUTH_TYPE.'|', '', $USER->GetParam("XML_ID"));

		$cookie = new \Bitrix\Main\Web\Cookie('LIVECHAT_HASH', $authCode, null, false);
		$cookie->setHttpOnly(false);
		$context->getResponse()->addCookie($cookie);

		return true;
	}
}