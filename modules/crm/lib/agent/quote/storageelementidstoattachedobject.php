<?php

namespace Bitrix\Crm\Agent\Quote;


use Bitrix\Crm\Integration\Disk\DiskRepository;
use Bitrix\Crm\Integration\Disk\Dto\SaveAOParam;
use Bitrix\Crm\Integration\Disk\QuoteConnector;
use Bitrix\Crm\Integration\Disk\QuoteItemAttachedObjectPersist;
use Bitrix\Crm\Item\Quote;
use Bitrix\Crm\QuoteTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use CModule;

/**
 * Converts the STORAGE_ELEMENT_IDS fields in "Offers" so that the IDs stored there refer
 * to the Bitrix\Disk\AttachedObject instead directly to the ID of the file in the storage
 */
class StorageElementIdsToAttachedObject
{
	use Singleton;

	private const DONE = true;

	private const CONTINUE = false;

	private const DEFAULT_LIMIT = 20;

	private const LIMIT_OPTION_KEY = 'quote_storage_element_ids_convert_progress_limit';

	private const LAST_ID_OPTION_KEY = 'quote_storage_element_ids_convert_progress_last_id';

	public function execute(): bool
	{
		if (!CModule::IncludeModule("disk"))
		{
			return self::DONE;
		}

		$quotes = $this->queryQuotes($this->getLimit(), $this->getLastId());

		if (empty($quotes))
		{
			return self::DONE;
		}

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Quote);

		foreach ($quotes as $quote)
		{
			/** @var Quote $item */
			$item = $factory->createItem($quote);

			$this->convertStorageElements($item);
		}

		$this->setLastId(end($quotes)['ID']);

		return self::CONTINUE;
	}

	private function queryQuotes(int $limit, int $offset): array
	{
		return QuoteTable::query()
			->setSelect(['ID', 'STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS', 'ASSIGNED_BY_ID'])
			->whereNotNull('STORAGE_ELEMENT_IDS')
			->setLimit($limit)
			->where('ID', '>', $offset)
			->setOrder(['ID' => 'ASC'])
			->fetchAll();
	}

	private function getLimit(): int
	{
		return Option::get('crm', self::LIMIT_OPTION_KEY, self::DEFAULT_LIMIT);
	}

	private function getLastId(): int
	{
		return Option::get('crm', self::LAST_ID_OPTION_KEY, -1);
	}

	private function setLastId(int $lastId): void
	{
		Option::set('crm', self::LAST_ID_OPTION_KEY, $lastId);
	}


	private function checkIsConversionNeeded(Quote $quote): bool
	{
		if ($quote->isNew())
		{
			return false;
		}

		if (!CModule::IncludeModule("disk"))
		{
			return false;
		}

		if (Option::get('crm', 'quote_storage_element_ids_convert_progress', 'N') === 'N')
		{
			return false;
		}

		if (empty($quote->getStorageElementIds()))
		{
			return false;
		}

		return !DiskRepository::getInstance()
			->existsByEntity($quote->getId(), QuoteConnector::class);
	}

	public function convertStorageElements(Quote $quote): void
	{
		if (!$this->checkIsConversionNeeded($quote))
		{
			return;
		}

		$fileIds = $quote->getStorageElementIds();
		$fileIds = array_map(function ($id) {
			if (is_string($id) && strlen($id) > 0 && $id[0] === 'n')
			{
				return (int)substr($id, 1);
			}

			return $id;
		}, $fileIds);

		if (empty($fileIds))
		{
			return;
		}

		$fileIds = array_filter($fileIds, fn($id) => $id > 0);

		// n prefix means that it's "new" file, so we need to save it in the attached object table
		$fileIds = array_map(fn($id) => 'n' . $id, $fileIds);

		$param = new SaveAOParam($quote->getId(), [], $fileIds, $quote->getAssignedById());

		$attachedObjectIds = QuoteItemAttachedObjectPersist::getInstance()
			->saveAllAsAttachedObject($param);

		QuoteTable::update($quote->getId(), ['STORAGE_ELEMENT_IDS' => $attachedObjectIds]);

		$quote->setStorageElementIds($attachedObjectIds);
	}

	public static function cleanOptions(): void
	{
		Option::delete('crm', ['name' => self::LAST_ID_OPTION_KEY]);
		Option::delete('crm', ['name' => self::LIMIT_OPTION_KEY]);
		Option::delete('crm', ['name' => 'quote_storage_element_ids_convert_progress']);
	}
}