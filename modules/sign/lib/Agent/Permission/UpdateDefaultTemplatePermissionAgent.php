<?php

namespace Bitrix\Sign\Agent\Permission;

use Bitrix\Main\Loader;
use Bitrix\Sign\Access\Permission\PermissionDictionary as CrmPermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Service\Container;

class UpdateDefaultTemplatePermissionAgent
{
	public static function run(): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		$permissionsService = Container::instance()->getPermissionsService();
		$result = $permissionsService->copyPermissionValuesForAllRoles([
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD => SignPermissionDictionary::SIGN_B2E_TEMPLATE_CREATE,
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE => SignPermissionDictionary::SIGN_B2E_TEMPLATE_WRITE,
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ => SignPermissionDictionary::SIGN_B2E_TEMPLATE_READ,
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE => SignPermissionDictionary::SIGN_B2E_TEMPLATE_DELETE,
		]);
		if (!$result->isSuccess())
		{
			Logger::getInstance()->error('UpdateDefaultTemplatePermissionAgent error: ' . implode(', ', $result->getErrorMessages()));

			return '';
		}

		return '';
	}
}
