<?php

namespace Bitrix\Tasks\Internals\Counter;

class Role
{
	const ALL = 'view_all';
	const RESPONSIBLE = 'view_role_responsible';
	const ACCOMPLICE = 'view_role_accomplice';
	const AUDITOR = 'view_role_auditor';
	const ORIGINATOR = 'view_role_originator';

	public static function getRoles()
	{
		static $roles = array();

		if (!$roles)
		{
			foreach (self::getKnownRoles() as $roleId => $roleCode)
			{
				$roles[$roleCode] = array(
					'ID' => $roleId,
					'CODE' => $roleCode,
					'TITLE' => self::getRoleName($roleId),
				);
			}
		}

		return $roles;
	}

	private static function getKnownRoles()
	{
		return array(
			\CTaskListState::VIEW_ROLE_RESPONSIBLE => self::RESPONSIBLE,
			\CTaskListState::VIEW_ROLE_ACCOMPLICE => self::ACCOMPLICE,
			\CTaskListState::VIEW_ROLE_ORIGINATOR => self::ORIGINATOR,
			\CTaskListState::VIEW_ROLE_AUDITOR => self::AUDITOR
		);
	}

	// \CTaskListState::getKnownRoles
	public static function getRoleName($roleId)
	{
		/** @noinspection PhpDeprecationInspection */
		return \CTaskListState::getRoleNameById($roleId);
	}
}