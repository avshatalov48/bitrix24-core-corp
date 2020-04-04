<?php

namespace Bitrix\Disk\Rest\Service;

use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\Internals\Error\IErrorable;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Rest\RestException;

abstract class Base implements IErrorable
{
	const ERROR_REQUIRED_PARAMETER = 'DISK_BASE_SERVICE_22001';

	/** @var ErrorCollection */
	protected $errorCollection;
	/** @var string */
	protected $methodName;
	/** @var array */
	protected $methodParams;
	/** @var array */
	protected $params;
	/** @var string */
	protected $start;
	/** @var \CRestServer */
	protected $restServer;
	/** @var int */
	protected $userId;

	/**
	 * Base constructor.
	 * @param   string      $methodName Method name which invokes REST.
	 * @param array         $params     Input params.
	 * @param        string $start      Start position for listing items.
	 * @param \CRestServer  $restServer
	 */
	public function __construct($methodName, array $params, $start, \CRestServer $restServer)
	{
		$this->methodName = $methodName;
		$this->params = $params;
		$this->start = $start;
		$this->restServer = $restServer;
		$this->errorCollection = new ErrorCollection;

		$this->init();
	}

	/**
	 * Initialize service.
	 */
	protected function init()
	{
		global $USER;
		$this->userId = $USER->getId();
	}

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	/**
	 * Executes method.
	 * @return mixed
	 * @throws RestException
	 */
	public function processMethodRequest()
	{
		try
		{
			$binder = new Engine\Binder($this, $this->methodName, array($this->params));

			return $binder->invoke();
		}
		catch(ArgumentNullException $e)
		{
			throw new RestException(
				"Invalid value of parameter { {$e->getParameter()} }.",
				RestException::ERROR_ARGUMENT
			);
		}

	}

	/**
	 * @param array $inputParams
	 * @param array $required
	 * @return bool
	 */
	protected function checkRequiredInputParams(array $inputParams, array $required)
	{
		foreach ($required as $item)
		{
			if(!isset($inputParams[$item]) || (!$inputParams[$item] && !(is_string($inputParams[$item]) && strlen($inputParams[$item]))))
			{
				$this->errorCollection->add(array(new Error("Error: required parameter {$item}", self::ERROR_REQUIRED_PARAMETER)));
				return false;
			}
		}

		return true;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting array of errors with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorsByCode($code)
	{
		return $this->errorCollection->getErrorsByCode($code);
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error[]
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}