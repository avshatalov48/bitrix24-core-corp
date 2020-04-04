<?php

namespace Bitrix\Dav\Profile\Response\Payload\Dictionaries;

use Bitrix\Dav\Application;
use Bitrix\Dav\Profile\Response\Base as ResponseBase;
use Bitrix\Main\IO;
use Bitrix\Main\Context;

/**
 * Class ComponentBase
 * @package Bitrix\Dav\Profile\Response\Payload\Dictionaries
 */
abstract class ComponentBase extends Base
{
	const TEMPLATE_DICT_NAME = '';


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
		$params['username'] = $user['LOGIN'];
		$params['payloadIdentifier'] = 'com.apple.' . static::TEMPLATE_DICT_NAME . '.account.' . $this->getProfileIdentifier();
		$params['payloadUUID'] = $this->getProfileIdentifier();
		$templatePath = IO\Path::getDirectory(__DIR__ . '/templates/') . '/' . static::TEMPLATE_DICT_NAME . '.dict';
		return ResponseBase::render($templatePath, $params);
	}
}