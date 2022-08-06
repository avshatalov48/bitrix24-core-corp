<?php

namespace Bitrix\Crm\Controller\DocumentGenerator;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\EventResult;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class Base extends Controller
{
	const MODULE_ID = 'crm';
	const FILE_PARAM_NAME = 'file';
	const CONTROLLER_PATH = 'crm.documentgenerator';

	/**
	 * @return array|\Bitrix\Main\Engine\AutoWire\Parameter[]
	 */
	public function getAutoWiredParameters()
	{
		if(DocumentGeneratorManager::getInstance()->isEnabled())
		{
			return $this->getDocumentGeneratorController()->getAutoWiredParameters($this);
		}

		return [];
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		if(DocumentGeneratorManager::getInstance()->isEnabled())
		{
			return array_merge(parent::configureActions(), $this->getDocumentGeneratorController()->configureActions());
		}

		return parent::configureActions();
	}

	/**
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = function()
		{
			if(!DocumentGeneratorManager::getInstance()->isEnabled())
			{
				$this->errorCollection[] = new Error(
					'Module documentgenerator is not installed'
				);

				return new EventResult(EventResult::ERROR, null, null, $this);
			}

			return new EventResult(EventResult::SUCCESS);
		};
		$preFilters[] = new CheckModule();
		if (DocumentGeneratorManager::getInstance()->isEnabled())
		{
			$defaultPostFilters = $this->getDocumentGeneratorController()->getDefaultPreFilters();
			$preFilters = array_merge($preFilters, $defaultPostFilters);
		}

		return $preFilters;
	}

	/**
	 * @return \Bitrix\DocumentGenerator\Controller\Base
	 */
	abstract protected function getDocumentGeneratorController();

	/**
	 * @param string $action
	 * @param array $arguments
	 * @return mixed
	 */
	protected function proxyAction($action, array $arguments = [])
	{
		$controller = $this->getDocumentGeneratorController();
		$controller->setScope($this->getScope());
		/** @var Result $result */
		$result = call_user_func_array([$controller, $action], $arguments);
		$this->errorCollection->add($controller->getErrors());
		if($result === false)
		{
			$result = null;
		}

		return $result;
	}

	/**
	 * @param array $array
	 * @param array $requiredParams
	 * @return array
	 */
	protected function checkArrayRequiredParams(array $array, array $requiredParams)
	{
		$emptyParams = [];

		foreach($requiredParams as $param)
		{
			if(!isset($array[$param]) || empty($array[$param]))
			{
				$emptyParams[] = $param;
			}
		}

		return $emptyParams;
	}

	/**
	 * @param null $fileContent
	 * @param array $options
	 * @return false|int
	 * @throws \Exception
	 */
	protected function uploadFile($fileContent = null, array $options = [])
	{
		$options = array_merge([
			'fileParamName' => null,
			'required' => true,
			'fileName' => null,
			'isTemplate' => false,
		], $options);
		$fileParamName = $options['fileParamName'];
		$required = $options['required'];
		$fileName = $options['fileName'];
		if(!$fileParamName)
		{
			$fileParamName = static::FILE_PARAM_NAME;
		}
		if(!$fileContent)
		{
			$fileContent = $this->request->getFile($fileParamName);
		}
		if(!$fileContent && !$required)
		{
			return null;
		}
		if(!$fileContent)
		{
			$this->errorCollection[] = new Error('Missing file content');
			return false;
		}

		$fileArray = \CRestUtil::saveFile($fileContent);
		if(!$fileArray)
		{
			$this->errorCollection[] = new Error('Could not save file');
			return false;
		}

		if($fileName && is_string($fileName))
		{
			$fileArray['name'] = $fileArray['fileName'] = $fileName;
		}
		$fileArray['isTemplate'] = $options['isTemplate'];
		$saveResult = FileTable::saveFile($fileArray);
		if($saveResult->isSuccess())
		{
			return $saveResult->getId();
		}
		else
		{
			$this->errorCollection->add($saveResult->getErrors());
			return false;
		}
	}
}