<?php
namespace Bitrix\ImConnector\Provider\ImConnectorServer;

use Bitrix\Main\Text\Encoding;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Converter;
use Bitrix\ImConnector\Provider\Base;

class Input extends Base\Input
{
	/** @var int Version of the client module of an exchange */
	protected $version = 1;
	protected $type = '';

	/**
	 * The method requests a hash of a license key.
	 *
	 * @param $type
	 * @param $str
	 * @return string
	 */
	protected static function requestSign($type, $str): string
	{
		if (
			$type == self::TYPE_BITRIX24
			&& function_exists('bx_sign')
		)
		{
			$result = (string)bx_sign($str);
		}
		else
		{
			$LICENSE_KEY = '';
			include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/license_key.php');
			$result = md5($str.md5($LICENSE_KEY));
		}

		return $result;
	}

	/**
	 * Authorization of the server on the client.
	 *
	 * @param $params
	 * @return Result
	 */
	protected function authorize($params): Result
	{
		$result = new Result();

		if($params['BX_HASH'] === '')
		{
			$result->addError(new Error('Hash is empty', 'HASH_EMPTY', __METHOD__));
		}
		else
		{
			$hash = $params['BX_HASH'];
			unset($params['BX_HASH']);

			if(
				!(
					$params['BX_TYPE'] === self::TYPE_BITRIX24
					&& self::requestSign($params['BX_TYPE'], md5(implode('|', $params) . '|' . BX24_HOST_NAME)) === $hash
					|| $params['BX_TYPE'] === self::TYPE_CP
					&& self::requestSign($params['BX_TYPE'], md5(implode('|', $params))) === $hash
				)
			)
			{
				$result->addError(new Error('Licence key is invalid', 'LICENCE_ERROR', __METHOD__));
			}
		}

		return $result;
	}

	/**
	 * Input constructor.
	 * @param array $params
	 */
	public function __construct(array $params)
	{
		parent::__construct($params);

		$resultAuthorize = $this->authorize($params);

		if($resultAuthorize->isSuccess())
		{
			$params['DATA'] = unserialize(base64_decode($params['DATA']), ['allowed_classes' => false]);
			$params = Converter::convertEmptyInStub($params);
			$params = Encoding::convertEncoding($params, 'UTF-8', SITE_CHARSET);
			if(!is_array($params['DATA']))
			{
				$params['DATA'] = [];
			}

			$this->params = $params;
			$this->version = $params['BX_VERSION'];
			$this->type = $params['BX_TYPE'];

			$this->command = $this->params['BX_COMMAND'];
			$this->connector = $this->params['CONNECTOR'];
			$this->line = $this->params['LINE'];
			$this->data = $this->params['DATA'];
		}
		else
		{
			$this->result->addErrors($resultAuthorize->getErrors());
		}
	}

	/**
	 * @return Result
	 */
	protected function receivingMessage(): Result
	{
		$result = parent::receivingMessage();
		$result->setData([]);

		return $result;
	}
}
