<?php

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Engine\CheckScope;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\Main\Engine\Binder;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;

abstract class Base extends Controller
{
	const FILE_PARAM_NAME = 'file';
	const ERROR_ACCESS_DENIED = 'DOCGEN_ACCESS_ERROR';

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new CheckScope();

		return $preFilters;
	}

	protected function init()
	{
		parent::init();

		Binder::registerParameterDependsOnName(
			\Bitrix\DocumentGenerator\Document::class,
			function($className, $id)
			{
				/** @var \Bitrix\DocumentGenerator\Document $className */
				return $className::loadById($id);
			}, function()
			{
				return 'id';
			}
		);

		Binder::registerParameterDependsOnName(
			\Bitrix\DocumentGenerator\Template::class,
			function($className, $id)
			{
				/** @var \Bitrix\DocumentGenerator\Template $className */
				return $className::loadById($id);
			}, function()
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