<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\DocumentGenerator\Body\Docx;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\Integration\TransformerManager;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\DocumentGenerator\Model\ExternalLinkTable;
use Bitrix\DocumentGenerator\Model\FileTable;
use Bitrix\DocumentGenerator\Storage\Disk;
use Bitrix\Main\Engine\Response\DataType\ContentUri;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Numerator\Numerator;
use Bitrix\Main\Result;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;

/**
 * Class Document
 * @package Bitrix\DocumentGenerator
 * @property-read int ID
 * @property-read int FILE_ID
 * @property-read int IMAGE_ID
 * @property-read int PDF_ID
 */
final class Document
{
	const THIS_PLACEHOLDER = 'this';
	const STAMPS_ENABLED_PLACEHOLDER = 'stampsEnabled';
	const IMAGE = 'jpg';
	const PDF = 'pdf';

	const ERROR_NO_TRANSFORMER_MODULE = 'ERROR_NO_TRANSFORMER_MODULE';
	const ERROR_TRANSFORMATION = 'ERROR_TRANSFORMATION';

	/** @var array Current field descriptions */
	protected $fields = [];
	/** @var array Field description */
	protected $externalFields = [];
	/** @var Body|null  */
	protected $body;
	protected $values = [];
	protected $entities = [];
	protected $result;
	/** @var Template */
	protected $template;
	protected $fieldNames = [];
	protected $data = [];
	protected $transformer;
	protected $externalValues = [];
	protected $selectFields = [];
	protected $isCheckAccess = false;

	/**
	 * Document constructor.
	 * @param Body $body
	 * @param array $fields
	 * @param array $data
	 * @param mixed $value
	 */
	private function __construct(Body $body, array $fields = [], array $data = [], $value = null)
	{
		$this->body = $body;
		$this->fields = $fields;
		$this->data = $data;
		$this->result = new Result();
		$this->values = [
			Template::DOCUMENT_PROVIDER_PLACEHOLDER => $this,
		];
		if($value)
		{
			$this->values[Template::MAIN_PROVIDER_PLACEHOLDER] = $value;
		}
	}

	/**
	 * @param Template $template
	 * @param mixed $value
	 * @param array $data
	 * @return Document|false
	 */
	public static function createByTemplate(Template $template, $value, array $data = [])
	{
		$fields = $template->getFields();
		$body = $template->getBody();
		if(!$body && $data['FILE_ID'] > 0)
		{
			$body = new Docx(FileTable::getContent($data['FILE_ID']));
		}
		if(!$body)
		{
			return false;
		}

		$document = new static($body, $fields, $data, $value);
		$document->setTemplate($template);
		if($template->WITH_STAMPS === 'Y')
		{
			$document->enableStamps(true);
		}

		return $document;
	}

	/**
	 * @param $documentId
	 * @return static|false
	 */
	public static function loadById($documentId)
	{
		if($documentId > 0)
		{
			$documentData = DocumentTable::getById($documentId)->fetch();
			if($documentData)
			{
				$template = Template::loadById($documentData['TEMPLATE_ID']);
				if($template)
				{
					$template->setSourceType($documentData['PROVIDER']);
					$document = static::createByTemplate($template, $documentData['VALUE'], $documentData);
				}
				else
				{
					$body = new Docx(FileTable::getContent($documentData['FILE_ID']));
					$document = new static($body, [], $documentData, $documentData['VALUE']);
				}
				if(is_array($documentData['VALUES']))
				{
					$document->setValues($documentData['VALUES']);
				}

				return $document;
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
	 * @param int $pdfId
	 * @return Document
	 */
	public function setPdfId($pdfId)
	{
		$this->data['PDF_ID'] = (int)$pdfId;
		return $this;
	}

	/**
	 * @param int $imageId
	 * @return Document
	 */
	public function setImageId($imageId)
	{
		$this->data['IMAGE_ID'] = (int)$imageId;
		return $this;
	}

	/**
	 * Add new values or rewrite old ones, but does not clear all the list.
	 *
	 * @param array $values
	 * @return $this
	 */
	public function setValues(array $values)
	{
		foreach($values as $placeholder => $value)
		{
			if($placeholder === Template::MAIN_PROVIDER_PLACEHOLDER)
			{
				$this->values[$placeholder] = $value;
			}
			else
			{
				$this->externalValues[$placeholder] = $value;
			}
		}

		// rewrite values
		if(isset($this->fields[Template::MAIN_PROVIDER_PLACEHOLDER]))
		{
			$this->fields[Template::MAIN_PROVIDER_PLACEHOLDER]['OPTIONS']['VALUES'] = $this->getExternalValues();
		}

		return $this;
	}

	/**
	 * Add new fields or rewrite old ones (except 'SOURCE' and 'DOCUMENT'), but does not clear all the list.
	 *
	 * @param array $fields
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		foreach($fields as $name => $field)
		{
			// do not let change these fields
			if($name == Template::DOCUMENT_PROVIDER_PLACEHOLDER || $name == Template::MAIN_PROVIDER_PLACEHOLDER)
			{
				continue;
			}
			$this->externalFields[$name] = $field;
		}

		return $this;
	}

	/**
	 * If $requiredOnly is true returns a list of required with empty values.
	 * If false - returns a list of not found placeholders without a value.
	 *
	 * @param bool $requiredOnly
	 * @return array
	 */
	public function checkFields($requiredOnly = true)
	{
		$requiredFields = [];

		if($this->result->isSuccess())
		{
			$this->resolveProviders();
		}

		$fieldNames = $this->getFieldNames();

		if($this->result->isSuccess())
		{
			$values = $this->getValues($fieldNames);
			foreach($fieldNames as $placeholder)
			{
				if(isset($this->fields[$placeholder]) && isset($this->fields[$placeholder]['REQUIRED']) && $this->fields[$placeholder]['REQUIRED'] == 'Y' && empty($values[$placeholder]))
				{
					$requiredFields[$placeholder] = $this->fields[$placeholder];
				}
				elseif(empty($values[$placeholder]) && !isset($this->getExternalValues()[$placeholder]) && !$requiredOnly)
				{
					$requiredFields[$placeholder] = [];
				}
			}

			foreach($this->selectFields as $placeholder => $field)
			{
				if($field['VALUE'] && is_array($field['VALUE']) && DataProviderManager::getInstance()->getValueFromList($field['VALUE']) == $field['VALUE'])
				{
					$requiredFields[$placeholder] = $field;
				}
			}
		}

		return $requiredFields;
	}

	/**
	 * @return Template|null
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param bool $sendToTransformation
	 * @param bool $skipTransformationError
	 * @return Result
	 */
	public function getFile($sendToTransformation = true, $skipTransformationError = false)
	{
		if(!$this->result->isSuccess())
		{
			return $this->result;
		}
		if(!$this->ID)
		{
			$this->process()->save();
		}
		if($this->result->isSuccess())
		{
			$data = [];
			$provider = $this->getProvider();
			if($provider)
			{
				$data = $provider->getAdditionalDocumentInfo($this);
			}
			$data = array_merge($data, [
				'downloadUrl' => $this->getDownloadUrl(),
				'publicUrl' => $this->getPublicUrl(),
				'title' => $this->getTitle(),
				'number' => $this->getNumber(),
				'id' => $this->ID,
				'createTime' => $this->getCreateTime(),
				'updateTime' => $this->getUpdateTime(),
				'stampsEnabled' => $this->isStampsEnabled(),
				'isTransformationError' => false,
				'value' => $this->getValue(Template::MAIN_PROVIDER_PLACEHOLDER),
				'values' => $this->getExternalValues(),
			]);
			$template = $this->getTemplate();
			if($template)
			{
				$data['templateId'] = $template->ID;
			}
			$provider = $this->getProvider();
			if($provider)
			{
				$data['provider'] = get_class($provider);
			}
			if($sendToTransformation)
			{
				if(!$this->PDF_ID || !$this->IMAGE_ID)
				{
					$transformResult = $this->transform();
					if($transformResult->isSuccess())
					{
						$data['isTransformationError'] = false;
					}
					else
					{
						$data['isTransformationError'] = true;
						if(!$skipTransformationError)
						{
							$this->result->addErrors($transformResult->getErrors());
						}
					}
				}
			}
			$pullTag = $this->getPullTag();
			if($pullTag)
			{
				$data['pullTag'] = $pullTag;
			}
			if($this->IMAGE_ID > 0)
			{
				$data['imageUrl'] = $this->getImageUrl();
			}
			if($this->PDF_ID > 0)
			{
				$data['pdfUrl'] = $this->getPdfUrl();
				$data['printUrl'] = $this->getPrintUrl();
				$data['emailDiskFile'] = $this->getEmailDiskFile();
			}
			else
			{
				$data['emailDiskFile'] = $this->getEmailDiskFile(true);
			}
			$this->result->setData($data);
		}
		else
		{
			$this->result->setData([]);
		}

		return $this->result;
	}

	/**
	 * @param array $values
	 * @param bool $sendToTransformation
	 * @param bool $skipTransformationError
	 * @return Result
	 */
	public function update(array $values, $sendToTransformation = true, $skipTransformationError = false)
	{
		if($this->ID > 0)
		{
			$this->values = [
				Template::MAIN_PROVIDER_PLACEHOLDER => $this->values[Template::MAIN_PROVIDER_PLACEHOLDER],
				Template::DOCUMENT_PROVIDER_PLACEHOLDER => $this->values[Template::DOCUMENT_PROVIDER_PLACEHOLDER],
			];
			$this->selectFields = [];
			return $this->setValues($values)->process()->save()->getFile($sendToTransformation, $skipTransformationError);
		}
		else
		{
			$this->result->addError(new Error('Cant update not saved document'));
		}

		return $this->result;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		if(isset($this->externalValues['DocumentTitle']))
		{
			$title = $this->externalValues['DocumentTitle'];
		}
		elseif(isset($this->data['TITLE']))
		{
			$title = $this->data['TITLE'];
		}
		else
		{
			$title = '';
			if($this->template)
			{
				$title .= $this->template->NAME;
			}
			$title .= ' '.ltrim($this->getNumber());
			$this->data['TITLE'] = $title;
		}

		return $title;
	}

	/**
	 * @param bool $preview
	 * @return string
	 */
	public function getNumber($preview = true)
	{
		if(isset($this->externalValues['DocumentNumber']))
		{
			$number = $this->externalValues['DocumentNumber'];
		}
		elseif(isset($this->data['NUMBER']))
		{
			$number = $this->data['NUMBER'];
		}
		else
		{
			$number = '';
			if($this->template)
			{
				$numerator = Numerator::load($this->template->NUMERATOR_ID, $this->getProvider());
				if(!$numerator)
				{
					$numerator = Driver::getInstance()->getDefaultNumerator($this->getProvider());
				}
				if($numerator)
				{
					if($preview === false)
					{
						$number = $numerator->getNext();
						$this->data['NUMBER'] = $number;
					}
					else
					{
						$number = $numerator->previewNextNumber();
					}
				}
			}
			if(!$number)
			{
				$this->result->addError(new Error('Error getting next number'));
			}
		}

		return $number;
	}

	/**
	 * @return DateTime
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getCreateTime()
	{
		if(!isset($this->data['CREATE_TIME']) || empty($this->data['CREATE_TIME']))
		{
			$this->data['CREATE_TIME'] = new DateTime();
		}

		return $this->data['CREATE_TIME'];
	}

	/**
	 * @return DateTime
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getUpdateTime()
	{
		if(!isset($this->data['UPDATE_TIME']) || empty($this->data['UPDATE_TIME']))
		{
			$this->data['UPDATE_TIME'] = new DateTime();
		}

		return $this->data['UPDATE_TIME'];
	}

	/**
	 * @return DataProvider|Nameable|false
	 */
	public function getProvider()
	{
		if(isset($this->fields[Template::MAIN_PROVIDER_PLACEHOLDER]))
		{
			$mainField = $this->fields[Template::MAIN_PROVIDER_PLACEHOLDER];
			return DataProviderManager::getInstance()->createDataProvider($mainField, $this->getValue(Template::MAIN_PROVIDER_PLACEHOLDER));
		}
		elseif($this->data['PROVIDER'] && $this->data['VALUE'])
		{
			return DataProviderManager::getInstance()->getDataProvider($this->data['PROVIDER'], $this->data['VALUE']);
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param bool $status
	 * @return $this
	 */
	public function enableStamps($status = true)
	{
		$this->setValues([static::STAMPS_ENABLED_PLACEHOLDER => ($status === true)]);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isStampsEnabled()
	{
		return ($this->getValue(static::STAMPS_ENABLED_PLACEHOLDER) === true);
	}

	/**
	 * Returns array with all placeholders, their field descriptions and actual values.
	 *
	 * @param array $fieldNames
	 * @param bool $isConvertValuesToString
	 * @param bool $groupsAsArrays
	 * @return array
	 */
	public function getFields(array $fieldNames = [], $isConvertValuesToString = false, $groupsAsArrays = false)
	{
		DataProviderManager::getInstance()->setContext(Context::createFromDocument($this));
		Loc::loadLanguageFile(__FILE__);
		$this->resolveProviders();
		$fields = [];
		$fields['DocumentTitle'] = [
			'TITLE' => Loc::getMessage('DOCUMENT_TITLE_FIELD_NAME'),
			'VALUE' => $this->getTitle(),
			'GROUP' => [
				$this->getFieldGroup(''),
				'',
			],
			'CHAIN' => 'this.DOCUMENT.DOCUMENT_TITLE',
		];

		$default = !($this->ID > 0);
		if(empty($fieldNames))
		{
			$fieldNames = $this->getFieldNames();
		}
		$externalValues = $this->getExternalValues(true);
		foreach($fieldNames as $placeholder)
		{
			$this->getValue($placeholder);
			$defaultValue = $value = $this->values[$placeholder];
			if(!$default)
			{
				if(isset($externalValues[$placeholder]))
				{
					$value = $externalValues[$placeholder];
				}
			}
			if($value instanceof ArrayDataProvider)
			{
				continue;
			}
			if(is_array($value))
			{
				$value = '';
			}
			$valueParts = explode('.', $value);
			if($valueParts[0] && in_array($valueParts[0], $this->fieldNames))
			{
				continue;
			}
			$field = [
				'VALUE' => '',
			];
			if(isset($this->fields[$placeholder]))
			{
				$field = $this->fields[$placeholder];
				$field['CHAIN'] = $field['VALUE'];
			}
			$field['VALUE'] = $this->normalizeValue($value, $isConvertValuesToString);
			$field['DEFAULT'] = $this->normalizeValue($defaultValue, $isConvertValuesToString);
			$fields[$placeholder] = $field;
		}
		foreach($this->selectFields as $placeholder => $field)
		{
			if(is_array($field['VALUE']) && empty($field['VALUE']))
			{
				continue;
			}
			if(empty($field['GROUP']))
			{
				$field['GROUP'] = $this->getFieldGroup('this.SOURCE.'.$placeholder.'.popMe');
			}
			$fields[$placeholder] = $field;
		}
		foreach($fields as &$field)
		{
			if(is_array($field['GROUP']))
			{
				array_pop($field['GROUP']);
			}
			if(!$groupsAsArrays && is_array($field['GROUP']))
			{
				$field['GROUP'] = array_pop($field['GROUP']);
			}
		}

		return $fields;
	}

	/**
	 * @param mixed $value
	 * @param bool $isConvertToString
	 * @return string
	 */
	protected function normalizeValue($value, $isConvertToString = false)
	{
		$result = $value;

		if(is_array($value) || is_bool($value))
		{
			$result = '';
		}
		elseif($isConvertToString && $value instanceof Value)
		{
			$result = $value->getValue();
			if(is_object($result) || is_array($result) || is_bool($result))
			{
				$result = $value->toString();
			}
		}

		return $result;
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected function getFieldGroup($value)
	{
		$group = Loc::getMessage('DOCUMENT_GROUP_NAME');

		if(empty($value) || strpos($value, 'this.SOURCE.') !== 0)
		{
			return $group;
		}

		$value = str_replace('this.SOURCE.', '', $value);
		if(empty($value))
		{
			return $group;
		}

		$valueParts = explode('.', $value);
		array_pop($valueParts);
		$providerName = implode('.', $valueParts);
		if(empty($providerName))
		{
			return $this->getProvider()->getLangName();
		}

		$field = DataProviderManager::getInstance()->getProviderField($this->getProvider(), $providerName);
		if(is_array($field) && isset($field['TITLE']))
		{
			return $field['TITLE'];
		}

		return $group;
	}

	/**
	 * Process document and returns Result.
	 *
	 * @return $this
	 */
	protected function process()
	{
		// here we get actual number
		$this->getNumber(false);
		EventManager::getInstance()->send(new Event(Driver::MODULE_ID, 'onBeforeProcessDocument', ['document' => $this]));
		if(!$this->template)
		{
			$this->result->addError(new Error('Cant process document without template'));
			return $this;
		}
		if($this->template->isDeleted())
		{
			$this->result->addError(new Error('Cant process document on deleted template'));
			return $this;
		}
		if(!$this->template->getSourceType())
		{
			$this->result->addError(new Error('Cant process document on template without sourceType'));
			return $this;
		}
		DataProviderManager::getInstance()->setContext(Context::createFromDocument($this));
		$requiredFields = $this->checkFields();
		foreach($requiredFields as $placeholder => $field)
		{
			$this->result->addError(new Error('No value for required placeholder '.$placeholder));
		}
		if($this->result->isSuccess())
		{
			$values = $this->getValues($this->getFieldNames());
			if(!$this->isStampsEnabled())
			{
				foreach($this->fields as $placeholder => $field)
				{
					if(
						isset($field['TYPE']) &&
						$field['TYPE'] === DataProvider::FIELD_TYPE_STAMP
					)
					{
						$values[$placeholder] = ' ';
					}
				}
			}
			$bodyResult = $this->body->setValues($values)->setFields($this->fields)->process();
			if($bodyResult->isSuccess())
			{
				$resultData = ['BODY' => $this->body];
				$this->result->setData($resultData);
			}
			else
			{
				$this->result = $bodyResult;
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	protected function save()
	{
		if($this->result->isSuccess())
		{
			$resultData = $this->result->getData();
			$saveResult = $this->body->save($this->getFileName());
			if(!$saveResult->isSuccess())
			{
				$this->result->addErrors($saveResult->getErrors());
			}
			else
			{
				$data = [
					'TEMPLATE_ID' => $this->template->ID,
					'VALUE' => $this->getValue(Template::MAIN_PROVIDER_PLACEHOLDER),
					'FILE_ID' => $saveResult->getId(),
					'VALUES' => $this->getExternalValues(true),
					'PROVIDER' => $this->template->getSourceType(),
					'IMAGE_ID' => null,
					'PDF_ID' => null,
					'UPDATE_TIME' => new DateTime(),
					'TITLE' => $this->getTitle(),
					'NUMBER' => $this->getNumber(false),
				];
				if($this->ID > 0)
				{
					$data['UPDATED_BY'] = Driver::getInstance()->getUserId();
					$result = DocumentTable::update($this->ID, $data);
					$eventName = 'onUpdateDocument';
				}
				else
				{
					$data['CREATED_BY'] = Driver::getInstance()->getUserId();
					$result = DocumentTable::add($data);
					$eventName = 'onCreateDocument';
				}
				if($result->isSuccess())
				{
					$data['ID'] = $result->getId();
					$this->data = $data;
					$resultData['DOCUMENT_ID'] = $result->getId();
					if($eventName)
					{
						EventManager::getInstance()->send(new Event(Driver::MODULE_ID, $eventName, ['document' => $this]));
					}
				}
				else
				{
					$this->result->addErrors($result->getErrors());
				}
			}
			$this->result->setData($resultData);
		}

		return $this;
	}

	protected function actualizeFields()
	{
		$provider = $this->getProvider();
		if(!$provider)
		{
			return;
		}
		$placeholders = array_keys($this->fields);
		$fields = DataProviderManager::getInstance()->getProviderFields($provider, $placeholders, true);
		foreach($fields as $field)
		{
			array_unshift($field['GROUP'], Loc::getMessage('DOCUMENT_GROUP_NAME'));
			$placeholder = DataProviderManager::getInstance()->valueToPlaceholder($field['VALUE']);
			unset($field['VALUE']);
			if(!isset($this->fields[$placeholder]))
			{
				$this->fields[$placeholder] = [];
			}

			$this->fields[$placeholder] = array_merge($this->fields[$placeholder], $field);
		}
		foreach($this->externalFields as $placeholder => $field)
		{
			$this->fields[$placeholder] = $field;
		}
	}

	/**
	 * Link entities by their names and values.
	 */
	protected function resolveProviders()
	{
		$this->actualizeFields();
		foreach($this->fields as $name => $field)
		{
			$this->resolveProvider($field, $name);
		}
	}

	/**
	 * @param array $field Field description.
	 * @param string $name
	 */
	protected function resolveProvider(array $field, $name)
	{
		if(!$field['PROVIDER'])
		{
			return;
		}
		if(isset($field['VALUE']))
		{
			$this->values[$name] = $field['VALUE'];
		}
		$parentDataProvider = null;
		if($name !== Template::MAIN_PROVIDER_PLACEHOLDER && $name !== Template::DOCUMENT_PROVIDER_PLACEHOLDER)
		{
			$parentDataProvider = $this->getProvider();
			if(!$parentDataProvider)
			{
				$parentDataProvider = null;
			}
		}
		$value = $this->getValue($name);
		$dataProvider = DataProviderManager::getInstance()->createDataProvider($field, $value, $parentDataProvider);
		if($dataProvider && $dataProvider->isLoaded())
		{
			if($this->isCheckAccess && !DataProviderManager::getInstance()->checkDataProviderAccess($dataProvider))
			{
				$this->result->addError(new Error('Access denied to provider '.$field['PROVIDER'].' for placeholder '.$name));
				return;
			}
			if($dataProvider instanceof ArrayDataProvider && $dataProvider->getItemKey())
			{
				$this->fieldNames[$name] = $name;
			}
			$providerFields = $dataProvider->getFields();
			foreach($providerFields as $placeholder => $providerField)
			{
				$fullName = $name.'.'.$placeholder;
				if(!isset($this->fields[$fullName]))
				{
					$this->fields[$fullName] = [];
				}

				$providerValue = $dataProvider->getValue($placeholder);
				if($providerValue instanceof ArrayDataProvider)
				{
					// here we add inner item of the ArrayDataProvider to the fields.
					$this->fields[$placeholder] = [
						'VALUE' => static::THIS_PLACEHOLDER.'.'.$fullName,
					];
					$this->fieldNames[$placeholder] = $placeholder;
				}

				$this->fields[$fullName] = array_merge($this->fields[$fullName], ['VALUE' => $providerValue]);
			}
			if(isset($this->externalValues[$name]))
			{
				$this->values[$name] = $dataProvider;
				unset($this->externalValues[$name]);
			}
		}
		else
		{
			$this->result->addError(new Error('Cant resolve provider '.$field['PROVIDER'].' for placeholder '.$name));
		}
	}

	/**
	 * Get values for $fields.
	 *
	 * @param array $fieldNames
	 * @return array
	 */
	protected function getValues(array $fieldNames)
	{
		$values = [];
		foreach($fieldNames as $fieldName)
		{
			$values[$fieldName] = $this->normalizeValue($this->getValue($fieldName));
		}

		return $values;
	}

	/**
	 * Returns value by its $name.
	 *
	 * @param string $name
	 * @return array|string
	 */
	protected function getValue($name)
	{
		if(isset($this->values[$name]))
		{
			$value = $this->values[$name];
		}
		elseif(isset($this->fields[$name]['VALUE']))
		{
			$value = $this->fields[$name]['VALUE'];
		}
		else
		{
			$value = $this->getProviderValue($name);
		}

		$value = $this->resolveValue($value);

		if($value && $this->fields[$name]['PROVIDER'] && isset($this->fields[$name]['PROVIDER_NAME']))
		{
			/** @var DataProvider $dataProvider */
			$dataProvider = DataProviderManager::getInstance()->createDataProvider($this->fields[$name], $value);
			if($dataProvider && $dataProvider->isLoaded())
			{
				if($this->isCheckAccess && !DataProviderManager::getInstance()->checkDataProviderAccess($dataProvider))
				{
					$value = null;
				}
				else
				{
					$value = $dataProvider->getValue($this->fields[$name]['PROVIDER_NAME']);
				}
			}
		}

		// save found calculated value.
		$this->values[$name] = $value;

		// if this value has been overwritten - use it.
		$externalValues = $this->getExternalValues();
		if(isset($externalValues[$name]) && $externalValues[$name] != $this->values[$name] && $externalValues[$name] != htmlspecialcharsbx($this->values[$name]))
		{
			$value = $externalValues[$name];
			$value = $this->resolveValue($value);
			if(isset($this->fields[$name]))
			{
				$value = DataProviderManager::getInstance()->prepareValue($value, $this->fields[$name]);
			}
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function resolveValue($value)
	{
		if(is_string($value))
		{
			$valueNameParts = explode('.', $value);
			if(count($valueNameParts) > 1 && $valueNameParts[0] === static::THIS_PLACEHOLDER)
			{
				array_shift($valueNameParts);
				$valueName = implode('.', $valueNameParts);
				$value = $this->getValue($valueName);
				// next code is needed when we have a placeholder that points to some item value from ArrayDataProvider.
				if($value === $valueName)
				{
					$value = static::THIS_PLACEHOLDER.'.'.$valueName;
				}
			}
		}

		return $value;
	}

	/**
	 * This method resolves entities from $name.
	 * For example, 'basket.price' is a placeholder.
	 * First we need to get value for 'basket', and if it is a dataProvider - we can get 'price' value from it
	 * $name may consist from infinite number of providers (provider1.provider2.provider3.provider4...name).
	 * Move from left to right assuming that each next value is a provider.
	 *
	 * @param string $name
	 * @param DataProvider|null $dataProvider
	 * @param string $fullChain
	 * @return string
	 */
	protected function getProviderValue($name, DataProvider $dataProvider = null, $fullChain = '')
	{
		$value = '';

		// if not a string - there is no provider chain.
		if(!is_string($name))
		{
			return $value;
		}
		$nameParts = explode('.', $name);
		if(count($nameParts) > 1)
		{
			// here we move from left to right.
			$providerName = $nameParts[0];
			if($dataProvider === null)
			{
				// if it is the first iteration - get value from $this.
				$value = $this->getValue($providerName);
			}
			else
			{
				$value = $dataProvider->getValue($providerName);
			}
			// here we handle multiple values for inner providers
			if(is_array($value) && $dataProvider)
			{
				$value = $this->handleMultipleProviderValue($value, $dataProvider, $fullChain, $providerName);
				if(is_array($value))
				{
					return $value;
				}
				else
				{
					// initialize child data provider manually
					$value = DataProviderManager::getInstance()->createDataProvider($dataProvider->getFields()[$providerName], $value, $dataProvider, $providerName);
				}
			}
			array_shift($nameParts);
			// combine valueName from all parts but first
			$valueName = implode('.', $nameParts);
			if($value instanceof ArrayDataProvider)
			{
				// if current value is an ArrayDataProvider and $valueName doesn't point to ArrayDataProvider outer field
				// then we assume that this $name points to the field of ArrayDataProvider item - thus
				// we need to return $name as it is.
				// In Body there will be a cycle with this $name as placeholder.
				$providerFields = $value->getFields();
				if(!in_array($valueName, $providerFields, true) && $valueName != ArrayDataProvider::NUMBER_PLACEHOLDER)
				{
					return $name;
				}
			}
			// if there is PROVIDER in field description then we need to initialize new provider of this type on $value.
			if(isset($this->fields[$providerName]) && isset($this->fields[$providerName]['PROVIDER']))
			{
				$value = DataProviderManager::getInstance()->createDataProvider($this->fields[$providerName], $value, $dataProvider, $providerName);
			}
			if($value instanceof DataProvider)
			{
				if($this->isCheckAccess && $value->isLoaded() && !DataProviderManager::getInstance()->checkDataProviderAccess($value))
				{
					$value = null;
				}
				else
				{
					$value = $this->getProviderValue($valueName, $value, $name);
				}
			}
		}
		// if it is not the first iteration, there are no more providers in chain and we got $dataProvider.
		elseif($dataProvider)
		{
			$value = $dataProvider->getValue($name);
		}

		return $value;
	}

	/**
	 * @param array $value
	 * @param DataProvider $dataProvider
	 * @param $placeholder
	 * @param $providerName
	 * @return bool
	 */
	protected function handleMultipleProviderValue(array $value, DataProvider $dataProvider, $placeholder, $providerName)
	{
		$fullPlaceholder = $placeholder;
		$placeholderParts = explode('.', $placeholder);
		$placeholder = '';
		foreach($placeholderParts as $part)
		{
			if($part === Template::MAIN_PROVIDER_PLACEHOLDER)
			{
				continue;
			}
			if(strlen($placeholder) > 0)
			{
				$placeholder .= '.';
			}
			$placeholder .= $part;
			if($part == $providerName)
			{
				break;
			}
		}
		if(!$placeholder)
		{
			$this->result->addError(new Error('Multiple values for root provider are not allowed'));
			return false;
		}
		if(isset($this->selectFields[$placeholder]))
		{
			return DataProviderManager::getInstance()->getValueFromList($value);
		}
		if($dataProvider === null)
		{
			$fields = $this->getProvider()->getFields();
		}
		else
		{
			$fields = $dataProvider->getFields();
		}
		if(isset($fields[$providerName]))
		{
			$group = [];
			foreach($this->fields as $field)
			{
				if(is_string($field['VALUE']) && strpos($field['VALUE'], $fullPlaceholder) !== false)
				{
					$group = $field['GROUP'];
					break;
				}
			}
			if(is_array($group))
			{
				// 3 = 1 (document) + 1(minimum) + 1(will be popped)
				$group = array_slice($group, 0, (3 + substr_count($placeholder, '.')));
			}
			$title = $providerName;
			if(isset($fields[$providerName]['TITLE']))
			{
				$title = $fields[$providerName]['TITLE'];
			}
			$this->selectFields[$placeholder] = [
				'TITLE' => $title,
				'VALUE' => $value,
				'GROUP' => $group,
			];
		}

		return DataProviderManager::getInstance()->getValueFromList($value);
	}

	/**
	 * Add $fieldNames to $this->excludeFields (it is not rewrite them)
	 *
	 * @param array $fieldNames
	 * @return $this
	 */
	public function excludeFields(array $fieldNames)
	{
		$this->body->setExcludedPlaceholders($fieldNames);

		return $this;
	}

	/**
	 * @param Storage $storage
	 * @return $this
	 */
	public function setStorage(Storage $storage)
	{
		$this->body->setStorage($storage);

		return $this;
	}

	/**
	 * @param Template $template
	 * @return $this
	 */
	public function setTemplate(Template $template)
	{
		$this->template = $template;

		return $this;
	}

	/**
	 * Returns array of external values.
	 * If $unique is true - returns values that are not equal to calculated
	 *
	 * @param bool $unique
	 * @return array
	 */
	protected function getExternalValues($unique = false)
	{
		foreach($this->externalValues as $placeholder => $value)
		{
			if(empty($value))
			{
				unset($this->externalValues[$placeholder]);
			}
		}
		$result = $this->externalValues;
		if($unique)
		{
			$result = [];
			foreach($this->externalValues as $placeholder => $value)
			{
				$this->getValue($placeholder);
				if($value != $this->values[$placeholder])
				{
					if(is_array($this->values[$placeholder]) || $value != htmlspecialcharsbx($this->values[$placeholder]))
					{
						$result[$placeholder] = $value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $userId
	 * @return boolean
	 */
	public function hasAccess($userId)
	{
		$this->isCheckAccess = true;
		$sourceProvider = $this->getProvider();
		if($sourceProvider)
		{
			return DataProviderManager::getInstance()->checkDataProviderAccess($sourceProvider, $userId);
		}

		return true;
	}

	/**
	 * @return TransformerManager|false
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getTransformer()
	{
		if($this->transformer === null)
		{
			$this->transformer = false;
			if(Loader::includeModule('transformer'))
			{
				$this->transformer = new TransformerManager($this);
			}
		}

		return $this->transformer;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function getPullTag()
	{
		$transformer = $this->getTransformer();
		if($transformer)
		{
			return $transformer->getPullTag();
		}

		return false;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function transform()
	{
		$transformer = $this->getTransformer();
		if($transformer)
		{
			$result = $transformer->transform([static::IMAGE, static::PDF]);
			if(!$result->isSuccess())
			{
				$result = (new Result())->addError(new Error(Loc::getMessage('DOCUMENT_TRANSOFMATION_ERROR'), static::ERROR_TRANSFORMATION));
			}

			return $result;
		}

		return (new Result())->addError(new Error(Loc::getMessage('DOCUMENT_TRANSOFMER_MODULE_ERROR'), static::ERROR_NO_TRANSFORMER_MODULE));
	}

	/**
	 * @param bool $absolute
	 * @return \Bitrix\Main\Web\Uri
	 */
	public function getImageUrl($absolute = false)
	{
		return new ContentUri(UrlManager::getInstance()->create('documentgenerator.api.document.getimage', ['id' => $this->ID, 'ts' => $this->getUpdateTime()->getTimestamp()], $absolute)->getUri());
	}

	/**
	 * @param bool $absolute
	 * @return \Bitrix\Main\Web\Uri
	 */
	public function getPdfUrl($absolute = false)
	{
		return new ContentUri(UrlManager::getInstance()->create('documentgenerator.api.document.getpdf', ['id' => $this->ID, 'ts' => $this->getUpdateTime()->getTimestamp()], $absolute)->getUri());
	}

	/**
	 * @param bool $absolute
	 * @return \Bitrix\Main\Web\Uri
	 */
	public function getPrintUrl($absolute = false)
	{
		return new ContentUri(UrlManager::getInstance()->create('documentgenerator.api.document.showpdf', ['id' => $this->ID, 'print' => 'y', 'ts' => $this->getUpdateTime()->getTimestamp()], $absolute)->getUri());
	}

	/**
	 * @param bool $absolute
	 * @return \Bitrix\Main\Web\Uri
	 */
	public function getDownloadUrl($absolute = false)
	{
		return new ContentUri(UrlManager::getInstance()->create('documentgenerator.api.document.getfile', ['id' => $this->ID, 'ts' => $this->getUpdateTime()->getTimestamp()], $absolute)->getUri());
	}

	/**
	 * @param bool $status
	 * @return Result
	 */
	public function enablePublicUrl($status = true)
	{
		$result = new Result();

		if(!$this->ID)
		{
			return $result->addError(new Error('Document is not saved'));
		}

		$link = ExternalLinkTable::getByDocumentId($this->ID);
		if($status)
		{
			if(!$link)
			{
				$result = ExternalLinkTable::add([
					'HASH' => md5(uniqid($this->ID, true) . \CMain::getServerUniqID()),
					'DOCUMENT_ID' => $this->ID,
				]);
			}
		}
		else
		{
			if($link)
			{
				$result = ExternalLinkTable::deleteByDocumentId($this->ID);
			}
		}

		return $result;
	}

	/**
	 * @param bool $absolute
	 * @return Uri|false
	 */
	public function getPublicUrl($absolute = true)
	{
		$link = ExternalLinkTable::getByDocumentId($this->ID);
		if(!$link)
		{
			return false;
		}

		if($link)
		{
			if($absolute)
			{
				$link = UrlManager::getInstance()->getHostUrl() . $link;
			}

			return new Uri($link);
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected function getFieldNames()
	{
		$fieldNames = [];
		if(!$this->body)
		{
			$this->result->addError(new Error('no body'));
		}
		else
		{
			$fieldNames = array_merge($this->body->getFieldNames(), $this->fieldNames);
		}

		return $fieldNames;
	}

	/**
	 * @param bool $docx
	 * @return int
	 */
	public function getEmailDiskFile($docx = false)
	{
		if($this->PDF_ID > 0)
		{
			$file = FileTable::getById($this->PDF_ID)->fetch();
			if($file)
			{
				$storage = new $file['STORAGE_TYPE'];
				if($storage instanceof Disk)
				{
					return $file['STORAGE_WHERE'];
				}
			}
		}

		if($docx && $this->FILE_ID > 0)
		{
			$file = FileTable::getById($this->FILE_ID)->fetch();
			if($file)
			{
				$storage = new $file['STORAGE_TYPE'];
				if($storage instanceof Disk)
				{
					return $file['STORAGE_WHERE'];
				}
			}
		}

		return 0;
	}

	/**
	 * @param string $extension
	 * @return string
	 */
	public function getFileName($extension = '')
	{
		if($extension === '')
		{
			$extension = $this->body->getFileExtension();
		}
		return $this->getTitle().'.'.$extension;
	}

	/**
	 * @param Template $template
	 * @param $value
	 * @param $title
	 * @param $number
	 * @param $fileId
	 * @param null $pdfId
	 * @param null $imageId
	 * @return Result
	 * @throws \Exception
	 */
	public static function upload(Template $template, $value, $title, $number, $fileId, $pdfId = null, $imageId = null)
	{
		$result = new Result();

		$fileData = FileTable::getById($fileId);
		if(!$fileData)
		{
			return $result->addError(new Error('Wrong fileId - data not found'));
		}
		if($pdfId)
		{
			$fileData = FileTable::getById($pdfId);
			if(!$fileData)
			{
				return $result->addError(new Error('Wrong pdfId - data not found'));
			}
		}
		if($imageId)
		{
			$fileData = FileTable::getById($imageId);
			if(!$fileData)
			{
				return $result->addError(new Error('Wrong imageId - data not found'));
			}
		}

		$data = [
			'ACTIVE' => 'Y',
			'TEMPLATE_ID' => $template->ID,
			'VALUE' => $value,
			'FILE_ID' => $fileId,
			'PROVIDER' => $template->getSourceType(),
			'IMAGE_ID' => $imageId,
			'PDF_ID' => $pdfId,
			'UPDATE_TIME' => new DateTime(),
			'TITLE' => $title,
			'NUMBER' => $number,
			'CREATED_BY' => Driver::getInstance()->getUserId(),
		];
		$result = DocumentTable::add($data);
		if($result->isSuccess())
		{
			$document = static::loadById($result->getId());
			EventManager::getInstance()->send(new Event(Driver::MODULE_ID, 'onCreateDocument', ['document' => $document]));
			return $document->getFile(true, true);
		}
		else
		{
			return $result;
		}
	}

	/**
	 * @param bool $isCheckAccess
	 * @return Document
	 */
	public function setIsCheckAccess($isCheckAccess)
	{
		$this->isCheckAccess = $isCheckAccess;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getIsCheckAccess()
	{
		return ($this->isCheckAccess === true);
	}
}