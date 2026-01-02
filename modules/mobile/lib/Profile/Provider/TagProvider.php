<?php

namespace Bitrix\Mobile\Profile\Provider;

use Bitrix\Intranet\Component\UserProfile\Tags;
use Bitrix\Intranet\Component\UserProfile;

class TagProvider
{
	protected ?Tags $tagService;

	/**
	 * @param Tags|null $tagService
	 */
	public function __construct(?Tags $tagService = null)
	{
		$this->tagService = $tagService;
	}

	private function getTagService(int $ownerId): Tags
	{
		if (!$this->tagService)
		{
			$this->tagService = new Tags([
				'profileId' => $ownerId,
				'pathToUser' => '',
				//todo check permissions
				'permissions' => [
					'view' => true,
					'edit' => true,
				],
			]);
		}

		return $this->tagService;
	}

	public function getTagsList(int $ownerId): array
	{
		$service = $this->getTagService($ownerId);
		$tagsList = $service->getTagsListAction();

		return $this->prepareTagsListResult($tagsList);
	}

	public function saveTags(int $ownerId, array $tagList): array
	{
		$service = $this->getTagService($ownerId);

		$tagNames = array_map(
			static fn($tag) => (string)($tag['name']),
			$tagList,
		);
		$currentTagList = $service->getTagsListAction();
		$currentTagNames = array_map(
			static fn($tagName) => (string)$tagName,
			array_keys($currentTagList)
		);

		$tagsToAdd = array_diff($tagNames, $currentTagNames);
		$tagsToRemove = array_diff($currentTagNames, $tagNames);

		foreach ($tagsToAdd as $tag)
		{
			$service->addTagAction(['userId' => $ownerId, 'tag' => $tag]);
		}

		foreach ($tagsToRemove as $tag)
		{
			$service->removeTagAction(['userId' => $ownerId, 'tag' => $tag]);
		}

		return $this->getTagsList($ownerId);
	}

	public function searchTags(int $ownerId, int $limit, string $searchString = ''): array
	{
		$service = $this->getTagService($ownerId);
		$tagsList = $service->searchTagsAction(['searchString' => $searchString, 'limit' => $limit, 'excludeUserTags' => false]);

		return $this->prepareSearchResult($tagsList);
	}

	public function addTag(int $ownerId, string $tag): array
	{
		$service = $this->getTagService($ownerId);
		$result = $service->addTagAction(['userId' => $ownerId, 'tag' => $tag]);

		return $this->prepareTagsListResult($result) ?? [];
	}

	public function removeTag(int $ownerId, string $tag): bool
	{
		$service = $this->getTagService($ownerId);

		$result = $service->removeTagAction(['userId' => $ownerId, 'tag' => $tag]);

		if (is_bool($result))
		{
			return $result;
		}

		return $result->isSuccess();
	}

	private function prepareSearchResult(array $tagsList): array
	{
		return [
			'tags' => array_map(
				static fn($tag) => ['name' => (string)($tag['NAME'])],
				$tagsList
			),
		];
	}

	private function prepareTagsListResult(array $tagsList): array
	{
		$tags = [];
		$allUserIds = [];
		foreach ($tagsList as $tagName => $tagData)
		{
			$tagUserIds = $this->prepareTagUserIdsList($tagData['USERS']);
			$tags[] = [
				'name' => (string)$tagName,
				'count' => (int)$tagData['COUNT'],
				'userIds' => $tagUserIds,
			];
			array_push($allUserIds, ...$tagUserIds);
		}

		return [
			'userIds' => array_unique($allUserIds) ?: [],
			'tags' => $tags,
		];
	}

	private function prepareTagUserIdsList(array $tagUserList): array
	{
		return array_map(static fn($user) => (int)$user['ID'], $tagUserList);
	}
}
