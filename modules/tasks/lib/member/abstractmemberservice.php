<?php

namespace Bitrix\Tasks\Member;

use Bitrix\Main\Error;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Member\Config\ConfigInterface;
use Bitrix\Tasks\Member\Exception\MemberException;
use Bitrix\Tasks\Member\Exception\MemberTypeException;
use Bitrix\Tasks\Member\Result\MemberResult;
use Bitrix\Tasks\Member\Role\Accomplices;
use Bitrix\Tasks\Member\Role\Auditors;
use Bitrix\Tasks\Member\Role\Responsibles;
use Bitrix\Tasks\Member\Role\Directors;
use Bitrix\Tasks\Member\Type\Member;
use Bitrix\Tasks\Member\Type\MemberCollection;

abstract class AbstractMemberService
{
	protected int $entityId;
	private array $roles = [];
	private ConfigInterface $config;
	private MemberCollection $members;
	private RepositoryInterface $repository;
	private MemberResult $result;

	public static function invalidate(): void
	{
		AbstractMemberManager::invalidate();
	}

	public function __construct(int $entityId)
	{
		$this->entityId = $entityId;
		$this->init();
	}

	abstract public function getRepository(): RepositoryInterface;

	/**
	 * @see RoleDictionary
	 */
	public function get(array $roles, ConfigInterface $config): MemberResult
	{
		$this->roles = $roles;
		$this->config = $config;
		$this->result = new MemberResult();
		$this->members->clear();

		if (is_null($this->repository->getEntity()))
		{
			$this->addError("{$this->repository->getType()}: entity {$this->entityId} not found.");
			return $this->result;
		}

		if (!$this->isRolesValid())
		{
			$this->addError('Invalid $roles data: ' . implode(', ', $this->roles));
			return $this->result;
		}

		try
		{
			$this->setMembers();
		}
		catch (MemberException $exception)
		{
			$this->addError($exception->getMessage());
			return $this->result;
		}

		return $this->result;
	}

	/**
	 * @throws MemberTypeException
	 */
	private function getHandler(string $role, ConfigInterface $config): AbstractMemberManager
	{
		return match ($role)
		{
			RoleDictionary::ROLE_ACCOMPLICE => new Accomplices($this->repository, $config),
			RoleDictionary::ROLE_RESPONSIBLE => new Responsibles($this->repository, $config),
			RoleDictionary::ROLE_DIRECTOR => new Directors($this->repository, $config),
			RoleDictionary::ROLE_AUDITOR => new Auditors($this->repository, $config),
			default => throw new MemberTypeException("Unknown member type {$role} for entity {$this->repository->getEntity()->getId()}"),
		};
	}

	private function isRolesValid(): bool
	{
		foreach ($this->roles as $role)
		{
			if (!in_array($role, RoleDictionary::getAvailableRoles(), true))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @throws MemberException
	 */
	private function setMembers(): void
	{
		foreach ($this->roles as $role)
		{
			$this->setMembersByRole($role);
		}

		$this->result->setMembers($this->members);
	}

	/**
	 * @throws MemberException
	 */
	private function setMembersByRole(string $role): void
	{
		$members = $this->getHandler($role, $this->config)->get();
		array_map(function (Member $member) use ($role): void {
			$this->members->set($role, $member);
		}, $members);
	}

	private function addError(string $message): void
	{
		$this->result->addError(new Error($message));
	}

	private function init(): void
	{
		$this->repository = $this->getRepository();
		$this->members = new MemberCollection();
	}
}
