<?php

namespace Bitrix\Sign\Agent\Permission;

use Bitrix\Main\Loader;
use Bitrix\Sign\Access\Permission\PermissionDictionary as CrmPermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\PermissionsService;
use Psr\Log\LoggerInterface;

class UpdateDefaultSignersListPermissionAgent
{
	public function __construct(
		private readonly PermissionsService $permissionsService,
		private readonly LoggerInterface $logger,
	)
	{
	}

	public static function run(): string
	{
		$permissionsService = Container::instance()->getPermissionsService();
		$logger = Logger::getInstance();
		(new static($permissionsService, $logger))->copyFromDocumentPermissions();
		
		return '';
	}
	
	public function copyFromDocumentPermissions(): void
	{
		if (!Loader::includeModule('crm'))
		{
			$this->logger->error('UpdateDefaultSignersListPermissionAgent error: crm module required');

			return;
		}

		$result = $this->permissionsService->copyPermissionValuesForAllRoles([
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_ADD => SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_ADD,
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_WRITE => SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_EDIT,
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_READ => SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_READ,
			CrmPermissionDictionary::SIGN_CRM_SMART_B2E_DOC_DELETE => SignPermissionDictionary::SIGN_B2E_SIGNERS_LIST_DELETE,
		]);

		if (!$result->isSuccess())
		{
			$this->logger->error('UpdateDefaultSignersListPermissionAgent error: ' . implode(', ', $result->getErrorMessages()));
		}
	}
}
