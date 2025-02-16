<?php

namespace Bitrix\Crm\Security\Role\Utils;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Security\Role\Repositories\PermissionRepository;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CCrmSaleHelper;

class RoleManagerUtils
{
	use Singleton;

	private PermissionRepository $permissionRepository;

	private function __construct()
	{
		$this->permissionRepository = PermissionRepository::getInstance();
	}

	public function checkTariffRestriction(): Result
	{
		$result = new Result();
		$restriction = $this->permissionRepository->getTariffRestrictions();

		if (!$restriction->hasPermission())
		{
			Container::getInstance()->getLocalization()->loadMessages();

			$result->addError(
				new Error(
					Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'),
					ErrorCode::RESTRICTED_BY_TARIFF,
					[
						'sliderCode' => $restriction->sliderCode(),
					],
				)
			);
		}

		return $result;
	}

	public function clearRolesCache(): void
	{
		$cache = new \CPHPCache();
		$cache->CleanDir("/crm/list_crm_roles/");

		\CCrmRole::ClearCache();
	}

	public function saleUpdateShopAccess(): void
	{
		CCrmSaleHelper::updateShopAccess();
	}

	public function isUsePermConfigV2(): bool
	{
		return Option::get('crm', 'use_v2_version_config_perms', 'N') === 'Y';
	}
}
