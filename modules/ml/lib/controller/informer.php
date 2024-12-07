<?php

namespace Bitrix\Ml\Controller;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Ml\Model;

class Informer extends Base
{
	/**
	 * @param $modelName
	 * @param $state
	 * @param array $additionalParams
	 *
	 * @return array|null
	 */
	public function setModelStateAction($modelName, $state, array $additionalParams): ?array
	{
		$model = Model::loadWithName($modelName);
		if (!$model)
		{
			$this->addError(new Error("Model " . $modelName . " is not found"));

			return null;
		}

		$model->setState($state);
		$model->save();

		$fields = [
			"model" => $model,
		];

		if (isset($additionalParams["PERFORMANCE"]))
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
	public function setPredictionResultAction($predictionId, array $result): void
	{

	}

	public function testAction(): string
	{
		return "asd";
	}
}
