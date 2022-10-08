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

namespace Bitrix\Tasks;

use Bitrix\Main\IO\Directory;
use Bitrix\Rest\RestException;
use Bitrix\Tasks\Dispatcher\ExecutionResult;
use Bitrix\Tasks\Dispatcher\Operation;
use Bitrix\Tasks\Dispatcher\PublicAction;
use Bitrix\Tasks\Dispatcher\ToDo;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Util\Error\Filter;
use Bitrix\Tasks\Util\Result;

final class Dispatcher
{
	protected static $currentRestMehtod = null;
	protected static $requestData = [];

	protected $rootNamespace = 		false;
	/**
	 * @var Collection|null
	 * @deprecated
	 */
	protected $errors = 			null;
	protected $runtimeActions =     array();

	protected static $enabled =     true;

	const ERROR_TYPE_PARSE = 		'PARSE';
	/** @deprecated */
	const ERROR_TYPE_CALL = 		'CALL';

	const NAMESPACE_TO_CALLABLE = 	'\\Dispatcher\\PublicAction';
	const DIRECTORY_TO_CALLABLE = 	'/dispatcher/publicaction';

	public function __construct()
	{
		$this->rootNamespace = __NAMESPACE__.static::NAMESPACE_TO_CALLABLE;
		$this->errors = new Collection();
	}

	public static function globalDisable()
	{
		static::$enabled = false;
	}

	public static function globalEnable()
	{
		static::$enabled = false;
	}

	public static function isGloballyEnabled()
	{
		return !!static::$enabled;
	}

	public function addRuntimeActions($actions)
	{
		if(is_array($actions))
		{
			foreach($actions as $name => $callable)
			{
				if(is_callable($callable))
				{
					$this->runtimeActions[ToLower($name)] = $callable;
				}
			}
		}
	}

	/**
	 * Check if rest method exists. Return method description if exists.
	 * @param string $rest Method name.
	 * @return array|boolean
	 */
	public static function restRegister($rest)
	{
		$rest = mb_strtolower(trim((string)$rest));

		if (
			!isset($rest) || $rest == '' ||
			!preg_match('#^([a-z_]+[a-z0-9_]+)(\.[a-z_]+[a-z0-9_]+)+$#', $rest)
		)
		{
			return false;
		}

		$path = explode('.', $rest);

		$method = array_pop($path);
		$namespace = array_map('ucfirst', $path);
		$classPrefix = '\\Bitrix\\Tasks\\Dispatcher\\Publicaction\\';
		$class = $classPrefix . implode('\\', $namespace);

		if (method_exists($class, $method))
		{
			static::$currentRestMehtod = $rest;
			return array(
				'scope' => 'task',
				'callback' => array(
					'\\'.__CLASS__, 'restGateway'
				)
			);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Gateway between REST and Dispatcher.
	 *
	 * @param $queryArguments
	 * @return mixed
	 * @throws RestException
	 * @throws \TasksException
	 */
	public static function restGateway($queryArguments)
	{
		if (static::$currentRestMehtod === null)
		{
			throw new RestException('Method not found!', 'ERROR_METHOD_NOT_FOUND');
		}

		// run Dispatcher
		$dispatcher = new self();
		$plan = new Dispatcher\ToDo\Plan();
		$plan->import([
			[
				'OPERATION' => static::$currentRestMehtod,
				'ARGUMENTS' => $queryArguments,
			],
		]);
		$result = $dispatcher->run($plan);

		// work with result
		if ($result->isSuccess())
		{
			$return = array_values($plan->exportResult());
			$errors = $result->getErrors();

			if ($errors && !$errors->isEmpty())
			{
				$error = $errors->first();
				throw new RestException($error->getMessage(), $error->getCode());
			}

			if (isset($return[0]['RESULT']))
			{
				return $return[0]['RESULT'];
			}

			throw new RestException('Unknown error', 'UNKNOWN_ERROR');
		}

		$errors = $result->getErrors();
		$error = $errors->first();

		throw new RestException($error->getMessage(), 'DISPATCHER_ERROR');
	}

	/**
	 * @param ToDo\Plan $plan
	 * @return Result
	 * @throws \TasksException
	 */
	public function run($plan)
	{
		$result = new ExecutionResult();

		$this->checkPlan($plan, $result);
		if($result->isSuccess())
		{
			// bind plan operations to the actual routines
			// it could be standard actions, runtime actions or whatever else
			$operations = $this->getOperationsByPlan($plan, $result);

			// todo: this part definitely needs for some refactoring, it is too complicated
			// todo: and especially when we will introduce operation dependency (but which entity this logic should belong to?)

			if($result->isSuccess())
			{
				// execute
				foreach($operations as $code => $op)
				{
					$callResult = $this->wrapOpCall($op);

					$opResult = new Result();
					$opResult->setData($callResult);
					$opResult->getErrors()->load($op->getErrors());

					// store execution result in the plan
					/** @var ToDo $todo */
					$todo = $plan->findOne(array('=CODE' => $code));
					if($todo)
					{
						$todo->setResult($opResult);
					}

					// duplicate errors to the dispatcher, but as warnings
					$errors = $op->getErrors();
					if(!$errors->isEmpty())
					{
						$result->getErrors()->load($op->getErrors()->transform(array('TYPE' => Error::TYPE_WARNING)));
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param ToDo\Plan $plan
	 * @param Result $result
	 * @return Operation[]
	 */
	protected function getOperationsByPlan($plan, $result)
	{
		$bindings = array();

		/** @var ToDo $op */
		foreach($plan as $op)
		{
			if($op->isProcessed())
			{
				continue;
			}

			$action = $op->getAction();
			$code = $op->getCode();

			$opClassPrefix = '\\Bitrix\\Tasks\\Dispatcher\\';
			$opClass = $opClassPrefix.'Operation';
			$opArgs = array('NAMESPACE' => $this->rootNamespace);
			if(mb_substr($action, 0, 8) == 'runtime:')
			{
				$action = mb_substr($action, 8);
				if(!array_key_exists($action, $this->runtimeActions))
				{
					$result->getErrors()->add('ILLEGAL_RUNTIME_ACTION', 'Runtime action not found: '.$action, static::ERROR_TYPE_PARSE);
				}

				$opClass = $opClassPrefix.'RunTimeOperation';
				$opArgs['CALLABLE'] = $this->runtimeActions[$action];
			}

			if($result->isSuccess())
			{
				/** @var Operation $boundOp */
				$boundOp = new $opClass($op->export(), $opArgs);
				$boundOp->parse();

				$bindings[$code] = $boundOp;

				$result->getErrors()->load($boundOp->getErrors());
			}
		}

		return $bindings;
	}

	/**
	 * @param ToDo\Plan $plan
	 * @param Result $result
	 */
	protected function checkPlan($plan, $result)
	{
		$codesUsed = array();
		/** @var ToDo $op */
		foreach($plan as $op)
		{
			$code = $op->getCode();

			if(isset($codesUsed[$code]))
			{
				$result->getErrors()->add('CODE_USED_MULTIPLE_TIMES', 'The following code is used more than once: '.$code, static::ERROR_TYPE_PARSE);
			}
			else
			{
				$codesUsed[$code] = true;
			}
		}
	}

	/**
	 * @param Operation $operation
	 * @return array
	 * @throws \TasksException
	 */
	private function wrapOpCall($operation)
	{
		$callResult = array();
		try
		{
			$callResult = $operation->call();
		}
		catch(\TasksException $e) // old-style tasks exception
		{
			$errorCode = static::getErrorCodeByTasksException($e);

			if($errorCode !== false)
			{
				$reasonsAdded = false;
				if($e->checkOfType(\TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE) && $e->getMessage() !== false)
				{
					$errors = \Bitrix\Tasks\Util\Type::unSerializeArray($e->getMessage());
					foreach ($errors as $error)
					{
						$operation->getErrors()->add((string) $error["id"] == '' ? 'ACTION_FAILED_REASON' : $error["id"], htmlspecialcharsBack($error["text"]));
						$reasonsAdded = true;
					}
				}

				if(!$reasonsAdded)
				{
					$operation->getErrors()->add($errorCode, static::proxyExceptionMessage($e));
				}
			}
			else
			{
				throw $e; // let it log
			}
		}
		catch(\Bitrix\Tasks\AccessDeniedException $e)
		{
			// access to the entity is not allowed
			$operation->getErrors()->add('ACCESS_DENIED', static::proxyExceptionMessage($e));
		}
		catch(\Bitrix\Tasks\ActionNotAllowedException $e)
		{
			// access to the entity is generally allowed, but the certain action is forbidden to execute
			$operation->getErrors()->add('ACTION_NOT_ALLOWED', static::proxyExceptionMessage($e));
			static::addReasons($operation, $e->getErrors(), 'ACTION_NOT_ALLOWED');
		}
		catch(\Bitrix\Tasks\ActionFailedException $e)
		{
			// action was allowed, but due to some reasons execution failed
			$operation->getErrors()->add('ACTION_FAILED', static::proxyExceptionMessage($e));
			$errors = $e->getErrors();

			if(is_array($errors) && !empty($errors))
			{
				foreach($errors as $error)
				{
					$operation->getErrors()->add('ACTION_FAILED_REASON', $error);
				}
			}
		}
		catch(\Bitrix\Tasks\Exception $e)
		{
			// some general tasks error, no idea what to do
			$operation->getErrors()->add('ACTION_FAILED', static::proxyExceptionMessage($e));
			$errors = $e->getErrors();

			if(is_array($errors) && !empty($errors))
			{
				foreach($errors as $error)
				{
					$operation->getErrors()->add('ACTION_FAILED_REASON', $error);
				}
			}
		}

		return $callResult;
	}

	/**
	 * This method is deprecated, it accepts only array, returns only array and throws some exceptions, and this is not good
	 *
	 * @param $batch
	 * @return array
	 * @throws Dispatcher\BadQueryException
	 * @throws Dispatcher\Exception
	 * @throws \TasksException
	 *
	 * @deprecated
	 */
	public function execute(array $batch)
	{
		$batch = $this->parseBatchDeprecated($batch);

		$result = array();
		if($this->errors->checkHasErrorOfType(static::ERROR_TYPE_PARSE))
		{
			throw new Dispatcher\BadQueryException(false);
		}

		// executing operations
		/** @var Operation $operation */
		foreach($batch as $operation)
		{
			// todo: break chain execution or continue when exception occured?
			// todo: replace call() with execute() which will return Operation Result object, move all Task/Exception catches inside operation->execute()

			$callResult = $this->wrapOpCall($operation);

			$op = $operation->getOperation();

			// todo: an object Result with ArrayAccess, getOperation(), getArguments(), etc would be more appropriate here
			$result[$op['PARAMETERS']['CODE']] = array(
				'OPERATION' => 	$op['OPERATION'],
				'ARGUMENTS' => 	$op['ARGUMENTS'],
				'RESULT' => 	$callResult,
				'SUCCESS' => 	$operation->getErrors()->checkNoFatals(),
				'ERRORS' => 	$operation->getErrors()->getAll(true, new Filter())
			);
		}

		return $result;
	}

	/**
	 * @param array $batch
	 * @return array
	 *
	 * @deprecated
	 */
	private function parseBatchDeprecated(array $batch)
	{
		// parse code and sort first
		$i = 0;
		$codesUsed = array();
		foreach($batch as &$operation)
		{
			if(is_array($operation['PARAMETERS']))
			{
				$operation['PARAMETERS'] = array_change_key_case($operation['PARAMETERS'], CASE_UPPER);
			}
			else
			{
				$operation['PARAMETERS'] = array();
			}

			if((string) $operation['PARAMETERS']['CODE'] === '')
			{
				$operation['PARAMETERS']['CODE'] = 'op_'.$i;
			}

			if(isset($codesUsed[$operation['PARAMETERS']['CODE']]))
			{
				$this->errors->add('CODE_USED_MULTIPLE_TIMES', 'The following code is used more than once: '.$operation['PARAMETERS']['CODE'], static::ERROR_TYPE_PARSE);
			}
			else
			{
				$codesUsed[$operation['PARAMETERS']['CODE']] = true;
			}

			$i++;
		}
		unset($operation);

		$batchParsed = array();
		foreach($batch as $operation)
		{
			$op = new Dispatcher\Operation($operation, array('NAMESPACE' => $this->rootNamespace));
			$op->parse();
			$batchParsed[] = $op;
			$this->errors->load($op->getErrors());
		}

		return $batchParsed;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public static function getErrorCodeByTasksException($e)
	{
		$result = false;

		if($e instanceof \TasksException)
		{
			if($e->checkOfType(\TasksException::TE_ACTION_FAILED_TO_BE_PROCESSED))
			{
				$result = 'ACTION_FAILED';
			}
			elseif($e->checkOfType(\TasksException::TE_ACTION_NOT_ALLOWED)) // DO NOT relocate this ...
			{
				$result = 'ACTION_NOT_ALLOWED';
			}
			elseif($e->checkOfType(\TasksException::TE_ACCESS_DENIED)) // ... after this
			{
				$result = 'ACCESS_DENIED';
			}
			elseif($e->checkOfType(\TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE))
			{
				$result = 'ACCESS_DENIED.NO_TASK';
			}
		}

		return $result;
	}

	/**
	 * There may be a policy of preventing users from seeing exception message due to security reasons
	 * @param \Bitrix\Main\SystemException $e
	 * @return string
	 */
	public static function proxyExceptionMessage($e)
	{
		if(method_exists($e, 'getMessageFriendly'))
		{
			return $e->getMessageFriendly();
		}
		else
		{
			return $e->getMessage();
		}
	}

	/**
	 * Use this to get info about methods supported.
	 * This is just a reference generator for developers. Proper work is not guaranteed. Also untested on Windows.
	 *
	 * @access private
	 */
	public function getDescription()
	{
		$list = $this->getClasses();

		$result = array();
		foreach($list as $item)
		{
			$methods = get_class_methods($item['CLASS']);
			/** @var PublicAction $class */
			$class = $item['CLASS'];
			$forbiddenMethods = array_flip(array_map('ToLower', $class::getForbiddenMethods()));

			if(is_array($methods))
			{
				foreach($methods as $method)
				{
					$method = ToLower($method);

					if(!isset($forbiddenMethods[$method]))
					{
						if(is_callable(array($item['CLASS'], $method)))
						{
							$info = static::getMethodInfo($item['CLASS'], $method);

							$query = $item['ENTITY'].'.'.$method;
							$info['QUERY'] = $query;

							$result[$query] = $info;
						}
					}
				}
			}
		}

		//ksort($result);

		return $result;
	}

	public function getDescriptionFormatted()
	{
		$formatted = '';

		$desc = $this->getDescription();

		foreach($desc as $method)
		{
			$argsFormatted = array();
			if(is_array($method['ARGUMENTS']))
			{
				foreach($method['ARGUMENTS'] as $arg)
				{
					$argsFormatted[] = $arg['TYPE'].' '.$arg['NAME'].($arg['REQUIRED'] ? '*' : '');
				}
			}

			$formatted[] = $method['QUERY'].'('.implode(', ', $argsFormatted).')'.($method['DOC'] !== '' ? ' - '.$method['DOC'] : '');
		}

		return implode(PHP_EOL, $formatted);
	}

	public static function isA($instance)
	{
		return is_a($instance, get_called_class());
	}

	protected function getMethodInfo($class, $method)
	{
		$info = new \ReflectionMethod($class, $method);

		$doc = '';
		$comment = $info->getDocComment();
		if((string) $comment !== '')
		{
			$found = array();
			preg_match('#/\*\*\s+\*([^\*]+)#', $comment, $found);

			if($found[1] !== '')
			{
				$doc = trim($found[1]);
			}
		}

		$args = array();
		$arguments = $info->getParameters();
		if(is_array($arguments))
		{
			foreach($arguments as $arg)
			{
				$argName = ToLower($arg->getName());
				$args[] = array(
					'NAME' => 		$argName,
					'TYPE' => 		$arg->isArray() ? 'array' : 'string',
					'REQUIRED' => 	!$arg->isOptional(),
				);
			}
		}

		return array(
			'DOC' => $doc,
			'ARGUMENTS' => $args
		);
	}

	protected function getClasses()
	{
		if($this->rootNamespace == false)
		{
			throw new Dispatcher\Exception('Root namespace incorrect'); // paranoid disorder
		}

		$dir = __DIR__.static::DIRECTORY_TO_CALLABLE;

		$result = array();

		if(Directory::isDirectoryExists($dir))
		{
			$index = array();
			static::walkDirectory($dir, $index, '');

			if(is_array($index['FILE']))
			{
				foreach($index['FILE'] as $fileName)
				{
					$fileName = str_replace($dir, '', $fileName);
					$fileName = explode('/', $fileName);
					$query = array();
					if(is_array($fileName))
					{
						foreach($fileName as $part)
						{
							if((string) $part !== '' || preg_match('#\.php$#', $part))
							{
								$query[] = preg_replace('#\.php$#', '', $part);
							}
						}
					}

					$result[] = array(
						'ENTITY' => implode('.', $query),
						'CLASS' => $this->rootNamespace.'\\'.implode('\\', array_map('ucfirst', $query))
					);
				}
			}
		}

		return $result;
	}

	// todo: rewirite this on \Bitrix\Main\IO functions
	protected static function walkDirectory($dir, &$index, $rootDir)
	{
		$fullDir = $rootDir.$dir;

		if(!is_readable($fullDir))
			return;

		if(is_file($fullDir))
		{
			$index['FILE'][] = $dir;
			return;
		}
		elseif(is_dir($fullDir) && (string) $dir != '')
		{
			$index['DIR'][] = $dir;
			sort($index['DIR'], SORT_STRING);
		}

		foreach(new \DirectoryIterator($fullDir) as $entry)
		{
			if($entry->isDot())
			{
				continue;
			}

			$file = $dir.'/'.$entry->getFilename();
			static::walkDirectory($file, $index, $rootDir);
		}
	}

	/**
	 * @param $args
	 * @return mixed
	 */
	private static function tryParseBatchArguments($args)
	{
		if (count($args) == 1 && isset($args['cmd']))
		{
			foreach ($args['cmd'] as $key => $url)
			{
				if (!array_key_exists($key, static::$requestData))
				{
					$parsedUrl = parse_url($url);

					if ($parsedUrl['path'] == static::$currentRestMehtod)
					{
						static::$requestData[] = $key;
						parse_str($parsedUrl['query'], $args);
						break;
					}
				}
			}
		}

		return $args;
	}

	private static function addReasons(Dispatcher\Operation $operation, array $reasons, $reasonPrefix = '')
	{
		$errors = $operation->getErrors();

		if((string) $reasonPrefix != '')
		{
			$reasonPrefix = '_'.$reasonPrefix;
		}

		foreach($reasons as $reason)
		{
			if(is_string($reason))
			{
				$errors->add($reasonPrefix.'REASON', $reason);
			}
			else
			{
				if((string) $reason['MESSAGE'] != '')
				{
					$code = ((string) $reason['CODE'] != '' ? $reason['CODE'] : $reasonPrefix.'REASON');
					$errors->add($code, $reason['MESSAGE']);
				}
			}
		}
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