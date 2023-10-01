<?php

namespace Bitrix\Crm\Ml;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Spotlight;
use Bitrix\Ml\Model;
use CCrmOwnerType;

class ViewHelper
{
	private const LEAD_SPOTLIGHT_ID = 'spotlight_lead_scoring';
	private const DEAL_SPOTLIGHT_ID = 'spotlight_deal_scoring';

	public static function prepareData(int $entityTypeId, int $entityId): array
	{
		$result = [];

		if (
			!Loader::includeModule('ml')
			|| !$entityId
			|| !$entityTypeId
		)
		{
			return $result;
		}

		$model = Scoring::getScoringModel($entityTypeId, $entityId);
		if (!$model)
		{
			return $result;
		}

		$modelExits = !is_null($model->getMlModel());

		$spotlightId = static::getSpotlightId($entityTypeId, $entityId);
		if ($spotlightId)
		{
			$spotlight = new Spotlight($spotlightId);
			$spotlightAvailable = $spotlight->isAvailable();

			$result['SHOW_SPOTLIGHT'] = $spotlightAvailable && !$modelExits && Scoring::canStartTraining($model, true)->isSuccess();
			$result['SPOTLIGHT_ID'] = $spotlightId;
		}
		$result['CURRENT_PREDICTION'] = Scoring::getCurrentPrediction($entityTypeId, $entityId);
		$result['MODEL_READY'] = $model->getState() === Model::STATE_READY;

		return $result;

	}

	public static function subscribePredictionUpdate(int $entityTypeId, int $entityId): void
	{
		global $USER;

		if (!Loader::includeModule('pull'))
		{
			return;
		}

		$tag = Scoring::getPredictionUpdatePullTag($entityTypeId, $entityId);

		\CPullWatch::Add($USER->GetID(), $tag, true);
	}

	public static function isEntityFinal(int $entityTypeId, int $entityId): bool
	{
		if ($entityTypeId === CCrmOwnerType::Lead)
		{
			$leadFields = LeadTable::getList([
				'select' => ['STATUS_ID'],
				'filter' => ['=ID' => $entityId]
			])->fetch();

			return PhaseSemantics::isFinal(\CCrmLead::GetSemanticID($leadFields['STATUS_ID']));
		}

		if ($entityTypeId === CCrmOwnerType::Deal)
		{
			$dealFields = DealTable::getList([
				'select' => ['STAGE_SEMANTIC_ID'],
				'filter' => ['=ID' => $entityId]
			])->fetch();

			return PhaseSemantics::isFinal($dealFields['STAGE_SEMANTIC_ID']);
		}

		return false;
	}

	protected static function getSpotlightId(int $entityTypeId, int $entityId): ?string
	{
		switch ($entityTypeId)
		{
			case CCrmOwnerType::Lead:
				return static::LEAD_SPOTLIGHT_ID;
			case CCrmOwnerType::Deal:
				return static::DEAL_SPOTLIGHT_ID;
			default:
				return null;
		}
	}
}
