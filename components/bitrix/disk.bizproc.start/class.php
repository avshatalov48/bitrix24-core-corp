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

class CDiskBizprocStartComponent extends BaseComponent implements SidePanelWrappable
{
	const ERROR_COULD_NOT_FIND_MODULE_ID = 'DISK_BSC_22001';
	const ERROR_COULD_NOT_FIND_STORAGE_ID = 'DISK_BSC_22002';
	const ERROR_COULD_NOT_FIND_DOCUMENT_ID = 'DISK_BSC_22003';

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
		$this->arParams['TEMPLATE_ID'] = intval($this->request->getQuery('workflow_template_id'));
		$this->arParams['SET_TITLE'] = $this->arParams['SET_TITLE'] == 'N' ? 'N' : 'Y';
		if (!isset($this->arParams['MODULE_ID']))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('BPATT_NO_MODULE_ID'), self::ERROR_COULD_NOT_FIND_MODULE_ID)));
		}
		if (!isset($this->arParams['STORAGE_ID']))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('BPABS_EMPTY_DOC_TYPE'), self::ERROR_COULD_NOT_FIND_STORAGE_ID)));
		}
		if (!isset($this->arParams['DOCUMENT_ID']))
		{
			$this->errorCollection->add(array(new Error(Loc::getMessage('BPABS_EMPTY_DOC_ID'), self::ERROR_COULD_NOT_FIND_DOCUMENT_ID)));
		}
		if ($this->errorCollection->hasErrors())
		{
			$error = array_shift($this->getErrors());
			throw new ArgumentException($error->getMessage());
		}

		$this->arResult['DOCUMENT_DATA'] = array(
			'DISK' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->arParams['STORAGE_ID']),
				'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($this->arParams['DOCUMENT_ID']),
			),
			'WEBDAV' => array(
				'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->arParams['STORAGE_ID']),
				'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($this->arParams['DOCUMENT_ID']),
			),
		);

		return $this;
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		if (!CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::StartWorkflow,
			$this->getUser()->getID(),
			$this->arResult['DOCUMENT_DATA']['DISK']['DOCUMENT_ID'],
			array()))
		{
			$this->showAccessDenied();
			return false;
		}
		return true;
	}

	protected function processActionDefault()
	{
		$this->getData();
		if($this->arParams['SET_TITLE'] == 'Y')
		{
			$this->application->setTitle(Loc::getMessage('BPABS_TITLE'));
		}
		$this->includeComponentTemplate();
	}

	protected function getData()
	{
		$this->arResult['SHOW_MODE'] = 'SelectWorkflow';
		$this->arResult['TEMPLATES'] = array();
		$this->arResult['PARAMETERS_VALUES'] = array();
		$this->arResult['ERROR_MESSAGE'] = '';

		$runtime = CBPRuntime::getRuntime();
		$runtime->startRuntime();
		$this->arResult['DocumentService'] = $runtime->getService('DocumentService');

		foreach($this->arResult['DOCUMENT_DATA'] as $nameModule => $data)
		{
			$workflowTemplateObject = CBPWorkflowTemplateLoader::getList(
				array(),
				array('DOCUMENT_TYPE' => $data['DOCUMENT_TYPE'], 'ACTIVE' => 'Y'),
				false,
				false,
				array('ID', 'NAME', 'DESCRIPTION', 'MODIFIED', 'USER_ID', 'PARAMETERS')
			);
			while ($workflowTemplate = $workflowTemplateObject->getNext())
			{
				if (!CBPDocument::canUserOperateDocument(
					CBPCanUserOperateOperation::StartWorkflow,
					$this->getUser()->getID(),
					$data['DOCUMENT_ID'],
					array())):
					continue;
				endif;
				if($nameModule == 'DISK')
				{
					$this->arResult['TEMPLATES'][$workflowTemplate['ID']] = $workflowTemplate;
					$this->arResult['TEMPLATES'][$workflowTemplate['ID']]['URL'] =
						htmlspecialcharsex($this->application->getCurPageParam(
							'workflow_template_id='.$workflowTemplate['ID'].'&'.bitrix_sessid_get(),
							array('workflow_template_id', 'sessid')));
				}
				else
				{
					$this->arResult['TEMPLATES_OLD'][$workflowTemplate['ID']] = $workflowTemplate;
					$this->arResult['TEMPLATES_OLD'][$workflowTemplate['ID']]['URL'] =
						htmlspecialcharsex($this->application->getCurPageParam(
							'workflow_template_id='.$workflowTemplate['ID'].'&old=1&'.bitrix_sessid_get(),
							array('workflow_template_id', 'sessid')));
				}

			}
		}

		if ($this->arParams['TEMPLATE_ID'] > 0 && $this->request->getPost('CancelStartParamWorkflow') == ''
			&& (array_key_exists($this->arParams['TEMPLATE_ID'], $this->arResult['TEMPLATES']) || array_key_exists($this->arParams['TEMPLATE_ID'], $this->arResult['TEMPLATES_OLD'])))
		{
			if(array_key_exists($this->arParams['TEMPLATE_ID'], $this->arResult['TEMPLATES']))
			{
				$templates = $this->arResult['TEMPLATES'];
				$documentParameters = $this->arResult['DOCUMENT_DATA']['DISK'];
				$this->arResult['CHECK_TEMPLATE'] = 'DISK';
			}
			else
			{
				$templates = $this->arResult['TEMPLATES_OLD'];
				$documentParameters = $this->arResult['DOCUMENT_DATA']['WEBDAV'];
				$this->arResult['CHECK_TEMPLATE'] = 'WEBDAV';
			}

			$workflowTemplate = $templates[$this->arParams['TEMPLATE_ID']];

			$arWorkflowParameters = array();
			$canStartWorkflow = false;

			if (count($workflowTemplate['PARAMETERS']) <= 0)
			{
				$canStartWorkflow = true;
			}
			elseif ($this->request->isPost() && $this->request->getPost('DoStartParamWorkflow') <> '' && check_bitrix_sessid())
			{
				$errorsTemporary = array();
				$request = $this->request->getPostList()->toArray();

				foreach ($_FILES as $key => $value)
				{
					if (array_key_exists('name', $value))
					{
						if (is_array($value['name']))
						{
							$keys = array_keys($value['name']);
							for ($i = 0, $cnt = count($keys); $i < $cnt; $i++)
							{
								$array = array();
								foreach ($value as $k1 => $v1)
								{
									$array[$k1] = $v1[$keys[$i]];
								}
								$request[$key][] = $array;
							}
						}
						else
						{
							$request[$key] = $value;
						}
					}
				}

				$arWorkflowParameters = CBPWorkflowTemplateLoader::checkWorkflowParameters(
					$workflowTemplate['PARAMETERS'],
					$request,
					$documentParameters['DOCUMENT_TYPE'],
					$errorsTemporary
				);

				if (count($errorsTemporary) > 0)
				{
					$canStartWorkflow = false;
					foreach ($errorsTemporary as $e)
					{
						$this->errorCollection->add(array(new Error($e['message'])));
					}
				}
				else
				{
					$canStartWorkflow = true;
				}
			}

			if ($canStartWorkflow)
			{
				$errorsTemporary = array();

				$workflowId = CBPDocument::startWorkflow(
					$this->arParams['TEMPLATE_ID'],
					$documentParameters['DOCUMENT_ID'],
					array_merge($arWorkflowParameters, array('TargetUser' => 'user_'.intval($this->getUser()->getID()))),
					$errorsTemporary
				);

				if (count($errorsTemporary) > 0)
				{
					$this->arResult['SHOW_MODE'] = 'StartWorkflowError';
					foreach ($errorsTemporary as $e)
					{
						$this->errorCollection->add(array(new Error('['.$e['code'].'] '.$e['message'])));
					}
				}
				else
				{
					$this->arResult['SHOW_MODE'] = 'StartWorkflowSuccess';
					if ($this->arResult['back_url'] <> '')
					{
						LocalRedirect(str_replace('#WF#', $workflowId, $this->request->getQuery('back_url')));
						$this->end(true);
					}
				}
			}
			else
			{
				$doStartParam = ($this->request->isPost() && $this->request->getPost('DoStartParamWorkflow') && check_bitrix_sessid() <> '');
				$keys = array_keys($workflowTemplate['PARAMETERS']);
				foreach ($keys as $key)
				{
					$value = ($doStartParam ? $this->request->getQuery($key) : $workflowTemplate['PARAMETERS'][$key]['Default']);
					if (!is_array($value))
					{
						$this->arResult['PARAMETERS_VALUES'][$key] = CBPHelper::convertParameterValues($value);
					}
					else
					{
						$keys1 = array_keys($value);
						foreach ($keys1 as $key1)
						{
							$this->arResult['PARAMETERS_VALUES'][$key][$key1] = CBPHelper::convertParameterValues($value[$key1]);
						}
					}
				}

				$this->arResult['SHOW_MODE'] = 'WorkflowParameters';
			}
			if ($this->errorCollection->hasErrors())
			{
				$error = array_shift($this->getErrors());
				ShowError($error->getMessage());
			}
		}
		else
		{
			$this->arResult['SHOW_MODE'] = 'SelectWorkflow';
		}
	}
}