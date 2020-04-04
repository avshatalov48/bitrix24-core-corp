<?php
namespace Bitrix\Transformer;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Transformer\Entity\CommandTable;

class Http
{
	const MODULE_ID = 'transformer';

	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';
	const VERSION = 1;

	const BACK_URL = '/bitrix/tools/transformer_result.php';

	const CONNECTION_ERROR = 'no connection with controller';

	private $controllerUrl = 'https://transformer.bitrix.info/json/add_queue.php';
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
			$optionsControllerUrl = Option::get(self::MODULE_ID, 'transformer_controller_url', 'https://transformer-de.bitrix.info/json/add_queue.php');
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

		if($publicUrl != '')
			return $publicUrl;
		else
			return (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps() ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']);
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
		if(strlen($command) <= 0)
		{
			throw new ArgumentNullException('command');
		}
		if(!is_array($params))
		{
			throw new ArgumentTypeException('params', 'array');
		}

		if(!$this->shouldWeSend())
		{
			return array('success' => false, 'result' => array(
				'msg' => 'Too much connection errors', 'code' => 'FAIL_CONNECT')
			);
		}

		if($params['file'])
		{
			$uri = new \Bitrix\Main\Web\Uri($params['file']);
			if(strlen($uri->getHost()) <= 0)
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
			return array('success' => false, 'result' => array(
				'msg' => self::CONNECTION_ERROR, 'code' => 'FAIL_CONNECT')
			);
		}
		try
		{
			return Json::decode($response);
		}
		catch(ArgumentException $e)
		{
			return array('success' => false, 'result' => array(
				'msg' => 'wrong response from controller: '.$response, 'code' => 'FAIL_RESPONSE')
			);
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
		if(strlen($uri->getHost()) <= 0)
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