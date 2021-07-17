<?php

namespace Bitrix\Disk\Document;

use Bitrix\Main\Context;
use Bitrix\Main\EO_User;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Security;

final class DocumentEditorUser
{
	public const EXTERNAL_AUTH_ID = 'document_editor';
	public const COOKIE_AUTH_HASH_NAME = 'DOCUMENT_EDITOR_HASH';

	public static function login(array $fieldsToCreate = []): bool
	{
		if (!$GLOBALS['USER']->isAuthorized())
		{
			$GLOBALS['USER']->loginByCookies();
		}

		if ($GLOBALS['USER']->isAuthorized())
		{
			return true;
		}

		$request = Context::getCurrent()->getRequest();
		$authHash = $request->getCookieRaw(self::COOKIE_AUTH_HASH_NAME);

		$user = null;
		if (is_string($authHash) && !empty($authHash))
		{
			$user = self::getUserByHash($authHash);
		}

		if (!$user)
		{
			$user = self::create($fieldsToCreate);
		}

		if ($user)
		{
			$authHash = str_replace(self::EXTERNAL_AUTH_ID . '|', '', $user->getXmlId());
			$cookie = new Cookie(self::COOKIE_AUTH_HASH_NAME, $authHash, null, false);
			Context::getCurrent()->getResponse()->addCookie($cookie);

			$GLOBALS['USER']->authorize($user->getId(), false, true, 'public');

			return true;
		}

		return false;
	}

	public static function create(array $fields = []): ?EO_User
	{
		$name = $fields['NAME'] ?? 'Guest';
		$lastName = $fields['LAST_NAME'] ?? '';

		$login = 'disk_document_editor_' . rand(1000, 9999) . Security\Random::getString(8);
		$password = md5($login . '|' . rand(1000, 9999) . '|' . time());
		$xmlId = self::EXTERNAL_AUTH_ID . '|' . md5(time() . $login . $password . uniqid());

		$userManager = new \CUser;
		$userId = $userManager->add([
			'NAME' => $name,
			'LAST_NAME' => $lastName,
			'LOGIN' => $login,
			'PASSWORD' => $password,
			'CONFIRM_PASSWORD' => $password,
			'EXTERNAL_AUTH_ID' => self::EXTERNAL_AUTH_ID,
			'XML_ID' => $xmlId,
			'ACTIVE' => 'Y',
		]);

		return (
			$userId > 0
				? UserTable::getById($userId)->fetchObject()
				: null
		);
	}

	public static function getUserByHash(string $hash): ?EO_User
	{
		if (!preg_match('/^[0-9a-f]{32}$/', $hash))
		{
			return null;
		}

		$xmlId = self::EXTERNAL_AUTH_ID . '|' . $hash;

		return UserTable::getList([
			'filter' => [
				'=ACTIVE' => 'Y',
				'=EXTERNAL_AUTH_ID' => self::EXTERNAL_AUTH_ID,
				'=XML_ID' => $xmlId
			]
		])->fetchObject();
	}
}
