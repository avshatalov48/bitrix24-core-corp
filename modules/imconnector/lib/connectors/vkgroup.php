<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Facebook
 * @package Bitrix\ImConnector\Connectors
 */
class VkGroup extends Base
{
	protected const ERROR_CONNECTOR_UNABLE_DELETE_MESSAGE_FOR_RECIPIENTS = 'CONNECTOR_UNABLE_DELETE_MESSAGE_FOR_RECIPIENTS';

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotDeleteMessageChat($paramsError, string $message = ''): bool
	{
		if($paramsError['params']['errorCode'] === self::ERROR_CONNECTOR_UNABLE_DELETE_MESSAGE_FOR_RECIPIENTS)
		{
			$paramsError['messageConnector'] = '';
			$message = Loc::getMessage('IMCONNECTOR_VKGROUP_UNABLE_DELETE_MESSAGE_FOR_RECIPIENTS');
		}

		return parent::receivedErrorNotDeleteMessageChat($paramsError, $message);
	}
}