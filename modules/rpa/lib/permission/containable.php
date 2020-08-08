<?php

namespace Bitrix\Rpa\Permission;

interface Containable
{
	public function getId(): ?int;

	public function getPermissions(): array;

	public function getPermissionEntity(): string;
}