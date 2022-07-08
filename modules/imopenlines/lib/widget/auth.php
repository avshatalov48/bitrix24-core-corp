<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Imopenlines\Widget;

class Auth
{
	const AUTH_TYPE = 'livechat';

	const AUTH_CODE_REGISTER = 'register';
	const AUTH_CODE_GUEST = 'guest';

	const METHODS_WITHOUT_AUTH = [
		'imopenlines.widget.user.register',
		'imopenlines.widget.user.get',
		'imopenlines.widget.user.consent.apply',
		'imopenlines.widget.config.get',
		'imopenlines.widget.dialog.get',
		'im.dialog.messages.get',
		'im.chat.get',
		'server.time',
		'pull.config.get',
		'smile.get',
	];

	const METHODS_WITH_AUTH = [
		// imopenlines
		'imopenlines.widget.dialog.get',
		'imopenlines.widget.dialog.list',
		'imopenlines.widget.chat.create',
		'imopenlines.widget.user.get',
		'imopenlines.widget.operator.get',
		'imopenlines.widget.user.consent.apply',
		'imopenlines.widget.vote.send',
		'imopenlines.widget.action.send',
		'imopenlines.widget.crm.bindings.get',

		// pull
		'server.time',
		'pull.config.get',
		'pull.watch.extend',
		// im
		'im.chat.get',
		'im.message.add',
		'im.message.update',
		'im.message.delete',
		'im.message.like',
		'im.dialog.writing',
		'im.dialog.messages.get',
		'im.dialog.read',
		'im.disk.folder.get',
		'im.disk.file.commit',
		// disk
		'disk.folder.uploadfile',
	];

	// TODO sync AUTH_ID_PARAM with file rest/services/rest/index.php
	const AUTH_ID_PARAM = 'livechat_auth_id';
	const AUTH_UID_PARAM = 'livechat_user_id';
	const AUTH_CUSTOM_ID_PARAM = 'livechat_custom_auth_id';

	protected static $authQueryParams = [
		self::AUTH_ID_PARAM,
	];

	public static function onRestCheckAuth(array $query, $scope, &$res)
	{
		$authCode = null;
		foreach(static::$authQueryParams as $key)
		{
			if(array_key_exists($key, $query))
			{
				$authCode = $query[$key];
				break;
			}
		}

		if($authCode === null)
		{
			return null;
		}

		define('BX24_REST_SKIP_SEND_HEADERS', true);
		$origin = \Bitrix\Main\Context::getCurrent()->getServer()->get('HTTP_ORIGIN');
		if ($origin)
		{
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Credentials: true');
		}

		global $USER;
		if (!$USER->IsAuthorized())
		{
			$USER->LoginByCookies();
		}

		if ($authCode == self::AUTH_CODE_GUEST)
		{
			if (self::checkQueryMethod(self::METHODS_WITHOUT_AUTH))
			{
				if ($USER->IsAuthorized())
				{
					if ($USER->GetParam('EXTERNAL_AUTH_ID') == User::EXTERNAL_AUTH_ID && mb_substr($USER->GetParam('XML_ID'), 0, mb_strlen(self::AUTH_TYPE)) == self::AUTH_TYPE)
					{
						$customAuthCode = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get(self::AUTH_CUSTOM_ID_PARAM);
						if ($customAuthCode && preg_match("/^[a-fA-F0-9]{32}$/i", $customAuthCode))
						{
							$res = self::getSuccessfulResult();
							return true;
						}
						else
						{
							$res = array(
								'error' => 'LIVECHAT_AUTH_WIDGET_USER',
								'error_description' => 'Livechat: you are authorized with a different user [1]',
								'additional' => array('hash' => mb_substr($USER->GetParam('XML_ID'), mb_strlen(self::AUTH_TYPE) + 1))
							);
							return false;
						}
					}
					else
					{
						$res = array(
							'error' => 'LIVECHAT_AUTH_PORTAL_USER',
							'error_description' => 'Livechat: you are authorized with a portal user [1]',
							'additional' => array()
						);
						return false;
					}
				}
				else
				{
					$res = self::getSuccessfulResult();
					return true;
				}
			}
			else
			{
				$res = array(
					'error' => 'LIVECHAT_AUTH_METHOD_ERROR',
					'error_description' => 'Livechat: you don\'t have access to use this method [1]',
					'additional' => array()
				);
				return false;
			}
		}
		else if (!preg_match("/^[a-fA-F0-9]{32}$/i", $authCode))
		{
			$res = array(
				'error' => 'LIVECHAT_AUTH_FAILED',
				'error_description' => 'LiveChat: user auth failed [code is not correct]',
				'additional' => array()
			);
		}
		else if ($_SESSION['LIVECHAT']['AUTH_ERROR_COUNTER'] > 3)
		{
			$res = array(
				'error' => 'LIVECHAT_AUTH_BLOCKED',
				'error_description' => 'LiveChat: user auth blocked',
				'additional' => array()
			);

			return false;
		}

		if (!self::checkQueryMethod(array_merge(self::METHODS_WITH_AUTH, self::METHODS_WITHOUT_AUTH)))
		{
			$res = array(
				'error' => 'LIVECHAT_AUTH_METHOD_ERROR',
				'error_description' => 'Livechat: you don\'t have access to use this method [2]',
				'additional' => array()
			);
			return false;
		}

		$xmlId = self::AUTH_TYPE."|".$authCode;

		if ($USER->IsAuthorized())
		{
			if ($USER->GetParam('EXTERNAL_AUTH_ID') == User::EXTERNAL_AUTH_ID)
			{
				if ($USER->GetParam('XML_ID') == $xmlId)
				{
					$res = self::getSuccessfulResult();

					\CUser::SetLastActivityDate($USER->GetID(), true);

					return true;
				}
				else
				{
					$res = array(
						'error' => 'LIVECHAT_AUTH_WIDGET_USER',
						'error_description' => 'Livechat: you are authorized with a different user [2]',
						'additional' => array('hash' => mb_substr($USER->GetParam('XML_ID'), mb_strlen(self::AUTH_TYPE) + 1))
					);
					return false;
				}
			}
			else
			{
				$res = array(
					'error' => 'LIVECHAT_AUTH_PORTAL_USER',
					'error_description' => 'Livechat: you are authorized with a portal user [2]',
					'additional' => array()
				);
				return false;
			}
		}

		$userData = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID'],
			'filter' => ['=XML_ID' => $xmlId]
		])->fetch();

		if($userData && $userData['EXTERNAL_AUTH_ID'] == User::EXTERNAL_AUTH_ID)
		{
			self::authorizeById($userData['ID']);

			$res = self::getSuccessfulResult();

			\CUser::SetLastActivityDate($USER->GetID(), true);

			return true;
		}

		$res = array(
			'error' => 'LIVECHAT_AUTH_FAILED',
			'error_description' => 'LiveChat: user auth failed [user not found]',
			'additional' => array()
		);

		if (
			$_SESSION['LIVECHAT']['AUTH_CODE']
			&& $_SESSION['LIVECHAT']['AUTH_CODE'] != $xmlId
		)
		{
			$_SESSION['LIVECHAT']['AUTH_ERROR_COUNTER'] += 1;
		}

		$_SESSION['LIVECHAT']['AUTH_CODE'] = $xmlId;

		return false;
	}

	public static function onDiskCheckAuth(\Bitrix\Main\Event $event)
	{
		global $USER;
		if ($USER->IsAuthorized())
		{
			return false;
		}

		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

		$authCode = $request->get(self::AUTH_ID_PARAM);
		$authUid = (int)$request->get(self::AUTH_UID_PARAM);
		$authFileId = (int)$request->get('fileId');

		if (!$authCode || !$authUid || !$authFileId || !preg_match("/^[a-fA-F0-9]{32}$/i", $authCode))
		{
			return false;
		}

		/** @var \Bitrix\Main\Engine\Action $action */
		$action = $event->getParameter('action');
		if (!in_array(mb_strtolower($action->getName()), ['download', 'showimage', 'showpreview']))
		{
			return false;
		}

		$userData = \Bitrix\Main\UserTable::getList([
			'select' => ['ID', 'EXTERNAL_AUTH_ID', 'XML_ID'],
			'filter' => ['ID' => $authUid]
		])->fetch();

		if($userData && $userData['EXTERNAL_AUTH_ID'] == User::EXTERNAL_AUTH_ID)
		{
			if ($authCode === md5($userData['ID'].'|'.$authFileId.'|'.str_replace(self::AUTH_TYPE."|", '', $userData['XML_ID'])))
			{
				self::authorizeById($userData['ID'], false);
				setSessionExpired(true);
				return false;
			}
		}
	}

	public static function authorizeById($userId, $setCookie = null, $skipAuthorizeCheck = false)
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

	private static function getSuccessfulResult()
	{
		global $USER;

		return [
			'user_id' => $USER->GetID(),
			'scope' => implode(',', \CRestUtil::getScopeList()),
			'parameters_clear' => static::$authQueryParams,
			'auth_type' => static::AUTH_TYPE,
		];
	}

	private static function checkQueryMethod($whiteListMethods)
	{
		if (\CRestServer::instance()->getMethod() == 'batch')
		{
			$result = false;
			foreach (\CRestServer::instance()->getQuery()['cmd'] as $key => $method)
			{
				$method = mb_substr($method, 0, mb_strrpos($method, '?'));
				$result = in_array(mb_strtolower($method), $whiteListMethods);
				if (!$result)
				{
					break;
				}
			}
		}
		else
		{
			$result = in_array(\CRestServer::instance()->getMethod(), $whiteListMethods);
		}

		return $result;
	}
}