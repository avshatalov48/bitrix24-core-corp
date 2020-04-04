<?php

namespace Bitrix\Crm\Ml\Controller;

use Bitrix\Crm\Ml\Model;
use Bitrix\Main\Loader;

/**
 * Controller class for the component crm.ml.entity.detail
 *
 * @package Bitrix\Crm\Ml\Controller
 */
class Details
{
	/**
	 * Sends pull event to the view, if model was changed.
	 *
	 * @param Model\Base $model Model.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function onModelUpdate(Model\Base $model)
	{
		if(!Loader::includeModule("pull"))
		{
			return;
		}

		\CPullWatch::AddToStack(
			static::getPushTag($model),
			[
				"module_id" => "crm",
				"command" => "mlModelUpdate",
				"params" => [
					"model" => $model,
				]
			]
		);
	}

	/**
	 * Sends pull event to the view, to update on training progress.
	 *
	 * @param Model\Base $model Model.
	 * @param array $currentTraining Current training fields.
	 */
	public static function onTrainingProgress(Model\Base $model, array $currentTraining)
	{
		if(!Loader::includeModule("pull"))
		{
			return;
		}

		\CPullWatch::AddToStack(
			static::getPushTag($model),
			[
				"module_id" => "crm",
				"command" => "trainingProgress",
				"params" => [
					"model" => $model,
					"currentTraining" => $currentTraining
				]
			]
		);
	}

	/**
	 * Returns push tag to receive model and training update events.
	 *
	 * @param Model\Base $model Associated scoring model.
	 * @return string
	 */
	public static function getPushTag(Model\Base $model)
	{
		return "CRM_ML_DETAIL_VIEW_" . $model->getName();
	}
}