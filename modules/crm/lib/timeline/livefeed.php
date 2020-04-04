<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class LiveFeed
{
	public static function registerEntityMessages($entityTypeID, $entityID)
	{
		if(!Main\Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$liveFeedEntity = \CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		if($liveFeedEntity === \CCrmLiveFeedEntity::Undefined)
		{
			return;
		}

		$entity = new \CSocNetLog();
		$dbResult = $entity->GetList(
			array(),
			array(
				'LOG_RIGHTS' => "{$liveFeedEntity}{$entityID}",
				'EVENT_ID' => array(
					\CCrmLiveFeedEvent::GetEventID(\CCrmLiveFeedEntity::Deal, \CCrmLiveFeedEvent::Message),
					\CCrmLiveFeedEvent::GetEventID(\CCrmLiveFeedEntity::Lead, \CCrmLiveFeedEvent::Message),
					\CCrmLiveFeedEvent::GetEventID(\CCrmLiveFeedEntity::Contact, \CCrmLiveFeedEvent::Message),
					\CCrmLiveFeedEvent::GetEventID(\CCrmLiveFeedEntity::Company, \CCrmLiveFeedEvent::Message),
				)
			),
			false,
			false,
			array('ID', 'USER_ID', 'MESSAGE', 'LOG_DATE')
		);

		while($fields = $dbResult->Fetch())
		{
			//$entryID = (int)$fields['ID'];
			$message = isset($fields['MESSAGE'])
				? preg_replace('/\[[^\]]+\]/', '', $fields['MESSAGE'])
				: '';
			if($message === '')
			{
				continue;
			}

			$created = isset($fields['LOG_DATE']) ? DateTime::tryParse($fields['LOG_DATE']) : null;
			$userID = isset($fields['USER_ID']) ? (int)$fields['USER_ID'] : 0;

			CommentEntry::create(
				array(
					'TEXT' => $message,
					'CREATED' => $created,
					'AUTHOR_ID' => $userID,
					'BINDINGS' => array(
						array('ENTITY_TYPE_ID' => $entityTypeID, 'ENTITY_ID' => $entityID)
					)
				)
			);
		}
	}
}