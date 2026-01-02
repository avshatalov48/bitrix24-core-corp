<?php

namespace Bitrix\BIConnector\Integration\UI\EntitySelector;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroup;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupTable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;

class SupersetGroupProvider extends BaseProvider
{
	public const ENTITY_ID = 'biconnector-superset-group';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['onlySystemGroups'] = (bool)($options['onlySystemGroups'] ?? false);
		$this->options['checkAccessRights'] = (bool)($options['checkAccessRights'] ?? true);
	}

	public function isAvailable(): bool
	{
		global $USER;

		return is_object($USER) && $USER->isAuthorized();
	}

	public function fillDialog(Dialog $dialog): void
	{
		$dialog->addItems($this->getItems([]));
		$dialog->addTab(new Tab([
			'id' => 'groups',
			'title' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GROUP_PROVIDER_TAB_LABEL'),
		]));
	}

	public function getItems(array $ids): array
	{
		$result = [];
		$filter = [];

		if ($this->options['onlySystemGroups'])
		{
			$filter['TYPE'] = SupersetDashboardGroupTable::GROUP_TYPE_SYSTEM;
		}

		if ($this->options['checkAccessRights'])
		{
			$groupFilter = AccessController::getCurrent()->getEntityFilter(
				ActionDictionary::ACTION_BIC_DASHBOARD_VIEW,
				SupersetDashboardGroupTable::class,
			);
			if ($groupFilter)
			{
				$filter['ID'] = $groupFilter['=ID'];
			}
		}

		$groups = SupersetDashboardGroupTable::getList([
			'select' => ['ID', 'NAME', 'TYPE', 'SCOPE'],
			'filter' => $filter,
		])
			->fetchCollection()
		;

		foreach ($groups as $group)
		{
			$result[] = $this->makeItem($group);
		}

		return $result;
	}

	private function makeItem(SupersetDashboardGroup $group): Item
	{
		$itemParams = [
			'id' => $group->getId(),
			'entityId' => self::ENTITY_ID,
			'title' => $group->getName(),
			'description' => null,
			'tabs' => 'groups',
			'saveable' => false,
			'avatar' => $group->isSystem()
				? '/bitrix/js/ui/icons/disk/images/ui-icon-air-folder-24.svg'
				: '/bitrix/js/ui/icons/disk/images/ui-icon-air-folder-person.svg',
			'avatarOptions' => [
				'borderRadius' => '4px',
			],
			'customData' => [
				'groupScopes' => $group->getScope()->getScopeCodeList(),
			],
		];

		return new Item($itemParams);
	}
}
