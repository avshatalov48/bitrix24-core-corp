<?php
namespace Bitrix\ImConnector;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Library::loadMessages();

class Provider
{
	protected const PROVIDER = [
		'imconnectorserver' =>
			[
				'imconnectorserver',//common for all
				Library::ID_WHATSAPPBYTWILIO_CONNECTOR,
				Library::ID_AVITO_CONNECTOR,
				Library::ID_VIBER_CONNECTOR,
				Library::ID_TELEGRAMBOT_CONNECTOR,
				Library::ID_IMESSAGE_CONNECTOR,
				Library::ID_WECHAT_CONNECTOR,
				Library::ID_VKGROUP_CONNECTOR,
				Library::ID_OK_CONNECTOR,
				Library::ID_OLX_CONNECTOR,
				Library::ID_FB_MESSAGES_CONNECTOR,
				Library::ID_FB_COMMENTS_CONNECTOR,
				Library::ID_FBINSTAGRAM_CONNECTOR,
				Library::ID_FBINSTAGRAMDIRECT_CONNECTOR,
			],
		'livechat' => [Library::ID_LIVE_CHAT_CONNECTOR],
		'network' => [Library::ID_NETWORK_CONNECTOR],
		'messageservice' => [Library::ID_EDNA_WHATSAPP_CONNECTOR]
	];

	/** @var string[][] */
	protected static $loadProvider;

	/**
	 * @return string[][]
	 */
	protected static function getAllIdsProvider(): array
	{
		if (empty(self::$loadProvider))
		{
			$provider = self::PROVIDER;

			if (Loader::includeModule('notifications'))
			{
				$provider['notifications'] = [Library::ID_NOTIFICATIONS_CONNECTOR];
			}

			$customConnectors = CustomConnectors::getListConnectorId();

			if ($customConnectors)
			{
				$provider['custom'] = $customConnectors;
			}

			self::$loadProvider = $provider;
		}

		return self::$loadProvider;
	}

	/**
	 * @param string $connector
	 * @return string
	 */
	protected static function getIdProvider(string $connector): string
	{
		$result = '';

		foreach (self::getAllIdsProvider() as $provider=>$providerConnectors)
		{
			foreach ($providerConnectors as $providerConnector)
			{
				if ($providerConnector === $connector)
				{
					$result = $provider;
					break 2;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $direction
	 * @return string
	 */
	protected static function validateDirection(string $direction): string
	{
		if ($direction === 'output')
		{
			$direction = 'Output';
		}
		elseif ($direction === 'input')
		{
			$direction = 'Input';
		}
		else
		{
			$direction = '';
		}

		return $direction;
	}

	/**
	 * @param $idProvider
	 * @param string $direction
	 * @return Result
	 */
	protected static function getProviderForAll($idProvider, string $direction): Result
	{
		$result = new Result();

		$direction = self::validateDirection($direction);

		if(!empty($direction))
		{
			if (empty(self::getAllIdsProvider()[$idProvider]))
			{
				$result->addError(new Error(
					Loc::getMessage('IMCONNECTOR_ERROR_NO_CORRECT_PROVIDER'),
					Library::ERROR_IMCONNECTOR_NO_CORRECT_PROVIDER,
					__METHOD__,
					$idProvider
				));
			}
			else
			{
				$nameClassProvider = 'Bitrix\\ImConnector\\Provider\\' . $idProvider . '\\' . $direction;

				if (class_exists($nameClassProvider))
				{
					/** @var Provider\Base\Input|Provider\Base\Output $provider */
					$provider = new $nameClassProvider('all');

					$result->setResult($provider);
				}
				else
				{
					$result->addError(new Error(
						Loc::getMessage('IMCONNECTOR_ERROR_COULD_NOT_GET_PROVIDER_OBJECT'),
						Library::ERROR_IMCONNECTOR_COULD_NOT_GET_PROVIDER_OBJECT,
						__METHOD__,
						$nameClassProvider
					));
				}
			}
		}

		return $result;
	}

	/**
	 * @param $connector
	 * @param $arguments
	 * @param string $direction
	 * @return Result
	 */
	protected static function getProviderForConnector($connector, $arguments, string $direction): Result
	{
		$result = new Result();

		$direction = self::validateDirection($direction);

		$idProvider = self::getIdProvider(Connector::getConnectorRealId($connector));

		if (empty($idProvider))
		{
			return $result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_ERROR_NO_CORRECT_PROVIDER'),
				Library::ERROR_IMCONNECTOR_NO_CORRECT_PROVIDER,
				__METHOD__,
				$connector
			));
		}

		$nameClassProvider = 'Bitrix\\ImConnector\\Provider\\' . $idProvider . '\\' . $direction;

		if (class_exists($nameClassProvider))
		{
			/** @var Provider\Base\Input|Provider\Base\Output $provider */
			$provider = new $nameClassProvider(...$arguments);

			$result->setResult($provider);
		}
		else
		{
			$result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_ERROR_COULD_NOT_GET_PROVIDER_OBJECT'),
				Library::ERROR_IMCONNECTOR_COULD_NOT_GET_PROVIDER_OBJECT,
				__METHOD__,
				$connector
			));
		}

		return $result;
	}

	/**
	 * @return Provider\ImConnectorServer\Output[]
	 * @return Provider\LiveChat\Output[]
	 * @return Provider\Network\Output[]
	 * @return Provider\Custom\Output[]
	 */
	public static function getAllProviderForAllOutput(): array
	{
		$result = [];

		foreach (self::getAllIdsProvider() as $idProvider => $connectorsProvider)
		{
			$provider = self::getProviderForAll($idProvider, 'output');

			if ($provider->isSuccess())
			{
				$result[] = $provider->getResult();
			}
		}

		return $result;
	}

	/**
	 * @param $connector
	 * @param string|false $line
	 * @return Result
	 */
	public static function getProviderForConnectorOutput($connector, $line = false): Result
	{
		return self::getProviderForConnector(
			$connector,
			[
				$connector,
				$line
			],
			'output'
		);
	}


	/**
	 * @param string $connector
	 * @param array $params
	 * @return Result
	 */
	public static function getProviderForConnectorInput(string $connector, array $params): Result
	{
		return self::getProviderForConnector(
			$connector,
			[$params],
			'input'
		);
	}
}
