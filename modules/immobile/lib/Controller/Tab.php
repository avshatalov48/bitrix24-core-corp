<?php

namespace Bitrix\ImMobile\Controller;

use Bitrix\Call\Call;
use Bitrix\Im\Department;
use Bitrix\Im\Promotion;
use Bitrix\Im\V2\Anchor\DI\AnchorContainer;
use Bitrix\Im\V2\Entity\User\User;
use Bitrix\Im\V2\Message\CounterService;
use Bitrix\Im\V2\Recent\RecentChannel;
use Bitrix\Im\V2\Recent\RecentCollab;
use Bitrix\Im\V2\TariffLimit\Limit;
use Bitrix\ImMobile\NavigationTab\Tab\AvailableMethodList;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use CIMMessenger;
use CUserCounter;

abstract class Tab extends BaseController
{
	protected const PROMO_TYPE ='mobile';
	protected const OFFSET = 0;
	protected const LIMIT = 50;

	protected array $options;
	protected CurrentUser $currentUser;

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			\Bitrix\Im\V2\Chat::class,
			'chat',
			function ($className, $id) {
				return \Bitrix\Im\V2\Chat::getInstance((int)$id);
			}
		);
	}

	public function loadAction(array $methodList, CurrentUser $currentUser, $options = []): array
	{
		$this->options = $options;
		$this->currentUser = $currentUser;

		$data = [];
		foreach ($methodList as $method)
		{
			switch ($method)
			{
				case (AvailableMethodList::RECENT_LIST->value):
					$data[$method] = $this->getRecentList();
					break;
				case (AvailableMethodList::USER_DATA->value):
					$data[$method] = $this->getUserData();
					break;
				case (AvailableMethodList::PORTAL_COUNTERS->value):
					$data[$method] = $this->getPortalCounters();
					break;
				case (AvailableMethodList::IM_COUNTERS->value):
					$data[$method] = $this->getImCounters();
					break;
				case (AvailableMethodList::ANCHORS->value):
					$data[$method] = $this->getAnchors();
					break;
				case (AvailableMethodList::MOBILE_REVISION->value):
					$data[$method] = $this->getRevision();
					break;
				case (AvailableMethodList::SERVER_TIME->value):
					$data[$method] = $this->getServerTime();
					break;
				case (AvailableMethodList::DESKTOP_STATUS->value):
					$data[$method] = $this->getDesktopStatus();
					break;
				case (AvailableMethodList::PROMOTION->value):
					$data[$method] = Promotion::getActive(self::PROMO_TYPE);
					break;
				case (AvailableMethodList::DEPARTMENT_COLLEAGUES->value):
					$data[$method] = $this->getDepartmentColleagues();
					break;
				case (AvailableMethodList::TARIFF_RESTRICTION->value):
					$data[$method] = $this->getTariffRestriction();
					break;
				case (AvailableMethodList::CHATS_LIST->value):
					$data[$method] = $this->getChatsList();
					break;
				case (AvailableMethodList::COPILOT_LIST->value):
					$data[$method] = $this->getCopilotList();
					break;
				case (AvailableMethodList::CHANNEL_LIST->value):
					$data[$method] = $this->getChannelList();
					break;
				case (AvailableMethodList::COLLAB_LIST->value):
					$data[$method] = $this->getCollabList();
					break;
				case (AvailableMethodList::TASK_LIST->value):
					$data[$method] = $this->getTaskList();
					break;
				case (AvailableMethodList::ACTIVE_CALLS->value):
					$data[$method] = [];
					if (Loader::includeModule('call'))
					{
						$data[$method] = Call::getActiveCalls();
					}

					break;
			}
		}

		return $data;
	}

	protected function getRevision(): int
	{
		return \Bitrix\Im\Revision::getMobile();
	}

	protected function getServerTime(): string
	{
		return date('c');
	}

	protected function getPortalCounters(): array
	{
		$time = microtime(true);
		$siteId = $this->options['siteId'] ?? SITE_ID;

		$counters = [$siteId => CUserCounter::GetValues($this->currentUser->getId(), $siteId)];
		$counters = CUserCounter::getGroupedCounters($counters);

		return [
			'result' => $counters,
			'time' => $time,
		];
	}

	protected function getDesktopStatus(): array
	{
		return [
			'isOnline' => CIMMessenger::CheckDesktopStatusOnline(),
			'version' => CIMMessenger::GetDesktopVersion(),
		];
	}

	protected function getUserData(): array
	{
		$userData = \Bitrix\Im\User::getInstance($this->currentUser->getId())->getArray(['JSON' => 'Y']);

		$userData['desktop_last_date'] = \CIMMessenger::GetDesktopStatusOnline($this->currentUser->getId());
		$userData['desktop_last_date'] = $userData['desktop_last_date']
			? date('c', $userData['desktop_last_date'])
			: false
		;

		return $userData;
	}

	protected function getDepartmentColleagues(): array
	{
		$user = User::getInstance($this->currentUser->getId());

		if (!$user->isExist() || $user->isExtranet() || $user->isBot())
		{
			return [];
		}

		//Todo: delete this in next updates
		if (!method_exists(Department::class, 'getColleaguesSimple'))
		{
			$params = [
				'OFFSET' => self::OFFSET,
				'LIMIT' => self::LIMIT,
			];
			$colleagues = Department::getColleagues(
				null, ['JSON' => 'Y', 'USER_DATA' => 'Y', 'LIST' => $params]
			);

			return $colleagues['result'] ?? [];
		}

		return Department::getColleaguesSimple($user, self::LIMIT);
	}

	protected function getImCounters(): array
	{
		return $this->convertKeysToCamelCase((new CounterService())->get());
	}

	protected function getAnchors(): array
	{
		$anchorProvider = AnchorContainer::getInstance()
			->getAnchorProvider()
			->setContextUser($this->getCurrentUser()?->getId());

		return $anchorProvider->getUserAnchors();
	}

	protected function getTariffRestriction(): array
	{
		return Limit::getInstance()->getRestrictions();
	}

	protected function getChatsList(): array
	{
		$recentList = \Bitrix\Im\Recent::getList(
			null,
			[
				'JSON' => 'Y',
				'SKIP_OPENLINES' => 'Y',
				'GET_ORIGINAL_TEXT' => 'N',
				'OFFSET' => self::OFFSET,
				'LIMIT' => self::LIMIT,
			]
		);
		
		return $recentList ?: [];
	}
	
	protected function getCopilotList(): array
	{
		$recentList = \Bitrix\Im\Recent::getList(
			null,
			[
				'JSON' => 'Y',
				'SKIP_OPENLINES' => 'Y',
				'GET_ORIGINAL_TEXT' => 'N',
				'OFFSET' => self::OFFSET,
				'LIMIT' => self::LIMIT,
				'ONLY_COPILOT' => 'Y',
			]
		);
		
		return $recentList ?: [];
	}

	protected function getChannelList(): array
	{
		$recentList = RecentChannel::getOpenChannels(self::LIMIT);

		return $this->toRestFormatWithPaginationData(
			[$recentList],
			self::LIMIT,
			$recentList->count()
		);
	}

	protected function getCollabList(): array
	{
		$recentList = RecentCollab::getCollabs(self::LIMIT);

		return $this->toRestFormatWithPaginationData(
			[$recentList],
			self::LIMIT,
			$recentList->count()
		);
	}

	protected function getTaskList(): array
	{
		// TODO: task-tab remove class_exists after merge feature
		if (!\Bitrix\ImMobile\Settings::isTasksRecentListAvailable() || !class_exists('\Bitrix\Im\V2\Recent\RecentExternalChat'))
		{
			return $this->toRestFormatWithPaginationData(
				[],
				self::LIMIT,
				0
			);
		}

		$recentList = \Bitrix\Im\V2\Recent\RecentExternalChat::getExternalChats('tasksTask', self::LIMIT);

		return $this->toRestFormatWithPaginationData(
			[$recentList],
			self::LIMIT,
			$recentList->count()
		);
	}

	abstract protected function getRecentList(): array;
}
