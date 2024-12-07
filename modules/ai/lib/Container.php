<?php declare(strict_types=1);

namespace Bitrix\AI;

use Bitrix\AI\Exception\ContainerException;
use Bitrix\Main\SystemException;

/**
 * This container for auto wired parameters.
 *    Has restrictions for use in $this->checkClassName()
 *
 *    When determining a constructor's dependency on a certain class, an instance of this class is created,
 *        placed in the container, and passed to the constructor of the class being created.
 *        If an object of a certain class already exists in the container, a new one will not be created.
 *        An instance from the container will be passed as the dependency.
 */
class Container
{
	protected const AVAILABLE_CLASS_TYPES = [
		'Repository',
		'Request',
		'Service',
		'Validator',
		'Guard',
	];

	private static ?self $instance;
	private array $components = [];

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	public function __wakeup()
	{
		throw new SystemException('Forbidden wakeup');
	}

	/**
	 * Returns a single container instance
	 *
	 * @return static
	 */
	public static function init(): static
	{
		if (empty(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Create object by className with all dependencies on construct
	 *
	 * @param string $className
	 * @return object
	 * @throws ContainerException If className not from this module
	 */
	public function getItem(string $className): object
	{
		try
		{
			return $this->getObjectWithFullConstruct($className);
		}
		catch (\ReflectionException $exception)
		{
			throw new ContainerException($exception->getMessage());
		}
	}

	/**
	 * Returns object with dependencies on construct and save all dependencies and this object in container
	 *
	 * @param string $className
	 * @return object
	 * @throws ContainerException
	 * @throws \ReflectionException
	 */
	protected function getObjectWithFullConstruct(string $className): object
	{
		if (isset($this->components[$className]))
		{
			return $this->components[$className];
		}

		$this->checkClassName($className);

		$class = new \ReflectionClass($className);
		$constructor = $class->getConstructor();
		if (empty($constructor))
		{
			$this->components[$className] = new $className();

			return $this->components[$className];
		}

		$params = $constructor->getParameters();
		if (empty($params))
		{
			$this->components[$className] = new $className();

			return $this->components[$className];
		}

		$paramsForClass = [];
		foreach ($params as $param)
		{
			$type = $param->getType();
			if (empty($type))
			{
				throw new ContainerException('All parameters in the constructor must have type indications');
			}

			$paramsForClass[] = $this->getObjectWithFullConstruct($type->getName());
		}

		$component = $class->newInstanceArgs($paramsForClass);

		if (empty($component))
		{
			throw new ContainerException('Failed to create component ' . $className);
		}

		$this->components[$className] = $component;

		return $this->components[$className];
	}

	/**
	 * Protection against use in other modules that do not support this containerization
	 *
	 * @param string $className
	 * @return void
	 * @throws ContainerException
	 */
	protected function checkClassName(string $className): void
	{
		if (!str_contains($className, $this->getBaseNamespace()))
		{
			throw new ContainerException("This container only includes classes from the current namespace.");
		}

		$parts = explode('\\', $className);
		$classNameBase = end($parts);
		foreach (self::AVAILABLE_CLASS_TYPES as $availableClassType)
		{
			if (str_contains($classNameBase, $availableClassType))
			{
				return;
			}
		}

		throw new ContainerException("Invalid class type requested.");
	}

	/**
	 * Returns base namespace for this container
	 *
	 * @return string
	 */
	protected function getBaseNamespace(): string
	{
		static $namespace;
		if (empty($namespace))
		{
			$className = explode('\\', __CLASS__);
			$namespace = str_replace(end($className), '', __CLASS__);
		}

		return $namespace;
	}
}
