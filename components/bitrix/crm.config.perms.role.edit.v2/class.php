<?php

use Bitrix\Crm\Security\Role\Exceptions\RoleNotFoundException;
use Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2\Manage;
use Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2\RoleEditorSerializer;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Engine\ActionFilter\ContentType;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Crm\Security\Role\Utils\RolePermissionLogContext;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

class CrmConfigPermsRoleEditV2 extends CBitrixComponent implements Controllerable
{

	private Manage $manage;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->manage = new Manage();
	}

	public function executeComponent(): void
	{
		if (!$this->hasAccessToManageCrmPermissions())
		{
			Container::getInstance()->getLocalization()->loadMessages();
			ShowError(GetMessage('CRM_COMMON_ERROR_ACCESS_DENIED'));

			return;
		}

		$roleId = (int)($this->arParams['ROLE_ID'] ?? 0);

		if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete']) && check_bitrix_sessid() && $roleId)
		{
			RolePermissionLogContext::getInstance()->set([
				'component' => 'crm.config.perms.role.edit.v2',
			]);
			$this->manage->delete($roleId);
			RolePermissionLogContext::getInstance()->clear();
			LocalRedirect('/crm/configs/perms/');

			return;
		}

		$roleId = (int)$this->arParams['ROLE_ID'];

		try {
			$data = $this->manage->getRoleData($roleId);
			$serializer = new RoleEditorSerializer();

			$this->arResult['APP_DATA'] = $serializer->serialize($data);
		}
		catch (RoleNotFoundException $e)
		{
			LocalRedirect('/crm/configs/perms/');

			return;
		}


		$this->IncludeComponentTemplate();
	}

	public function configureActions(): array
	{
		return [
			'save' => [
				'+prefilters' => [
					new ContentType([ContentType::JSON]),
				],
			],
			'delete' => [
				'+prefilters' => [
					new ContentType([ContentType::JSON]),
				],
			],
		];
	}

	public function saveAction(array $values): AjaxJson
	{
		if (!$this->hasAccessToManageCrmPermissions())
		{
			return AjaxJson::createDenied(null, ['message' => GetMessage('CRM_PERMISSION_DENIED')]);
		}

		try
		{
			RolePermissionLogContext::getInstance()->set([
				'component' => 'crm.config.perms.role.edit.v2',
			]);
			$result = $this->manage->save($values);
			RolePermissionLogContext::getInstance()->clear();

			$roleId = (int)($result->getData()['id'] ?? null);

			if (!$result->isSuccess())
			{
				$msg = $result->getErrorMessages()[0] ?? '';
				return AjaxJson::createError(null, ['message' => $msg]);
			}

			return AjaxJson::createSuccess([
				'redirectUrl' => '/crm/configs/perms/',
				'roleUrl' => "/crm/configs/perms/$roleId/edit/"
			]);
		}
		catch (RoleNotFoundException $e)
		{
			return AjaxJson::createError(null, ['message' => GetMessage('CRM_PERMISSION_DENIED')]);
		}

	}

	public function deleteAction(array $values): AjaxJson
	{
		if (!$this->hasAccessToManageCrmPermissions())
		{
			return AjaxJson::createDenied(null, ['message' => GetMessage('CRM_PERMISSION_DENIED')]);
		}

		$roleId = (int)($values['roleId'] ?? null);

		if (empty($roleId))
		{
			return AjaxJson::createError();
		}

		$result = $this->manage->delete($roleId);

		if (!$result->isSuccess())
		{
			$msg = $result->getErrorMessages()[0] ?? '';
			return AjaxJson::createError(null, ['message' => $msg]);
		}

		return AjaxJson::createSuccess(['redirectUrl' => '/crm/configs/perms/']);
	}


	private function hasAccessToManageCrmPermissions(): bool
	{
		return Container::getInstance()->getUserPermissions()->canWriteConfig();
	}

}
