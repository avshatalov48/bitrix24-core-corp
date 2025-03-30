<?php

namespace Bitrix\Crm\Security\Role\Manage\Permissions;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\BaseControlMapper;
use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\ControlMapper\DependentVariables;
use Bitrix\Main\Localization\Loc;

class Transition extends Permission
{
	public const CODE = 'TRANSITION';

	public const TRANSITION_ANY = 'ANY';
	public const TRANSITION_INHERIT = 'INHERIT';
	public const TRANSITION_BLOCKED = 'BLOCKED';

	public function code(): string
	{
		return self::CODE;
	}

	public function name(): string
	{
		return Loc::getMessage('CRM_SECURITY_ROLE_PERMS_HEAD_TRANSITION');
	}

	public function canAssignPermissionToStages(): bool
	{
		return true;
	}

	public function sortOrder(): ?int
	{
		return 8;
	}

	public function getMaxAttributeValue(): ?string
	{
		return null;
	}

	public function getMinAttributeValue(): ?string
	{
		return null;
	}

	public function getDefaultSettings(): array
	{
		return [self::TRANSITION_BLOCKED];
	}

	public function getMaxSettingsValue(): array
	{
		return [self::TRANSITION_ANY];
	}

	public function getMinSettingsValue(): array
	{
		return [self::TRANSITION_BLOCKED];
	}

	protected function createDefaultControlMapper(): BaseControlMapper
	{
		return (new DependentVariables\ValuesAsSettings());
	}

	public function getHeadDefaultSettings(): array
	{
		return [self::TRANSITION_ANY];
	}

	public function getDeputyDefaultSettings(): array
	{
		return [self::TRANSITION_ANY];
	}
}
