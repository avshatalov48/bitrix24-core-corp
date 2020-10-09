<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;

class ZoomController extends EntityController
{
	/** @var ZoomController|null */
	private static $instance = null;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * @return ZoomController
	 */
	public static function getInstance(): ?ZoomController
	{
		if (self::$instance === null)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	public function onCreate($ownerId, array $params): void
	{
		$ownerId = (int)$ownerId;
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$settings = is_array($params['SETTINGS']) ? $params['SETTINGS'] : [];
		$bindings = $params['BINDINGS'] ?? [];

		$authorId = \CCrmOwnerType::GetResponsibleID(\CCrmOwnerType::Activity, $ownerId, false);

		if (!empty($settings))
		{
			$historyEntryID = ZoomEntry::create([
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => self::mapBindings($bindings),
				'SETTINGS' => $settings
			]);

			if ($historyEntryID > 0)
			{
				foreach ($bindings as $binding)
				{
					$tag = TimelineEntry::prepareEntityPushTag($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']);
					self::pushHistoryEntry($historyEntryID, $tag, 'timeline_activity_add');
				}
			}
		}
	}

	protected static function pushHistoryEntry($entryID, $tagName, $command): void
	{
		if(!Main\Loader::includeModule('pull'))
		{
			return;
		}

		$params = array('TAG' => $tagName);
		$entryFields = TimelineEntry::getByID($entryID);
		if(is_array($entryFields))
		{
			self::prepareItemDisplayData($entryFields, $entryFields['AUTHOR_ID']);
			$params['HISTORY_ITEM'] = $entryFields;
		}

		\CPullWatch::AddToStack(
			$tagName,
			array(
				'module_id' => 'crm',
				'command' => $command,
				'params' => $params,
			)
		);
	}

	protected static function mapBindings(array $bindings)
	{
		return array_map(
			function($binding)
			{
				return array(
					'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
					'ENTITY_ID' => $binding['OWNER_ID']
				);
			},
			$bindings
		);
	}

	private static function prepareItemDisplayData(array &$item, int $userId): void
	{
		$items = array($item);
		\Bitrix\Crm\Timeline\TimelineManager::prepareDisplayData($items, $userId);
		$item = $items[0];
	}
}