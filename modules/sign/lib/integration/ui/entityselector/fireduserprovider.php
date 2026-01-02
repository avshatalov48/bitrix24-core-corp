<?php

namespace Bitrix\Sign\Integration\Ui\EntitySelector;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EO_User;
use Bitrix\Main\EO_User_Collection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\Tab;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Factory\Access\AccessibleItemFactory;
use Bitrix\Sign\Service\Container;

if (!Loader::includeModule('socialnetwork'))
{
	return;
}

/**
 * @see Socialnetwork\Integration\UI\EntitySelector\FiredUserProvider
 */
class FiredUserProvider extends Socialnetwork\Integration\UI\EntitySelector\UserProvider
{
	private AccessController $accessController;
	private AccessibleItemFactory $accessibleItemFactory;

	protected const ENTITY_ID = 'sign-fired-user';

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$currentUser = (int)CurrentUser::get()->getId();

		if ($currentUser <= 0)
		{
			throw new SystemException('The user must be authorized to use sign-fired-user provider');
		}

		$this->accessController = new AccessController($currentUser);
		$this->accessibleItemFactory = Container::instance()->getAccessibleItemFactory();
	}

	public function isAvailable(): bool
	{
		if (!$this->accessController->check(ActionDictionary::ACTION_B2E_MY_SAFE_FIRED))
		{
			return false;
		}

		return parent::isAvailable();
	}

	protected function prepareOptions(array $options = []): void
	{
		parent::prepareOptions($options);
		$this->options['activeUsers'] = false;
	}

	protected function getPreloadedUsersCollection(): EO_User_Collection
	{
		return $this->getUserCollection([
			'order' => [
				'LAST_ACTIVITY_DATE' => 'desc',
			],
			'limit' => self::MAX_USERS_IN_RECENT_TAB,
		]);
	}

	public function handleBeforeItemSave(Item $item): void
	{
		// exclude fired users from the recent tab
		$item->setSaveable(false);
	}

	public function fillDialog(Dialog $dialog): void
	{
		parent::fillDialog($dialog);

		if ($this->shouldShowFiredUsersTab($dialog))
		{
			$firedTab = $this->createTab();
			$dialog->addTab($firedTab);
		}
	}

	public static function makeItem(EO_User $user, array $options = []): Item
	{
		$item = parent::makeItem($user, $options);

		// exclude fired users from the recent tab
		$item->setAvailableInRecentTab(false);

		return $item;
	}

	private function shouldShowFiredUsersTab(Dialog $dialog): bool
	{
		return count($dialog->getItemCollection()->getEntityItems(self::ENTITY_ID)) > 0;
	}

	private function createTab(): Tab
	{
		return new Tab([
			'id' => self::ENTITY_ID,
			'title' => Loc::getMessage('SIGN_ENTITY_SELECTOR_FIREDUSER_TAB_TITLE'),
			'stub' => true,
			'icon' => [
				'default' => $this->getTabIcon(),
				'selected' => $this->getSelectedTabIcon(),
			]
		]);
	}

	private function getSelectedTabIcon(): string
	{
		return str_replace('ABB1B8', 'fff', $this->getTabIcon());
	}

	private function getTabIcon(): string
	{
		return
			'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2223%22%20height%3D%2223%22%20' .
			'fill%3D%22none%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M11' .
			'.934%202.213a.719.719%200%2001.719%200l3.103%201.79c.222.13.36.367.36.623V8.21a.719.71' .
			'9%200%2001-.36.623l-3.103%201.791a.72.72%200%2001-.719%200L8.831%208.832a.719.719%200%' .
			'2001-.36-.623V4.627c0-.257.138-.495.36-.623l3.103-1.791zM7.038%2010.605a.719.719%200%2' .
			'001.719%200l3.103%201.792a.72.72%200%2001.359.622v3.583a.72.72%200%2001-.36.622l-3.102' .
			'%201.792a.719.719%200%2001-.72%200l-3.102-1.791a.72.72%200%2001-.36-.623v-3.583c0-.257' .
			'.138-.494.36-.622l3.103-1.792zM20.829%2013.02a.719.719%200%2000-.36-.623l-3.102-1.792a' .
			'.719.719%200%2000-.72%200l-3.102%201.792a.72.72%200%2000-.36.622v3.583a.72.72%200%2000' .
			'.36.622l3.103%201.792a.719.719%200%2000.719%200l3.102-1.791a.719.719%200%2000.36-.623v' .
			'-3.583z%22%20fill%3D%22%23ABB1B8%22/%3E%3C/svg%3E'
		;
	}
}
