<?php

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Engine\CheckScope;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Model\RoleTable;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Intranet\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

abstract class Base extends Controller
{
	const FILE_PARAM_NAME = 'file';
	const ERROR_ACCESS_DENIED = 'DOCGEN_ACCESS_ERROR';

	protected function getDefaultPreFilters()
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new CheckScope();

		if (
			!Driver::getInstance()->getUserPermissions()->canModifySettings()
			&& Loader::includeModule('intranet')
		)
		{
			$preFilters[] = new ActionFilter\IntranetUser();
		}

		return $preFilters;
	}

	/**
	 * @return array|\Bitrix\Main\Engine\AutoWire\Parameter[]
	 */
	public function getAutoWiredParameters(Controller $controller = null)
	{
		return [
			new ExactParameter(
				\Bitrix\DocumentGenerator\Document::class,
				'document',
				function($className, $id) use ($controller) {
					if (!$controller)
					{
						$controller = $this;
					}
					/** @var \Bitrix\DocumentGenerator\Document $className */
					$document = $className::loadById((int)$id);
					if (!$document)
					{
						$controller->addError(
							new Error(
								Loc::getMessage('DOCGEN_CONTROLLER_DOCUMENT_NOT_FOUND_ERROR')
							)
						);
					}

					return $document;
				}
			),
			new ExactParameter(
				\Bitrix\DocumentGenerator\Template::class,
				'template',
				function($className, $id) use ($controller) {
					if (!$controller)
					{
						$controller = $this;
					}
					/** @var \Bitrix\DocumentGenerator\Template $className */
					$template = $className::loadById($id);
					if (!$template)
					{
						$controller->addError(
							new Error(
								Loc::getMessage('DOCGEN_CONTROLLER_TEMPLATE_NOT_FOUND_ERROR')
							)
						);
					}

					return $template;
				}
			),
			new ExactParameter(
				\Bitrix\DocumentGenerator\Template::class,
				'template',
				function($className, $templateId) use ($controller) {
					if (!$controller)
					{
						$controller = $this;
					}
					/** @var \Bitrix\DocumentGenerator\Template $className */
					$template = $className::loadById($templateId);
					if (!$template)
					{
						$controller->addError(
							new Error(
								Loc::getMessage('DOCGEN_CONTROLLER_TEMPLATE_NOT_FOUND_ERROR')
							)
						);
					}

					return $template;
				}
			),
			new ExactParameter(
				\Bitrix\DocumentGenerator\Model\Role::class,
				'role',
				function($className, $id)
				{
					return RoleTable::getById($id)->fetchObject();
				}
			),
			new ExactParameter(
				\Bitrix\Main\Numerator\Numerator::class,
				'numerator',
				function($className, $id)
				{
					/** @var \Bitrix\Main\Numerator\Numerator $className */
					return $className::load($id);
				}
			),
		];
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
			'isTemplate' => false,
		], $options);
		$fileParamName = $options['fileParamName'];
		$required = $options['required'];
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

	/**
	 * @param array $filter
	 * @param array $dateTimeFields
	 */
	protected function prepareDateTimeFieldsForFilter(array &$filter, array $dateTimeFields)
	{
		foreach($filter as $name => $value)
		{
			foreach($dateTimeFields as $field)
			{
				if($this->isCorrectFieldName($name, $field))
				{
					$filter[$name] = \CRestUtil::unConvertDateTime($value);
					break;
				}
			}
		}
	}

	/**
	 * @param $filterName
	 * @param $field
	 * @return bool
	 */
	protected function isCorrectFieldName($filterName, $field)
	{
		static $prefixes = [
			'' => true, '=' => true, '%' => true, '>' => true, '<' => true, '@' => true, '!=' => true,
			'!%' => true, '><' => true, '>=' => true, '<=' => true, '=%' => true, '%=' => true,
			'!><' => true, '!=%' => true, '!%=' => true,
		];
		return isset($prefixes[str_replace($field, '', $filterName)]);

	}
}
