<?php

namespace Bitrix\Rpa\Permission;

class Result extends \Bitrix\Main\Result
{
	protected $isSaved = false;
	protected $resultPermissions = [];
	protected $addPermissions = [];
	protected $deletePermission = [];

	public function isSaved(): bool
	{
		return $this->isSaved;
	}

	public function setSaved(): Result
	{
		$this->isSaved = true;

		return $this;
	}

	public function getResultPermissions(): array
	{
		return $this->resultPermissions;
	}

	public function setResultPermissions(array $resultPermissions): Result
	{
		$this->resultPermissions = $resultPermissions;

		return $this;
	}

	public function getAddPermissions(): array
	{
		return $this->addPermissions;
	}

	public function setAddPermissions(array $addPermissions): Result
	{
		$this->addPermissions = $addPermissions;

		return $this;
	}

	public function getDeletePermission(): array
	{
		return $this->deletePermission;
	}

	public function setDeletePermission(array $deletePermission): Result
	{
		$this->deletePermission = $deletePermission;

		return $this;
	}
}