<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class LiveFeedController extends Controller
{
	public function onMessageCreate($ownerID, array $params)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}
		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$fields = isset($params['FIELDS']) && is_array($params['FIELDS']) ? $params['FIELDS'] : null;
		if(!is_array($fields))
		{
			return;
		}

		$message = $fields['MESSAGE'];
		if($message === '')
		{
			return;
		}

		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? (int)$fields['ENTITY_TYPE_ID'] : 0;
		$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
		if(!(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0))
		{
			return;
		}

		$bindings = array(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID
			)
		);

		if(isset($fields['PARENTS']) && is_array($fields['PARENTS']))
		{
			foreach($fields['PARENTS'] as $entityInfo)
			{
				$bindings[] = $entityInfo;
			}
		}

		$settings = $attachments = array();
		if (!empty($fields['WEB_DAV_FILES']['UF_SONET_LOG_DOC']) && is_array($fields['WEB_DAV_FILES']['UF_SONET_LOG_DOC']))
		{
			$attachments = $fields['WEB_DAV_FILES']['UF_SONET_LOG_DOC'];
			$settings['HAS_FILES'] = 'Y';
		}

		$userID = isset($fields['USER_ID']) ? (int)$fields['USER_ID'] : 0;
		$created = isset($fields['LOG_DATE']) ? DateTime::tryParse($fields['LOG_DATE']) : null;
		$historyEntryID = CommentEntry::create(
			array(
				'TEXT' => $message,
				'CREATED' => $created,
				'AUTHOR_ID' => $userID,
				'BINDINGS' => $bindings,
				'SETTINGS' => $settings,
				'FILES' => $attachments
			)
		);

		if ($historyEntryID > 0)
		{
			$saveData = array(
				'COMMENT' => $message,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
			);
			CommentController::getInstance()->onCreate($historyEntryID, $saveData);
		}

		$enableHistoryPush = $historyEntryID > 0;
		if($enableHistoryPush && Main\Loader::includeModule('pull'))
		{
			$pushParams = array();
			if($enableHistoryPush)
			{
				$historyFields = TimelineEntry::getByID($historyEntryID);
				if(is_array($historyFields))
				{
					$pushParams['HISTORY_ITEM'] = $this->prepareHistoryDataModel(
						$historyFields,
						array('ENABLE_USER_INFO' => true)
					);
				}
			}

			foreach($bindings as $binding)
			{
				$entityTypeID = (int)$binding['ENTITY_TYPE_ID'];
				$entityID = (int)$binding['ENTITY_ID'];

				$tag = $pushParams['TAG'] = TimelineEntry::prepareEntityPushTag($entityTypeID, $entityID);
				\CPullWatch::AddToStack(
					$tag,
					array(
						'module_id' => 'crm',
						'command' => 'timeline_comment_add',
						'params' => $pushParams,
					)
				);
			}
		}
	}

	protected static function prepareMessage($text)
	{
		if($text === '')
		{
			return '';
		}
		return preg_replace('/\[[^\]]+\]/', '', $text);
	}
}
