<?php

namespace Bitrix\Crm\Controller\DocumentGenerator;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\Main\Engine\Binder;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\EventResult;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

abstract class Base extends Controller
{
	const MODULE_ID = 'crm';
	const FILE_PARAM_NAME = 'file';
	const CONTROLLER_PATH = 'crm.documentgenerator';

	protected function init()
	{
		parent::init();

		Binder::registerParameterDependsOnName(
			'\Bitrix\DocumentGenerator\Document',
			function($className, $id)
			{
				/** @var \Bitrix\DocumentGenerator\Document $className */
				return $className::loadById($id);
			},
			function()
			{
				return 'id';
			}
		);

		Binder::registerParameterDependsOnName(
			'\Bitrix\DocumentGenerator\Template',
			function($className, $id)
			{
				/** @var \Bitrix\DocumentGenerator\Template $className */
				return $className::loadById($id);
			},
			function()
			{
				return 'id';
			}
		);

		Binder::registerParameterDependsOnName(
			\Bitrix\Main\Numerator\Numerator::class,
			function($className, $id)
			{
				/** @var \Bitrix\Main\Numerator\Numerator $className */
				return $className::load($id);
			}, function()
			{
				return 'id';
			}
		);
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
		};
		$preFilters[] = new CheckModule();

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
	 * @param null $fileParamName
	 * @param bool $required
	 * @return false|int
	 * @throws \Exception
	 */
	protected function uploadFile($fileContent = null, $fileParamName = null, $required = true)
	{
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