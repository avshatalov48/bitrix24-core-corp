<?php

namespace Bitrix\Dav\Profile\Response;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\IO\File;
use Bitrix\Main\Web\Json;

/**
 * Class Base
 * @package Bitrix\Dav\Profile\Response
 */
abstract class Base
{
	protected $body = '';
	protected $headers = [];
	protected $errors = [];
	protected $status = '';

	/**
	 * @return mixed Headers will set in response.
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return void
	 */
	public function setHeader($name, $value): void
	{
		$this->headers[$name] = $value;
	}

	/**
	 * @return mixed|string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param $status
	 *
	 * @return void
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * @return mixed Body will set in response.
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $content String to concatenate with existing body property.
	 * @return void
	 */
	public function setBody($content)
	{
		$this->body .= $content;
	}


	/**
	 * @return void
	 * @throws ArgumentException
	 */
	protected function setErrorBodyContent()
	{
		$this->setBody(Json::encode(array('error_messages' => $this->errors)));
	}

	/**
	 * @return mixed Is user with access token has access.
	 */
	abstract public function isAccess();

	/**
	 * @param string $templateUrl Path to template.
	 * @param array $params Params passing to template.
	 * @return mixed
	 */
	public static function render($templateUrl, $params)
	{
		$baseTemplate = File::getFileContents($templateUrl);
		$keys = array_map(static function ($element)
		{
			return '#' . $element . '#';
		}, array_keys($params));
		$values = array_values($params);
		return str_replace($keys, $values, $baseTemplate);
	}

}