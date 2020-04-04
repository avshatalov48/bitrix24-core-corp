<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Text\Encoding;
use \Bitrix\ImConnector\Input\Router;

/**
 * Class for reception of messages from the server of connectors at the initiative of the server.
 * @package Bitrix\ImConnector
 */
class Input
{
	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';

	private $type = '';
	private $params = array();

	/** @var int Version of the client module of an exchange */
	private $version = 1;

	private $result;

	/**
	 * The method requests a hash of a license key.
	 *
	 * @param $type
	 * @param $str
	 * @return string
	 */
	public static function requestSign($type, $str)
	{
		if ($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			return md5($str.md5($LICENSE_KEY));
		}
	}

	/**
	 * Input constructor.
	 * @param array $params
	 */
	function __construct(array $params)
	{
		$this->result = new Result();

		$this->authorize($params);

		$params["DATA"] = unserialize(base64_decode($params["DATA"]));

		$params = Converter::convertEmptyInStub($params);

		$params = Encoding::convertEncoding($params, 'UTF-8', SITE_CHARSET);

		if(!is_array($params['DATA']))
			$params['DATA'] = array();

		$this->params = $params;
	}

	/**
	 * Authorization of the server on the client.
	 *
	 * @param $params
	 * @return bool
	 */
	private function authorize($params)
	{
		if(strlen($params["BX_HASH"]) <= 0)
		{
			$this->result->addError(new Error('Hash is empty', 'HASH_EMPTY', __METHOD__));

			return false;
		}

		$hash = $params["BX_HASH"];
		unset($params["BX_HASH"]);

		if(
			$params['BX_TYPE'] == self::TYPE_BITRIX24 && self::requestSign($params['BX_TYPE'], md5(implode("|", $params)."|".BX24_HOST_NAME)) === $hash ||
			$params['BX_TYPE'] == self::TYPE_CP && self::requestSign($params['BX_TYPE'], md5(implode("|", $params))) === $hash
		)
		{
			$this->type = $params['BX_TYPE'];
		}
		else
		{
			$this->result->addError(new Error('Licence key is invalid', 'LICENCE_ERROR', __METHOD__));

			return false;
		}

		$this->version = $params['BX_VERSION'];

		return true;
	}

	/**
	 * Processing of the connectors accepted data from the server.
	 *
	 * @return Result
	 */
	public function reception()
	{
		try
		{
			if(!is_array($this->params['DATA']))
			{
				$this->params['DATA'] = array($this->params['DATA']);
			}

			$result = Router::receiving($this->params['BX_COMMAND'], $this->params['CONNECTOR'], $this->params['LINE'], $this->params['DATA']);
			if(!is_object($result))
			{
				if(!is_array($result))
					$this->result->setResult($result);
				else
					$this->result->setData($result);
			}
			else
			{
				if(!$result->isSuccess())
					$this->result->addErrors($result->getErrors());

				$this->result->setData($result->getData());
			}
		}
		catch (\Exception $e)
		{
			$this->result->addError(new Error($e->getMessage(), $e->getCode(), __METHOD__));
		}

		return $this->result;
	}
}