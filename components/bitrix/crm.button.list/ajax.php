<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Crm\SiteButton\Button;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('crm'))
{
	return;
}

use \Bitrix\Crm\SiteButton\Script;

Loc::loadMessages(__FILE__);

class CrmSiteButtonListAjaxController extends \Bitrix\Crm\SiteButton\ComponentController
{
	protected function getActions()
	{
		return array(
			'activate',
			'deactivate',
			'delete',
			'copy',
			'show_script',
		);
	}

	protected function activate()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		global $USER;
		if(!Button::activate($this->requestData['BUTTON_ID'], true, $USER->GetID()))
		{
			$this->errors[] = '';
		}
	}

	protected function deactivate()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		global $USER;
		if(!Button::activate($this->requestData['BUTTON_ID'], false, $USER->GetID()))
		{
			$this->errors[] = '';
		}
	}

	protected function delete()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		if(!Button::delete($this->requestData['BUTTON_ID']))
		{
			$this->errors[] = '';
		}
	}

	protected function copy()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		global $USER;
		$copiedId = Button::copy($this->requestData['BUTTON_ID'], $USER->GetID());
		if(!$copiedId)
		{
			$this->errors[] = '';
		}
		else
		{
			$button = new Button($copiedId);
			$data = $button->getData();
			$this->responseData['copiedId'] = $copiedId;
			$this->responseData['copiedName'] = $data['NAME'];
		}
	}

	protected function show_script()
	{
		$button = new Button($this->request->get('button_id'));
		$script = Script::getScript($button);
		$script = htmlspecialcharsbx($script);
		$script = str_replace("\t", str_repeat('&nbsp;', 8), $script);
		$this->responseData['html'] = nl2br($script);
	}

	protected function checkPermissionsWrite()
	{
		/**@var $USER \CUser*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		return !$CrmPerms->HavePerm('BUTTON', BX_CRM_PERM_NONE, 'WRITE');
	}

	protected function checkPermissions()
	{
		/**@var $USER \CUser*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		return !$CrmPerms->HavePerm('BUTTON', BX_CRM_PERM_NONE, 'READ');
	}

	protected function prepareRequestData()
	{
		$this->requestData = array(
			'BUTTON_ID' => intval($this->request->get('button_id'))
		);
	}
}

$controller = new CrmSiteButtonListAjaxController();
$controller->exec();