<?php

namespace Bitrix\Crm\Order\Buyer\Agent;

use Bitrix\Crm\Order\Buyer;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\OrderTable;

/**
 * Update user external id for buyers (users with existed orders).
 * Only for users with empty external id.
 * Only for not cloud versions.
 */
class UpdateUserExternalIdAgent
{
	private const ONE_RUN_LIMIT = 100;
	private const OPTION_LAST_ID = 'lastId';

	/**
	 * Run agent.
	 *
	 * @return void
	 */
	public static function runAgent()
	{
		$self = new static();

		if ($self->run())
		{
			$self->deleteLastProcessUserId();
			return '';
		}

		return __METHOD__ . '();';
	}

	/**
	 * Internal run.
	 *
	 * @return bool TRUE - is finished
	 */
	private function run(): bool
	{
		if (!Loader::includeModule('sale') || Loader::includeModule('bitrix24'))
		{
			return true;
		}

		$lastId = $this->getLastProcessUserId();
		$extranetUsersWithoutExternalIds = $this->getExtranetUsersWithoutExternalIds($lastId);
		if (empty($extranetUsersWithoutExternalIds))
		{
			return true;
		}
		$this->setLastProcessUserId(end($extranetUsersWithoutExternalIds));

		$usersWithOrdersIds = OrderTable::getList([
			'select' => [
				'USER_ID',
			],
			'filter' => [
				'=USER_ID' => $extranetUsersWithoutExternalIds,
			],
		]);
		$usersWithOrdersIds = array_column($usersWithOrdersIds->fetchAll(), 'USER_ID');
		if (empty($usersWithOrdersIds))
		{
			return false;
		}

		$usersWithOrdersIds = join(',', $usersWithOrdersIds);
		$sql = new SqlExpression(
			"UPDATE ?# SET `EXTERNAL_AUTH_ID` = ? WHERE ID IN ({$usersWithOrdersIds})",
			UserTable::getTableName(),
			Buyer::AUTH_ID
		);

		Application::getConnection()->queryExecute($sql);
		UserTable::getEntity()->cleanCache();

		return false;
	}

	/**
	 * Extranet users ids.
	 *
	 * @return array
	 */
	private function getExtranetUsersWithoutExternalIds(int $lastId): array
	{
		$extranetUsersWithoutExternalIds = UserTable::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'>ID' => $lastId,
				'=ACTIVE' => 'Y',
				'=EXTERNAL_AUTH_ID' => null,
				'=UF_DEPARTMENT' => null,
			],
			'order' => [
				'ID' => 'asc',
			],
			'limit' => self::ONE_RUN_LIMIT,
		]);
		return array_column($extranetUsersWithoutExternalIds->fetchAll(), 'ID');
	}

	/**
	 * Get last processed id.
	 *
	 * @return int
	 */
	private function getLastProcessUserId(): int
	{
		return (int)Option::get('crm', self::OPTION_LAST_ID, 0);
	}

	/**
	 * Set last processed id.
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	private function setLastProcessUserId(int $id): void
	{
		Option::set('crm', self::OPTION_LAST_ID, $id);
	}

	/**
	 * Delete last processed id.
	 *
	 * @return void
	 */
	public function deleteLastProcessUserId(): void
	{
		Option::delete('crm', [
			'name' => self::OPTION_LAST_ID,
		]);
	}
}