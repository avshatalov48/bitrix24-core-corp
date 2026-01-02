<?php

namespace Bitrix\Mobile\Profile\Provider;

use Bitrix\Main\Loader;
use Bitrix\Intranet\Component\UserProfile\Grats;
use Bitrix\Main\LoaderException;
use Bitrix\Main\UI\PageNavigation;

class GratitudeProvider
{
	protected ?Grats $gratitudeService;
	private const DEFAULT_PAGE_SIZE = 30;
	/**
	 * @param Grats|null $gratitudeService
	 */
	public function __construct(?Grats $gratitudeService = null)
	{
		$this->gratitudeService = $gratitudeService;
	}

	/**
	 * @param int $ownerId
	 * @param int|null $limit
	 * @return array
	 * @throws LoaderException
	 */
	public function getBadges(int $ownerId, ?int $limit = null): array
	{
		$items = $this->fetchItems($ownerId, $limit ?? self::DEFAULT_PAGE_SIZE);

		return [
			'items' => $this->collectGratitudeItems($items['POSTS'], $items['BADGES'], $ownerId),
			'totalCount' => $this->countTotalBadges($items['BADGES'])
		];
	}

	/**
	 * @param int $ownerId
	 * @param PageNavigation|null $pageNavigation
	 * @return array
	 * @throws LoaderException
	 */
	public function getListItems(int $ownerId, ?PageNavigation $pageNavigation = null): array
	{
		$pageSize = $pageNavigation?->getPageSize() ?? self::DEFAULT_PAGE_SIZE;
		$pageNum = $pageNavigation?->getCurrentPage();

		$items = $this->fetchItems($ownerId, $pageSize, $pageNum);
		$gratitudeItems = $this->collectGratitudeItems($items['POSTS'], $items['BADGES'], $ownerId);

		return [
			'items' => $gratitudeItems,
			'authorIds' => $this->extractAuthorIds($items['POSTS']),
		];
	}

	/**
	 * @throws LoaderException
	 */
	private function fetchItems(int $ownerId, int $pageSize, ?int $pageNum = 1): array
	{
		if (!Loader::includeModule('intranet'))
		{
			return [];
		}

		$service = $this->getGratitudeService($ownerId, $pageSize);
		$result = $service->getGratitudePostListAction(['pageNum' => $pageNum]);

		return [
			'POSTS' => $result['POSTS'] ?? [],
			'BADGES' => $result['BADGES'] ?? [],
		];
	}

	private function getGratitudeService(int $ownerId, ?int $pageSize = self::DEFAULT_PAGE_SIZE): Grats
	{
		if (!$this->gratitudeService)
		{
			$this->gratitudeService = new Grats([
				'profileId' => $ownerId,
				'pageSize' => $pageSize,
				'pathToPostEdit' => '',
				'pathToUserGrat' => '',
				'pathToPost' => '',
				'pathToUser' => '',
			]);
		}

		return $this->gratitudeService;
	}

	private function countTotalBadges(array $badges): int
	{
		return array_reduce(
			$badges,
			static fn(int $total, array $badge) => $total + count($badge['ID'] ?? []),
			0
		);
	}

	private function collectGratitudeItems(array $posts, array $badges, int $ownerId): array
	{
		$badgeNames = $this->createBadgeNameMap($badges);
		$result = [];

		foreach ($posts as $post)
		{
			$result[] = [
				'id' => (int)$post['UF_GRATITUDE'],
				'name' => (string)$badgeNames[$post['UF_GRATITUDE'] ?? ''],
				'title' => (string)$post['TITLE'],
				'authorId' => (int)$post['AUTHOR_ID'],
				'createdAt' => (int)$post['DATE_PUBLISH_TS'],
				'relatedPostId' => (int)$post['ID'],
				'ownerId' => $ownerId,
			];
		}

		if (!empty($result))
		{
			usort($result, static fn($newer, $older) => $older['createdAt'] <=> $newer['createdAt']);
		}

		return $result;
	}

	private function createBadgeNameMap(array $badges): array
	{
		$map = [];
		foreach ($badges as $badge)
		{
			foreach ($badge['ID'] ?? [] as $id)
			{
				$map[$id] = $badge['NAME'] ?? '';
			}
		}

		return $map;
	}

	private function extractAuthorIds(array $posts): array
	{
		return array_values(array_unique(array_filter(
			array_column($posts, 'AUTHOR_ID'),
			static fn($id) => !empty($id)
		)));
	}
}
