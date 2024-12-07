<?php

namespace Bitrix\ImMobile\Controller;

use Bitrix\Im\Promotion;
use Bitrix\Im\V2\Entity\User\User;
use Bitrix\Im\V2\Message\CounterService;
use Bitrix\Im\V2\TariffLimit\Limit;
use Bitrix\ImMobile\NavigationTab\Tab\AvailableMethodList;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use CIMMessenger;

abstract class Tab extends BaseController
{
	protected const PROMO_TYPE ='mobile';
	protected const OFFSET = 0;
	protected const LIMIT = 50;

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

	public function loadAction(array $methodList, CurrentUser $currentUser): array
	{
		$data = [];
		foreach ($methodList as $method)
		{
			switch ($method)
			{
				case (AvailableMethodList::RECENT_LIST->value):
					$data[$method] = $this->getRecentList();
					break;
				case (AvailableMethodList::USER_DATA->value):
					$data[$method] = $this->getUserData($currentUser);
					break;
				case (AvailableMethodList::PORTAL_COUNTERS->value):
					$data[$method] = $this->getPortalCounters($currentUser);
					break;
				case (AvailableMethodList::IM_COUNTERS->value):
					$data[$method] = $this->getImCounters();
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
					$data[$method] = $this->getDepartmentColleagues($currentUser);
					break;
				case (AvailableMethodList::TARIFF_RESTRICTION->value):
					$data[$method] = $this->getTariffRestriction();
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

	protected function getPortalCounters(CurrentUser $user): array
	{
		$time = microtime(true);

		$counters = \CUserCounter::GetAllValues($user->getId());
		$counters = \CUserCounter::getGroupedCounters($counters);

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

	protected function getUserData(CurrentUser $currentUser): array
	{
		$userData = \Bitrix\Im\User::getInstance($currentUser->getId())->getArray(['JSON' => 'Y']);

		$userData['desktop_last_date'] = \CIMMessenger::GetDesktopStatusOnline($currentUser->getId());
		$userData['desktop_last_date'] = $userData['desktop_last_date']
			? date('c', $userData['desktop_last_date'])
			: false
		;

		return $userData;
	}

	protected function getDepartmentColleagues(CurrentUser $CurrentUser): array
	{
		$user = User::getInstance($CurrentUser->getId());

		if ($user->isExtranet() || $user->isBot())
		{
			return [];
		}

		$params = [
			'OFFSET' => self::OFFSET,
			'LIMIT' => self::LIMIT,
		];
		return \Bitrix\Im\Department::getColleagues(
			null, ['JSON' => 'Y', 'USER_DATA' => 'Y', 'LIST' => $params]
		);
	}

	protected function getImCounters(): array
	{
		return $this->convertKeysToCamelCase((new CounterService())->get());
	}

	protected function getTariffRestriction(): array
	{
		return Limit::getInstance()->getRestrictions();
	}

	abstract protected function getRecentList(): array;
}
