<?php

namespace Bitrix\Ml;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Ml\Entity\EO_Model;
use Bitrix\Ml\Entity\ModelTable;

class Model extends EO_Model
{
	public const TYPE_BINARY = 'binary';
	public const TYPE_REGRESSION = 'regression';
	public const TYPE_MULTI_CLASS = 'multiClass';

	public const STATE_NOT_SYNCHRONIZED = 'not_synchronized';
	public const STATE_NEW = 'new';
	public const STATE_TRAINING = 'training';
	public const STATE_EVALUATING = 'evaluating';
	public const STATE_ERROR = 'error';
	public const STATE_READY = 'ready';

	/**
	 * Creates new ML model and returns Result object with instance of it.
	 *
	 * @param string $name
	 * @param string $type
	 * @param array $fields
	 *
	 * @return Result
	 */
	public static function create(string $name, string $type, array $fields): Result
	{
		$result = new Result();

		// checking for existence
		$instance = static::loadWithName($name);
		if ($instance)
		{
			return $result->addError(new Error("Model " . $name . " already exists"));
		}

		$client = new Client();
		$apiResult = $client->createModel([
			'name' => $name,
			'type' => 'binary',
			'fields' => $fields,
		]);

		if (!$apiResult->isSuccess())
		{
			return $result->addErrors($apiResult->getErrors());
		}

		$instance = new static();
		$instance->setName($name);
		$instance->setType($type);
		$instance->setState(static::STATE_NEW);

		$instance->save();

		$result->setData([
			'model' => $instance,
		]);

		return $result;
	}

	public static function load(int $id): ?Model
	{
		return ModelTable::getList([
			'filter' => [
				'=ID' => $id,
			]
		])->fetchObject();
	}

	public static function loadWithName(string $name): ?Model
	{
		return ModelTable::getList([
			'filter' => [
				'=NAME' => $name,
			]
		])->fetchObject();
	}

	public function deleteCascade(): Result
	{
		$result = new Result();
		$deleteResult = parent::delete();

		$client = new Client();

		$remoteResult = $client->deleteModel([
			'modelName' => $this->getName(),
		]);

		if (!$remoteResult->isSuccess())
		{
			return $result->addErrors($remoteResult->getErrors());
		}

		if (!$deleteResult->isSuccess())
		{
			return $result->addErrors($deleteResult->getErrors());
		}

		return $result;
	}
}
