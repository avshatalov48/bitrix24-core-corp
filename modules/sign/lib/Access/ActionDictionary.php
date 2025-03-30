<?php

namespace Bitrix\Sign\Access;

use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use ReflectionClass;

class ActionDictionary
{
	
	public const ACTION_DOCUMENT_ADD = 'ACTION_DOCUMENT_ADD';
	public const ACTION_DOCUMENT_EDIT = 'ACTION_DOCUMENT_EDIT';
	public const ACTION_DOCUMENT_READ = 'ACTION_DOCUMENT_READ';
	public const ACTION_DOCUMENT_DELETE = 'ACTION_DOCUMENT_DELETE';

	public const ACTION_MY_SAFE_DOCUMENTS = 'ACTION_MY_SAFE_DOCUMENTS';
	public const ACTION_MY_SAFE = 'ACTION_MY_SAFE';

	public const ACTION_ACCESS_RIGHTS = 'ACTION_ACCESS_RIGHTS';
	public const ACTION_USE_TEMPLATE = 'ACTION_USE_TEMPLATE';


	public const ACTION_B2E_DOCUMENT_ADD = 'ACTION_B2E_DOCUMENT_ADD';
	public const ACTION_B2E_DOCUMENT_EDIT = 'ACTION_B2E_DOCUMENT_EDIT';
	public const ACTION_B2E_DOCUMENT_READ = 'ACTION_B2E_DOCUMENT_READ';
	public const ACTION_B2E_DOCUMENT_DELETE = 'ACTION_B2E_DOCUMENT_DELETE';

	public const ACTION_B2E_TEMPLATE_ADD = 'ACTION_B2E_TEMPLATE_ADD';
	public const ACTION_B2E_TEMPLATE_EDIT = 'ACTION_B2E_TEMPLATE_EDIT';
	public const ACTION_B2E_TEMPLATE_READ = 'ACTION_B2E_TEMPLATE_READ';
	public const ACTION_B2E_TEMPLATE_DELETE = 'ACTION_B2E_TEMPLATE_DELETE';

	public const ACTION_B2E_MY_SAFE_DOCUMENTS = 'ACTION_B2E_MY_SAFE_DOCUMENTS';
	public const ACTION_B2E_MY_SAFE = 'ACTION_B2E_MY_SAFE';

	public const ACTION_B2E_PROFILE_FIELDS_READ = 'ACTION_B2E_PROFILE_FIELDS_READ';
	public const ACTION_B2E_PROFILE_FIELDS_EDIT = 'ACTION_B2E_PROFILE_FIELDS_EDIT';
	public const ACTION_B2E_PROFILE_FIELDS_ADD = 'ACTION_B2E_PROFILE_FIELDS_ADD';
	public const ACTION_B2E_PROFILE_FIELDS_DELETE = 'ACTION_B2E_PROFILE_FIELDS_DELETE';
	public const ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE = 'ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE';

	public const ACTION_B2E_USE_TEMPLATE = 'ACTION_USE_B2E_TEMPLATE';

	public const PREFIX = "ACTION_";

	/**
	 * get action name by string value
	 *
	 * @param string $value string value of action
	 *
	 * @return string|null
	 */
	public static function getActionName(string $value): ?string
	{
		$constants = self::getActionNames();
		if (!array_key_exists($value, $constants))
		{
			return null;
		}
		
		return str_replace(self::PREFIX, '', $constants[$value]);
	}

	/**
	 * @return array
	 */
	private static function getActionNames(): array
	{
		$class = new ReflectionClass(__CLASS__);
		$constants = $class->getConstants();
		foreach ($constants as $name => $value)
		{
			if (!str_starts_with($name, self::PREFIX))
			{
				unset($constants[$name]);
			}
		}
		
		return array_flip($constants);
	}

	public static function getActionPermissionMap(): array
	{
		return [
			self::ACTION_ACCESS_RIGHTS => SignPermissionDictionary::SIGN_ACCESS_RIGHTS,


			self::ACTION_MY_SAFE_DOCUMENTS => SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS,
			self::ACTION_MY_SAFE => SignPermissionDictionary::SIGN_MY_SAFE,

			self::ACTION_DOCUMENT_ADD => PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_ADD,
			self::ACTION_DOCUMENT_EDIT => PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_WRITE,
			self::ACTION_DOCUMENT_READ => PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_READ,
			self::ACTION_DOCUMENT_DELETE => PermissionDictionary::SIGN_CRM_SMART_DOCUMENT_DELETE,

			self::ACTION_USE_TEMPLATE => SignPermissionDictionary::SIGN_TEMPLATES,


			self::ACTION_B2E_MY_SAFE_DOCUMENTS => SignPermissionDictionary::SIGN_B2E_MY_SAFE_DOCUMENTS,
			self::ACTION_B2E_MY_SAFE => SignPermissionDictionary::SIGN_B2E_MY_SAFE,

			self::ACTION_B2E_DOCUMENT_ADD => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD,
			self::ACTION_B2E_DOCUMENT_EDIT => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE,
			self::ACTION_B2E_DOCUMENT_READ => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ,
			self::ACTION_B2E_DOCUMENT_DELETE => PermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE,

			self::ACTION_B2E_TEMPLATE_ADD => SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
			self::ACTION_B2E_TEMPLATE_EDIT => SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
			self::ACTION_B2E_TEMPLATE_READ => SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ,
			self::ACTION_B2E_TEMPLATE_DELETE => SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,

			self::ACTION_B2E_USE_TEMPLATE => SignPermissionDictionary::SIGN_B2E_TEMPLATES,

			self::ACTION_B2E_PROFILE_FIELDS_READ => SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_READ,
			self::ACTION_B2E_PROFILE_FIELDS_ADD => SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_ADD,
			self::ACTION_B2E_PROFILE_FIELDS_EDIT => SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_EDIT,
			self::ACTION_B2E_PROFILE_FIELDS_DELETE => SignPermissionDictionary::SIGN_B2E_PROFILE_FIELDS_DELETE,
			self::ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE => SignPermissionDictionary::SIGN_B2E_MEMBER_DYNAMIC_FIELDS_DELETE,
		];
	}

	public static function getPermissionIdByAction(string $action): string|int|null
	{
		return self::getActionPermissionMap()[$action] ?? null;
	}

	public static function getB2eSectionAccessActions(): array
	{
		return [
			self::ACTION_B2E_DOCUMENT_READ,
			self::ACTION_B2E_TEMPLATE_READ,
			self::ACTION_B2E_MY_SAFE_DOCUMENTS,
			self::ACTION_B2E_PROFILE_FIELDS_DELETE,
			self::ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE,
			self::ACTION_ACCESS_RIGHTS,
		];
	}
}
