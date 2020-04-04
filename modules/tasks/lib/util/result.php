<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util;

use Bitrix\Tasks\Util\Error;
use Bitrix\Main\Entity\FieldError;
use Bitrix\Tasks\Util;

class Result
{
	/** @var Error\Collection|null  */
	protected $errors = null;
	protected $data = null;

	public function __construct()
	{
		$this->errors = new Error\Collection();
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @return null
	 */
	public function getData()
	{
		return $this->data;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function isSuccess()
	{
		return $this->getErrors()->checkNoFatals();
	}

	public function isErrorsEmpty()
	{
		return $this->getErrors()->isEmpty();
	}

	/**
	 * @param $e
	 * @param string $message
	 * @param mixed[] $settings
	 */
	public function addException($e, $message = '', $settings = array())
	{
		if(!($e instanceof \Exception))
		{
			return;
		}

		$code = $e->getCode();
		if($code)
		{
			$code = ToUpper($code);
		}
		else
		{
			// todo: generate appropriate code from $e class, for example
			// todo: SqlException => SQL
		}

		$message = (string) $message;
		if($message == '')
		{
			$message = $e->getMessage();
		}

		if(!is_array($settings) || $settings['DUMP'] != false)
		{
			Util::log($e);
		}
		$this->getErrors()->add('EXCEPTION'.($code ? '.'.$code : ''), $message, Error::TYPE_FATAL, array('EXCEPTION' => $e));
	}

	public function addError($code, $message, $type = Error::TYPE_FATAL)
	{
		$this->getErrors()->add($code, $message, $type);
	}

	public function addWarning($code, $message)
	{
		$this->addError($code, $message, Error::TYPE_WARNING);
	}

	public function getErrorCount()
	{
		return $this->errors->count();
	}

	public function loadErrors(Error\Collection $errors)
	{
		$this->getErrors()->load($errors);
	}

	/**
	 * @param Result|\Bitrix\Main\Entity\Result $source
	 * @param array $transform
	 */
	public function adoptErrors($source, $transform = array())
	{
		if(Result::isA($source) && $source->getErrorCount())
		{
			// adopt from another result

			$sourceErrors = $source->getErrors();
			if(is_array($transform) && count($transform))
			{
				$sourceErrors = $sourceErrors->transform($transform);
			}

			$this->errors->load($sourceErrors);
		}
		elseif($source instanceof \Bitrix\Main\Entity\Result)
		{
			// adopt from entity result

			$errors = $source->getErrors();
			foreach($errors as $error)
			{
				$additional = array();
				if($error instanceof FieldError)
				{
					$additional['FIELD'] = $error->getField();
				}

				$messages = explode('<br>', $error->getMessage()); // split by legacy trailing <br>, if any
				foreach($messages as $message)
				{
					$message = trim($message);
					if($message)
					{
						$this->errors->add($error->getCode(), $message, Error::TYPE_FATAL, $additional);
					}
				}
			}
		}
	}

	/**
	 * @internal
	 * @return string
	 */
	public function dump($showData = false)
	{
		$str = "Result success = ".($this->isSuccess() ? 'YES' : 'NO').PHP_EOL;

		$fatals = $this->errors->filter(array('TYPE' => Error::TYPE_FATAL));
		if($fatals->count())
		{
			$str .= 'Fatals:'.PHP_EOL;
			$str .= implode('', array_map(function($message){return "\t* ".$message.PHP_EOL;}, $fatals->getMessages()));
		}

		$warnings = $this->errors->filter(array('TYPE' => Error::TYPE_WARNING));
		if($warnings->count())
		{
			$str .= 'Warnings:'.PHP_EOL;
			$str .= implode('', array_map(function($message){return "\t* ".$message.PHP_EOL;}, $warnings->getMessages()));
		}

		if($showData)
		{
			$str .= $this->dumpData();
		}

		return $str;
	}

	protected function dumpData()
	{
		$data = $this->getData();
		$str = '';
		if($data !== null)
		{
			$str .= 'Data:'.PHP_EOL;
			$str .= '<pre>'.print_r($data, true).'</pre>';
		}

		return $str;
	}

	public static function isA($obj)
	{
		return is_a($obj, get_called_class());
	}
}