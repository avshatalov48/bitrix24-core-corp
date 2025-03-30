<?php

namespace Bitrix\Dav\Profile\Response\Payload\Dictionaries;

use Bitrix\Dav\Application;
use Bitrix\Dav\Profile\Response\Base as ResponseBase;
use Bitrix\Main\IO;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialservices\UserTable;

/**
 * Class ComponentBase
 * @package Bitrix\Dav\Profile\Response\Payload\Dictionaries
 */
abstract class ComponentBase extends Base
{
	public const TEMPLATE_DICT_NAME = '';
	public const MAIN_EXTERNAL_USER_ID_SOCSERVICES = 'socservices';
	public const SOCIALSERVICES_EXTERNAL_USER_ID_NETWORK = 'Bitrix24Net';


	/**
	 * @return string
	 */
	public function prepareBodyContent()
	{
		$params['host'] = Context::getCurrent()->getServer()->getHttpHost();
		$params['port'] =  Context::getCurrent()->getServer()->getServerPort();
		$params['isSSL'] = Context::getCurrent()->getRequest()->isHttps() ? 'true' : 'false';
		$user = $this->getUser();
		$params['password'] = Application::generateAppPassword($user['ID'], static::TEMPLATE_DICT_NAME);
		$params['username'] = $this->getLogin();
		$params['payloadIdentifier'] = 'com.apple.' . static::TEMPLATE_DICT_NAME . '.account.' . $this->getProfileIdentifier();
		$params['payloadUUID'] = $this->getProfileIdentifier();
		$templatePath = IO\Path::getDirectory(__DIR__ . '/templates/') . '/' . static::TEMPLATE_DICT_NAME . '.dict';
		if (empty($params['port']))
		{
			$params['port'] = $this->getPortWithScheme();
		}

		return ResponseBase::render($templatePath, $params);
	}

	/**
	 * @return string
	 */
	public function getPortWithScheme(): string
	{
		return Context::getCurrent()->getRequest()->isHttps() ? '443' : '80';
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getLogin(): string
	{
		$user = $this->getUser();

		if (
			$user['EXTERNAL_AUTH_ID'] === static::MAIN_EXTERNAL_USER_ID_SOCSERVICES
			&& ModuleManager::isModuleInstalled('bitrix24')
			&& Loader::includeModule('socialservices')
		)
		{
			$socservicesUserDb = UserTable::getList([
				'filter' => [
					'USER_ID' => $user['ID'],
					'EXTERNAL_AUTH_ID' => static::SOCIALSERVICES_EXTERNAL_USER_ID_NETWORK,
				],
				'select' => [
					'LOGIN',
				],
			]);
			if ($socservicesUser = $socservicesUserDb->fetch())
			{
				return $socservicesUser['LOGIN'];
			}
		}

		return $user['LOGIN'];
	}
}
