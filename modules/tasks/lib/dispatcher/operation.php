<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * This class DOES NOT check any CSRF tokens and even for current user`s authorization, so BE CAREFUL using it.
 */

namespace Bitrix\Tasks\Dispatcher;

use Bitrix\Tasks\Dispatcher;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\Error\Collection;

final class Operation
{
	const ARGUMENT_TYPE_STRING = 	'string';
	const ARGUMENT_TYPE_ARRAY = 	'array';

	protected $operation = 	array();
	protected $namespace = 	false;

	protected $parsed = 	array();

	public function __construct($operation, array $parameters = array())
	{
		$this->operation = $operation;
		$this->errors = new Collection();

		if((string) $parameters['NAMESPACE'] != '')
		{
			$this->namespace = $parameters['NAMESPACE'];
		}
		else
		{
			throw new Exception('Root NAMESPACE must be specified');
		}
	}

	public function parse()
	{
		// parse out class name and method name
		$this->parsed = $this->parseQueryPath($this->operation['OPERATION']);

		// check arguments presense
		if(!isset($this->operation['ARGUMENTS']))
		{
			$this->operation['ARGUMENTS'] = array();
		}
		elseif(!is_array($this->operation['ARGUMENTS']))
		{
			$this->addParseError('Arguments must be of type array for '.$this->operation['OPERATION']);
		}

		if($this->errors->isEmpty())
		{
			$this->checkClass();
		}

		if($this->errors->isEmpty())
		{
			$this->parsed['SIGNATURE'] = $this->getMethodSignature();
			$this->parsed['ARGUMENTS'] = $this->prepareArguments(); // re-order and check
		}
	}

	public function call()
	{
		$opResult = array();

		if($this->parsed['SIGNATURE']['STATIC'])
		{
			$opResult = call_user_func_array($this->parsed['CLASS'].'::'.$this->parsed['METHOD'], $this->parsed['ARGUMENTS']);
		}
		else
		{
			$class = $this->parsed['CLASS'];
			$instance = new $class();

			if($instance->canExecute())
			{
				$opResult = call_user_func_array(array($instance, $this->parsed['METHOD']), $this->parsed['ARGUMENTS']);
			}

			// get errors from operation instance itself
			$this->errors->load($instance->getErrors());
		}

		if($opResult instanceof Result)
		{
			// also get errors from result, in case of object
			$this->errors->load($opResult->getErrors());
			return $opResult->getData();
		}
		else
		{
			return $opResult;
		}
	}

	protected function prepareArguments()
	{
		$result = array();
		if(!empty($this->parsed['SIGNATURE']['ARGUMENTS']))
		{
			$values = array_change_key_case($this->operation['ARGUMENTS'], CASE_LOWER);

			foreach($this->parsed['SIGNATURE']['ARGUMENTS'] as $argName => $argDesc)
			{
				$typeArray = $argDesc['TYPE'] == static::ARGUMENT_TYPE_ARRAY;

				// check if argument is required, but no value passed for it
				if(!isset($values[$argName]) && $argDesc['REQUIRED'])
				{
					$this->addParseError('Argument "'.$argName.'" is required, but no value passed for '.$this->parsed['FULLPATH']);
					continue;
				}

				// optional argument somewhere in the middle, not passed
				// initialize it with the default value
				if(!isset($values[$argName]) && !$argDesc['REQUIRED'])
				{
					$values[$argName] = $argDesc['DEFAULT_VALUE'];
				}

				if(isset($values[$argName]) && !is_array($values[$argName]) && $typeArray)
				{
                    if((string) $values[$argName] == '')
                    {
                        // it seems an empty array was transferred as an empty string, replace then
	                    $values[$argName] = array();
                    }
                    elseif(!is_array($values[$argName]))
                    {
                        $this->addParseError('Argument "'.$argName.'" must be of type array, but given something else for '.$this->parsed['FULLPATH']);
                    }
				}

				// the value is okay
				$result[$argName] = $values[$argName];
			}

			$this->operation['ARGUMENTS'] = $values;
		}

		return $result;
	}

	protected function getMethodSignature()
	{
		$info = new \ReflectionMethod($this->parsed['CLASS'], $this->parsed['METHOD']);

		$result = array(
			'STATIC' => $info->isStatic(),
			'ARGUMENTS' => array()
		);
		$arguments = $info->getParameters();
		if(is_array($arguments))
		{
			foreach($arguments as $arg)
			{
				$optional = $arg->isOptional();
				$default = null;
				if($optional)
				{
					$default = $arg->getDefaultValue();
				}

				$argName = ToLower($arg->getName());
				$result['ARGUMENTS'][$argName] = array(
					'NAME' => 		    $argName,
					'TYPE' => 		    $arg->isArray() ? self::ARGUMENT_TYPE_ARRAY : self::ARGUMENT_TYPE_STRING,
					'REQUIRED' => 	    !$optional,
					'DEFAULT_VALUE' =>  $default,
				);
			}
		}

		return $result;
	}

	protected function checkClass()
	{
		$noEntity = false;
		if(class_exists($this->parsed['CLASS']) && is_subclass_of($this->parsed['CLASS'], 'TasksBaseComponent'))
		{
			// its a component class. Such class can not be loaded by autoloader, so it must be pre-loaded above
			$class = $this->parsed['CLASS'];
			$allowedMethods = $class::getAllowedMethods();
			if(!is_array($allowedMethods))
			{
				throw new \Bitrix\Tasks\Exception('Method '.$class.'::allowedMethods() returned a non-array value, too frightful to execute');
			}
			else
			{
				$allowedMethods = array_flip($allowedMethods);
				$allowedMethods = array_change_key_case($allowedMethods, CASE_LOWER);
			}

			// in the component class the easiest way to control accessibility of methods is the white-list,
			// because there are also huge amount of methods that can be potentially called by mistake
			if(!isset($allowedMethods[$this->parsed['METHOD']]))
			{
				$this->addParseError('Method is not allowed to call: '.$this->parsed['FULLPATH']);
				return;
			}
		}
		else
		{
			$this->parsed['CLASS'] = '\\'.$this->namespace.'\\'.$this->parsed['CLASS'];

			// in the callable class each public method is meant to be callable outside, and only few methods are not, so the black-list here
			if(class_exists($this->parsed['CLASS']) && is_subclass_of($this->parsed['CLASS'], '\Bitrix\Tasks\Dispatcher\PublicAction'))
			{
				$class = $this->parsed['CLASS'];
				$forbiddenMethods = $class::getForbiddenMethods();
				if(!is_array($forbiddenMethods))
				{
					throw new \Bitrix\Tasks\Exception('Method '.$class.'::getForbiddenMethods() returned a non-array value, too frightful to execute');
				}
				else
				{
					$forbiddenMethods = array_flip($forbiddenMethods);
					$forbiddenMethods = array_change_key_case($forbiddenMethods, CASE_LOWER);
				}

				if(isset($forbiddenMethods[$this->parsed['METHOD']]))
				{
					$this->addParseError('Method is not allowed to call: '.$this->parsed['FULLPATH']);
					return;
				}
			}
			else
			{
				$noEntity = true;
				$this->addParseError('Entity not found: '.$this->parsed['ENTITY']);
			}
		}

		if(!$noEntity && !is_callable($this->parsed['CLASS'].'::'.$this->parsed['METHOD']))
		{
			$this->addParseError('Method not found or not callable: '.$this->parsed['FULLPATH']);
		}
	}

	protected function parseQueryPath($path)
	{
		$path = ToLower(trim((string) $path));

		// not empty
		// contains at least two parts: entity.method, each part should not start with a digit, should not start from or end with comma
		if(!isset($path) || $path == '' || !preg_match('#^([a-z_]+[a-z0-9_]+)(\.[a-z_]+[a-z0-9_]+)+$#', $path))
		{
			$this->addParseError('Incorrect method name');
			return;
		}

		$fullPath = $path;
		$path = explode('.', $path);
		$method = array_pop($path);

		$namespace = array_map('ucfirst', $path);

		return array(
			'FULLPATH' => 		$fullPath,
			'ENTITY' => 	implode('.', $path),
			'CLASS' => 		implode('\\', $namespace),
			'METHOD' => 	$method
		);
	}

	protected function addParseError($message)
	{
		$this->errors->add('PARSE_ERROR', $message, Dispatcher::ERROR_TYPE_PARSE, $this->getSupplementaryErrorInfo());
	}

	protected function getSupplementaryErrorInfo()
	{
		return array(
			'QUERY' => $this->operation
		);
	}

	public function getOperation()
	{
		return $this->operation;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * @return Collection|null
	 *
	 * @deprecated Bad name
	 */
	public function getErrorCollection()
	{
		return $this->errors;
	}
}