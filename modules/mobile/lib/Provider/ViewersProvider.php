<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\Item\UserContentView;

class ViewersProvider
{
	private ?PageNavigation $pageNavigation;

	/**
	 * @param PageNavigation|null $pageNavigation
	 */
	public function __construct(
		?PageNavigation $pageNavigation = null,
	)
	{
		$this->pageNavigation = $pageNavigation;
	}

	public function getViewersData(string $entityType, int $entityId, ?int $page = null): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		$currentPage = $this->getCurrentPage($page);

		$result = [];
		$params = [
			'contentId' => "$entityType-$entityId",
			'page' => $currentPage,
		];

		$viewers = UserContentView::getUserList($params);

		$userIds = [];
		if (isset($viewers['items']) && is_array($viewers['items']))
		{
			foreach ($viewers['items'] as $item)
			{
				$clientOffset = \CTimeZone::GetOffset();
				$serverTime = strtotime($item['DATE_VIEW']);
				$clientTime = $serverTime - $clientOffset;

				$userIds[] = (int) $item['ID'];

				$result['items'][] = [
					'entityType' => $entityType,
					'entityId' => $entityId,
					'id' => (int)$item['ID'],
					'viewTimestamp' => $clientTime,
				];
			}
		}

		$result['users'] = self::getUserDataByIds($userIds);

		return $result;
	}

	public static function setUserContentView(string $entityType, int $entityId, int $userId): void
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return;
		}

		$viewParams = [
			'xmlIdList' => [
				[
					'xmlId' => "$entityType-$entityId",
					'save' => 'Y',
				],
			],
			'userId' => $userId,
		];

		UserContentView::set($viewParams);
	}

	private static function getUserDataByIds(array $userIds): array
	{
		return UserRepository::getByIds($userIds);
	}

	private function getCurrentPage(?int $page): int
	{
		if ($page !== null)
		{
			return $page;
		}

		if ($this->pageNavigation !== null)
		{
			return $this->pageNavigation->getCurrentPage();
		}

		return 1;
	}
}