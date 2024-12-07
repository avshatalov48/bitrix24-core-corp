<?php

namespace Bitrix\Crm\Service\Communication\Controller;

use Bitrix\Crm\Service\Communication\Channel\Channel;
use Bitrix\Crm\Service\Communication\Channel\ChannelInterface;
use Bitrix\Crm\Service\Communication\Channel\ChannelsCollection;
use Bitrix\Crm\Service\Communication\Entity\CommunicationChannelTable;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Result;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Data\AddResult;
use ReflectionClass;
use ReflectionException;

final class ChannelController
{
	use Singleton;

	/**
	 * @throws ReflectionException
	 * @throws NotImplementedException
	 */
	public function register(
		string $moduleId,
		string $code,
		int $categoryId,
		int $userId,
		string $handlerClass,
	): AddResult
	{
		$reflect = new ReflectionClass($handlerClass);
		if (!$reflect->implementsInterface(ChannelInterface::class))
		{
			throw new \Bitrix\Main\NotImplementedException(
				$handlerClass . ' does not implement ' . ChannelInterface::class
			);
		}

		return CommunicationChannelTable::add([
			'MODULE_ID' => $moduleId,
			'CODE' => $code,
			'CATEGORY_ID' => $categoryId,
			'HANDLER_CLASS' => $handlerClass,
			'CREATED_BY_ID' => $userId,
			'UPDATED_BY_ID' => $userId,
		]);
	}

	public function unregister(string $moduleId, string $code): Result
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();

		$sql =
			'DELETE FROM ' . CommunicationChannelTable::getTableName()
			. ' WHERE MODULE_ID =' . $sqlHelper->convertToDbString($moduleId)
			. ' AND CODE =' . $sqlHelper->convertToDbString($code)
		;

		return Application::getConnection()->query($sql);
	}

	public function getChannel(string $moduleId, string $code): ?Channel
	{
		$channel = CommunicationChannelTable::getRow([
			'select' => [
				'ID',
				'MODULE_ID',
				'CATEGORY_ID',
				'CODE',
				'HANDLER_CLASS',
				'IS_ENABLED',
			],
			'filter' => [
				'=MODULE_ID' => $moduleId,
				'=CODE' => $code,
			],
		]);

		if (!$channel)
		{
			return null;
		}

		return new Channel(
			$channel['ID'],
			$channel['MODULE_ID'],
			$channel['CATEGORY_ID'],
			$channel['CODE'],
			$channel['HANDLER_CLASS'],
			$channel['IS_ENABLED'] === 'Y',
		);
	}

	public function getChannels(int $offset = 0): ChannelsCollection
	{
		$collection = CommunicationChannelTable::getList([
			'select' => ['ID', 'MODULE_ID', 'CODE', 'HANDLER_CLASS', 'IS_ENABLED'],
			//'filter' => ['IS_ENABLED' => 'Y'],
			'order' => [
				'SORT' => 'ASC',
			],
			'limit' => 50,
			'offset' => $offset,
		])->fetchCollection();

		$channels = [];
		foreach ($collection as $item)
		{
			$channels[] = new Channel(
				$item['ID'],
				$item['MODULE_ID'],
				$item['CATEGORY_ID'] ?? 0,
				$item['CODE'],
				$item['HANDLER_CLASS'],
				$item['IS_ENABLED'] === 'Y',
			);
		}

		return new ChannelsCollection($channels);
	}
}
