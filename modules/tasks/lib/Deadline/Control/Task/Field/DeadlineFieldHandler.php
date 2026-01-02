<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Control\Task\Field;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository;
use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\Integration\Socialnetwork\Internals\Registry\GroupRegistry;
use Psr\Container\NotFoundExceptionInterface;

class DeadlineFieldHandler
{
	private DeadlineUserOptionRepositoryInterface $deadlineUserOptionRepository;
	private ?\Bitrix\Socialnetwork\Internals\Registry\GroupRegistry $groupRegistry;

	/**
	 * @throws ObjectNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct(private readonly int $userId)
	{
		$serviceLocator = ServiceLocator::getInstance();

		$this->deadlineUserOptionRepository = $serviceLocator->get(CacheDeadlineUserOptionRepository::class);

		$this->groupRegistry = GroupRegistry::getInstance();
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function modify(array &$fields): array
	{
		if (isset($fields['DEADLINE']))
		{
			return $fields;
		}

		$groupId = (int)($fields['GROUP_ID'] ?? 0);

		$group = $this->groupRegistry->get($groupId);
		if ($group?->isScrumProject())
		{
			return $fields;
		}

		$deadlineUserOption = $this->deadlineUserOptionRepository->getByUserId($this->userId);

		$matchWorkTime = $fields['MATCH_WORK_TIME'] ?? false;
		$deadline = $deadlineUserOption->getDefaultDeadlineDate($matchWorkTime);

		$fields['DEADLINE'] = $deadline?->format(DateTime::getFormat());

		return $fields;
	}
}
