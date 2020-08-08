<?php

use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CDiskBizprocListComponent extends BaseComponent implements SidePanelWrappable
{
	const ERROR_COULD_NOT_FIND_MODULE_ID = 'DISK_BLC_22001';
	const ERROR_COULD_NOT_FIND_STORAGE_ID = 'DISK_BLC_22002';

	protected function listActions()
	{
		return array(
			'edit' => 'autoload',
			'create' => 'autoload',
			'editPresent' => 'autoload',
			'createPresent' => 'autoload',
			'createDefault',
			'delete',
		);
	}

	protected function checkRequiredModules()
	{
		if (!\Bitrix\Disk\Integration\BizProcManager::isAvailable())
		{
			throw new SystemException('Install module "bizproc"');
		}
		return $this;
	}

	protected function prepareParams()
	{
		$this->arParams['STORAGE_ID'] = isset($this->arParams['DOCUMENT_ID']) ?
			intval(str_replace('STORAGE_', '', $this->arParams['DOCUMENT_ID'])) : $this->arParams['STORAGE_ID'];
		$this->arParams['SET_TITLE'] = $this->arParams['SET_TITLE'] == 'N' ? 'N' : 'Y';

		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ?
			COption::getOptionString('bizproc', 'name_template', CSite::getNameFormat(false), SITE_ID) :
			str_replace(array('#NOBR#','#/NOBR#'), array('',''), $this->arParams['NAME_TEMPLATE']);

		if (!isset($this->arParams['MODULE_ID']))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('BPATT_NO_MODULE_ID'), self::ERROR_COULD_NOT_FIND_MODULE_ID)));
		}
		if (!isset($this->arParams['STORAGE_ID']))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('BPATT_NO_DOCUMENT_TYPE'), self::ERROR_COULD_NOT_FIND_STORAGE_ID)));
		}

		if ($this->errorCollection->hasErrors())
		{
			$error = array_shift($this->getErrors());
			throw new ArgumentException($error->getMessage());
		}

		$this->arParams['DOCUMENT_DATA'] = array(
			'DISK' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->arParams['STORAGE_ID']),
			),
			'WEBDAV' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->arParams['STORAGE_ID']),
			)
		);

		$this->arParams['DOCUMENT_TYPE'] = \Bitrix\Disk\BizProcDocument::generateDocumentType($this->arParams['STORAGE_ID']);

		return $this;
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		if (!CBPDocument::canUserOperateDocumentType(
			CBPCanUserOperateOperation::CreateWorkflow,
			$this->getUser()->getID(),
			$this->arParams['DOCUMENT_DATA']['DISK']['DOCUMENT_TYPE'],
			array()
		))
		{
			$this->showAccessDenied();
			return false;
		}
		return true;
	}

	protected function processActionDefault()
	{
		$this->getTemplateData();
		if($this->arParams['SET_TITLE'] == 'Y')
		{
			$this->application->setTitle(Loc::getMessage('BPATT_TITLE'));
		}
		$this->includeComponentTemplate();
	}

	protected function processActionAutoload()
	{
		$this->checkRequiredInputParams($_GET, array('action'));
		if($this->errorCollection->hasErrors())
		{
			$error = array_shift($this->getErrors());
			throw new SystemException($error->getMessage());
		}
		$requestAction = $this->request->getQuery('action');
		$requestBackUrl = $this->request->getQuery('back_url');
		foreach($this->arParams['DOCUMENT_DATA'] as $nameModule => $data)
		{
			$result = CBPWorkflowTemplateLoader::getList(
				array('name' => 'asc'),
				array('DOCUMENT_TYPE' => $data['DOCUMENT_TYPE'], 'ID' => $this->request->getQuery('ID')),
				false,
				false,
				array('ID', 'NAME', 'AUTO_EXECUTE'));
			if ($result && $res = $result-> fetch())
			{
				$fields = array('AUTO_EXECUTE' => $res['AUTO_EXECUTE']);
				$temporary = false;
				if (mb_strpos($requestAction, 'create') !== false)
				{
					$temporary = CBPDocumentEventType::Create;
				}
				elseif (mb_strpos($requestAction, 'edit') !== false)
				{
					$temporary = CBPDocumentEventType::Edit;
				}
				elseif (mb_strpos($requestAction, 'delete') !== false)
				{
					$temporary = CBPDocumentEventType::Delete;
				}

				if ($temporary != false)
				{
					if (mb_strpos($requestAction, 'present') !== false)
					{
						$fields['AUTO_EXECUTE'] = ((($fields['AUTO_EXECUTE'] & $temporary) != 0) ? $fields['AUTO_EXECUTE'] ^ $temporary : $fields['AUTO_EXECUTE']);
					}
					else
					{
						$fields['AUTO_EXECUTE'] = ((($fields['AUTO_EXECUTE'] & $temporary) == 0) ? $fields['AUTO_EXECUTE'] ^ $temporary : $fields['AUTO_EXECUTE']);
					}
				}

				if ($fields['AUTO_EXECUTE'] != $res['AUTO_EXECUTE'])
				{
					CBPWorkflowTemplateLoader::update($this->request->getQuery('ID'), $fields);
				}
			}
		}
		$url = (!empty($requestBackUrl) ? $requestBackUrl : $this->application->getCurPageParam('', array('action', 'sessid', 'ID')));
		LocalRedirect($url);
	}

	protected function processActionCreateDefault()
	{
		CBPDocument::addDefaultWorkflowTemplates($this->arParams['DOCUMENT_DATA']['DISK']['DOCUMENT_TYPE']);
		LocalRedirect($this->application->getCurPageParam('', array('action', 'sessid')));
	}

	protected function processActionDelete()
	{
		$errorsTemporary = array();
		foreach($this->arParams['DOCUMENT_DATA'] as $nameModule => $data)
		{
			$result = CBPWorkflowTemplateLoader::getList(
				array('name' => 'asc'),
				array('DOCUMENT_TYPE' => $data['DOCUMENT_TYPE'], 'ID' => $this->request->getQuery('ID')),
				false,
				false,
				array('ID')
			);
			$availabilityTemplate = $result->fetch();
			if(!empty($availabilityTemplate))
			{
				CBPDocument::deleteWorkflowTemplate($this->request->getQuery('ID'), $data['DOCUMENT_TYPE'], $errorsTemporary);
			}
		}
		if (empty($errorsTemporary))
		{
			$requestBackUrl = $this->request->getQuery('back_url');
			$url = (!empty($requestBackUrl) ? $requestBackUrl : $this->application->getCurPageParam('', array('action', 'sessid', 'ID')));
			LocalRedirect($url);
		}
		elseif (!empty($errorsTemporary))
		{
			$errors = array();
			foreach ($errorsTemporary as $e)
			{
				$errors[] = array('id' => 'delete_error', 'text' => $e['message']);
			}
			$e = new CAdminException($errors);
			ShowError($e->getString());
		}
	}

	protected function getTemplateData()
	{
		$this->arResult['NAV_RESULT'] = "";
		$this->arResult['TEMPLATES'] = array();
		$this->arResult['GRID_TEMPLATES'] = array();
		$this->arResult['CREATE_NEW_TEMPLATES'] = false;
		$this->arResult['PROMPT_OLD_TEMPLATE'] = false;
		$checkNewTemplate = false;
		$checkOldTemplate = false;

		foreach($this->arParams['DOCUMENT_DATA'] as $nameModule => $data)
		{
			$result = CBPWorkflowTemplateLoader::getList(
				array('name' => 'asc'),
				array('DOCUMENT_TYPE' => $data["DOCUMENT_TYPE"]),
				false,
				false,
				array(
					'ID', 'NAME', 'DESCRIPTION', 'MODIFIED', 'USER_ID', 'AUTO_EXECUTE', 'USER_NAME',
					'USER_LAST_NAME', 'USER_LOGIN', 'ACTIVE', 'USER_SECOND_NAME'));
			if ($result)
			{
				$checklistTemplate = $result->fetch();
				if(!empty($checklistTemplate) && $nameModule == 'DISK')
				{
					$checkNewTemplate = true;
				}
				elseif(!empty($checklistTemplate) && $nameModule == 'WEBDAV')
				{
					$checkOldTemplate = true;
				}
				$result->NavStart(25, false);
				$this->arResult['NAV_RESULT'] = $result;
				$adminPage = $this->application->getCurPageParam('&action=delete&'.bitrix_sessid_get(),
					array('back_url', 'action', 'ID', 'sessid'));

				while ($res = $result->getNext())
				{
					$res['URL'] = array(
						'EDIT' => CComponentEngine::makePathFromTemplate($this->arParams['~EDIT_URL'], array('ID' => $res['ID'])),
						'DELETE' => $adminPage.'&ID='.$res['ID']);
					foreach ($res['URL'] as $key => $val)
					{
						$res['URL']['~'.$key] = $val;
						$res['URL'][$key] = htmlspecialcharsbx($val);
					}
					$res['USER'] = CUser::formatName($this->arParams['NAME_TEMPLATE'], array(
						'NAME' => $res['~USER_NAME'], 'LAST_NAME' => $res['~USER_LAST_NAME'],
						'SECOND_NAME' => $res['~USER_SECOND_NAME'], 'LOGIN' => $res['~USER_LOGIN']), true);

					$autoExecuteText = array();
					if ($res['AUTO_EXECUTE'] == CBPDocumentEventType::None)
						$autoExecuteText[] = Loc::getMessage('BPATT_AE_NONE');
					if (($res['AUTO_EXECUTE'] & CBPDocumentEventType::Create) != 0)
						$autoExecuteText[] = Loc::getMessage('BPATT_AE_CREATE');
					if (($res['AUTO_EXECUTE'] & CBPDocumentEventType::Edit) != 0)
						$autoExecuteText[] = Loc::getMessage('BPATT_AE_EDIT');
					if (($res['AUTO_EXECUTE'] & CBPDocumentEventType::Delete) != 0)
						$autoExecuteText[] = Loc::getMessage('BPATT_AE_DELETE');

					$res['AUTO_EXECUTE'] = $autoExecuteText;
					$this->arResult['TEMPLATES'][$res['ID']] = $res;

					$presentCreate = (($res['~AUTO_EXECUTE'] & CBPDocumentEventType::Create) != 0);
					$url = $this->application->getCurPageParam('ID='.$res['ID'].'&action=create'.($presentCreate ? 'present' : '').'&'.bitrix_sessid_get(),
						array('back_url', 'action', 'ID', 'sessid'));
					$presentEdit = (($res["~AUTO_EXECUTE"] & CBPDocumentEventType::Edit) != 0);
					$url1 = $this->application->getCurPageParam('ID='.$res["ID"].'&action=edit'.($presentEdit ? 'present' : '').'&'.bitrix_sessid_get(),
						array('back_url', 'action', 'ID', 'sessid'));

					$actions = array(
						array(
							'ICONCLASS' => '',
							'TITLE' => ($presentCreate ? Loc::getMessage('BPATT_DO_N_LOAD_CREATE_TITLE') : Loc::getMessage('BPATT_DO_LOAD_CREATE_TITLE')),
							'TEXT' => ($presentCreate ? Loc::getMessage('BPATT_DO_N_LOAD_CREATE') : Loc::getMessage('BPATT_DO_LOAD_CREATE')),
							'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($url)."');"),
						array(
							'ICONCLASS' => '',
							'TITLE' => ($presentEdit ? Loc::getMessage('BPATT_DO_N_LOAD_EDIT_TITLE') : Loc::getMessage('BPATT_DO_LOAD_EDIT_TITLE')),
							'TEXT' => ($presentEdit ? Loc::getMessage('BPATT_DO_N_LOAD_EDIT') : Loc::getMessage('BPATT_DO_LOAD_EDIT')),
							'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($url1)."');"));
					$actions[] = array('SEPARATOR' => true);
					if (isset($res['URL']['VARS']))
					{
						$actions[] = array(
							'ICONCLASS' => "edit",
							'TITLE' => Loc::getMessage('BPATT_DO_EDIT_VARS'),
							'TEXT' => Loc::getMessage('BPATT_DO_EDIT_VARS1'),
							'ONCLICK' => "top.document.location='".CUtil::JSEscape($res['URL']['~VARS'])."';",
							'DEFAULT' => false);
					}
					if (Loader::includeModule('bizprocdesigner'))
					{
						$actions[] = array(
							'ICONCLASS' => 'edit',
							'TITLE' => Loc::getMessage('BPATT_DO_EDIT1'),
							'TEXT' => Loc::getMessage('BPATT_DO_EDIT1'),
							'ONCLICK' => "top.document.location='".CUtil::JSEscape($res['URL']['~EDIT'])."';",
							'DEFAULT' => true);
					}
					$actions[] = array(
						'ICONCLASS' => "delete",
						'TITLE' => Loc::getMessage('BPATT_DO_DELETE1'),
						'TEXT' => Loc::getMessage('BPATT_DO_DELETE1'),
						'ONCLICK' => "if(confirm('".CUtil::JSEscape(Loc::getMessage('BPATT_DO_DELETE1_CONFIRM'))."')){jsUtils.Redirect([], '".CUtil::JSEscape($res['URL']['~DELETE'])."')};");

					if($nameModule == 'WEBDAV')
					{
						$res['NAME'] .= ' '.Loc::getMessage('BPATT_DO_OLD_TEMPLATE');
						$res['~NAME'] .= ' '.Loc::getMessage('BPATT_DO_OLD_TEMPLATE');
					}
					$this->arResult['GRID_TEMPLATES'][$res['ID']] = array(
						'id' => $res['ID'],
						'data' => $res,
						'actions' => $actions,
						'columns' => array(
							'NAME' => (Loader::includeModule('bizprocdesigner') ? '<a href="'.$res['URL']['EDIT'].'">'.$res['NAME'].'</a>' : $res['NAME']),
							'AUTO_EXECUTE' => implode("<br />", $res['AUTO_EXECUTE'])),
						'editable' => false);

				}
			}
		}

		if($checkOldTemplate)
		{
			$this->arResult['PROMPT_OLD_TEMPLATE'] = true;
			if(!$checkNewTemplate)
			{
				$this->arResult['CREATE_NEW_TEMPLATES'] = true;
			}
		}
	}
}