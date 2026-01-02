<?php

namespace Bitrix\HumanResources\Integration\UI;

use Bitrix\HumanResources\Type\StructureRole;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Integration\UI\EntitySelector\UserProvider;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;

final class RoleProvider extends BaseProvider
{
	private const ENTITY_ID = 'structure-node-role';
	private const TAB_ID = 'structure-node-roles';
	/**
	 * @var int[]
	 */
	private array $excludedRoleIdList = [];

	public function __construct(array $options = [])
	{
		parent::__construct();
		if (isset($options['excludedRoleIdList']) && is_array($options['excludedRoleIdList']))
		{
			$this->excludedRoleIdList = array_map(fn(int $roleId): int => $roleId, $options['excludedRoleIdList']);
		}
	}

	public function isAvailable(): bool
	{
		if ((int)CurrentUser::get()->getId() < 1)
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		return UserProvider::isIntranetUser();
	}

	public function getItems(array $ids): array
	{
		$result = [];

		$result[] = new Item(
			[
				'id' => StructureRole::HEAD->value,
				'entityId' => self::ENTITY_ID,
				'title' => Loc::getMessage(
						'HUMANRESOURCES_INTEGRATION_ROLE_PROVIDER_HEAD',
					) ?? '',
				'availableInRecentTab' => true,
				'searchable' => true,
				'tabs' => self::TAB_ID,
			],
		);

		$result[] = new Item(
			[
				'id' => StructureRole::DEPUTY_HEAD->value,
				'entityId' => self::ENTITY_ID,
				'title' => Loc::getMessage(
						'HUMANRESOURCES_INTEGRATION_ROLE_PROVIDER_DEPUTY_HEAD',
					) ?? '',
				'availableInRecentTab' => true,
				'searchable' => true,
				'tabs' => self::TAB_ID,
			],
		);

		return array_filter(
			$result,
			fn(Item $item): bool => !in_array($item->getId(), $this->excludedRoleIdList, true)
		);
	}

	public function fillDialog(Dialog $dialog): void
	{
		$icon = 'data:image/svg+xml,%3Csvg width="23" height="23" fill="none" xmlns="http://www.w3.org/2000/svg'
			. '"%3E%3Cpath d="M12.479 4.062a.3.3 0 00-.512.212v4.455H4.249a.3.3 0 00-.3.3v4.879a.3.3 0 00.3.3h7.71'
			. '8v4.455a.3.3 0 00.512.212l7.195-7.194a.3.3 0 000-.425l-7.195-7.194z" fill="%23ABB1B8"/%3E%3C/svg%3E';

		$items = $this->getItems([]);

		foreach ($items as $item)
		{
			$dialog->addItem($item);
		}

		$dialog->addTab(
			new Tab(
				[
					'id' => self::TAB_ID,
					'title' => Loc::getMessage('HUMANRESOURCES_INTEGRATION_ROLE_PROVIDER_TAB_TITLE') ?? '',
					'itemMaxDepth' => 7,
					'icon' => [
						'default' => $icon,
						'selected' => str_replace('ABB1B8', 'fff', $icon),
					],
				],
			),
		);
	}
}
