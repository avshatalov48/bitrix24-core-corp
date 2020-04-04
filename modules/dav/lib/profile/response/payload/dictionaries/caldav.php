<?php

namespace Bitrix\Dav\Profile\Response\Payload\Dictionaries;

use Bitrix\Main\ModuleManager;


/**
 * Class CalDav
 * @package Bitrix\Dav\Profile\Response\Payload\Dictionaries
 */
class CalDav extends ComponentBase
{
	const TEMPLATE_DICT_NAME = 'caldav';

	/**
	 * @return bool
	 */
	public function isAvailable()
	{
		return ModuleManager::isModuleInstalled('calendar');
	}

}