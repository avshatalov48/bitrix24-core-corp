<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * Class for sending messages for the server of connectors.
 *
 * @see \Bitrix\ImConnectorServer\Connectors\Facebook\Lib::delUserActive
 * @method Result delUserActive($idUser)
 * @see \Bitrix\ImConnectorServer\Connectors\Facebook\Lib::delPageActive
 * @method Result delPageActive($idPage, $local = false)
 * @see \Bitrix\ImConnectorServer\Connectors\Facebook\Lib::authorizationPage
 * @method Result authorizationPage($idPage)
 * @see \Bitrix\ImConnectorServer\Connectors\Connector::deleteConnector
 * @method Result deleteConnector($sendDeactivateConnector = false)
 * @see \Bitrix\ImConnectorServer\Connectors\Facebook\LibInstagram::getAuthorizationInformation
 * @method Result getAuthorizationInformation($returnUrl = '')
 * @package Bitrix\ImConnector
 * @final
 * @internal
 *
 * Dynamic methods:
 *
 * @method register(array $data = []): \Bitrix\ImConnector\Result
 * @method delete(array $data = []): \Bitrix\ImConnector\Result
 *
 */
final class Output
{
	/*** @var Result */
	protected $result;

	/**
	 * @var Provider\ImConnectorServer\Output|Provider\LiveChat\Output|Provider\Network\Output|Provider\Custom\Output
	 */
	protected $provider;

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 * @param bool $ignoreDeactivatedConnector
	 */
	function __construct($connector, $line = false, $ignoreDeactivatedConnector = false)
	{
		$this->result = new Result();

		if(
			$connector !== 'all'
		&& (
				!empty($ignoreDeactivatedConnector) ||
				Connector::isConnector($connector)
			)
		)
		{
			$provider = Provider::getProviderForConnectorOutput($connector, $line);

			if($provider->isSuccess())
			{
				$this->provider = $provider->getResult();
			}
			else
			{
				$this->result->addErrors($provider->getErrors());
			}
		}
		elseif($connector == 'all')
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_GENERAL_REQUEST_NOT_DYNAMIC_METHOD'), Library::ERROR_IMCONNECTOR_PROVIDER_GENERAL_REQUEST_NOT_DYNAMIC_METHOD, __METHOD__, $connector));
		}
		else
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_NO_ACTIVE_CONNECTOR'), Library::ERROR_IMCONNECTOR_PROVIDER_NO_ACTIVE_CONNECTOR, __METHOD__, $connector));
		}
	}

	/**
	 * Magic method for handling dynamic methods.
	 *
	 * @param string $name The name of the called method.
	 * @param array $arguments The set of parameters passed to the method.
	 * @return Result
	 */
	public function __call($name, $arguments): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			$result = $this->provider->call($name, $arguments);
		}

		return $result;
	}

	/**
	 * Static magic method.
	 * Caching is used for a number of methods.
	 *
	 * @param string $name The name of the called method.
	 * @param array $arguments The set of parameters passed to the method.
	 * @return Result
	 */
	public static function __callStatic($name, $arguments)
	{
		$result = new Result();
		$resultsCall = [];

		$providers = Provider::getAllProviderForAllOutput();

		foreach ($providers as $provider)
		{
			$resultCall = $provider->call($name, $arguments);

			if(!empty($resultCall->getData()))
			{
				$resultsCall = array_merge($resultsCall, $resultCall->getData());
			}

			if(!$resultCall->isSuccess())
			{
				$result->addErrors($resultCall->getErrors());
			}
		}

		$result->setData($resultsCall);

		return $result;
	}

	/**
	 * The removal of the open line of this website from the remote server connectors.
	 *
	 * @param string $lineId ID of the deleted lines.
	 * @return Result
	 */
	public static function deleteLine($lineId): Result
	{
		Status::deleteAll($lineId);

		return self::__callStatic('deleteLine', [$lineId]);
	}
}