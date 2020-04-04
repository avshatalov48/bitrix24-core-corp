<?php

namespace Bitrix\Crm\Ml;

use Bitrix\Main\Loader;
use Bitrix\Ml\Model;

class ViewHelper
{
	const LEAD_SPOTLIGHT_ID = "spotlight_lead_scoring";
	const DEAL_SPOTLIGHT_ID = "spotlight_deal_scoring";

	public static function prepareData($entityTypeId, $entityId)
	{
		$result = [];
		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		if(!Loader::includeModule("ml") || !$entityId || !$entityTypeId)
		{
			return $result;
		}

		$model = Scoring::getScoringModel($entityTypeId, $entityId);
		if(!$model)
		{
			return $result;
		}
		$modelExits = !is_null($model->getMlModel());

		$spotlightId = static::getSpotlightId($entityTypeId, $entityId);
		if($spotlightId)
		{
			$spotlight = new \Bitrix\Main\UI\Spotlight($spotlightId);
			$spotlightAvailable = $spotlight->isAvailable();

			$result['SHOW_SPOTLIGHT'] = $spotlightAvailable && !$modelExits && Scoring::canStartTraining($model, true)->isSuccess();
			$result['SPOTLIGHT_ID'] = $spotlightId;
		}
		$result['CURRENT_PREDICTION'] = Scoring::getCurrentPrediction($entityTypeId, $entityId);
		$result['MODEL_READY'] = $model->getState() === Model::STATE_READY;

		return $result;

	}

	public static function subscribePredictionUpdate($entityTypeId, $entityId)
	{
		global $USER;
		if(!Loader::includeModule("pull"))
		{
			return;
		}

		$tag = Scoring::getPredictionUpdatePullTag($entityTypeId, $entityId);
		\CPullWatch::Add($USER->GetID(), $tag, true);
	}

	public static function isEntityFinal($entityTypeId, $entityId)
	{
		if($entityTypeId == \CCrmOwnerType::Lead)
		{
			$leadFields = \Bitrix\Crm\LeadTable::getList([
				'select' => ['STATUS_ID'],
				'filter' => ['=ID' => $entityId]
			])->fetch();
			return \Bitrix\Crm\PhaseSemantics::isFinal(\CCrmLead::GetSemanticID($leadFields['STATUS_ID']));
		}
		else if($entityTypeId == \CCrmOwnerType::Deal)
		{
			$dealFields = \Bitrix\Crm\DealTable::getList([
				'select' => ['STAGE_SEMANTIC_ID'],
				'filter' => ['=ID' => $entityId]
			])->fetch();
			return \Bitrix\Crm\PhaseSemantics::isFinal($dealFields['STAGE_SEMANTIC_ID']);
		}
		return false;
	}

	protected static function getSpotlightId($entityTypeId, $entityId)
	{
		switch ($entityTypeId)
		{
			case \CCrmOwnerType::Lead:
				return static::LEAD_SPOTLIGHT_ID;
			case \CCrmOwnerType::Deal:
				return static::DEAL_SPOTLIGHT_ID;
			default:
				return null;
		}
	}
}