<?php

namespace Bitrix\ImConnector\Provider\Messageservice;

use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Provider\Base;
use Bitrix\Main\Loader;
use Bitrix\MessageService\Sender\SmsManager;

class Input extends Base\Input
{
	/**
	 * Input constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);

		if (!Loader::includeModule('messageservice'))
		{
			$this->result->addError(new \Bitrix\ImConnector\Error(
				'Module messageservice is not installed',
				'NO_MESSAGESERVICE_MODULE',
				__METHOD__,
				Library::ID_EDNA_WHATSAPP_CONNECTOR
			));
		}

		$this->command = $this->parseCommandFromCallback($params);

		if ($this->command === 'receivingMessage')
		{
			$this->params = $this->prepareMessageParams($params);
		}
		else
		{
			$this->params = $params;
		}
		$this->connector = Library::ID_EDNA_WHATSAPP_CONNECTOR;
		$this->line = SmsManager::getSenderById('ednaru')->getLineId();
		$this->data = [$this->params];
	}

	private function prepareMessageParams(array $params): array
	{
		$message =  [
			'message' => [
				'id' => $params['id'],
			],
			'user' => [
				'id' => $params['address'],
				'phone' => $params['address'],
				'name' => $params['userName'],
			],
			'chat' => [
				'id' => $params['address'] . '@' . $params['imSubject'],
			],
		];

		$lastMessage = $this->getSentTemplateMessage($params['imSubject'], $params['address']);
		if ($lastMessage !== '')
		{
			$message['chat']['last_message'] = $lastMessage;
		}

		if (
			$params['contentType'] === 'text'
			|| $params['contentType'] === 'button'
		)
		{
			$message['message']['text'] = $params['text'];
		}

		if ($params['avatarUrl'])
		{
			$message['user']['picture'] = ['url' => $params['avatarUrl']];
		}

		if (
			$params['contentType'] === 'image'
			|| $params['contentType'] === 'video'
			|| $params['contentType'] === 'document'
			|| $params['contentType'] === 'audio'
		)
		{
			$file = [];
			$file['url'] = $params['attachmentUrl'];
			if (isset($params['attachmentName']))
			{
				$file['name'] = $params['attachmentName'];
			}
			$message['message']['files'] = [$file];

			if (isset($params['caption']) && !is_null($params['caption']))
			{
				$message['message']['text'] = $params['caption'];
			}
		}

		return $message;
	}

	private function parseCommandFromCallback(array $data): ?string
	{
		if (isset($data['BX_COMMAND']))
		{
			return $data['BX_COMMAND'];
		}

		if (isset($data['dlvStatus']))
		{
			return 'receivingStatusDelivery';
		}

		if (isset($data['imSubject']))
		{
			return 'receivingMessage';
		}

		return null;
	}

	private function getSentTemplateMessage(string $from, string $to): string
	{
		return SmsManager::getSenderById('ednaru')->getSentTemplateMessage($from, $to);
	}
}
