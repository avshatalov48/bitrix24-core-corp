<?php declare(strict_types=1);

namespace Bitrix\AI;

use Bitrix\AI\Exception\ErrorCollectionException;
use Bitrix\AI\Exception\ValidateException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

/**
 * Main task for this class - get valid date for request.
 *	If data not valid - will be thrown ErrorCollectionException
 */
abstract class BaseRequest
{
	public const ERROR_BAD_REQUEST = 400;

	protected ?HttpRequest $request;
	protected ?CurrentUser $currentUser;
	protected ?object $object;
	protected array $validatorResultList = [];

	/**
	 * For fill $this->object
	 */
	protected abstract function getObjectWithData();

	/**
	 * Main function. Returns valid data from request
	 *
	 * @param HttpRequest $request
	 * @param CurrentUser|null $currentUser
	 * @return object|void|null
	 * @throws ErrorCollectionException
	 * @throws SystemException
	 */
	public function getData(HttpRequest $request, CurrentUser $currentUser = null)
	{
		$this->request = $request;
		$this->currentUser = $currentUser;

		try
		{
			$this->object = $this->getObjectWithData();
			$this->validate();

			return $this->prepareData();
		}
		catch (ValidateException $validateException)
		{
			$this->startErrors(
				$this->fillErrorCollection(new ErrorCollection(), $validateException)
			);
		}
	}

	/**
	 * For prepare date after validate request
	 *
	 * @return object|null
	 */
	protected function prepareData()
	{
		return $this->object;
	}

	/**
	 * This function set actions for validate $this->object
	 *    For example:
	 *        in $this->object has property "test" and "test2".
	 *        Then this function need contains next array
	 *        [
	 *            'test' => [
	 *                [$this, 'checkFunction']
	 *                [$this->validator, 'checkFunction2']
	 *            ]
	 *            'test2' => [
	 *                [$this->validator, 'checkFunction']
	 *                [$this, 'checkFunction2']
	 *            ]
	 *        ]
	 *
	 * @return array
	 */
	protected function getValidateActions(): array
	{
		return [];
	}

	/**
	 * Function for run validate actions
	 *
	 * @return void
	 * @throws ErrorCollectionException
	 * @throws SystemException
	 */
	protected function validate(): void
	{
		$this->validatorResultList = [];
		if (empty($this->getValidateActions()))
		{
			return;
		}

		$errorCollection = new ErrorCollection();
		foreach ($this->getValidateActions() as $fieldName => $callableActions)
		{
			$value = null;
			if (isset($this->object->$fieldName))
			{
				$value = $this->object->$fieldName;
			}

			$this->validatorResultList[$fieldName] = [];
			foreach ($callableActions as $key => $callableAction)
			{
				if (!is_callable($callableAction))
				{
					throw new SystemException(Loc::getMessage('AI_REQUEST_NO_FOUND_FUNCTION_FOR_VALIDATION'));
				}

				try
				{
					$this->validatorResultList[$fieldName][$key] = call_user_func($callableAction, $value, $fieldName);
				}
				catch (ValidateException $validateException)
				{
					$errorCollection = $this->fillErrorCollection($errorCollection, $validateException);
				}
			}
		}

		if (!$errorCollection->isEmpty())
		{
			$this->startErrors($errorCollection);
		}
	}

	/**
	 * Fill ErrorCollection from ValidateException
	 *
	 * @param ErrorCollection $errorCollection
	 * @param ValidateException $validateException
	 * @return ErrorCollection
	 */
	protected function fillErrorCollection(
		ErrorCollection $errorCollection,
		ValidateException $validateException
	): ErrorCollection
	{
		$errorCollection->setError(
			new Error(
				sprintf(
					"%s: %s",
					$validateException->getFieldName(),
					$validateException->getError()
				),
				static::ERROR_BAD_REQUEST
			)
		);

		return $errorCollection;
	}

	/**
	 * For throw base ErrorCollectionException
	 *
	 * @param ErrorCollection $errorCollection
	 * @throws ErrorCollectionException
	 */
	protected function startErrors(ErrorCollection $errorCollection)
	{
		throw new ErrorCollectionException($errorCollection);
	}

	protected function getInt(string $name, int $defaultValue = 0): int
	{
		$value = $this->request->get($name);
		if (empty($value) || !is_numeric($value))
		{
			return $defaultValue;
		}

		return (int)$value;
	}

	protected function getString(string $name, string $defaultValue = ''): string
	{
		$value = $this->request->get($name);
		if (empty($value) || !is_string($value))
		{
			return $defaultValue;
		}

		return $value;
	}

	protected function getArray(string $name, array $defaultValue = []): array
	{
		$value = $this->request->get($name);
		if (empty($value) || !is_array($value))
		{
			return $defaultValue;
		}

		return $value;
	}

	protected function getBool(string $name, mixed $elementForComparisonTrue, bool $defaultValue = false): bool
	{
		$value = $this->request->get($name);
		if (!is_bool($value) && empty($value))
		{
			return $defaultValue;
		}

		if (!is_null($elementForComparisonTrue))
		{
			return $value === $elementForComparisonTrue;
		}

		return (bool)$value;
	}

	protected function getFile(string $name, array $defaultValue = []): array
	{
		$value = $this->request->getFile($name);
		if (empty($value) || !is_array($value))
		{
			return $defaultValue;
		}

		return $value;
	}
}
