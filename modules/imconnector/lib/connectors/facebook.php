<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Library;

use Bitrix\UI;

Loc::loadMessages(__FILE__);

/**
 * Class Facebook
 * @package Bitrix\ImConnector\Connectors
 */
class Facebook extends Base
{
	//Input

	//END Input

	//Output

	//END Output

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotSendMessageChat($paramsError, string $message = ''): bool
	{
		if(
			(int)$paramsError['params']['errorCode'] === 10
			&& (int)$paramsError['params']['errorSubCode'] === 2018278
			&& Loader::includeModule('ui')
		)
		{
			$paramsError['messageConnector'] = '';
			$message = Loc::getMessage('IMCONNECTOR_FACEBOOK_NOT_SEND_MESSAGE_CHAT_24_TIME_LIMIT', [
				'#A_START#' => '[URL=' . UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT) . ']',
				'#A_END#' => '[/URL]',
			]);
		}

		return parent::receivedErrorNotSendMessageChat($paramsError, $message);
	}
}