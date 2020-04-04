<?php

namespace Bitrix\Crm\Integration\Sms\Provider;

use Bitrix\Crm\Integration\Sms\MessageStatusResult;
use Bitrix\Crm\Integration\Sms\SendMessageResult;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Sms\Message;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class SmsRu extends BaseInternal
{
	private $sendersCache;
	private $balanceCache;

	public function getId()
	{
		return 'smsru';
	}

	public function getName()
	{
		return Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_SMSRU_NAME');
	}

	public function getShortName()
	{
		return 'sms.ru';
	}

	public function getBalance()
	{
		if ($this->balanceCache !== null)
		{
			return $this->balanceCache;
		}

		$params = array(
			'embed_id' => $this->getOption('embed_id')
		);
		$apiResult = $this->callExternalMethod('my/balance', $params);

		$balance = false;
		if ($apiResult->isSuccess())
		{
			$balanceData = $apiResult->getData();
			$this->balanceCache = $balance = (float)$balanceData['balance'];
		}

		return $balance;
	}

	public function getTestBalance()
	{
		$params = array(
			'embed_id' => $this->getOption('embed_id')
		);
		$apiResult = $this->callExternalMethod('my/free', $params);

		$balance = array(
			'total_free' => 0,
			'used_today' => 0,
			'available_today' => 0
		);

		if ($apiResult->isSuccess())
		{
			$balanceData = $apiResult->getData();
			$balance['total_free'] = (int)$balanceData['total_free'];
			$balance['used_today'] = (int)$balanceData['used_today'];
			$balance['available_today'] = max(0, $balance['total_free'] - $balance['used_today']);
		}

		return $balance;
	}

	public function getSenderList()
	{
		if ($this->sendersCache !== null)
		{
			return $this->sendersCache;
		}

		$params = array(
			'embed_id' => $this->getOption('embed_id')
		);
		$apiResult = $this->callExternalMethod('my/senders', $params);

		$from = array();
		if ($apiResult->isSuccess())
		{
			$sendersData = $apiResult->getData();
			foreach ($sendersData['senders'] as $sender)
			{
				if (!empty($sender))
				{
					$from[] = $sender;
				}
			}
		}

		$this->sendersCache = $from;
		return $from;
	}

	public function getDefaultSender()
	{
		$sender = $this->getOption('default_sender');
		if (!$sender)
		{
			$senders = $this->getSenderList();
			$sender = $senders[0];
			//Try to find alphanumeric sender
			foreach ($senders as $item)
			{
				if (!preg_match('#^[0-9]+$#', $item))
				{
					$sender = $item;
					break;
				}
			}
			$this->setOption('default_sender', $sender);
		}
		return $sender;
	}

	public function setDefaultSender($sender)
	{
		$sender = (string)$sender;
		$this->setOption('default_sender', $sender);
		return $this;
	}

	public function isConfirmed()
	{
		return ($this->getOption('is_confirmed') === true);
	}

	public function isRegistered()
	{
		return ($this->getOption('embed_id') !== null);
	}

	public function register(array $fields)
	{
		$userPhone = \NormalizePhone($fields['user_phone']);
		$params = array(
			'user_phone' => $userPhone,
			'user_firstname' => $fields['user_firstname'],
			'user_lastname' => $fields['user_lastname'],
			'user_email' => $fields['user_email'],
			'embed_partner' => $this->getEmbedPartner(),
			'embed_hash' => $this->getEmbedHash($userPhone)
		);

		$result = $this->callExternalMethod('embed/register', $params);
		if ($result->isSuccess())
		{
			$data = $result->getData();

			$this->setOption('embed_id', $data['embed_id']);
			$this->setOption('user_phone', $userPhone);
			if (!empty($params['user_firstname']))
			{
				$this->setOption('user_firstname', $params['user_firstname']);
			}
			if (!empty($params['user_lastname']))
			{
				$this->setOption('user_lastname', $params['user_lastname']);
			}
			if (!empty($params['user_email']))
			{
				$this->setOption('user_email', $params['user_email']);
			}

			if (!empty($data['confirmed']))
			{
				$this->setOption('is_confirmed', true);
			}
		}

		return $result;
	}

	/**
	 * @return array [
	 * 	'phone' => '',
	 *  'firstName' => '',
	 *  'lastName' => '',
	 *  'email' => ''
	 * ]
	 */
	public function getOwnerInfo()
	{
		return array(
			'phone' => $this->getOption('user_phone'),
			'firstName' => $this->getOption('user_firstname'),
			'lastName' => $this->getOption('user_lastname'),
			'email' => $this->getOption('user_email')
		);
	}

	/**
	 * @param array $fields
	 * @return Result
	 */
	public function confirmRegistration(array $fields)
	{
		$params = array(
			'embed_id' => $this->getOption('embed_id'),
			'confirm' => $fields['confirm']
		);
		$result = $this->callExternalMethod('embed/confirm', $params);

		if ($result->isSuccess())
		{
			$this->setOption('is_confirmed', true);
		}

		return $result;
	}

	public function sendConfirmationCode()
	{
		if ($this->isRegistered())
		{
			$ownerInfo = $this->getOwnerInfo();
			$result = $this->register(array(
				'user_phone' => $ownerInfo['phone'],
				'user_firstname' => $ownerInfo['firstName'],
				'user_lastname' => $ownerInfo['lastName'],
				'user_email' => $ownerInfo['email'],
			));
		}
		else
		{
			$result = new Result();
			$result->addError(new Error('Provider is not registered.'));
		}

		return $result;
	}

	public function getExternalManageUrl()
	{
		if ($this->isRegistered())
		{
			return 'https://sms.ru/?panel=login&action=login&embed_id='.$this->getOption('embed_id');
		}
		return 'https://sms.ru/?panel=login';
	}

	public function sendMessage(Message $message)
	{
		if (!$this->canUse())
		{
			$result = new SendMessageResult();
			$result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_SMSRU_CAN_USE_ERROR')));
			return $result;
		}

		$params = array(
			'to' => $message->getTo(),
			'text' => $message->getText(),
			'embed_id' => $this->getOption('embed_id')
		);

		if ($this->isDemo())
		{
			$params['to'] = $this->getDefaultSender();
		}

		if ($message->getFrom())
		{
			$params['from'] = $message->getFrom();
		}

		$result = new SendMessageResult();
		$apiResult = $this->callExternalMethod('sms/send', $params);
		if (!$apiResult->isSuccess())
		{
			$result->addErrors($apiResult->getErrors());
		}
		else
		{
			$resultData = $apiResult->getData();
			$smsData = current($resultData['sms']);

			if (isset($smsData['sms_id']))
			{
				$result->setMessageId($smsData['sms_id']);
			}

			if ((int)$smsData['status_code'] !== 100)
			{
				$result->addError(new Error($this->getErrorMessage($smsData['status_code'])));
			}
		}

		return $result;
	}

	public function getMessageStatus($messageId)
	{
		$result = new MessageStatusResult();
		$result->setMessageId($messageId);

		if (!$this->canUse())
		{
			$result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_SMSRU_CAN_USE_ERROR')));
			return $result;
		}

		$params = array(
			'sms_id' => $messageId,
			'embed_id' => $this->getOption('embed_id')
		);

		$apiResult = $this->callExternalMethod('sms/status', $params);
		if (!$apiResult->isSuccess())
		{
			$result->addErrors($apiResult->getErrors());
		}
		else
		{
			$resultData = $apiResult->getData();
			$smsData = current($resultData['sms']);

			$result->setStatusCode($smsData['status_code']);
			$result->setStatusText($smsData['status_text']);

			if ((int)$resultData['status_code'] !== 100)
			{
				$result->addError(new Error($this->getErrorMessage($smsData['status_code'])));
			}
		}

		return $result;
	}

	private function callExternalMethod($method, $params)
	{
		$url = 'https://sms.ru/'.$method;

		$httpClient = new HttpClient(array(
			"socketTimeout" => 10,
			"streamTimeout" => 30,
			"waitResponse" => true,
		));
		$httpClient->setHeader('User-Agent', 'Bitrix24');
		$httpClient->setCharset('UTF-8');

		$isUtf = Application::getInstance()->isUtfMode();

		if (!$isUtf)
		{
			$params = \Bitrix\Main\Text\Encoding::convertEncoding($params, SITE_CHARSET, 'UTF-8');
		}
		$params['json'] = 1;

		$result = new Result();
		$answer = array();

		if ($httpClient->query(HttpClient::HTTP_POST, $url, $params) && $httpClient->getStatus() == '200')
		{
			$answer = $this->parseExternalAnswer($httpClient->getResult());
		}

		$answerCode = isset($answer['status_code']) ? (int)$answer['status_code'] : 0;

		if ($answerCode !== 100)
		{
			$result->addError(new Error($this->getErrorMessage($answerCode)));
		}
		else
		{
			$result->setData($answer);
		}

		return $result;
	}

	private function parseExternalAnswer($httpResult)
	{
		try
		{
			$answer = Json::decode($httpResult);
		}
		catch (\Bitrix\Main\ArgumentException $e)
		{
			$data = explode(PHP_EOL, $httpResult);
			$code = (int)array_shift($data);
			$answer = $data;
			$answer['status_code'] = $code;
			$answer['status'] = $code === 100 ? 'OK' : 'ERROR';
		}

		if (!is_array($answer) && is_numeric($answer))
		{
			$answer = array(
				'status' => $answer === 100 ? 'OK' : 'ERROR',
				'status_code' => $answer
			);
		}

		return $answer;
	}

	private function getEmbedPartner()
	{
		return 'bitrix24';
	}

	private function getSecretKey()
	{
		return 'P46y811M84W3b4H18SmDpy9KG3pKG3Ok';
	}

	private function getEmbedHash($phoneNumber)
	{
		return md5($phoneNumber.$this->getSecretKey());
	}

	private function getErrorMessage($errorCode)
	{
		$message = Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_SMSRU_ERROR_'.$errorCode);
		return $message ?: Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_SMSRU_ERROR_OTHER');
	}
}