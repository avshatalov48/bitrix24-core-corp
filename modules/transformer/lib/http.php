<?php
namespace Bitrix\Transformer;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Transformer\Entity\CommandTable;

class Http
{
	const MODULE_ID = 'transformer';

	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'BOX';
	const VERSION = 1;

	const BACK_URL = '/bitrix/tools/transformer_result.php';

	const CONNECTION_ERROR = 'no connection with controller';

	public const CLOUD_CONVERTER_URL = 'https://transformer-de.bitrix.info/bitrix/tools/transformercontroller/add_queue.php';

	private $controllerUrl;
	private $licenceCode = '';
	private $domain = '';
	private $type = '';

	public function __construct()
	{
		if(defined('TRANSFORMER_CONTROLLER_URL'))
		{
			$this->controllerUrl = TRANSFORMER_CONTROLLER_URL;
		}
		else
		{
			$optionsControllerUrl = Option::get(self::MODULE_ID, 'transformer_controller_url', static::CLOUD_CONVERTER_URL);
			if(!empty($optionsControllerUrl))
			{
				$uri = new Uri($optionsControllerUrl);
				if($uri->getHost())
				{
					$this->controllerUrl = $uri->getLocator();
				}
			}
		}

		if(defined('BX24_HOST_NAME'))
		{
			$this->licenceCode = BX24_HOST_NAME;
		}
		else
		{
			require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client.php');
			$this->licenceCode = md5('BITRIX'.\CUpdateClient::GetLicenseKey().'LICENCE');
		}
		$this->type = self::getPortalType();
		$this->domain = self::getServerAddress();
	}

	/**
	 * @return string
	 */
	private static function getPortalType()
	{
		if(defined('BX24_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}
		else
		{
			$type = self::TYPE_CP;
		}

		return $type;
	}

	/**
	 * @return string
	 */
	public static function getServerAddress()
	{
		$publicUrl = \Bitrix\Main\Config\Option::get(self::MODULE_ID, 'portal_url');

		if(!empty($publicUrl))
		{
			return $publicUrl;
		}

		return UrlManager::getInstance()->getHostUrl();
	}


	/**
	 * Sign string with license or bx_sign.
	 *
	 * @param string $type Type of the license.
	 * @param string $str String to sign.
	 * @return string
	 */
	private static function requestSign($type, $str)
	{
		if($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			/** @var string $LICENSE_KEY */
			include($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php');
			return md5($str.md5($LICENSE_KEY));
		}
	}

	/**
	 * Add necessary parameters to post and send it to the controller.
	 *
	 * @param string $command Command to be executed.
	 * @param string $guid GUID of the command to form back url.
	 * @param array $params Parameters of the command.
	 * @return array|bool|mixed
	 * @throws ArgumentNullException
	 * @throws ArgumentTypeException
	 */
	public function query($command, $guid, $params = array())
	{
		if($command == '')
		{
			throw new ArgumentNullException('command');
		}
		if(!is_array($params))
		{
			throw new ArgumentTypeException('params', 'array');
		}

		if(!$this->shouldWeSend())
		{
			return [
				'success' => false,
				'result' => [
					'code' => Command::ERROR_CONNECTION_COUNT
				]
			];
		}

		if($params['file'])
		{
			$uri = new \Bitrix\Main\Web\Uri($params['file']);
			if($uri->getHost() == '')
			{
				$params['file'] = (new Uri($this->domain.$params['file']))->getLocator();
			}
		}

		$params['back_url'] = $this->getBackUrl($guid);

		$post = array('command' => $command, 'params' => $params);

		if($params['queue'])
		{
			$post['QUEUE'] = $params['queue'];
		}
		$post['BX_LICENCE'] = $this->licenceCode;
		$post['BX_DOMAIN'] = $this->domain;
		$post['BX_TYPE'] = $this->type;
		$post['BX_VERSION'] = self::VERSION;
		$post = \Bitrix\Main\Text\Encoding::convertEncoding($post, SITE_CHARSET, 'UTF-8');
		$post['BX_HASH'] = self::requestSign($this->type, md5(implode('|', $post)));

		Log::write('COMMAND: '.print_r($post, 1));

		$httpClient = new \Bitrix\Main\Web\HttpClient(array(
			'socketTimeout' => Option::get(self::MODULE_ID, 'connection_time', 8),
			'streamTimeout' => Option::get(self::MODULE_ID, 'stream_time', 8),
			'waitResponse' => true,
		));
		$httpClient->setHeader('User-Agent', 'Bitrix Transformer Client');
		$httpClient->setHeader('Referer', $this->domain);
		$response = $httpClient->post($this->controllerUrl, $post);

		Log::write('RESPONSE: '.$response);

		if($response === false)
		{
			return [
				'success' => false,
				'result' => [
					'code' => Command::ERROR_CONNECTION
				]
			];
		}
		try
		{
			return Json::decode($response);
		}
		catch(ArgumentException $e)
		{
			return [
				'success' => false,
				'result' => [
					'code' => Command::ERROR_CONNECTION_RESPONSE,
				]
			];
		}
	}

	/**
	 * Add 'id' parameter with real id of the command.
	 *
	 * @param int $id Id to find command from CommandTable on callback.
	 * @return string
	 */
	private function getBackUrl($id)
	{
		$uri = new Uri(self::BACK_URL);
		$uri->addParams(array('id' => $id));
		if($uri->getHost() == '')
		{
			$uri = (new Uri($this->domain.$uri->getPathQuery()))->getLocator();
		}
		return $uri;
	}

	private function shouldWeSend()
	{
		$timeSearchConnectionErrors = 1800;
		$errorCountForStopSend = 5;

		$errorCount = CommandTable::getList(array(
			'select' => array('CNT'),
			'filter' => array('=ERROR' => self::CONNECTION_ERROR, '>UPDATE_TIME' => DateTime::createFromTimestamp(time() - $timeSearchConnectionErrors)),
			'runtime' => array(
				new ExpressionField('CNT', 'COUNT(*)')
			),
		))->fetch();

		return ($errorCount['CNT'] < $errorCountForStopSend);
	}
}