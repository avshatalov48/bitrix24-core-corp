<?php
namespace Bitrix\ImConnector\Connectors;

use Bitrix\ImConnector\Result;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Library;

use Bitrix\UI;

use Bitrix\Im\Model\MessageTable;

Loc::loadMessages(__FILE__);

/**
 * Class Facebook
 * @package Bitrix\ImConnector\Connectors
 */
class Facebook extends Base
{
	//Input
	public function processingInputNewMessage($message, $line): Result
	{
		$catalogProducts = $message['message']['attachments']['catalog'];
		if (is_array($catalogProducts) && count($catalogProducts) > 0)
		{
			$message['message']['text'] = Loc::getMessage('IMCONNECTOR_FACEBOOK_ADDITIONAL_DATA');

			$blocks = [];
			foreach ($catalogProducts as $catalogProduct)
			{
				if ($catalogProduct['image_url'])
				{
					$blocks[] = ["IMAGE" => ['LINK' => $catalogProduct['image_url']]];
				}
				if ($catalogProduct['title'])
				{
					$blocks[] = ["MESSAGE" => $catalogProduct['title']];
				}
				if ($catalogProduct['subtitle'])
				{
					$blocks[] = ["MESSAGE" => $catalogProduct['subtitle']];
				}
			}

			if (count($blocks) > 0)
			{
				$message['message']['attach'] = \Bitrix\Main\Web\Json::encode(['BLOCKS' => $blocks]);
			}
		}

		return parent::processingInputNewMessage($message, $line);
	}
	//END Input

	//Output
	/**
	 * @param array $message
	 * @param $line
	 * @return array
	 */
	public function sendMessageProcessing(array $message, $line): array
	{
		$message = parent::sendMessageProcessing($message, $line);

		if (
			!empty($message['im']['message_id'])
			&& $message['im']['message_id'] > 0
			&& $this->isHumanAgent($line) === true
			&& Loader::includeModule('im')
		)
		{
			$raw = MessageTable::getList([
				'select' => [
					'AUTHOR_ID'
				],
				'filter' => [
					'=ID' => (int)$message['im']['message_id'],
				]
			]);

			if (
				($row = $raw->fetch())
				&& !empty($row['AUTHOR_ID'])
			)
			{
				$message['message']['long'] = true;
			}
		}

		return $message;
	}
	//END Output

	/**
	 * @param $paramsError
	 * @param string $message
	 * @return bool
	 */
	protected function receivedErrorNotSendMessageChat($paramsError, string $message = ''): bool
	{
		if(
			!empty($paramsError['params'])
			&& (int)$paramsError['params']['errorCode'] === 10
			&& (int)$paramsError['params']['errorSubCode'] === 2018278
			&& Loader::includeModule('ui')
		)
		{
			$paramsError['messageConnector'] = '';
			$message = Loc::getMessage('IMCONNECTOR_FACEBOOK_NOT_SEND_MESSAGE_CHAT_LIMIT', [
				'#A_START#' => '[URL=' . UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT) . ']',
				'#A_END#' => '[/URL]',
			]);

			if (
				!empty($paramsError['messageId'])
				&& $paramsError['messageId'] > 0
				&& Loader::includeModule('im')
			)
			{
				$raw = MessageTable::getList([
					'select' => [
						'AUTHOR_ID'
					],
					'filter' => [
						'=ID' => (int)$paramsError['messageId'],
					]
				]);

				if (
					($row = $raw->fetch())
					&& !empty($row['AUTHOR_ID'])
				)
				{
					if ($this->isHumanAgent($paramsError['line']) === true)
					{
						$message = Loc::getMessage('IMCONNECTOR_FACEBOOK_NOT_SEND_MESSAGE_CHAT_7_DAY_LIMIT', [
							'#A_START#' => '[URL=' . UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT) . ']',
							'#A_END#' => '[/URL]',
						]);
					}
					else
					{
						$message = Loc::getMessage('IMCONNECTOR_FACEBOOK_NOT_SEND_MESSAGE_CHAT_24_HOURS_LIMIT', [
							'#A_START#' => '[URL=' . UI\Util::getArticleUrlByCode(Library::CODE_ID_ARTICLE_TIME_LIMIT) . ']',
							'#A_END#' => '[/URL]',
						]);
					}
				}
			}
		}

		return parent::receivedErrorNotSendMessageChat($paramsError, $message);
	}
}