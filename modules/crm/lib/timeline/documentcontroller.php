<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\ArgumentException;

final class DocumentController extends EntityController
{
	protected $processedDeletedEntryIds = [];

	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		if(isset($data['SETTINGS']['DOCUMENT_ID']))
		{
			$data['DOCUMENT_ID'] = $data['SETTINGS']['DOCUMENT_ID'];
		}
		return parent::prepareHistoryDataModel($data, $options);
	}

	public function onCreate($id, array $params)
	{
		$id = (int)$id;
		if($id <= 0)
		{
			throw new ArgumentException('id must be greater than zero.');
		}

		$text = $params['COMMENT'];
		if(!$text)
		{
			$text = GetMessage('CRM_DOCUMENT_CONTROLLER_HISTORY_CREATE_MESSAGE');
		}
		$this->addEvent($text, $params);
		if ($params['ENTITY_TYPE_ID'] && $params['ENTITY_ID'])
		{
			$this->sendPullEventOnAdd(new ItemIdentifier($params['ENTITY_TYPE_ID'], $params['ENTITY_ID']), $id, $params['USER_ID']);
		}
	}

	public function onDelete($id, array $params)
	{
		$id = (int)$id;
		if($id <= 0)
		{
			throw new ArgumentException('id must be greater than zero.');
		}
		if(isset($this->processedDeletedEntryIds[$id]))
		{
			return;
		}

		$this->processedDeletedEntryIds[$id] = $id;

		$text = $params['COMMENT'];
		if(!$text)
		{
			$text = GetMessage('CRM_DOCUMENT_CONTROLLER_HISTORY_DELETE_MESSAGE');
		}

		if((int)$params['TYPE_CATEGORY_ID'] !== TimelineType::MODIFICATION)
		{
			$this->addEvent($text, $params);
		}
		if ($params['ENTITY_TYPE_ID'] && $params['ENTITY_ID'])
		{
			$this->sendPullEventOnDelete(new ItemIdentifier($params['ENTITY_TYPE_ID'], $params['ENTITY_ID']), $id, $params['USER_ID']);
		}

		DocumentEntry::delete($id);

		parent::onDelete($id, $params);
	}

	public function onUpdate($id, array $params)
	{
		$id = (int)$id;
		if($id <= 0)
		{
			throw new ArgumentException('id must be greater than zero.');
		}

		$title = $params['TITLE'];
		if(!$title)
		{
			$title = '';
		}
		$text = GetMessage('CRM_DOCUMENT_CONTROLLER_HISTORY_UPDATE_MESSAGE', ['#TITLE#' => $title]);
		$this->addEvent($text, $params);
		if ($params['ENTITY_TYPE_ID'] && $params['ENTITY_ID'])
		{
			$this->sendPullEventOnUpdate(new ItemIdentifier($params['ENTITY_TYPE_ID'], $params['ENTITY_ID']), $id, $params['USER_ID']);
		}
	}

	final public function onDocumentTransformationComplete(int $id, array $params): void
	{
		$ownerTypeId = (int)($params['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::Undefined);
		$ownerId = (int)($params['ENTITY_ID'] ?? 0);

		if (\CCrmOwnerType::IsDefined($ownerTypeId) && $ownerId > 0)
		{
			$owner = new ItemIdentifier($ownerTypeId, $ownerId);
			$entryIds = TimelineEntry::getEntriesIdsByAssociatedEntity(TimelineType::DOCUMENT, $id, 5);

			foreach ($entryIds as $entryId)
			{
				$this->sendPullEventOnUpdate(
					$owner,
					$entryId,
				);
			}
		}
	}

	public function prepareSearchContent(array $params)
	{
		$result = '';
		if(isset($params['COMMENT']))
		{
			//Try to parse document title.
			if(preg_match('/<a[^>]*>([^<]+)<\/a>/', $params['COMMENT'], $match))
			{
				$result = $match[1];
			}
			else
			{
				//We have HTML in this comment. Just remove all HTML tags.
				$result = strip_tags($params['COMMENT']);
			}
		}
		return $result;
	}

	protected function addEvent($text, array $params)
	{
		$ownerTypeID = $params['ENTITY_TYPE_ID'];
		$ownerID = $params['ENTITY_ID'];
		$userId = $params['USER_ID'];
		if(!$userId)
		{
			$userId = \CCrmSecurityHelper::GetCurrentUserID();
		}

		$text = strip_tags($text);

		$event = new \CCrmEvent();
		$event->Add([
			'USER_ID' => $userId,
			'ENTITY_ID' => $ownerID,
			'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($ownerTypeID),
			'EVENT_TYPE' => \CCrmEvent::TYPE_USER,
			'EVENT_NAME' => $text,
			'DATE_CREATE' => ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL', SITE_ID)
		], false);
	}
}
