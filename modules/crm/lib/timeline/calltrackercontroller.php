<?php
namespace Bitrix\Crm\Timeline;

use Bitrix\Main;

class CallTrackerController extends EntityController
{
	/** @var CallTrackerController|null */
	private static $instance = null;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * @return CallTrackerController
	 */
	public static function getInstance(): ?CallTrackerController
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
			throw new Main\ArgumentException(
				'Owner ID must be greater than zero.',
				'ownerID'
			);
		}

		$settings = (is_array($params['SETTINGS']) ? $params['SETTINGS'] : []);
		$bindings = ($params['BINDINGS'] ?? []);

		$authorId = \CCrmOwnerType::GetResponsibleID(
			\CCrmOwnerType::Activity,
			$ownerId,
			false
		);

		if (!empty($settings))
		{
			$historyEntryId = CallTrackerEntry::create([
				'ENTITY_ID' => $ownerId,
				'AUTHOR_ID' => $authorId,
				'BINDINGS' => self::mapBindings($bindings),
				'SETTINGS' => $settings
			]);

			if ($historyEntryId > 0)
			{
				foreach ($bindings as $binding)
				{
					$tag = TimelineEntry::prepareEntityPushTag(
						$binding['OWNER_TYPE_ID'],
						$binding['OWNER_ID']
					);
					self::pushHistoryEntry(
						$historyEntryId,
						$tag,
						'timeline_activity_add'
					);
				}
			}
		}
	}

	protected static function pushHistoryEntry($entryId, $tagName, $command): void
	{
		if(!Main\Loader::includeModule('pull'))
		{
			return;
		}

		$params = ['TAG' => $tagName];
		$entryFields = TimelineEntry::getByID($entryId);
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
			static function($binding)
			{
				return [
					'ENTITY_TYPE_ID' => $binding['OWNER_TYPE_ID'],
					'ENTITY_ID' => $binding['OWNER_ID']
				];
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