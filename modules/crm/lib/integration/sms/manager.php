<?php

namespace Bitrix\Crm\Integration\Sms;

use Bitrix\Crm\Integration\Sms\Provider\BaseInternal;
use Bitrix\Main;

class Manager
{
	private static $providers;

	/**
	 * @return Provider\Base[] List of providers.
	 */
	public static function getProviders()
	{
		if (self::$providers === null)
		{
			if (Main\Application::getInstance()->getContext()->getLanguage() === 'ru')
			{
				self::$providers = array(
					new Provider\SmsRu(),
					new Provider\Twilio()
				);
			}
			else
			{
				self::$providers = array(
					new Provider\Twilio(),
					new Provider\SmsRu()
				);
			}
		}
		return self::$providers;
	}

	/**
	 * @return array Simple list of providers, array(provider_id => provider_name)
	 */
	public static function getProviderSelectList()
	{
		$select = array();
		foreach (static::getProviders() as $provider)
		{
			$select[$provider->getId()] = $provider->getName();
		}
		return $select;
	}

	/**
	 * @return array Provider information.
	 */
	public static function getProviderInfoList()
	{
		$info = array();
		foreach (static::getProviders() as $provider)
		{
			$info[] = array(
				'id' => $provider->getId(),
				'type' => $provider->getType(),
				'name' => $provider->getName(),
				'shortName' => $provider->getShortName(),
				'canUse' => $provider->canUse(),
				'manageUrl' => $provider->getManageUrl(),
			);
		}
		return $info;
	}

	/**
	 * @param $id
	 * @return Provider\Base|null Provider instance.
	 */
	public static function getProviderById($id)
	{
		foreach (static::getProviders() as $provider)
		{
			if ($provider->getId() === $id)
			{
				return $provider;
			}
		}
		return null;
	}

	/**
	 * Get default SMS provider.
	 * @return Provider\Base
	 */
	public static function getDefaultProvider()
	{
		$providers = static::getProviders();
		return $providers[0];
	}

	/**
	 * @return bool Can use SMS transport.
	 */
	public static function canUse()
	{
		return static::getUsableProvider() !== null;
	}

	/**
	 * @return string Manage url
	 */
	public static function getManageUrl()
	{
		/** @var BaseInternal $defaultProvider */
		$defaultProvider = static::getDefaultProvider();
		return $defaultProvider->getManageUrl();
	}

	/**
	 * Get first Provider which is ready to use it.
	 * @return Provider\Base|null Provider instance.
	 */
	public static function getUsableProvider()
	{
		foreach (static::getProviders() as $provider)
		{
			if ($provider->canUse())
			{
				return $provider;
			}
		}
		return null;
	}

	/**
	 * @param array $messageFields
	 * @param Provider\Base|null $provider
	 * @return Message
	 */
	public static function createMessage(array $messageFields, Provider\Base $provider = null)
	{
		if (!$provider)
		{
			$provider = static::getUsableProvider();
		}

		$message = new Message($provider);

		if (isset($messageFields['text']))
		{
			$message->setText($messageFields['text']);
		}

		if (isset($messageFields['from']))
		{
			$message->setFrom($messageFields['from']);
		}

		if (isset($messageFields['to']))
		{
			$message->setTo($messageFields['to']);
		}

		return $message;
	}

	/**
	 * @param array $messageFields
	 * @param Provider\Base|null $provider
	 * @return SendMessageResult
	 */
	public static function sendMessage(array $messageFields, Provider\Base $provider = null)
	{
		$message = static::createMessage($messageFields, $provider);
		return $message->send();
	}
}