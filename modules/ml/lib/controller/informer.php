<?php

namespace Bitrix\Ml\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Ml\Model;

class Informer extends Base
{
	/**
	 * @param int $modelId
	 * @param string $state
	 * @param array $additionalParams
	 */
	public function setModelStateAction($modelName, $state, array $additionalParams)
	{
		$model = Model::loadWithName($modelName);
		if(!$model)
		{
			$this->addError(new Error("Model " . $modelName . " is not found"));
			return null;
		}

		$model->setState($state);
		$model->save();

		$fields = [
			"model" => $model,
		];

		if($additionalParams["PERFORMANCE"])
		{
			$fields["performance"] = $additionalParams["PERFORMANCE"];
		}
		$event = new Event("ml", "onModelStateChange", $fields);
		$event->send();

		return [];
	}

	/**
	 * @param int $predictionId
	 * @param array $result
	 */
	public function setPredictionResultAction($predictionId, array $result)
	{

	}

	public function testAction()
	{
		return "asd";
	}
}