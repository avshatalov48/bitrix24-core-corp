<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Action;

use CPullWatch;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Kanban\Entity;

class GetListAction extends Action
{
	public function run(string $entityType, PageNavigation $pageNavigation, array $extra = [])
	{
		$this->checkModules();
		if ($this->hasErrors())
		{
			return $this->showErrors();
		}

		$permissions = $this->getUserPermissions($entityType, $extra);

		if (!$permissions['read'])
		{
			return [
				'items' => [],
				'permissions' => $permissions,
			];
		}

		$extra['userId'] = $this->getCurrentUser()->getId();
		$extra['isReckonActivityLessItems'] = \CCrmUserCounterSettings::getValue(
			\CCrmUserCounterSettings::ReckonActivitylessItems,
			true
		);

		$entity = Entity::getInstance($entityType)
			->prepare($extra)
			->setPageNavigation($pageNavigation);

		$result = $entity->getList();

		if ($pageNavigation->getOffset() === 0)
		{
			$result['permissions'] = $permissions;

			if (empty($extra['subscribeUser']) || $extra['subscribeUser'] === 'true')
			{
				$result['isSubscribed'] = $this->subscribeUserToPull($entityType, $extra);
			}
		}

		return $result;
	}

	/**
	 * @param string $entityType
	 * @param array $extra
	 * @return array
	 */
	protected function getUserPermissions(string $entityType, array $extra = []): array
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
		$categoryId = (int)($extra['filterParams']['CATEGORY_ID'] ?? 0);

		$userPermissions = Container::getInstance()->getUserPermissions();

		return [
			'read' => $userPermissions->checkReadPermissions($entityTypeId, 0, $categoryId),
			'write' => $userPermissions->checkUpdatePermissions($entityTypeId, 0, $categoryId),
			'add' => $userPermissions->checkAddPermissions($entityTypeId, $categoryId),
		];
	}

	/**
	 * @param string $entityType
	 * @param array $extra
	 * @return bool
	 */
	private function subscribeUserToPull(string $entityType, array $extra): bool
	{
		$userId = $this->getCurrentUser()->getId();
		if ($userId > 0 && Loader::requireModule('pull'))
		{
			return CPullWatch::Add($userId, $this->getPullTag($entityType, $extra));
		}
		return false;
	}

	/**
	 * @param string $entityType
	 * @param array $extra
	 * @return string
	 */
	private function getPullTag(string $entityType, array $extra): string
	{
		$parts = [
			PullManager::EVENT_KANBAN_UPDATED,
			$entityType,
			($extra['filterParams']['CATEGORY_ID'] ?? 0),
		];

		return implode('_', $parts);
	}
}
