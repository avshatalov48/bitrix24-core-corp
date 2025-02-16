<?php

namespace Bitrix\Crm\Security\Role\Manage\AttrPreset;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Variants;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;

class UserRoleAndHierarchy
{
	public const NONE = '0';
	public const  SELF = 'SELF';
	public const  THIS_ROLE = 'THISROLE';
	public const  DEPARTMENT = 'DEPARTMENT';
	public const  SUBDEPARTMENTS = 'SUBDEPARTMENTS';
	public const  OPEN = 'OPEN';
	public const  ALL = 'ALL';
	public const  INHERIT = 'INHERIT';

	private array $included = [
		self::NONE => self::NONE,
		self::SELF => self::SELF,
		self::THIS_ROLE => self::THIS_ROLE,
		self::DEPARTMENT => self::DEPARTMENT,
		self::SUBDEPARTMENTS => self::SUBDEPARTMENTS,
		self::OPEN => self::OPEN,
		self::ALL => self::ALL,
		self::INHERIT => self::INHERIT,
	];

	public function exclude(string $variableId): self
	{
		$this->included = array_filter($this->included, fn(string $id) => $variableId !== $id);

		return $this;
	}

	private function isIncluded(string $id): bool
	{
		return isset($this->included[$id]);
	}

	private function isAllIncluded(array $ids): bool
	{
		foreach ($ids as $id)
		{
			if (!$this->isIncluded($id))
			{
				return false;
			}
		}

		return true;
	}

	private function filterOutNotIncluded(array $ids): array
	{
		return array_values(
			array_filter($ids, fn(string $id) => $this->isIncluded($id)),
		);
	}

	public function getVariants(): Variants
	{
		$variants = new Variants();

		if ($this->isIncluded(self::NONE))
		{
			$variants->add(
				self::NONE,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_'),
				[
					'useAsEmptyInSection' => true,
					'useAsNothingSelectedInSubsection' => true,
					'conflictsWith' => $this->filterOutNotIncluded([
						self::SELF,
						self::THIS_ROLE,
						self::DEPARTMENT,
						self::SUBDEPARTMENTS,
						self::OPEN,
						self::ALL,
						self::INHERIT,
					]),
				]
			);
		}

		if ($this->isIncluded(self::SELF))
		{
			$variants->add(
				self::SELF,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_A'),
				[
					'conflictsWith' => $this->filterOutNotIncluded([
						self::INHERIT,
					]),
				]
			);
		}

		if ($this->isIncluded(self::THIS_ROLE))
		{
			$variants->add(
				self::THIS_ROLE,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_B'),
				[
					'requires' => $this->filterOutNotIncluded([
						self::SELF,
					]),
					'conflictsWith' => $this->filterOutNotIncluded([
						self::INHERIT,
					]),
				]
			);
		}
		if ($this->isIncluded(self::DEPARTMENT))
		{
			$variants->add(
				self::DEPARTMENT,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_D'),
				[
					'requires' => $this->filterOutNotIncluded([
						self::SELF,
					]),
					'conflictsWith' => $this->filterOutNotIncluded([
						self::INHERIT,
					]),
				]
			);
		}

		if ($this->isIncluded(self::SUBDEPARTMENTS))
		{
			$variants->add(
				self::SUBDEPARTMENTS,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_F'),
				[
					'requires' => $this->filterOutNotIncluded([
						self::SELF,
						self::DEPARTMENT,
					]),
					'conflictsWith' => $this->filterOutNotIncluded([
						self::INHERIT,
					]),
				]
			);
		}

		if ($this->isIncluded(self::OPEN))
		{
			$variants->add(
				self::OPEN,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_O'),
				[
					'requires' => $this->filterOutNotIncluded([
						self::SELF,
						self::DEPARTMENT,
						self::SUBDEPARTMENTS,
					]),
					'conflictsWith' => $this->filterOutNotIncluded([
						self::INHERIT,
					]),
				]
			);
		}

		if ($this->isIncluded(self::ALL))
		{
			$variants->add(
				self::ALL,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_X_MSGVER_1'),
				[
					'requires' => $this->filterOutNotIncluded([
						self::SELF,
						self::THIS_ROLE,
						self::DEPARTMENT,
						self::SUBDEPARTMENTS,
						self::OPEN,
					]),
					'conflictsWith' => $this->filterOutNotIncluded([
						self::INHERIT,
						self::NONE,
					]),
				]
			);
		}

		if ($this->isIncluded(self::INHERIT))
		{
			$variants->add(
				self::INHERIT,
				(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_INHERIT'),
				[
					'hideInSection' => true,
					'useAsEmptyInSubsection' => true,
					'secondary' => true,
					'conflictsWith' => $this->filterOutNotIncluded([
						self::NONE,
						self::SELF,
						self::THIS_ROLE,
						self::OPEN,
						self::SUBDEPARTMENTS,
						self::DEPARTMENT,
						self::ALL,
					]),
				]
			);
		}

		return $variants;
	}

	/**
	 * @param string $singleValue
	 *
	 * @return string[]
	 * @throws ArgumentOutOfRangeException
	 */
	public function convertSingleToMultiValue(string $singleValue): array
	{
		switch ($singleValue)
		{
			case UserPermissions::PERMISSION_NONE:
				return $this->filterOutNotIncluded([
					self::NONE,
				]);
			case UserPermissions::PERMISSION_SELF:
				return $this->filterOutNotIncluded([
					self::SELF,
				]);
			case UserPermissions::PERMISSION_DEPARTMENT:
				return $this->filterOutNotIncluded([
					self::SELF,
					self::DEPARTMENT,
				]);
			case UserPermissions::PERMISSION_SUBDEPARTMENT:
				return $this->filterOutNotIncluded([
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
				]);
			case UserPermissions::PERMISSION_OPENED:
				return $this->filterOutNotIncluded([
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
				]);
			case UserPermissions::PERMISSION_ALL:
				return $this->filterOutNotIncluded([
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
					self::ALL,
				]);
		}

		throw new ArgumentOutOfRangeException('single value', UserPermissions::PERMISSION_NONE, UserPermissions::PERMISSION_ALL);
	}

	public function tryConvertMultiToSingleValue(array $multiValue): ?string
	{
		sort($multiValue, SORT_STRING);

		if ($this->isIncluded(self::ALL) && in_array(self::ALL, $multiValue, true))
		{
			return UserPermissions::PERMISSION_ALL;
		}
		if (
			$this->isAllIncluded([self::DEPARTMENT, self::OPEN, self::SELF, self::SUBDEPARTMENTS])
			&& $multiValue === [self::DEPARTMENT, self::OPEN, self::SELF, self::SUBDEPARTMENTS]
		)
		{
			return UserPermissions::PERMISSION_OPENED;
		}
		if (
			$this->isAllIncluded([self::DEPARTMENT, self::SELF, self::SUBDEPARTMENTS])
			&& $multiValue === [self::DEPARTMENT, self::SELF, self::SUBDEPARTMENTS]
		)
		{
			return UserPermissions::PERMISSION_SUBDEPARTMENT;
		}
		if (
			$this->isAllIncluded([self::DEPARTMENT, self::SELF])
			&& $multiValue === [self::DEPARTMENT, self::SELF]
		)
		{
			return UserPermissions::PERMISSION_DEPARTMENT;
		}
		if ($this->isIncluded(self::SELF) && $multiValue === [self::SELF])
		{
			return UserPermissions::PERMISSION_SELF;
		}
		if ($this->isIncluded(self::NONE) && $multiValue === [self::NONE])
		{
			return UserPermissions::PERMISSION_NONE;
		}

		return null; // some other combinations
	}
}
