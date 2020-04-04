<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;

final class DocumentController extends EntityController
{
	/** @var DocumentController|null */
	private static $instance = null;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * @return DocumentController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new static();
		}
		return self::$instance;
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
		$this->addToStack($id, 'timeline_document_add', $params);
	}

	public function onDelete($id, array $params)
	{
		$id = (int)$id;
		if($id <= 0)
		{
			throw new ArgumentException('id must be greater than zero.');
		}

		$text = $params['COMMENT'];
		if(!$text)
		{
			$text = GetMessage('CRM_DOCUMENT_CONTROLLER_HISTORY_DELETE_MESSAGE');
		}

		$this->addEvent($text, $params);
		$this->addToStack($id, 'timeline_document_delete', $params);

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
		$this->addToStack($id, 'timeline_document_update', $params);
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

	protected function addToStack($id, $command, array $params)
	{
		if(Loader::includeModule('pull') && \CPullOptions::GetQueueServerStatus())
		{
			$items = [$id => TimelineEntry::getByID($id)];
			TimelineManager::prepareDisplayData($items);

			$ownerTypeID = $params['ENTITY_TYPE_ID'];
			$ownerID = $params['ENTITY_ID'];
			$tag = TimelineEntry::prepareEntityPushTag($ownerTypeID, $ownerID);
			\CPullWatch::AddToStack(
				$tag,
				[
					'module_id' => 'crm',
					'command' => $command,
					'params' => ['TAG' => $tag, 'HISTORY_ITEM' => $items[$id]],
				]
			);
		}
	}
}