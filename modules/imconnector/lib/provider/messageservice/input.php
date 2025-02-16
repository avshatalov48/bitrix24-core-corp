<?php

namespace Bitrix\ImConnector\Provider\Messageservice;

use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Provider\Base;
use Bitrix\Main\Loader;
use Bitrix\MessageService;
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
			$subjectId = isset($params['imSubjectId']) ? (int)$params['imSubjectId'] : (int)$params['imSubject'];
		}
		else
		{
			$this->params = $params;
			$subjectId = $this->getSubjectIdFromPreparedMessageParams($params);
		}
		$this->data = [$this->params];

		$this->connector = Library::ID_EDNA_WHATSAPP_CONNECTOR;

		$sender = SmsManager::getSenderById(MessageService\Sender\Sms\Ednaru::ID);
		if ($sender instanceof MessageService\Sender\Base)
		{
			$this->line = $sender->getLineId($subjectId);
		}
		else
		{
			$this->result->addError(new \Bitrix\ImConnector\Error(
				'Messageservice is not enabled',
				'NO_MESSAGESERVICE_LINE',
				__METHOD__,
				Library::ID_EDNA_WHATSAPP_CONNECTOR
			));
		}
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
		return SmsManager::getSenderById(MessageService\Sender\Sms\Ednaru::ID)->getSentTemplateMessage($from, $to);
	}

	private function getSubjectIdFromPreparedMessageParams(array $params): ?int
	{
		if (isset($params['chat']['id']))
		{
			$parts = explode('@', $params['chat']['id']);
			if (isset($parts[1]) && !empty($parts[1]))
			{
				return (int)$parts[1];
			}
		}

		return null;
	}
}
