<?php

namespace Bitrix\DocumentGenerator\Controller;

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Error;
use Bitrix\DocumentGenerator\Document;
use Bitrix\DocumentGenerator\Template;
use Bitrix\DocumentGenerator\Model\FieldTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class DataProvider extends Base
{
	/**
	 * @param string $provider
	 * @param string $value
	 * @param array $options
	 * @param string $module
	 * @return array|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getProviderFieldsAction($provider, $value = '', array $options = [], $module = '')
	{
		if(!empty($module) && !(ModuleManager::isModuleInstalled($module) && Loader::includeModule($module)))
		{
			$this->errorCollection[] = new Error('cant load module '.$module);
			return null;
		}
		if(DataProviderManager::checkProviderName($provider, $module))
		{
			/** @var \Bitrix\DocumentGenerator\DataProvider $dataProvider */
			$dataProvider = new $provider($value, $options);
			return ['fields' => $this->getProviderFields($dataProvider)];
		}
		else
		{
			$this->errorCollection[] = new Error($provider.' is not a DataProvider');
			return null;
		}
	}

	/**
	 * @param $provider
	 * @param string $value
	 * @param array $options
	 * @param string $module
	 */
	public function isPrintableAction($provider, $value = '', array $options = [], $module = '')
	{
		if (
			!empty($module)
			&& !(ModuleManager::isModuleInstalled($module)
			&& Loader::includeModule($module))
		)
		{
			$this->errorCollection[] = new Error('cant load module '.$module);
			return null;
		}

		if (!DataProviderManager::checkProviderName($provider, $module))
		{
			$this->errorCollection[] = new Error($provider.' is not a DataProvider');
			return null;
		}

		/** @var \Bitrix\DocumentGenerator\DataProvider $dataProvider */
		$dataProvider = new $provider($value, $options);
		$isPrintableResult = $dataProvider->isPrintable();
		if (!$isPrintableResult->isSuccess())
		{
			$this->errorCollection->add($isPrintableResult->getErrors());
			return null;
		}
	}

	/**
	 * @param \Bitrix\DocumentGenerator\DataProvider $dataProvider
	 * @return array
	 */
	protected function getProviderFields(\Bitrix\DocumentGenerator\DataProvider $dataProvider)
	{
		$data = [];
		$fields = $dataProvider->getFields();
		foreach($fields as $placeholder => $field)
		{
			$option = [
				'placeholder' => $placeholder,
				'title' => $field['TITLE'],
			];
			if(isset($field['PROVIDER']))
			{
				$option['provider'] = $field['PROVIDER'];
			}
			if(isset($field['OPTIONS']))
			{
				$option['options'] = $field['OPTIONS'];
			}
			$data[] = $option;
		}

		return $data;
	}

	/**
	 * @param string $provider
	 * @param mixed $value
	 * @return array
	 */
	public function getPlaceholderAction($provider, $value)
	{
		$originalValue = $value;
		$aliases = [$value];
		$value = str_replace(Document::THIS_PLACEHOLDER.'.'.Template::MAIN_PROVIDER_PLACEHOLDER.'.', '', $value);
		$value = str_replace(Document::THIS_PLACEHOLDER.'.', '', $value);
		$providers = explode('.', $value);
		while(count($providers) > 1)
		{
			$aliases[] = implode('.', $providers);
			array_shift($providers);
		}
		$fieldsQuery = FieldTable::getList([
			'filter' => [
				'@VALUE' => $aliases
			]
		]);
		$result = [];
		while($field = $fieldsQuery->fetch())
		{
			if(isset($result[$field['PLACEHOLDER']]))
			{
				if($field['PROVIDER'] == mb_strtolower($provider))
				{
					$result[$field['PLACEHOLDER']] = $field;
				}
			}
			else
			{
				$result[$field['PLACEHOLDER']] = $field;
			}
		}
		$selectedPlaceholder = false;
		foreach($result as $placeholder => $field)
		{
			if($field['VALUE'] == $value || $field['VALUE'] == $originalValue)
			{
				$selectedPlaceholder = $placeholder;
				break;
			}
			if($selectedPlaceholder)
			{
				if(mb_strlen($result[$selectedPlaceholder]['VALUE']) < mb_strlen($field['VALUE']))
				{
					$selectedPlaceholder = $placeholder;
				}
			}
		}
		if($selectedPlaceholder)
		{
			$result[$selectedPlaceholder]['SELECTED'] = 'y';
		}
		$placeholder = DataProviderManager::getInstance()->valueToPlaceholder($value);
		if(!isset($result[$placeholder]))
		{
			$field = ['PLACEHOLDER' => $placeholder];
			if(!$selectedPlaceholder)
			{
				$field['SELECTED'] = 'y';
			}
			$result[] = $field;
		}

		return $result;
	}
}
