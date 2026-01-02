<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity\Tag;
use Bitrix\Tasks\V2\Internal\Entity\TagCollection;

class InMemoryTaskTagRepository implements TaskTagRepositoryInterface
{
	private readonly TaskTagRepositoryInterface $taskTagRepository;
	private array $cache = [];

	public function __construct(TaskTagRepository $taskTagRepository)
	{
		$this->taskTagRepository = $taskTagRepository;
	}

	public function getById(int $taskId): TagCollection
	{
		if (!isset($this->cache[$taskId]))
		{
			$this->cache[$taskId] = $this->taskTagRepository->getById($taskId);
		}

		return $this->cache[$taskId];
	}

	public function getByIds(array $taskIds): TagCollection
	{
		$notStoredIds = array_diff($taskIds, array_keys($this->cache));
		if (!empty($notStoredIds))
		{
			$tags = $this->taskTagRepository->getByIds($notStoredIds);
			foreach ($tags as $tag)
			{
				$this->cache[$tag->task?->id] ??= new TagCollection();
				$this->cache[$tag->task?->id]->add($tag);
			}
		}

		$entities = array_map(static fn (TagCollection $tags): array => $tags->getEntities(), $this->cache);
		$entities = array_merge(...$entities);
		$collection = new TagCollection(...$entities);

		return $collection->filter(static fn (Tag $tag): bool => in_array($tag->task?->id, $taskIds, true));
	}

	public function invalidate(int $taskId): void
	{
		unset($this->cache[$taskId]);
	}
}
