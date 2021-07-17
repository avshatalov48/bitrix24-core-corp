<?php
namespace Bitrix\ImConnector;

use Bitrix\ImConnector\Connectors\Notifications;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\EntitySelector\BaseProvider;

Library::loadMessages();

class Provider
{
	protected const PROVIDER = [
		'imconnectorserver' =>
			[
				'whatsappbytwilio',
				'avito',
				'viber',
				'telegrambot',
				'imessage',
				'wechat',
				'yandex',
				'vkgroup',
				'ok',
				'olx',
				'facebook',
				'facebookcomments',
				'fbinstagram',
				Library::ID_FBINSTAGRAMDIRECT_CONNECTOR,
				'botframework'
			],
		'livechat' =>
			[
				'livechat'
			],
		'network' =>
			[
				'network'
			],
		'notifications' =>
			[
				Notifications::CONNECTOR_ID
			],
	];

	protected static $loadProvider;

	protected static function loadProvider()
	{
		if(empty(self::$loadProvider))
		{
			$provider = self::PROVIDER;

			$customConnectors = CustomConnectors::getListConnectorId();

			if($customConnectors)
			{
				$provider['custom'] = $customConnectors;
			}

			self::$loadProvider = $provider;
		}
	}

	/**
	 * @return string[][]
	 */
	protected static function getAllIdsProvider(): array
	{
		self::loadProvider();

		return self::$loadProvider;
	}

	/**
	 * @param string $connector
	 * @return string
	 */
	protected static function getIdProvider(string $connector): string
	{
		$result = '';

		foreach (Provider::getAllIdsProvider() as $provider=>$providerConnectors)
		{
			foreach ($providerConnectors as $providerConnector)
			{
				if($providerConnector === $connector)
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
			if(empty(Provider::getAllIdsProvider()[$idProvider]))
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

				if(class_exists($nameClassProvider))
				{
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

		if(empty($idProvider))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_NO_CORRECT_PROVIDER'), Library::ERROR_IMCONNECTOR_NO_CORRECT_PROVIDER, __METHOD__, $connector));
		}
		else
		{
			$nameClassProvider = 'Bitrix\\ImConnector\\Provider\\' . $idProvider . '\\' . $direction;

			if(class_exists($nameClassProvider))
			{
				$provider = new $nameClassProvider(...$arguments);

				$result->setResult($provider);
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_COULD_NOT_GET_PROVIDER_OBJECT'), Library::ERROR_IMCONNECTOR_COULD_NOT_GET_PROVIDER_OBJECT, __METHOD__, $connector));
			}
		}

		return $result;
	}

	/**
	 * @return Provider\ImConnectorServer\Output []
	 * @return Provider\LiveChat\Output []
	 * @return Provider\Network\Output []
	 * @return Provider\Custom\Output []
	 */
	public static function getAllProviderForAllOutput(): array
	{
		$result = [];

		foreach (self::getAllIdsProvider() as $idProvider=>$connectorsProvider)
		{
			$provider = self::getProviderForAll($idProvider, 'output');

			if($provider->isSuccess())
			{
				$result[] = $provider->getResult();
			}
		}

		return $result;
	}

	/**
	 * @param $connector
	 * @param false $line
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
			$params,
			'input'
		);
	}
}