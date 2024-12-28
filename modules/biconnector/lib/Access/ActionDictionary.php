<?php

namespace Bitrix\BIConnector\Access;

use Bitrix\BIConnector\Access\Permission\PermissionDictionary;

final class ActionDictionary
{
	public const PREFIX = 'bic_';

	public const ACTION_BIC_ACCESS = 'bic_access';
	public const ACTION_BIC_DASHBOARD_CREATE = 'bic_create';
	public const ACTION_BIC_SETTINGS_ACCESS = 'bic_settings_access';
	public const ACTION_BIC_SETTINGS_EDIT_RIGHTS = 'bic_settings_edit_rights';
	public const ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG = 'bic_external_dashboard_config';
	public const ACTION_BIC_DASHBOARD_VIEW = 'bic_dashboard_view';
	public const ACTION_BIC_DASHBOARD_EDIT = 'bic_dashboard_edit';
	public const ACTION_BIC_DASHBOARD_DELETE = 'bic_dashboard_delete';
	public const ACTION_BIC_DASHBOARD_EXPORT = 'bic_dashboard_export';
	public const ACTION_BIC_DASHBOARD_COPY = 'bic_dashboard_copy';
	public const ACTION_BIC_DASHBOARD_TAG_MODIFY = 'bic_tag_modify';
	public const ACTION_BIC_EDIT_SCOPE = 'bic_edit_scope';

	public static function getActionPermissionMap(): array
	{
		return [
			self::ACTION_BIC_ACCESS => PermissionDictionary::BIC_ACCESS,
			self::ACTION_BIC_DASHBOARD_CREATE => PermissionDictionary::BIC_DASHBOARD_CREATE,
			self::ACTION_BIC_SETTINGS_ACCESS => PermissionDictionary::BIC_SETTINGS_ACCESS,
			self::ACTION_BIC_SETTINGS_EDIT_RIGHTS => PermissionDictionary::BIC_SETTINGS_EDIT_RIGHTS,
			self::ACTION_BIC_EXTERNAL_DASHBOARD_CONFIG => PermissionDictionary::BIC_EXTERNAL_DASHBOARD_CONFIG,
			self::ACTION_BIC_DASHBOARD_VIEW => PermissionDictionary::BIC_DASHBOARD,
			self::ACTION_BIC_DASHBOARD_EDIT => PermissionDictionary::BIC_DASHBOARD,
			self::ACTION_BIC_DASHBOARD_TAG_MODIFY => PermissionDictionary::BIC_DASHBOARD_TAG_MODIFY,
			self::ACTION_BIC_EDIT_SCOPE => PermissionDictionary::BIC_DASHBOARD_EDIT_SCOPE,
			self::ACTION_BIC_DASHBOARD_DELETE => PermissionDictionary::BIC_DASHBOARD,
			self::ACTION_BIC_DASHBOARD_EXPORT => PermissionDictionary::BIC_DASHBOARD,
			self::ACTION_BIC_DASHBOARD_COPY => PermissionDictionary::BIC_DASHBOARD,
		];
	}

	public static function getDashboardPermissionsMap(): array
	{
		return [
			self::ACTION_BIC_DASHBOARD_VIEW => PermissionDictionary::BIC_DASHBOARD_VIEW,
			self::ACTION_BIC_DASHBOARD_EDIT => PermissionDictionary::BIC_DASHBOARD_EDIT,
			self::ACTION_BIC_DASHBOARD_DELETE => PermissionDictionary::BIC_DASHBOARD_DELETE,
			self::ACTION_BIC_DASHBOARD_EXPORT => PermissionDictionary::BIC_DASHBOARD_EXPORT,
			self::ACTION_BIC_DASHBOARD_COPY => PermissionDictionary::BIC_DASHBOARD_COPY,
		];
	}
}
