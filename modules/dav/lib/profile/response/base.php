<?php

namespace Bitrix\Dav\Profile\Response;

use Bitrix\Main\IO\File;
use Bitrix\Main\Web\Json;

/**
 * Class Base
 * @package Bitrix\Dav\Profile\Response
 */
abstract class Base
{
	protected $body = '';
	protected $headers = array();
	protected $errors = array();

	/**
	 * @return mixed Headers will set in response.
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @param string $header Header string for add to existing response headers array.
	 * @return void
	 */
	public function setHeader($header)
	{
		$this->headers[] = $header;
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
		$keys = array_map(function ($element)
		{
			return '#' . $element . '#';
		}, array_keys($params));
		$values = array_values($params);
		return str_replace($keys, $values, $baseTemplate);
	}

}