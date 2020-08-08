<?php

use Bitrix\Disk\Internals\Engine\Contract\SidePanelWrappable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\PostDecodeFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CDiskBizprocEditComponent extends BaseComponent implements SidePanelWrappable
{
	protected function listActions()
	{
		return array(
			'saveAjax' => array(
				'method' => array('POST'),
				'name' => 'saveAjax',
			),
			'importTemplate' => array(
				'method' => array('POST'),
				'name' => 'importTemplate',
			),
			'exportTemplate',
		);
	}

	protected function checkRequiredModules()
	{
		if (!\Bitrix\Disk\Integration\BizProcManager::isAvailable())
		{
			throw new SystemException('Install module "bizproc"');
		}
		if (!Loader::includeModule('bizprocdesigner'))
		{
			throw new SystemException('Install module "bizprocdesigner"');
		}
		return $this;
	}

	protected function prepareParams()
	{
		$this->arResult['ID'] = intval($this->arParams['ID']);
		if(!empty($this->arParams['HIDE_TAB_PERMISSION']) && $this->arParams['HIDE_TAB_PERMISSION'] == 'Y')
		{
			$this->arResult['HIDE_TAB_PERMISSION'] = true;
		}
		else
		{
			$this->arResult['HIDE_TAB_PERMISSION'] = false;
		}
		$this->arResult['LIST_PAGE_URL'] = $this->arParams['LIST_PAGE_URL'];
		$this->arResult['EDIT_PAGE_TEMPLATE'] = $this->arParams['EDIT_PAGE_TEMPLATE'];
		$this->arResult['DOCUMENT_TYPE'] = \Bitrix\Disk\BizProcDocument::generateDocumentType($this->arParams['STORAGE_ID']);
		$this->arResult['MODULE_ID'] = $this->arParams['MODULE_ID'];

		return $this;
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		if($this->arParams['STORAGE_ID'] == '')
		{
			ShowError(Loc::getMessage('ACCESS_DENIED').' '.Loc::getMessage('BIZPROC_WFEDIT_ERROR_TYPE'));
			return false;
		}
		if($this->arResult['ID'] > 0)
		{
			$templatesList = CBPWorkflowTemplateLoader::getList(array(), array('ID' => $this->arResult['ID']));
			if($template = $templatesList->fetch())
			{
				if(!CBPDocument::canUserOperateDocumentType(
					CBPCanUserOperateOperation::CreateWorkflow,
					$this->getUser()->getID(),
					$template['DOCUMENT_TYPE'])
				)
				{
					$this->showAccessDenied();
					return false;
				}
				$this->arResult['TEMPLATE_NAME'] = $template['NAME'];
				$this->arResult['TEMPLATE_DESC'] = $template['DESCRIPTION'];
				$this->arResult['TEMPLATE_AUTOSTART'] = $template['AUTO_EXECUTE'];
				$this->arResult['TEMPLATE'] = $template['TEMPLATE'];
				$this->arResult['PARAMETERS'] = $template['PARAMETERS'];
				$this->arResult['VARIABLES'] = $template['VARIABLES'];
				$this->arResult['CONSTANTS'] = $template['CONSTANTS'];
			}
			else
			{
				$this->arResult['ID'] = 0;
			}
			if($template["ENTITY"] == Bitrix\Disk\BizProcDocument::className())
			{
				$this->arResult['DOCUMENT_COMPLEX_TYPE'] = \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->arParams['STORAGE_ID']);
				$this->arResult['ENTITY'] = $template['ENTITY'];
			}
			else
			{
				$this->arResult['DOCUMENT_COMPLEX_TYPE'] = \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->arParams['STORAGE_ID']);
				$this->arResult['ENTITY'] = $template['ENTITY'];
			}
		}
		else
		{
			$this->arResult['ENTITY'] = Bitrix\Disk\BizProcDocument::className();
			$this->arResult['DOCUMENT_COMPLEX_TYPE'] = \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->arParams['STORAGE_ID']);
			if(!CBPDocument::canUserOperateDocumentType(
				CBPCanUserOperateOperation::CreateWorkflow,
				$this->getUser()->getID(),
				$this->arResult['DOCUMENT_COMPLEX_TYPE'])
			)
			{
				$this->showAccessDenied();
				return false;
			}

			$this->arResult['TEMPLATE_NAME'] = Loc::getMessage("BIZPROC_WFEDIT_DEFAULT_TITLE");
			$this->arResult['TEMPLATE_DESC'] = '';
			$this->arResult['TEMPLATE_AUTOSTART'] = 1;
			$this->arResult['PARAMETERS'] = array();
			$this->arResult['VARIABLES'] = array();
			$this->arResult['CONSTANTS'] = array();

			if($this->request->getQuery('init') == 'statemachine')
			{
				$this->arResult['TEMPLATE'] = array(
					array(
						'Type' => 'StateMachineWorkflowActivity',
						'Name' => 'Template',
						'Properties' => array(),
						'Children' => array()
					)
				);
			}
			else
			{
				$this->arResult['TEMPLATE'] = array(
					array(
						'Type' => 'SequentialWorkflowActivity',
						'Name' => 'Template',
						'Properties' => array(),
						'Children' => array()
					)
				);
			}
		}

		return true;
	}

	protected function processActionDefault()
	{
		$this->arResult['ACTIVITY_GROUPS'] = array(
			'document' => Loc::getMessage('BIZPROC_WFEDIT_CATEGORY_DOC'),
			'logic' => Loc::getMessage('BIZPROC_WFEDIT_CATEGORY_CONSTR'),
			'interaction' => Loc::getMessage('BIZPROC_WFEDIT_CATEGORY_INTER'),
			'rest' => Loc::getMessage('BIZPROC_WFEDIT_CATEGORY_REST'),
		);

		$runtime = CBPRuntime::getRuntime();
		$documentType = \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->arParams['STORAGE_ID']);
		$this->arResult['ACTIVITIES'] = $runtime->searchActivitiesByType('activity', $documentType);

		foreach ($this->arResult['ACTIVITIES'] as $activity)
		{
			if (!empty($activity['CATEGORY']['OWN_ID']) && !empty($activity['CATEGORY']['OWN_NAME']))
			{
				$this->arResult['ACTIVITY_GROUPS'][$activity['CATEGORY']['OWN_ID']] = $activity['CATEGORY']['OWN_NAME'];
			}
		}
		$this->arResult['ACTIVITY_GROUPS']['other'] = Loc::getMessage("BIZPROC_WFEDIT_CATEGORY_OTHER");

		if($this->arResult['ID'] > 0)
		{
			$this->application->setTitle(Loc::getMessage('BIZPROC_WFEDIT_TITLE_EDIT'));
		}
		else
		{
			$this->application->setTitle(Loc::getMessage('BIZPROC_WFEDIT_TITLE_ADD'));
		}

		$defUserParamsStr = serialize(array('groups' => array()));
		$userParamsStr = CUserOptions::getOption('~bizprocdesigner', 'activity_settings', $defUserParamsStr);
		if (empty($userParamsStr) || !CheckSerializedData($userParamsStr))
			$userParamsStr = $defUserParamsStr;

		$this->arResult['USER_PARAMS'] = unserialize($userParamsStr);
		$this->arResult['BACK_TO_STORAGE'] = $this->getUrlToStorage();

		$this->includeComponentTemplate();
	}

	protected function getUrlToStorage()
	{
		$storage = \Bitrix\Disk\Storage::loadById($this->arParams['STORAGE_ID']);

		return $storage->getProxyType()->getBaseUrlFolderList();
	}

	protected function getUrlToStorageWithOpenedSliderBizproc()
	{
		return (new \Bitrix\Main\Web\Uri($this->getUrlToStorage()))->addParams(['cmd' => 'openSliderBp'])->getLocator();
	}

	protected function processActionSaveAjax()
	{
		if ($this->request->isAjaxRequest())
		{
			$this->request->addFilter(new PostDecodeFilter);
		}

		if($this->request->getQuery('saveuserparams')=='Y')
		{
			$serializeValue = serialize($this->request->getPost('USER_PARAMS'));
			$maxLength = 16777215;//pow(2, 24) - 1; //mysql mediumtext column length
			if (\Bitrix\Main\Text\BinaryString::getLength($serializeValue) > $maxLength)
			{
				$response = "
					<script>
						alert('".Loc::getMessage('BIZPROC_USER_PARAMS_SAVE_ERROR')."');
					</script>
				";
				$this->sendResponse($response);
			}
			CUserOptions::setOption('~bizprocdesigner', 'activity_settings', $serializeValue);
			$this->application->restartBuffer();
			$this->end();
		}

		$fields = array(
			'DOCUMENT_TYPE' => $this->arResult['DOCUMENT_COMPLEX_TYPE'],
			'AUTO_EXECUTE' 	=> $this->request->getPost('workflowTemplateAutostart'),
			'NAME' 			=> $this->request->getPost('workflowTemplateName'),
			'DESCRIPTION' 	=> $this->request->getPost('workflowTemplateDescription'),
			'TEMPLATE' 		=> $this->request->getPost('arWorkflowTemplate'),
			'PARAMETERS'	=> $this->request->getPost('arWorkflowParameters'),
			'VARIABLES' 	=> $this->request->getPost('arWorkflowVariables'),
			'CONSTANTS' 	=> $this->request->getPost('arWorkflowConstants'),
			'USER_ID'		=> intval($this->getUser()->getID()),
			'MODIFIER_USER' => new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser),
		);

		if(!is_array($fields["VARIABLES"]))
		{
			$fields['VARIABLES'] = array();
		}
		if(!is_array($fields["CONSTANTS"]))
		{
			$fields['CONSTANTS'] = array();
		}
		if($this->arResult['TEMPLATE'] != $fields['TEMPLATE'])
		{
			$fields['SYSTEM_CODE'] = '';
		}

		try
		{
			if($this->arResult['ID'] > 0)
			{
				CBPWorkflowTemplateLoader::update($this->arResult['ID'], $fields);
			}
			else
			{
				$this->arResult['ID'] = CBPWorkflowTemplateLoader::add($fields);
			}
		}
		catch (Exception $e)
		{
			$response = "
				<script>
					alert('". Loc::getMessage('BIZPROC_WFEDIT_SAVE_ERROR')."\\n ".preg_replace("#\.\W?#".BX_UTF_PCRE_MODIFIER, ".\\n", CUtil::JSEscape($e->getMessage()))."');
				</script>
			";
			$this->sendResponse($response);
		}

		$response = "
			<script>
				window.location = '".($this->request->getQuery('apply') == 'Y' ?
				str_replace('#ID#', $this->arResult['ID'], $this->arResult['EDIT_PAGE_TEMPLATE']) : CUtil::JSEscape($this->getUrlToStorageWithOpenedSliderBizproc()))."';
			</script>
		";
		$this->sendResponse($response);
	}

	protected function processActionExportTemplate()
	{
		$this->application->restartBuffer();
		if ($this->arResult['ID'] > 0)
		{
			$datum = CBPWorkflowTemplateLoader::exportTemplate($this->arResult['ID']);

			header("HTTP/1.1 200 OK");
			header("Content-Type: application/force-download; name=\"bp-".$this->arResult['ID'].".bpt\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".(function_exists('mb_strlen')? mb_strlen($datum, 'ISO-8859-1') : mb_strlen($datum)));
			header("Content-Disposition: attachment; filename=\"bp-".$this->arResult['ID'].".bpt\"");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Expires: 0");
			header("Pragma: public");

			echo $datum;
		}
		$this->end();
	}

	protected function processActionImportTemplate()
	{
		if($this->request->getPost('import_template')=='Y')
		{
			$res = 0;
			$error = '';
			if (is_uploaded_file($_FILES['import_template_file']['tmp_name']))
			{
				$f = fopen($_FILES['import_template_file']['tmp_name'], "rb");
				$datum = fread($f, filesize($_FILES['import_template_file']['tmp_name']));

				if($this->request->getPost('old_template'))
				{
					$this->arResult['DOCUMENT_COMPLEX_TYPE'] = \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->arParams['STORAGE_ID']);
				}

				fclose($f);
				try
				{
					$res = CBPWorkflowTemplateLoader::importTemplate(
						$this->arResult['ID'],
						$this->arResult['DOCUMENT_COMPLEX_TYPE'],
						$this->request->getPost('import_template_autostart'),
						$this->request->getPost('import_template_name'),
						$this->request->getPost('import_template_description'),
						$datum
					);
				}
				catch (Exception $e)
				{
					$error = preg_replace("#[\r\n]+#", " ", $e->getMessage());
				}
			}
			if(intval($res) <= 0)
			{
				$response = "
					<script>
						alert('".Loc::getMessage('BIZPROC_WFEDIT_IMPORT_ERROR').($error <> '' ? ': '.$error : '' )."');
						window.location = '".str_replace('#ID#', $this->arResult['ID'], $this->arResult['EDIT_PAGE_TEMPLATE'])."';
					</script>
				";
			}
			else
			{
				$response = "
					<script>
						window.location = '".str_replace('#ID#', $res, $this->arResult['EDIT_PAGE_TEMPLATE'])."';
					</script>
				";
			}
			$this->sendResponse($response);
		}
	}
}
