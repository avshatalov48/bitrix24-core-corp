<?php

namespace Bitrix\Ml;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Ml\Entity\EO_Model;
use Bitrix\Ml\Entity\ModelTable;

class Model extends EO_Model
{
	const TYPE_BINARY = "binary";
	const TYPE_REGRESSION = "regression";
	const TYPE_MULTI_CLASS = "multiClass";

	const STATE_NOT_SYNCHRONIZED = "not_synchronized";
	const STATE_NEW = "new";
	const STATE_TRAINING = "training";
	const STATE_EVALUATING = "evaluating";
	const STATE_ERROR = "error";
	const STATE_READY = "ready";

	/**
	 * Creates new ML model and returns Result object with instance of it.
	 *
	 * @param string $name
	 * @param string $type
	 * @param array $fields
	 *
	 * @return Result
	 */
	public static function create($name, $type, array $fields)
	{
		$result = new Result();

		// checking for existence
		$instance = static::loadWithName($name);
		if($instance)
		{
			return $result->addError(new Error("Model " . $name . " already exists"));
		}

		$client = new Client();
		$apiResult = $client->createModel([
			"name" => $name,
			"type" => "binary",
			"fields" => $fields
		]);
		if(!$apiResult->isSuccess())
		{
			return $result->addErrors($apiResult->getErrors());
		}

		$instance = new static();
		$instance->setName($name);
		$instance->setType($type);
		$instance->setState(static::STATE_NEW);

		$instance->save();

		$result->setData([
			'model' => $instance
		]);

		return $result;
	}

	public static function load($id)
	{
		return ModelTable::getList([
			"filter" => [
				"=ID" => $id
			]
		])->fetchObject();
	}

	/**
	 * @param $name
	 * @return Model|null
	 */
	public static function loadWithName($name)
	{
		return ModelTable::getList([
			"filter" => [
				"=NAME" => $name
			]
		])->fetchObject();
	}

	/**
	 * @return Result
	 */
	public function deleteCascade()
	{
		$result = new Result();
		$deleteResult = parent::delete();

		$client = new Client();

		$remoteResult = $client->deleteModel([
			"modelName" => $this->getName()
		]);

		if(!$remoteResult->isSuccess())
		{
			return $result->addErrors($remoteResult->getErrors());
		}

		if(!$deleteResult->isSuccess())
		{
			return $result->addErrors($deleteResult->getErrors());
		}

		return $result;
	}

}