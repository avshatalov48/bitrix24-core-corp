<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm;
use Bitrix\Crm\Ml\Scoring;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;

class ScoringController extends EntityController
{
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	public function onCreate($ID, array $params)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			throw new ArgumentException('id must be greater than zero.');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(!is_array($fields))
		{
			$fields = static::getEntity($ID);
		}
		if(!is_array($fields))
		{
			return;
		}

		$bindings = [
			[
				"ENTITY_TYPE_ID" => $fields["ENTITY_TYPE_ID"],
				"ENTITY_ID" => $fields["ENTITY_ID"],
			]
		];

		$historyEntryID = ScoringEntry::create([
			'ENTITY_ID' => $ID,
			'BINDINGS' => $bindings
		]);

		$entityTypeID = (int)$fields['ENTITY_TYPE_ID'] ?: 0;
		$entityID = (int)$fields['ENTITY_ID'] ?: 0;

		if(Main\Loader::includeModule('pull'))
		{
			$historyFields = TimelineEntry::getByID($historyEntryID);
			$historyFields['SCORING_INFO'] = $fields;
			$pushParams = array(
				'HISTORY_ITEM' => $historyFields,
			);

			$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag($entityTypeID, $entityID);
			\CPullWatch::AddToStack(
				$tag,
				array(
					'module_id' => 'crm',
					'command' => 'timeline_scoring_add',
					'params' => $pushParams,
				)
			);
		}
	}

	/**
	 * @param $ownerID
	 * @param array $params
	 * @throws ArgumentException
	 * @throws Main\LoaderException
	 */
	public function onDelete($ownerID, array $params)
	{
		$ownerID = (int)$ownerID;

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!empty($bindings) && Main\Loader::includeModule('pull'))
		{
			$tag = TimelineEntry::prepareEntityPushTag($params['ENTITY_TYPE_ID'], $params['ENTITY_ID']);
			\CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'crm',
					'command' => 'timeline_scoring_delete',
					'params' => ['ENTITY_ID' => $params['ENTITY_ID'], 'TAG' => $tag],
				]
			);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$scoringRecordId = $data['ASSOCIATED_ENTITY_ID'];
		$data['SCORING_INFO'] = Crm\Ml\Internals\PredictionHistoryTable::getRowById($scoringRecordId);
		$data['SCORING_IS_AVAILABLE'] = Scoring::isScoringAvailable();

		return parent::prepareHistoryDataModel($data, $options);
	}

	protected static function getEntity($ID)
	{
		return Crm\Ml\Internals\PredictionHistoryTable::getRowById($ID);
	}
}
