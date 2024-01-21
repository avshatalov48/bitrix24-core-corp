<?php

namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\DateTimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class NoteTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Note_Query query()
 * @method static EO_Note_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Note_Result getById($id)
 * @method static EO_Note_Result getList(array $parameters = [])
 * @method static EO_Note_Entity getEntity()
 * @method static \Bitrix\Crm\Timeline\Entity\EO_Note createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_Note_Collection createCollection()
 * @method static \Bitrix\Crm\Timeline\Entity\EO_Note wakeUpObject($row)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_Note_Collection wakeUpCollection($rows)
 */
class NoteTable extends Entity\DataManager
{
	public const NOTE_TYPE_HISTORY = 1;
	public const NOTE_TYPE_ACTIVITY = 2;
	public const NOTE_TYPE_SUSPENDED_ACTIVITY = 3;

	public static function getTableName()
	{
		return 'b_crm_timeline_note';
	}

	public static function getMap()
	{
		return [
			new IntegerField('ID', ['primary' => true, 'autocomplete' => true,]),
			new IntegerField('ITEM_TYPE', ['required' => true]),
			new IntegerField('ITEM_ID', ['required' => true]),
			new IntegerField('CREATED_BY_ID', ['required' => true]),
			new DateTimeField('CREATED_TIME', ['required' => true]),
			new IntegerField('UPDATED_BY_ID', ['required' => true]),
			new DateTimeField('UPDATED_TIME', ['required' => true]),
			(new StringField('TEXT', ['required' => true]))
				->addSaveDataModifier([\Bitrix\Main\Text\Emoji::class, 'encode'])
				->addFetchDataModifier([\Bitrix\Main\Text\Emoji::class, 'decode'])
			,
		];
	}

	public static function loadForItems(array $items, int $itemType): array
	{
		if (empty($items))
		{
			return $items;
		}

		$itemIds = array_column($items, 'ID');

		$notes = NoteTable::query()
			->addSelect('*')
			->whereIn('ITEM_ID', $itemIds)
			->where('ITEM_TYPE', $itemType)
			->fetchAll()
		;

		$noteItemIds = array_column($notes, 'ITEM_ID');
		$notesMap = array_combine($noteItemIds, $notes);
		$userIDs = array_column($notes, 'UPDATED_BY_ID');
		$users = Container::getInstance()->getUserBroker()->getBunchByIds($userIDs);

		foreach ($items as $id => $item)
		{
			$itemId = $item['ID'];
			$note = $notesMap[$itemId] ?? null;

			if ($note)
			{
				$items[$id]['NOTE'] = [
					'ID' => (int)$note['ID'],
					'ITEM_TYPE' => (int)$note['ITEM_TYPE'],
					'ITEM_ID' => (int)$note['ITEM_ID'],
					'TEXT' => $note['TEXT'],
					'UPDATED_BY' => $users[$note['UPDATED_BY_ID']],
				];
			}
		}

		return $items;
	}

	public static function deleteByItemId(int $itemType, int $itemId): void
	{
		$record = self::query()
			->where('ITEM_TYPE', $itemType)
			->where('ITEM_ID', $itemId)
			->setSelect(['ID'])
			->fetch()
		;
		if ($record)
		{
			self::delete($record['ID']);
		}
	}

	public static function rebind(int $fromItemType, int $fromItemId, int $toItemType, int $toItemId)
	{
		$record = self::query()
			->where('ITEM_TYPE', $fromItemType)
			->where('ITEM_ID', $fromItemId)
			->setSelect(['ID'])
			->fetch()
		;
		if ($record)
		{
			self::update($record['ID'], [
				'ITEM_TYPE' => $toItemType,
				'ITEM_ID' => $toItemId,
			]);
		}
	}
}
