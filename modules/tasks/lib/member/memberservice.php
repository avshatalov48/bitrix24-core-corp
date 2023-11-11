<?php

namespace Bitrix\Tasks\Member;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Member\Config\Config;
use Bitrix\Tasks\Member\Exception\MemberException;
use Bitrix\Tasks\Member\Exception\MemberTypeException;
use Bitrix\Tasks\Member\Role\Accomplices;
use Bitrix\Tasks\Member\Role\Auditors;
use Bitrix\Tasks\Member\Role\Responsibles;
use Bitrix\Tasks\Member\Role\Directors;
use Bitrix\Tasks\Member\Type\Member;

abstract class MemberService
{
	/** @var Member[] $members */
	private array $members = [];
	private Repository $repository;

	public function __construct(protected int $entityId)
	{
		$this->repository = $this->getRepository();
	}

	abstract public function getRepository(): Repository;

	/**
	 * @see RoleDictionary
	 */
	public function get(array $roles, Config $config): Result
	{
		$result = new Result();
		if (is_null($this->repository->getEntity()))
		{
			$result->addError(new Error('Task or template not found'));
			return $result;
		}

		if (!$this->isRolesValid($roles))
		{
			$result->addError(new Error('Invalid $roles data: ' . implode(', ', $roles)));
			return $result;
		}
		try
		{
			foreach ($roles as $role)
			{
				$this->members[$role] = $this->getHandler($role, $config)->get();
			}
		}
		catch (MemberException $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		$result->setData($this->members);

		return $result;
	}

	/**
	 * @throws MemberTypeException
	 */
	private function getHandler(string $role, Config $config): MemberManager
	{
		return match ($role)
		{
			RoleDictionary::ROLE_ACCOMPLICE => new Accomplices($this->repository, $config),
			RoleDictionary::ROLE_RESPONSIBLE => new Responsibles($this->repository, $config),
			RoleDictionary::ROLE_DIRECTOR => new Directors($this->repository, $config),
			RoleDictionary::ROLE_AUDITOR => new Auditors($this->repository, $config),
			default => throw new MemberTypeException("Unknown member type {$role}"),
		};
	}

	private function isRolesValid(array $roles): bool
	{
		foreach ($roles as $role)
		{
			if (!in_array($role, RoleDictionary::getAvailableRoles(), true))
			{
				return false;
			}
		}

		return true;
	}

	public static function invalidate(): void
	{
		MemberManager::invalidate();
	}
}