<?php

namespace Bitrix\Tasks;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Internals\Attribute\ErrorMessageInterface;
use Bitrix\Tasks\Internals\Trait\FromArrayTrait;
use Bitrix\Tasks\Internals\Trait\ToArrayTrait;
use Bitrix\Tasks\Internals\Attribute\CheckInterface;
use Bitrix\Tasks\Internals\Attribute\Department;
use Bitrix\Tasks\Internals\Attribute\Nullable;
use Bitrix\Tasks\Internals\Attribute\Parse;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Project;
use Bitrix\Tasks\Internals\Attribute\Template;
use Bitrix\Tasks\Internals\Attribute\trait\PropertyTrait;
use Bitrix\Tasks\Internals\Attribute\trait\PrimaryTrait;
use Bitrix\Tasks\Internals\Attribute\User;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * @method array getUserIdList()
 * @method array getProjectIdList()
 * @method array getTemplateIdList()
 * @method array getDepartmentIdList()
 */
abstract class AbstractCommand implements Arrayable
{
	use FromArrayTrait;
	use ToArrayTrait;
	use PropertyTrait;
	use PrimaryTrait;

	private bool $sendPush = true;
	private array $pushParams = [];

	/**
	 * @throws InvalidArgumentException
	 */
	public function __call(string $name, array $args)
	{
		$operation = substr($name, 0, 3);
		$property = lcfirst(substr($name, 3));

		if ($operation === 'set')
		{
			return $this->setProperty($property, $args);
		}

		if ($operation === 'get')
		{
			$attribute = match ($property)
			{
				'userIdList' => User::class,
				'projectIdList' => Project::class,
				'templateIdList' => Template::class,
				'departmentIdList' => Department::class,
				default => null
			};

			if (null !== $attribute)
			{
				return $this->getValuesByAttribute($attribute);
			}

			return [];
		}

		if ($operation === 'has')
		{
			$subOperation = lcfirst(substr($name, 3, 5));
			if ($subOperation === 'valid')
			{
				$property = lcfirst(substr($name, 8));

				return $this->isValid($property);
			}

			return $this->hasProperty($property);
		}

		$operation = lcfirst(substr($name, 0, 2));
		$subOperation = lcfirst(substr($name, -6));

		if ($operation === 'is' && $subOperation === 'filled')
		{
			$property = lcfirst(substr($name, 2, -6));

			return $this->isFilledProperty($property);
		}

		return null;
	}

	/**
	 * @throws InvalidCommandException
	 */
	public function validateAdd(string ...$skippedFields): void
	{
		$this->validateAddProperties(...$skippedFields);

		foreach ($this as $value)
		{
			if ($value instanceof static)
			{
				$value->validateAdd();
			}
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	public function validateUpdate(string ...$skippedFields): void
	{
		$this->validatePrimary();
		$this->validateUpdateProperties(...$skippedFields);

		foreach ($this as $value)
		{
			if ($value instanceof self)
			{
				$value->validateUpdate();
			}
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	protected function validateAddProperties(string ...$skippedFields): void
	{
		$reflection = new ReflectionClass($this);
		foreach ($reflection->getProperties() as $property)
		{
			$propertyName = $property->getName();

			if (in_array($propertyName, $skippedFields, true))
			{
				continue;
			}

			$this->validateProperty($propertyName);
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	protected function validateUpdateProperties(string ...$skippedFields): void
	{
		$reflection = new ReflectionClass($this);
		foreach ($reflection->getProperties() as $property)
		{
			$propertyName = $property->getName();

			if (!isset($this->{$propertyName}) || in_array($propertyName, $skippedFields, true))
			{
				continue;
			}

			$this->validateProperty($propertyName);
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	protected function validateProperty(string $propertyName): void
	{
		$propertyReflection = $this->createReflection($propertyName);

		if ($this->hasPrimaryAttribute($propertyReflection)) // skip primary validation
		{
			return;
		}

		if (!isset($this->{$propertyName}))
		{
			if (!$this->hasNullableAttribute($propertyReflection))
			{
				throw new InvalidCommandException("Property {$propertyName} cannot be null");
			}

			return;
		}

		$propertyValue = $this->{$propertyName};

		if ($propertyReflection->hasDefaultValue() && $propertyReflection->getDefaultValue() === $propertyValue)
		{
			return;
		}

		foreach ($propertyReflection->getAttributes() as $attribute)
		{
			$attributeInstance = $attribute->newInstance();

			if ($attributeInstance instanceof Nullable)
			{
				continue;
			}

			if ($attributeInstance instanceof CheckInterface && !$attributeInstance->check($propertyValue))
			{
				if ($attributeInstance instanceof ErrorMessageInterface)
				{
					$message = $attributeInstance->getError($propertyName);
				}
				else
				{
					$message = "{$propertyName}: unexpected value";
				}

				throw new InvalidCommandException($message);
			}
		}
	}

	protected function isValid(string $propertyName): bool
	{
		try
		{
			$this->validateProperty($propertyName);
			return true;
		}
		catch (InvalidCommandException)
		{
			return false;
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	public function validateDelete(): void
	{
		$this->validatePrimary();
		foreach ($this as $value)
		{
			if ($value instanceof self)
			{
				$value->validateDelete();
			}
		}
	}

	/**
	 * @throws InvalidCommandException
	 */
	public function validatePrimary(): void
	{
		$primaryName = $this->getPrimaryName();
		if (!isset($this->{$primaryName}))
		{
			throw new InvalidCommandException("Missing primary value");
		}

		$primaryReflection = $this->createReflection($primaryName);

		foreach ($primaryReflection->getAttributes() as $attribute)
		{
			$attributeInstance = $attribute->newInstance();

			if (
				$attributeInstance instanceof CheckInterface
				&& !$attributeInstance->check($this->{$primaryName})
			)
			{
				if ($attributeInstance instanceof ErrorMessageInterface)
				{
					$message = $attributeInstance->getError($primaryName);
				}
				else
				{
					$message = "{$primaryName}: unexpected value";
				}

				throw new InvalidCommandException($message);
			}
		}
	}

	public function disablePush(): self
	{
		$this->sendPush = false;

		return $this;
	}

	public function isNecessarySendPush(): bool
	{
		return $this->sendPush;
	}

	public function setPushParams(array $pushParams): self
	{
		$this->pushParams = $pushParams;

		return $this;
	}

	public function getPushParams(): array
	{
		return $this->pushParams;
	}

	protected function getValuesByAttribute(string $attributeClass): array
	{
		$values = [];
		$reflection = new ReflectionClass($this);
		foreach ($reflection->getProperties() as $reflectionProperty)
		{
			$propertyName = $reflectionProperty->getName();
			if (empty($reflectionProperty->getAttributes($attributeClass)))
			{
				continue;
			}

			$parsers = $reflectionProperty->getAttributes(Parse::class);
			$isParsable = !empty($parsers);

			if (!isset($this->{$propertyName}))
			{
				if (!$isParsable)
				{
					continue;
				}

				/** @var ReflectionAttribute $parserAttribute */
				$parserAttribute = array_shift($parsers);
				/** @var Parse $parser */
				$parser = $parserAttribute->newInstance();
				$source = $parser->sourceProperty;
				if (!isset($this->{$source}))
				{
					continue;
				}

				$propertyValue = $parser->parse($this->{$source});
			}
			else
			{
				$propertyValue = $this->{$propertyName};
			}

			if (is_int($propertyValue))
			{
				$values[] = $propertyValue;
				continue;
			}

			if (is_countable($propertyValue))
			{
				$values = array_merge($values, $propertyValue);
			}
		}

		return $values;
	}

	protected function hasNullableAttribute(ReflectionProperty $propertyReflection): bool
	{
		return !empty($propertyReflection->getAttributes(Nullable::class));
	}

	protected function hasPrimaryAttribute(ReflectionProperty $propertyReflection): bool
	{
		return !empty($propertyReflection->getAttributes(Primary::class));
	}

	/**
	 * @throws InvalidCommandException
	 */
	protected function createReflection(string $propertyName): ReflectionProperty
	{
		try
		{
			return new ReflectionProperty($this, $propertyName);
		}
		catch (ReflectionException $e)
		{
			throw new InvalidCommandException("Unable to create Command: {$e->getMessage()}");
		}
	}
}