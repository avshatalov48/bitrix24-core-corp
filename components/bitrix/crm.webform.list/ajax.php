<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Crm\WebForm\Form;
use Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('crm'))
{
	return;
}

Loc::loadMessages(__FILE__);

class CrmWebFormListAjaxController extends \Bitrix\Crm\WebForm\ComponentController
{
	protected function getActions()
	{
		return array(
			'activate',
			'deactivate',
			'delete',
			'copy',
			'reset_counters',
			'show_script',
			'clearFormCache',
		);
	}

	protected function activate()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		$form = new Form($this->requestData['FORM_ID']);
		if(!$form->isSystem() && !Form::canActivateForm())
		{
			$this->responseData['limited'] = true;
			$this->errors[] = '';
			return;
		}

		global $USER;
		if(!Form::activate($this->requestData['FORM_ID'], true, $USER->GetID()))
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
		if(!Form::activate($this->requestData['FORM_ID'], false, $USER->GetID()))
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

		//sleep(3); $this->errors[] = ''; return;
		if(!Form::delete($this->requestData['FORM_ID']))
		{
			$this->errors[] = '';
		}
	}

	protected function reset_counters()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		if(!Form::resetCounters($this->requestData['FORM_ID']))
		{
			$this->errors[] = '';
		}
	}

	protected function clearFormCache()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		if(!$this->requestData['FORM_ID'])
		{
			$this->errors[] = '';
			return;
		}

		Form::cleanCacheByTag($this->requestData['FORM_ID']);
	}

	protected function show_script()
	{
		ob_start();
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:crm.webform.script',
			'',
			array(
				'FORM_ID' => $this->requestData['FORM_ID'],
				'PATH_TO_WEB_FORM_FILL' => null,
				'TEMPLATE_CONTAINER_ID' => 'CRM_FORM_SCRIPT_CONT_' . $this->requestData['FORM_ID']
			),
			null,
			array('HIDE_ICONS' => true, 'ACTIVE_COMPONENT' => 'Y')
		);
		$this->responseData['html'] = ob_get_clean();
	}

	protected function copy()
	{
		if (!$this->checkPermissionsWrite())
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
			return;
		}

		global $USER;
		$copiedId = Form::copy($this->requestData['FORM_ID'], $USER->GetID());
		if(!$copiedId)
		{
			$this->errors[] = '';
		}
		else
		{
			$form = new Form($copiedId);
			$data = $form->get();
			$this->responseData['copiedId'] = $copiedId;
			$this->responseData['copiedName'] = $data['NAME'];
		}
	}

	protected function checkPermissionsWrite()
	{
		if (Loader::includeModule('bitrix24'))
		{
			if (!\Bitrix\Bitrix24\Feature::isFeatureEnabled('crm_webform_edit'))
			{
				return false;
			}
		}

		/**@var $USER \CUSER*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		return !$CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'WRITE');
	}

	protected function checkPermissions()
	{
		/**@var $USER \CUSER*/
		global $USER;
		$CrmPerms = new CCrmPerms($USER->GetID());
		return !$CrmPerms->HavePerm('WEBFORM', BX_CRM_PERM_NONE, 'READ');
	}

	protected function prepareRequestData()
	{
		$this->requestData = array(
			'FORM_ID' => intval($this->request->get('form_id'))
		);
	}
}

$controller = new CrmWebFormListAjaxController();
$controller->exec();