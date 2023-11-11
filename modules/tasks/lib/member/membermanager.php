<?php

namespace Bitrix\Tasks\Member;

use Bitrix\Main\EO_User;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Member\Config\AdditionalConfig;
use Bitrix\Tasks\Member\Config\BaseConfig;
use Bitrix\Tasks\Member\Config\Config;
use Bitrix\Tasks\Member\Exception\ConfigException;
use Bitrix\Tasks\Member\Exception\UserInfoException;
use Bitrix\Tasks\Member\Type\Member;
use CSite;
use CUser;
use Exception;

abstract class MemberManager
{
	private static array $cache = [];

	public function __construct(private Repository $repository, private Config $config)
	{
	}

	abstract protected function getRole(): string;

	/**
	 * @throws UserInfoException
	 * @throws ConfigException
	 */
	public function get(): array
	{
		if (isset(static::$cache[$this->getKey($this->config)]))
		{
			return static::$cache[$this->getKey($this->config)];
		}

		$coveringKey = $this->getCoveringKey();
		if (!is_null($coveringKey))
		{
			return static::$cache[$coveringKey];
		}

		return match ($this->config::class)
		{
			BaseConfig::class => $this->getBaseInfo(),
			AdditionalConfig::class => $this->getAdditionalInfo(),
			default => throw new ConfigException("Unknown config {$this->config->getType()}"),
		};
	}

	private function getCoveringKey(): ?string
	{
		$coveringConfigs = $this->config->getCoveringConfigs();
		foreach ($coveringConfigs as $config)
		{
			if (isset(static::$cache[$this->getKey($config)]))
			{
				return $this->getKey($config);
			}
		}

		return null;
	}

	private function getBaseInfo(): array
	{
		$members = $this->repository->getMembers();
		if ($members->isEmpty())
		{
			return [];
		}

		foreach ($members as $member)
		{
			if ($member->getType() === $this->getRole())
			{
				$getEntityId = 'get' . $this->repository->getType() . 'Id';
				static::$cache[$this->getKey($this->config)][$member->getUserId()] = new Member(
					$member->getUserId(),
					$this->getRole(),
					$member->$getEntityId(),
					$this->repository->getType()
				);
			}
		}

		return static::$cache[$this->getKey($this->config)] ?? [];
	}

	private function getKey(Config $config): string
	{
		return implode('|', [
			$this->repository->getEntity()->getId(), $this->repository->getType(), $this->getRole(), $config->getType()
		]);
	}

	/**
	 * @throws UserInfoException
	 */
	private function getAdditionalInfo(): array
	{
		$members = $this->repository->getMembers();
		if ($members->isEmpty())
		{
			return [];
		}

		$memberIds = [];
		$taskMembers = [];
		foreach ($members as $member)
		{
			if ($member->getType() === $this->getRole())
			{
				$getEntityId = 'get' . $this->repository->getType() . 'Id';
				$taskMembers[$member->getUserId()] = new Member(
					$member->getUserId(),
					$this->getRole(),
					$member->$getEntityId(),
					$this->repository->getType()
				);

				$memberIds[] = $member->getUserId();
			}
		}

		if (empty($memberIds))
		{
			return [];
		}

		try
		{
			$query = UserTable::query();
			$query
				->setSelect(['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'])
				->whereIn('ID', $memberIds)
			;

			$users = $query->exec()->fetchCollection();
		}
		catch (Exception $exception)
		{
			throw new UserInfoException($exception->getMessage());
		}

		foreach ($users as $user)
		{
			$taskMembers[$user->getId()]->setName($this->getName($user));
		}

		static::$cache[$this->getKey($this->config)] = $taskMembers;
		return $taskMembers;
	}

	private function getName(EO_User $user): string
	{
		return CUser::FormatName(
			CSite::GetNameFormat(),
			[
				'NAME' => $user->getName(),
				'LAST_NAME' => $user->getLastName(),
				'SECOND_NAME' => $user->getSecondName(),
			],
			true,
			$this->config->escapeString(),
		);
	}

	public static function invalidate(): void
	{
		static::$cache = [];
	}
}