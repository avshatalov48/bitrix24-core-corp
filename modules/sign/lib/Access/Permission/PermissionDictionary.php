<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Sign\Access\Permission;

class PermissionDictionary extends \Bitrix\Main\Access\Permission\PermissionDictionary
{
	use PermissionName;

	public const SIGN_CRM_CONTACT_READ = 'CCR';
	public const SIGN_CRM_CONTACT_DELETE = 'CCD';
	public const SIGN_CRM_CONTACT_WRITE = 'CCW';
	public const SIGN_CRM_CONTACT_ADD = 'CCA';
	public const SIGN_CRM_CONTACT_IMPORT = 'CCI';
	public const SIGN_CRM_CONTACT_EXPORT = 'CCE';
	public const SIGN_CRM_SMART_DOCUMENT_READ = 'CSDR';
	public const SIGN_CRM_SMART_DOCUMENT_DELETE = 'CSDD';
	public const SIGN_CRM_SMART_DOCUMENT_WRITE = 'CSDW';
	public const SIGN_CRM_SMART_DOCUMENT_ADD = 'CSDA';
	public const SIGN_CRM_SMART_B2E_DOC_READ = 'CSBDR';
	public const SIGN_CRM_SMART_B2E_DOC_DELETE = 'CSBDD';
	public const SIGN_CRM_SMART_B2E_DOC_WRITE = 'CSBDW';
	public const SIGN_CRM_SMART_B2E_DOC_ADD = 'CSBDA';

	public static function getType($permissionId): string
	{
		if (!self::getName($permissionId))
		{
			return '';
		}
		
		return static::TYPE_VARIABLES;
	}

	public static function getCrmPermissionMap(): array
	{
		return [
			self::SIGN_CRM_CONTACT_READ => ['checkReadPermissions', \CCrmOwnerType::Contact],
			self::SIGN_CRM_SMART_DOCUMENT_READ => ['checkReadPermissions', \CCrmOwnerType::SmartDocument],
			self::SIGN_CRM_SMART_DOCUMENT_DELETE => ['checkDeletePermissions', \CCrmOwnerType::SmartDocument],
			self::SIGN_CRM_SMART_DOCUMENT_WRITE => ['checkUpdatePermissions', \CCrmOwnerType::SmartDocument],
			self::SIGN_CRM_SMART_DOCUMENT_ADD => ['checkAddPermissions', \CCrmOwnerType::SmartDocument],
			self::SIGN_CRM_SMART_B2E_DOC_READ => ['checkReadPermissions', \CCrmOwnerType::SmartB2eDocument],
			self::SIGN_CRM_SMART_B2E_DOC_DELETE => ['checkDeletePermissions', \CCrmOwnerType::SmartB2eDocument],
			self::SIGN_CRM_SMART_B2E_DOC_WRITE => ['checkUpdatePermissions', \CCrmOwnerType::SmartB2eDocument],
			self::SIGN_CRM_SMART_B2E_DOC_ADD => ['checkAddPermissions', \CCrmOwnerType::SmartB2eDocument],
		];
	}
}
