<?php

namespace Bitrix\Crm\Component\EntityList\UserDataProvider;

use Bitrix\Crm\Item;
use Bitrix\Crm\Observer\ObserverManager;
use Bitrix\Crm\Service\Container;

final class Observers extends Base
{
	/**
	 * @param array $select
	 *
	 * @return void
	 */
	public function prepareSelect(array &$select): void
	{
		if (in_array(Item::FIELD_NAME_OBSERVERS, $select, true))
		{
			$this->addToResultFields['OBSERVER_USER'] = [
				self::FIELD_FORMATTED_NAME,
				self::FIELD_SHOW_URL,
			];
		}
	}

	/**
	 * @param array $entities
	 *
	 * @return void
	 */
	public function appendResult(array &$entities): void
	{
		if (empty($this->addToResultFields))
		{
			return;
		}

		$observers = ObserverManager::getEntityBulkObserverIDs($this->entityTypeId, array_keys($entities));
		if (empty($observers))
		{
			return;
		}

		$userIds = array_values(array_unique(array_merge(...array_values($observers))));
		if (empty($userIds))
		{
			return;
		}

		$this->fillEntities($userIds, $entities, $observers);
	}

	protected function fillEntities(array $userIds, array &$entities, array $params = []): void
	{
		$userData = $this->prepareUserData(Container::getInstance()->getUserBroker()->getBunchByIds($userIds), true);
		$userData = array_filter(array_combine(array_keys($userData), array_column($userData, 'OBSERVER_USER')));

		foreach ($entities as $entityId => $entity)
		{
			if (empty($params[$entityId]))
			{
				continue;
			}

			$entityObservers = array_values(
				array_filter(
					$userData,
					static fn(int $userId) => in_array((int)$userId, $params[$entityId], true),
					ARRAY_FILTER_USE_KEY,
				)
			);

			$entities[$entityId][Item::FIELD_NAME_OBSERVERS] = $entityObservers;
		}
	}
}
