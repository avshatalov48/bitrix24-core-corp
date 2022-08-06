<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Tab;

class ScrumUserProvider extends BaseProvider
{
	private $entityId = 'scrum-user';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['groupId'] = (int) $options['groupId'];
	}

	public function isAvailable(): bool
	{
		if (!$GLOBALS['USER']->isAuthorized())
		{
			return false;
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$groupId = $this->getOption('groupId');

		$group = Workgroup::getById($groupId);
		if (!$group || !$group->isScrumProject())
		{
			return false;
		}

		return true;
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	public function getSelectedItems(array $ids): array
	{
		return [];
	}

	public function fillDialog(Dialog $dialog): void
	{
		$groupId = $this->getOption('groupId');

		$userIds = $this->getUserToGroupIds($groupId);

		foreach (Dialog::getItems($userIds) as $item)
		{
			$item->addTab($this->entityId);
			$dialog->addItem($item);
		}

		$dialog->addTab(new Tab([
			'id' => $this->entityId,
			'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_SCRUM_USER_TAB'),
		]));
	}

	private function getUserToGroupIds(int $groupId): array
	{
		$userIds = [];

		$queryObject = UserToGroupTable::getList(
			[
				'filter' => [
					'GROUP_ID' => $groupId,
					'USER.ACTIVE' => 'Y',
					'ROLE' => [
						UserToGroupTable::ROLE_OWNER,
						UserToGroupTable::ROLE_MODERATOR,
					]
				],
				'select' => [
					'USER_ID',
					'GROUP_OWNER_ID' => 'GROUP.OWNER_ID',
				]
			]
		);
		while ($userToGroup = $queryObject->fetch())
		{
			$userIds[] = ['user', $userToGroup['USER_ID']];
		}

		return $userIds;
	}
}