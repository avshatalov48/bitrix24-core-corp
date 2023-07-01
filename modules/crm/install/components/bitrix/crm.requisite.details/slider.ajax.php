<?

use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

Loc::loadMessages(__FILE__);

/** @global \CMain */
global $APPLICATION;

if(!Loader::includeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
}
else
{
	$entityTypeId = isset($_REQUEST['etype']) ? (int)$_REQUEST['etype'] : 0;
	$entityId = isset($_REQUEST['eid']) ? (int)$_REQUEST['eid'] : 0;

	$categoryId = null;
	$checkPermissionsParams = null;
	if ($entityId <= 0 && isset($_REQUEST['cid']))
	{
		$categoryId = (int)$_REQUEST['cid'];
		$checkPermissionsParams['CATEGORY_ID'] = $categoryId;
	}
	$permissionToken = (string)($_REQUEST['permissionToken'] ?? '');

	$hasPermissions = (
		check_bitrix_sessid()
		&& EntityAuthorization::isAuthorized()
		&& (
			EntityAuthorization::checkReadPermission($entityTypeId, $entityId, null, $checkPermissionsParams)
			|| \Bitrix\Crm\Security\PermissionToken::canEditRequisites($permissionToken, $entityTypeId, $entityId)
		)
	);

	if(!$hasPermissions)
	{
		ShowError(Loc::getMessage('CRM_ACCESS_DENIED'));
	}
	else
	{
		$action = $_POST['ACTION'] ?? '';
		$isAction = (
			$_SERVER['REQUEST_METHOD'] === 'POST'
			&& check_bitrix_sessid()
		);
		$isSave = ($isAction && $action === 'SAVE');
		$isReaload = ($isAction && $action === 'RELOAD');
		unset($action, $isAction);

		$useFormData = (
			isset($_REQUEST['useFormData'])
			&& mb_strtoupper($_REQUEST['useFormData']) === 'Y'
			|| $isSave
			|| $isReaload
		);

		$mode = $_REQUEST['mode'] ?? '';
		$requisiteId = (int)($_REQUEST['requisite_id'] ?? 0);
		if (!in_array($mode, ['create', 'edit', 'copy', 'delete'], true))
		{
			if ($requisiteId > 0)
			{
				if (isset($_REQUEST['copy']) && !empty($_REQUEST['copy']))
				{
					$mode = 'copy';
				}
				else
				{
					$mode = 'edit';
				}
			}
			else
			{
				$mode = 'create';
			}
		}

		if (Context::getCurrent()->getRequest()->isAjaxRequest())
		{
			CUtil::JSPostUnescape();
		}

		$componentParams = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'CATEGORY_ID' => $categoryId,
			'ENTITY_ID' => $entityId,
			'REQUISITE_ID' => $requisiteId,
			'PSEUDO_ID' => $_REQUEST['pseudoId'] ?? '',
			'PRESET_ID' => (int)($_REQUEST['pid'] ?? 0),
			'MODE' => $mode,
			'DO_SAVE' => (isset($_REQUEST['doSave']) && mb_strtoupper($_REQUEST['doSave']) === 'Y') ? 'Y' : 'N',
			'USE_EXTERNAL_DATA' => isset($_REQUEST['externalData']) ? 'Y' : 'N',
			'EXTERNAL_DATA' => $_REQUEST['externalData'] ?? [],
			'USE_FORM_DATA' => $useFormData ? 'Y' : 'N',
			'FORM_DATA' => $useFormData ? $_POST : [],
			'EXTERNAL_CONTEXT_ID' => $_REQUEST['external_context_id'] ?? '',
			'IS_SAVE' => $isSave ? 'Y' : 'N',
			'IS_RELOAD' => $isReaload ? 'Y' : 'N',
			'ADD_BANK_DETAILS_ITEM' =>
				(
					isset($_REQUEST['addBankDetailsItem'])
					&& mb_strtoupper($_REQUEST['addBankDetailsItem']) === 'Y'
				)
					? 'Y'
					: 'N',
			'PERMISSION_TOKEN' => $permissionToken,
		];

		$APPLICATION->IncludeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'POPUP_COMPONENT_NAME' => 'bitrix:crm.requisite.details',
				'POPUP_COMPONENT_PARAMS' => $componentParams,
				'EDITABLE_TITLE_DEFAULT' => '',
				'EDITABLE_TITLE_SELECTOR' => "[data-cid='NAME']",
			]
		);
	}
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
exit;
?>