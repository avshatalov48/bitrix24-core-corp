<?php

namespace Bitrix\Crm\Integration\Sms\Provider;

use Bitrix\Crm\Integration\Sms\MessageStatusResult;
use Bitrix\Crm\Integration\Sms\SendMessageResult;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Sms\Message;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class Twilio extends BaseInternal
{
	public function getId()
	{
		return 'twilio';
	}

	public function getName()
	{
		return Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_TWILIO_NAME');
	}

	public function getShortName()
	{
		return 'twilio.com';
	}

	public function isDemo()
	{
		return false;
	}

	public function isRegistered()
	{
		return ($this->getOption('account_sid') !== null);
	}

	public function canUse()
	{
		return $this->isRegistered();
	}

	public function getSenderList()
	{
		$sid = $this->getOption('account_sid');
		$senders = array();

		if (!$sid)
		{
			return $senders;
		}

		$result = $this->callExternalMethod(
			HttpClient::HTTP_GET,
			'Accounts/'.$sid.'/IncomingPhoneNumbers'
		);
		if (!$result->isSuccess())
		{
			return $senders;
		}
		else
		{
			$resultData = $result->getData();
			if (isset($resultData['incoming_phone_numbers']) && is_array($resultData['incoming_phone_numbers']))
			{
				foreach ($resultData['incoming_phone_numbers'] as $phoneNumber)
				{
					if ($phoneNumber['capabilities']['sms'] === true)
					{
						$senders[] = $phoneNumber['phone_number'];
					}
				}
			}
		}

		return $senders;
	}

	public function getDefaultSender()
	{
		$sender = $this->getOption('default_sender');
		if (!$sender)
		{
			$senders = $this->getSenderList();
			if (count($senders) > 0)
			{
				$sender = $senders[0];
				$this->setDefaultSender($sender);
			}
		}
		return $sender;
	}

	public function setDefaultSender($sender)
	{
		$sender = (string)$sender;
		$this->setOption('default_sender', $sender);
		return $this;
	}

	public function register(array $fields)
	{
		$sid = (string)$fields['account_sid'];
		$token = (string)$fields['account_token'];

		$result = $this->callExternalMethod(
			HttpClient::HTTP_GET,
			'Accounts/'.$sid, array(), $sid, $token
		);
		if ($result->isSuccess())
		{
			$data = $result->getData();

			if ($data['status'] !== 'active')
			{
				$result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_TWILIO_ERROR_ACCOUNT_INACTIVE')));
			}
			else
			{
				$this->setOption('account_sid', $sid);
				$this->setOption('account_token', $token);
				$this->setOption('account_friendly_name', $data['friendly_name']);
			}
		}

		return $result;
	}

	public function getOwnerInfo()
	{
		return array(
			'sid' => $this->getOption('account_sid'),
			'friendly_name' => $this->getOption('account_friendly_name')
		);
	}

	public function getExternalManageUrl()
	{
		return 'https://www.twilio.com/console';
	}

	public function sendMessage(Message $message)
	{
		$sid = $this->getOption('account_sid');

		if (!$sid)
		{
			$result = new SendMessageResult();
			$result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_TWILIO_CAN_USE_ERROR')));
			return $result;
		}

		$params = array(
			'To' => $message->getTo(),
			'Body' => $message->getText(),
			'From' => $message->getFrom()
		);

		if (!$params['From'])
		{
			$params['From'] = $this->getDefaultSender();
		}

		if (is_string($params['From']) && strlen($params['From']) === 34) //unique id of the Messaging Service
		{
			$params['MessagingServiceSid'] = $params['From'];
			unset($params['From']);
		}

		$result = new SendMessageResult();
		$apiResult = $this->callExternalMethod(
			HttpClient::HTTP_POST,
			'Accounts/'.$sid.'/Messages/',
			$params
		);
		if (!$apiResult->isSuccess())
		{
			$result->addErrors($apiResult->getErrors());
		}
		else
		{
			$resultData = $apiResult->getData();
			if (isset($resultData['sid']))
			{
				$result->setMessageId($resultData['sid']);
			}
		}

		return $result;
	}

	public function getMessageStatus($messageId)
	{
		$result = new MessageStatusResult();
		$result->setMessageId($messageId);

		$sid = $this->getOption('account_sid');
		if (!$sid)
		{
			$result->addError(new Error(Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_TWILIO_CAN_USE_ERROR')));
			return $result;
		}

		$apiResult = $this->callExternalMethod(
			HttpClient::HTTP_GET,
			'Accounts/'.$sid.'/Messages/'.$messageId
		);
		if (!$apiResult->isSuccess())
		{
			$result->addErrors($apiResult->getErrors());
		}
		else
		{
			$resultData = $apiResult->getData();
			$result->setStatusCode($resultData['status']);
			$result->setStatusText($resultData['status']);
			if (in_array($resultData['status'],
				array('accepted', 'queued', 'sending', 'sent', 'delivered', 'undelivered', 'failed')))
			{
				$result->setStatusText(
					Loc::getMessage('CRM_INTEGRATION_SMS_PROVIDER_TWILIO_MESSAGE_STATUS_'.strtoupper($resultData['status']))
				);
			}
		}

		return $result;
	}

	private function callExternalMethod($httpMethod, $apiMethod, array $params = array(), $sid = null, $token = null)
	{
		$url = 'https://api.twilio.com/2010-04-01/'.$apiMethod.'.json';

		$httpClient = new HttpClient(array(
			"socketTimeout" => 10,
			"streamTimeout" => 30,
			"waitResponse" => true,
		));
		$httpClient->setHeader('User-Agent', 'Bitrix24');
		$httpClient->setCharset('UTF-8');

		if (!$sid || !$token)
		{
			$sid = $this->getOption('account_sid');
			$token = $this->getOption('account_token');
		}

		$httpClient->setAuthorization($sid, $token);

		$isUtf = Application::getInstance()->isUtfMode();

		if (!$isUtf)
		{
			$params = \Bitrix\Main\Text\Encoding::convertEncoding($params, SITE_CHARSET, 'UTF-8');
		}

		$result = new Result();
		$answer = array();

		if ($httpClient->query($httpMethod, $url, $params))
		{
			try
			{
				$answer = Json::decode($httpClient->getResult());
			}
			catch (ArgumentException $e)
			{
				$result->addError(new Error('Service error'));
			}

			$httpStatus = $httpClient->getStatus();
			if ($httpStatus >= 400)
			{
				if (isset($answer['message']) && isset($answer['code']))
				{
					$result->addError(new Error($answer['message'], $answer['code']));
				}
				else
				{
					$result->addError(new Error('Service error (HTTP Status '.$httpStatus.')'));
				}
			}
		}

		if ($result->isSuccess())
		{
			$result->setData($answer);
		}

		return $result;
	}
}