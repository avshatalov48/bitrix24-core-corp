<?php

namespace Bitrix\Main\Engine;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Event;
use Bitrix\Main\ObjectNotFoundException;

/**
 * Class Binder
 * @package Bitrix\Main\Engine
 * @deprecated
 * @see \Bitrix\Main\Engine\AutoWire\Binder
 */
class Binder
{
	const ANY_PARAMETER_NAME = -1;

	const STATUS_FOUND     = true;
	const STATUS_NOT_FOUND = false;

	const EVENT_ON_BUILD_AUTO_WIRED_CLASSES = 'onBuildAutoWiredClasses';

	private $instance;
	private $method;
	/** @var array */
	private $methodParams;
	/** @var array */
	private $args;
	private $listSourceParameters;
	/** @var \ReflectionFunctionAbstract */
	private $reflectionFunctionAbstract;
	/** @var array */
	private static $autoWiredHandlers = null;

	/**
	 * Binder constructor.
	 * @param mixed  $instance Instance of the class that contains the method.
	 * @param string $method Name of the method.
	 * @param array  $listSourceParameters List of parameters source which we want to bind.
	 */
	public function __construct($instance, $method, array $listSourceParameters)
	{
		$this->instance = $instance;
		$this->method = $method;
		$this->listSourceParameters = $listSourceParameters;

		$this->registerDefaultAutoWirings();
		//self::$autoWiredHandlers = $this->collectAutoWiredClasses();

		if ($instance === null)
		{
			$this->buildReflectionFunction();
		}
		else
		{
			$this->buildReflectionMethod();
		}

//		$this->bindParams();
	}

	private static function registerDefaultAutoWirings()
	{
		static $isAlreadyRegistered = false;
		if ($isAlreadyRegistered)
		{
			return;
		}

		$isAlreadyRegistered = true;
		self::registerParameter(
			CurrentUser::className(),
			function() {
				return CurrentUser::get();
			}
		);
	}

	final public static function buildForFunction($callable, array $listSourceParameters)
	{
		return AutoWire\Binder::buildForFunction($callable)
				->setSourcesParametersToMap($listSourceParameters);
	}

	final public static function buildForMethod($instance, $method, array $listSourceParameters)
	{
		return AutoWire\Binder::buildForMethod($instance, $method)
				->setSourcesParametersToMap($listSourceParameters);
	}

	final public static function registerParameter($className, \Closure $constructObjectByClassAndId)
	{
		self::registerDefaultAutoWirings();

		$dependsOnParameter = new AutoWire\Parameter($className, $constructObjectByClassAndId);
		AutoWire\Binder::registerGlobalAutoWiredParameter($dependsOnParameter);

		self::$autoWiredHandlers[$className] = array(
			'onConstructObjectByClassAndId' => $constructObjectByClassAndId,
			'onConstructIdParameterName' => self::ANY_PARAMETER_NAME,
		);
	}

	final public static function registerParameterDependsOnName($className, \Closure $constructObjectByClassAndId, \Closure $constructIdParameterName = null)
	{
		self::registerDefaultAutoWirings();

		$dependsOnParameter = new AutoWire\Parameter(
			$className, $constructObjectByClassAndId, $constructIdParameterName
		);
		AutoWire\Binder::registerGlobalAutoWiredParameter($dependsOnParameter);

		self::$autoWiredHandlers[$className] = array(
			'onConstructObjectByClassAndId' => $constructObjectByClassAndId,
			'onConstructIdParameterName' => $constructIdParameterName,
		);
	}

	/**
	 * Builds instance of reflection method.
	 * @return void
	 */
	private function buildReflectionMethod()
	{
		$this->reflectionFunctionAbstract = new \ReflectionMethod($this->instance, $this->method);
		$this->reflectionFunctionAbstract->setAccessible(true);
	}

	private function buildReflectionFunction()
	{
		$this->reflectionFunctionAbstract = new \ReflectionFunction($this->method);
	}

	final protected function collectAutoWiredClasses()
	{
		$event = new Event(
			'main',
			static::EVENT_ON_BUILD_AUTO_WIRED_CLASSES,
			array()
		);
		$event->send($this);

		$autoWiredHandler = array();
		foreach ($event->getResults() as $eventResult)
		{
			$parameters = $eventResult->getParameters();
			foreach ($parameters as $handler)
			{
				if (empty($handler['class']))
				{
					throw new ArgumentNullException('class');
				}

				if (empty($handler['onConstructObjectByClassAndId']) || !is_callable($handler['onConstructObjectByClassAndId'], true))
				{
					throw new ArgumentTypeException('onConstructObjectByClassAndId', 'callable');
				}

				if (empty($handler['onConstructIdParameterName']))
				{
					$handler['onConstructIdParameterName'] = function(\ReflectionParameter $parameter){
						return $parameter->getName() . 'Id';
					};
				}
				elseif (
					!is_callable($handler['onConstructIdParameterName'], true) &&
					$handler['onConstructIdParameterName'] !== self::ANY_PARAMETER_NAME)
				{
					throw new ArgumentTypeException('onConstructIdParameterName', 'callable');
				}

				$autoWiredHandler[$handler['class']] = array(
					'onConstructObjectByClassAndId' => $handler['onConstructObjectByClassAndId'],
					'onConstructIdParameterName' => $handler['onConstructIdParameterName'],
				);
			}
		}

		return $autoWiredHandler;
	}

	/**
	 * Returns list of method params.
	 * @return array
	 */
	final public function getMethodParams()
	{
		if ($this->methodParams === null)
		{
			$this->bindParams();
		}

		return $this->methodParams;
	}

	/**
	 * Sets list of method params.
	 * @param array $params List of parameters.
	 *
	 * @return $this
	 */
	final public function setMethodParams(array $params)
	{
		$this->methodParams = $params;
		$this->args = array_values($params);

		return $this;
	}

	/**
	 * Returns list of method params which possible use in call_user_func_array().
	 * @return array
	 */
	final public function getArgs()
	{
		if ($this->args === null)
		{
			$this->bindParams();
		}

		return $this->args;
	}

	/**
	 * Invokes method with binded parameters.
	 * return @mixed
	 */
	final public function invoke()
	{
		try
		{
			if($this->reflectionFunctionAbstract instanceof \ReflectionMethod)
			{
				return $this->reflectionFunctionAbstract->invokeArgs($this->instance, $this->getArgs());
			}
			elseif ($this->reflectionFunctionAbstract instanceof \ReflectionFunction)
			{
				return $this->reflectionFunctionAbstract->invokeArgs($this->getArgs());
			}
		}
		catch (\TypeError $exception)
		{
			$this->processException($exception);
		}
		catch (\ErrorException $exception)
		{
			$this->processException($exception);
		}

		return null;
	}

	private function processException($exception)
	{
		if (!($exception instanceof \TypeError) && !($exception instanceof \ErrorException))
		{
			return;
		}

		if (
			stripos($exception->getMessage(), 'must be an instance of') === false ||
			stripos($exception->getMessage(), 'null given') === false
		)
		{
			throw $exception;
		}

		$message = $this->extractParameterClassName($exception->getMessage());

		throw new ObjectNotFoundException(
			"Could not find value for class {{$message}} to build auto wired argument",
			$exception instanceof \TypeError? null : $exception //fix it. When we will use php7.1
		);
	}

	private function extractParameterClassName($message)
	{
		if (preg_match('%must be an instance of ([a-zA-Z0-9_\\\\]+), null given%', $message, $m))
		{
			return $m[1];
		}

		return null;
	}

	private function getParameterValue(\ReflectionParameter $parameter)
	{
		$reflectionClass = $parameter->getClass();
		$autoWiredHandler = $reflectionClass? $this->getAutoWiredHandler($reflectionClass) : null;

		if ($autoWiredHandler)
		{
			$primaryId = null;
			if($autoWiredHandler['onConstructIdParameterName'] !== self::ANY_PARAMETER_NAME)
			{
				$parameterName = call_user_func_array($autoWiredHandler['onConstructIdParameterName'], array($parameter));
				$primaryId = $this->findParameterInSourceList($parameterName, $status);
				if ($status === self::STATUS_NOT_FOUND)
				{
					if ($parameter->isDefaultValueAvailable())
					{
						return $parameter->getDefaultValue();
					}

					throw new ArgumentException(
						"Could not find value for parameter {{$parameterName}} to build auto wired argument {{$parameter->getClass()->name} {$parameter->getName()}}",
						$parameter
					);
				}
			}

			return call_user_func_array(
				$autoWiredHandler['onConstructObjectByClassAndId'],
				array($reflectionClass->getName(), $primaryId)
			);
		}

		$value = $this->findParameterInSourceList($parameter->getName(), $status);
		if ($status === self::STATUS_NOT_FOUND)
		{
			if ($parameter->isDefaultValueAvailable())
			{
				$value = $parameter->getDefaultValue();
			}
			else
			{
				throw new ArgumentException(
					"Could not find value for parameter {{$parameter->getName()}}",
					$parameter
				);
			}
		}

		if ($parameter->isArray())
		{
			$value = (array)$value;
		}

		return $value;
	}

	/**
	 * @param array $listSourceParameters
	 */
	public function setListSourceParameters($listSourceParameters)
	{
		$this->listSourceParameters = $listSourceParameters;
		$this->args = $this->methodParams = null;

		return $this;
	}

	private function findParameterInSourceList($name, &$status)
	{
		$status = self::STATUS_FOUND;
		foreach ($this->listSourceParameters as $source)
		{
			if (isset($source[$name]))
			{
				return $source[$name];
			}

			if ($source instanceof \ArrayAccess && $source->offsetExists($name))
			{
				return $source[$name];
			}
			elseif (is_array($source) && array_key_exists($name, $source))
			{
				return $source[$name];
			}
		}
		unset($source);
		$status = self::STATUS_NOT_FOUND;

		return null;
	}

	private function bindParams()
	{
		$this->args = $this->methodParams = array();

		foreach ($this->reflectionFunctionAbstract->getParameters() as $param)
		{
			$value = $this->getParameterValue($param);
			$this->args[] = $this->methodParams[$param->getName()] = $value;
		}

		return $this->args;
	}

	private function getAutoWiredHandler(\ReflectionClass $class)
	{
		foreach (self::$autoWiredHandlers as $autoWiredClass => $handler)
		{
			if ($class->isSubclassOf($autoWiredClass) || $class->name === ltrim($autoWiredClass, '\\'))
			{
				return $handler;
			}
		}

		return null;
	}

	private function isAutoWiredClass(\ReflectionClass $class)
	{
		return (bool)$this->getAutoWiredHandler($class);
	}
}
