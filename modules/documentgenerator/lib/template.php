<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\DocumentGenerator\Model\FieldTable;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Model\TemplateProviderTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\DocumentGenerator\Model\TemplateUserTable;
use Bitrix\Main\Engine\Response\DataType\ContentUri;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Uri;

/**
 * Class Template
 * @package Bitrix\DocumentGenerator
 * @property-read int ID
 * @property-read string NAME
 * @property-read string REGION
 * @property-read string CODE
 * @property-read string FILE_ID
 * @property-read string BODY_TYPE
 * @property-read string ACTIVE
 * @property-read string MODULE_ID
 * @property-read int NUMERATOR_ID
 * @property-read string WITH_STAMPS
 * @property-read string IS_DELETED
 * @property-read string SORT
 * @property-read string CREATE_TIME
 * @property-read string UPDATE_TIME
 */
final class Template
{
	const MAIN_PROVIDER_PLACEHOLDER = 'SOURCE';
	const DOCUMENT_PROVIDER_PLACEHOLDER = 'DOCUMENT';

	protected $data = [];
	protected $body;
	protected $providers;
	protected $users;
	protected $sourceType;
	protected $fields;

	protected function __construct(array $data)
	{
		$this->data = $data;
		if(isset($data['MODULE_ID']) && !empty($data['MODULE_ID']))
		{
			if(ModuleManager::isModuleInstalled($data['MODULE_ID']))
			{
				Loader::includeModule($data['MODULE_ID']);
			}
		}
	}

	/**
	 * @internal
	 * @param array $data
	 * @return Template
	 */
	public static function loadFromArray(array $data)
	{
		return new static($data);
	}

	/**
	 * @param int $id
	 * @return bool|Template
	 */
	public static function loadById($id)
	{
		if($id > 0)
		{
			$templateData = TemplateTable::getById($id)->fetch();
			if($templateData)
			{
				return new static($templateData);
			}
		}

		return false;
	}

	public function __get($name)
	{
		if(isset($this->data[$name]))
		{
			return $this->data[$name];
		}

		return null;
	}

	/**
	 * @return Body|false
	 */
	public function getBody()
	{
		if($this->body === null)
		{
			$this->body = false;

			if($this->FILE_ID && $this->BODY_TYPE)
			{
				$this->body = new $this->BODY_TYPE(FileTable::getContent($this->FILE_ID));
			}
		}

		return $this->body;
	}

	/**
	 * @return false|int
	 */
	public function getModificationTime()
	{
		return FileTable::getModificationTime($this->FILE_ID);
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			$this->fields = [];
			$this->fields[static::DOCUMENT_PROVIDER_PLACEHOLDER] = [
				'PROVIDER' => DataProvider\Document::class,
				'TEMPLATE_ID' => $this->ID,
				'REQUIRED' => 'Y',
			];
			$placeholders = [];
			$body = $this->getBody();
			if($body)
			{
				$allFields = $fieldNames = [];
				$placeholders = $body->getFieldNames();
				foreach($placeholders as $placeholder)
				{
					$fieldNames[$placeholder] = $placeholder;
					$fieldNames += static::getLevelsFromPlaceholder($placeholder);
				}
				if(!empty($fieldNames))
				{
					$getListResult = FieldTable::getList(['filter' => ['=PLACEHOLDER' => $fieldNames]]);
					while($field = $getListResult->fetch())
					{
						$allFields[$field['PLACEHOLDER']][] = $field;
					}
					foreach($allFields as $placeholder => $placeholderFields)
					{
						$this->fields[$placeholder] = $this->getPriorityField($placeholderFields);
					}
				}
			}
			if($this->sourceType)
			{
				$this->fields[static::MAIN_PROVIDER_PLACEHOLDER] = [
					'PROVIDER' => $this->sourceType,
					'TEMPLATE_ID' => $this->ID,
					'REQUIRED' => 'Y',
				];
				$this->fields[static::DOCUMENT_PROVIDER_PLACEHOLDER]['OPTIONS'] = [
					'PROVIDER' => $this->sourceType,
				];
			}
			$emptyPlaceholders = array_diff($placeholders, array_keys($this->fields));
			if(!empty($emptyPlaceholders))
			{
				$this->fields = array_merge($this->fields, DataProviderManager::getInstance()->getDefaultTemplateFields($this->sourceType, $emptyPlaceholders, ['REGION' => $this->REGION], true, true));
			}
		}

		return $this->fields;
	}

	/**
	 * @param string $placeholder
	 * @return array
	 */
	protected static function getLevelsFromPlaceholder($placeholder)
	{
		$names = [];
		$count = 10;
		while($count-- > 0)
		{
			$parts = explode('.', $placeholder);
			if(count($parts) > 1)
			{
				$name = '';
				foreach($parts as $part)
				{
					if($name)
					{
						$name .= '.';
					}
					$name .= $part;
					$names[$name] = $name;
				}
			}
		}

		return $names;
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	protected function getPriorityField(array $fields)
	{
		$resultField = [];
		$templateField = $providerField = $defaultField = null;

		foreach($fields as $field)
		{
			if($field['TEMPLATE_ID'] === $this->ID)
			{
				$templateField = $field;
				break;
			}
			elseif($field['PROVIDER'] == $this->sourceType)
			{
				$providerField = $field;
			}
			elseif(!$field['TEMPLATE_ID'] && empty($field['PROVIDER']))
			{
				$defaultField = $field;
			}
		}

		if($templateField)
		{
			$resultField = $templateField;
		}
		elseif($providerField)
		{
			if(!empty($providerField['PROVIDER_NAME']) && DataProviderManager::checkProviderName($providerField['PROVIDER_NAME']))
			{
				$providerField['PROVIDER'] = $providerField['PROVIDER_NAME'];
				unset($providerField['PROVIDER_NAME']);
			}
			else
			{
				unset($providerField['PROVIDER']);
			}
			$resultField = $providerField;
		}
		elseif($defaultField)
		{
			$resultField = $defaultField;
		}

		return $resultField;
	}

	/**
	 * @param string $prefix
	 * @return string
	 */
	public function getFileName($prefix = '')
	{
		$name = '';
		if(!empty($prefix))
		{
			$name .= $prefix.' ';
		}
		$name .= $this->NAME;
		if(!$name)
		{
			$name = $this->CODE;
		}
		$body = $this->getBody();
		if($body)
		{
			$name .= '.'.$body->getFileExtension();
		}

		return $name;
	}

	/**
	 * @param bool $combineExtended
	 * @return array
	 */
	public function getDataProviders($combineExtended = false)
	{
		if($this->providers === null)
		{
			$this->providers = [];
			if($this->ID > 0)
			{
				$providers = TemplateProviderTable::getList(['select' => ['PROVIDER'], 'filter' => ['TEMPLATE_ID' => $this->ID]]);
				while($provider = $providers->fetch())
				{
					if($combineExtended === true)
					{
						$provider['PROVIDER'] = TemplateProviderTable::getClassNameFromFilterString($provider['PROVIDER']);
					}
					$this->providers[$provider['PROVIDER']] = $provider['PROVIDER'];
				}
			}
		}

		return $this->providers;
	}

	/**
	 * @return array
	 */
	public function getUsers()
	{
		if($this->users === null)
		{
			$this->users = [];
			if($this->ID > 0)
			{
				$users = TemplateUserTable::getList(['select' => ['ACCESS_CODE'], 'filter' => ['TEMPLATE_ID' => $this->ID]]);
				while($user = $users->fetch())
				{
					$user['ACCESS_CODE'] = TemplateUserTable::removeSocialGroupAccessSuffix($user['ACCESS_CODE']);
					$this->users[$user['ACCESS_CODE']] = $user['ACCESS_CODE'];
				}
			}
		}

		return $this->users;
	}

	/**
	 * @param string $sourceType
	 * @return Template
	 */
	public function setSourceType($sourceType)
	{
		$sourceType = strtolower($sourceType);
		if(DataProviderManager::checkProviderName($sourceType, $this->MODULE_ID))
		{
			$this->sourceType = $sourceType;
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSourceType()
	{
		return $this->sourceType;
	}

	/**
	 * @param bool $absolute
	 * @return Uri
	 */
	public function getDownloadUrl($absolute = false)
	{
		$link = UrlManager::getInstance()->create('documentgenerator.api.template.download', ['id' => $this->ID, 'ts' => $this->getModificationTime()]);
		if($absolute)
		{
			$link = new ContentUri(UrlManager::getInstance()->getHostUrl().$link->getLocator());
		}

		return $link;
	}

	/**
	 * @return bool
	 */
	public function isDeleted()
	{
		return $this->IS_DELETED === 'Y';
	}
}