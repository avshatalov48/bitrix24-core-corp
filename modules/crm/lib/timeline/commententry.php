<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Text\Emoji;

class CommentEntry extends TimelineEntry
{
	private const ON_CRM_TIMELINE_COMMENT_ADD_EVENT = "OnAfterCrmTimelineCommentAdd";
	private const ON_CRM_TIMELINE_COMMENT_UPDATE_EVENT = "OnAfterCrmTimelineCommentUpdate";
	private const ON_CRM_TIMELINE_COMMENT_DELETE_EVENT = "OnAfterCrmTimelineCommentDelete";

	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$text = isset($params['TEXT']) ? Emoji::encode($params['TEXT']) : '';
		if ($text === '')
		{
			throw new Main\ArgumentException('Text must be greater not empty string.', 'text');
		}

		self::markCallTrackerActivitiesAsCompleted($bindings);

		$result = Entity\TimelineTable::add([
			'TYPE_ID' => TimelineType::COMMENT,
			'TYPE_CATEGORY_ID' => 0,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'COMMENT' => $text,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => 0,
			'ASSOCIATED_ENTITY_ID' => 0
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if (isset($params['FILES']) && is_array($params['FILES']))
		{
			self::attachFiles($createdId, $params['FILES']);
		}

		self::registerBindings($createdId, $bindings);
		self::buildSearchContent($createdId);

		$event = new Main\Event("crm", self::ON_CRM_TIMELINE_COMMENT_ADD_EVENT, ['ID' => $createdId]);
		$event->send();

		return $createdId;
	}

	/**
	 * @param $ID
	 * @param array $attachment
	 */
	private static function attachFiles($ID, array $attachment)
	{
		global $USER_FIELD_MANAGER;
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return;
		}

		$ufValues = [
			CommentController::UF_COMMENT_FILE_NAME => $attachment,
		];
		if ($USER_FIELD_MANAGER->CheckFields(CommentController::UF_FIELD_NAME, $ID, $ufValues))
		{
			$USER_FIELD_MANAGER->Update(CommentController::UF_FIELD_NAME, $ID, $ufValues);
		}
	}

	public static function rebind($entityTypeID, $oldEntityID, $newEntityID)
	{
		Entity\TimelineBindingTable::rebind($entityTypeID, $oldEntityID, $newEntityID, array(TimelineType::COMMENT));
	}

	public static function attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID)
	{
		Entity\TimelineBindingTable::attach($srcEntityTypeID, $srcEntityID, $targEntityTypeID, $targEntityID, array(TimelineType::COMMENT));
	}

	public static function update($ID, array $params)
	{
		$result = new Main\Result();
		
		if ($ID <= 0)
		{
			$result->addError(new Main\Error('Wrong entity ID'));
			return $result;
		}

		$updateData = array();

		if (isset($params['COMMENT']))
			$updateData['COMMENT'] = \Bitrix\Main\Text\Emoji::encode($params['COMMENT']);

		if (isset($params['SETTINGS']) && is_array($params['SETTINGS']))
			$updateData['SETTINGS'] = $params['SETTINGS'];

		if (!empty($updateData))
			$result = Entity\TimelineTable::update($ID, $updateData);

		if (isset($params['FILES']) && is_array($params['FILES']))
		{
			self::attachFiles($ID, $params['FILES']);
		}

		self::buildSearchContent($ID);

		$event = new Main\Event("crm", self::ON_CRM_TIMELINE_COMMENT_UPDATE_EVENT, ['ID' => $ID]);
		$event->send();

		return $result;
	}

	public static function delete($ID)
	{
		$result = parent::delete($ID);

		$GLOBALS['USER_FIELD_MANAGER']->Delete(CommentController::UF_FIELD_NAME, $ID);

		$event = new Main\Event("crm", self::ON_CRM_TIMELINE_COMMENT_DELETE_EVENT, ['ID' => $ID]);
		$event->send();

		return $result;
	}

	private static function markCallTrackerActivitiesAsCompleted(array $bindings): void
	{
		foreach ($bindings as $binding)
		{
			$ownerTypeId = (int)$binding['ENTITY_TYPE_ID'];
			$ownerId = (int)$binding['ENTITY_ID'];

			if ($ownerTypeId <= 0 || $ownerId <= 0)
			{
				continue;
			}

			$activityCollection = \CCrmActivity::GetList(
				[
					'ID' => 'ASC',
				],
				[
					'TYPE_ID' => \CCrmActivityType::Provider,
					'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\CallTracker::PROVIDER_ID,
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId,
					'CHECK_PERMISSIONS' => 'Y',
					'COMPLETED' => 'N',
				],
				false,
				false,
				['ID']
			);

			while ($activity = $activityCollection->Fetch())
			{
				\CCrmActivity::Complete(
					$activity['ID'],
					true,
					[
						'REGISTER_SONET_EVENT' => true,
					]
				);
			}
		}
	}
}
