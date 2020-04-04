<?php

namespace Bitrix\DocumentGenerator;

use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\HashDataProvider;
use Bitrix\DocumentGenerator\DataProvider\Rest;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\Model\RegionPhraseTable;
use Bitrix\DocumentGenerator\Value\DateTime;
use Bitrix\DocumentGenerator\Value\Multiple;
use Bitrix\DocumentGenerator\Value\Name;
use Bitrix\DocumentGenerator\Value\PhoneNumber;
use Bitrix\Main\Context\Culture;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\Date;

final class DataProviderManager
{
	const MAX_DEPTH_LEVEL_ROOT_PROVIDERS = 2;

	/** @var DataProviderManager */
	private static $instance;

	protected $providersCache = [];
	protected $accessCache = [];
	protected $phrases = [];
	protected $loadedPhrasePath = [];
	protected $context;

	private function __construct()
	{
		$this->context = new Context();
	}

	private function __clone()
	{
	}

	/**
	 * @return DataProviderManager
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new DataProviderManager();
		}

		return self::$instance;
	}

	/**
	 * @param Context $context
	 * @return DataProviderManager
	 */
	public function setContext(Context $context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return Context
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * Returns true if $providerClassName is a valid DataProvider.
	 * Module with this class should be included before this check.
	 *
	 * @param string $providerClassName
	 * @param string $moduleId
	 * @return bool
	 */
	public static function checkProviderName($providerClassName, $moduleId = null)
	{
		$result = is_a($providerClassName, DataProvider::class, true);

		$documentProviders = [
			strtolower(ArrayDataProvider::class),
			strtolower(User::class),
		];
		if(in_array(strtolower($providerClassName), $documentProviders))
		{
			return true;
		}
		if($result && $moduleId && is_string($moduleId) && !empty($moduleId))
		{
			$result = false;
			$providers = static::getInstance()->getList(['filter' => ['MODULE' => $moduleId]]);
			$providerClassName = strtolower($providerClassName);
			foreach($providers as $name => $provider)
			{
				if($name == $providerClassName || (isset($provider['ORIGINAL']) && $provider['ORIGINAL'] == $providerClassName))
				{
					return true;
				}
			}
		}

		return $result;
	}

	/**
	 * Resolve and executes callback from VALUE in field with $placeholder
	 *
	 * @param DataProvider $dataProvider
	 * @param string|int $placeholder
	 * @return DataProvider|false|mixed
	 */
	public function getDataProviderValue(DataProvider $dataProvider, $placeholder)
	{
		if(($placeholder !== 0 && empty($placeholder)) || is_array($placeholder) || is_object($placeholder))
		{
			return false;
		}

		if(!$dataProvider->isLoaded())
		{
			return false;
		}

		$placeholderParts = explode('.', $placeholder);
		if(count($placeholderParts) > 1)
		{
			$provider = $this->getDataProviderValue($dataProvider, $placeholderParts[0]);
			if($provider && $provider instanceof DataProvider)
			{
				$value = $provider->getValue(implode('.', array_slice($placeholderParts, 1)));
				if($this->getContext()->getIsCheckAccess() && !$this->checkDataProviderAccess($provider))
				{
					$value = false;
				}
			}
			else
			{
				$value = false;
			}
		}
		else
		{
			$value = $this->calculateDataProviderValue($dataProvider, $placeholder);
		}

		return $value;
	}

	/**
	 * @param DataProvider $dataProvider
	 * @param $placeholder
	 * @return DataProvider|false|mixed
	 */
	protected function calculateDataProviderValue(DataProvider $dataProvider, $placeholder)
	{
		$fields = $dataProvider->getFields();
		if(!isset($fields[$placeholder]))
		{
			return false;
		}

		$fieldDescription = $fields[$placeholder];

		// rewrite inner values from options.
		$value = false;
		$values = [];
		if(isset($fieldDescription['OPTIONS']) && isset($fieldDescription['OPTIONS']['VALUES']) && is_array($fieldDescription['OPTIONS']['VALUES']))
		{
			$values = $fieldDescription['OPTIONS']['VALUES'];
		}
		$options = $dataProvider->getOptions();
		if(
			isset($options['VALUES']) &&
			is_array($options['VALUES'])
		)
		{
			$values = array_merge($values, $options['VALUES']);
		}

		if(isset($values[$placeholder]))
		{
			$value = $values[$placeholder];
		}

		$calculatedValue = false;
		if(isset($fieldDescription['VALUE']))
		{
			$calculatedValue = $this->getValue($fieldDescription['VALUE'], $dataProvider, $placeholder);
		}
		if(is_array($calculatedValue) && count($calculatedValue) > 0 && is_array(reset($calculatedValue)))
		{
			$selectedFound = false;
			if($value)
			{
				foreach($calculatedValue as &$calcVal)
				{
					if($calcVal['VALUE'] != $value)
					{
						$calcVal['SELECTED'] = false;
					}
					else
					{
						$calcVal['SELECTED'] = true;
						$selectedFound = true;
					}
				}
			}
			if(!$selectedFound)
			{
				foreach($calculatedValue as &$calcVal)
				{
					if($calcVal['SELECTED'] === true)
					{
						$selectedFound = true;
						break;
					}
				}
			}
			if(!$selectedFound)
			{
				foreach($calculatedValue as &$calcVal)
				{
					$calcVal['SELECTED'] = true;
					break;
				}
			}
			$value = $calculatedValue;
		}

		if(!$value)
		{
			$value = $this->getValueFromList($calculatedValue);
		}
		else
		{
			$value = $calculatedValue;
		}

		if($value)
		{
			if(isset($fieldDescription['PROVIDER']))
			{
				// if $value is array and Provider does not accept array as value - returns $values as it is - to allow user decide
				// which value to use.
				if(is_array($value) && !$this->isProviderArray($fieldDescription['PROVIDER']))
				{
					return $value;
				}
				$value = $this->createDataProvider($fieldDescription, $value, $dataProvider, $placeholder);
			}
		}

		return $value;
	}

	/**
	 * Try to create new DataProvider instance from $fieldDescription on $value.
	 *
	 * @param array $fieldDescription
	 * @param mixed $value
	 * @param DataProvider|null $parentDataProvider
	 * @param string $placeholder
	 * @return DataProvider|false
	 */
	public function createDataProvider(array $fieldDescription, $value = null, DataProvider $parentDataProvider = null, $placeholder = null)
	{
		if(!$value && isset($fieldDescription['VALUE']))
		{
			$value = $this->getValue($fieldDescription['VALUE'], $parentDataProvider, $placeholder);
		}

		if(!$value)
		{
			return false;
		}

		if($value instanceof Value)
		{
			$value = $value->getValue();
		}

		if(isset($fieldDescription['PROVIDER']))
		{
			$options = [];
			if(isset($fieldDescription['OPTIONS']))
			{
				$options = $fieldDescription['OPTIONS'];
			}
			if(!isset($options['VALUES']))
			{
				$options['VALUES'] = [];
			}
			// rewrite values of inner provider from parent options
			if($parentDataProvider)
			{
				$parentProviderOptions = $parentDataProvider->getOptions();
				if(isset($parentProviderOptions['VALUES']) && is_array($parentProviderOptions['VALUES']) && $placeholder !== null)
				{
					$options['VALUES'] = array_merge($options['VALUES'], $this->reformOptionValues($parentProviderOptions['VALUES'], $placeholder));
				}
			}
			return $this->getDataProvider($fieldDescription['PROVIDER'], $value, $options, $parentDataProvider);
		}

		return false;
	}

	/**
	 * @param mixed $value
	 * @param array $fieldDescription
	 * @return Value|mixed
	 */
	public function prepareValue($value, $fieldDescription = [])
	{
		if($value instanceof Value)
		{
			return $value;
		}

		if(isset($fieldDescription['PROVIDER']) && !empty($fieldDescription['PROVIDER']))
		{
			return $value;
		}

		$type = null;
		$format = [];
		if(is_array($fieldDescription) && array_key_exists('TYPE', $fieldDescription) && !empty($fieldDescription['TYPE']))
		{
			$type = $fieldDescription['TYPE'];
		}
		if(isset($fieldDescription['FORMAT']))
		{
			$format = $fieldDescription['FORMAT'];
		}

		if($type != DataProvider::FIELD_TYPE_NAME && (is_array($value) && !is_array_assoc($value) || $value instanceof \Traversable))
		{
			$result = [];
			foreach($value as $singleValue)
			{
				if(!empty($singleValue))
				{
					$result[] = $this->getValueByType($singleValue, $type, $format);
				}
			}
			if(!empty($result))
			{
				// no need for Multiple if there is only one item.
				if(count($result) == 1)
				{
					return reset($result);
				}
				return new Multiple($result, $format);
			}
			else
			{
				return null;
			}
		}

		return $this->getValueByType($value, $type, $format);
	}

	/**
	 * @param $value
	 * @param $type
	 * @param $format
	 * @return Value
	 */
	protected function getValueByType($value, $type, $format)
	{
		if(empty($value))
		{
			return $value;
		}
		if($type === DataProvider::FIELD_TYPE_DATE || $value instanceof Date)
		{
			$value = new DateTime($value, $format);
		}
		elseif($type === DataProvider::FIELD_TYPE_NAME && is_array($value))
		{
			$value = new Name($value, $format);
		}
		elseif($type === DataProvider::FIELD_TYPE_PHONE)
		{
			$value = new PhoneNumber($value, $format);
		}
		elseif(is_a($type, Value::class, true))
		{
			$value = new $type($value, $format);
		}

		return $value;
	}

	/**
	 * Invoke callback to get value.
	 *
	 * @param $valueDescription
	 * @param DataProvider|null $parentDataProvider
	 * @param null $placeholder
	 * @return false|mixed
	 */
	protected function getValue($valueDescription, DataProvider $parentDataProvider = null, $placeholder = null)
	{
		$value = false;
		if($parentDataProvider && is_string($valueDescription) && $placeholder != $valueDescription)
		{
			$value = $parentDataProvider->getValue($valueDescription);
		}
		elseif(is_callable($valueDescription))
		{
			$value = call_user_func($valueDescription, $placeholder);
		}

		return $value;
	}

	/**
	 * Creates new DataProvider on $value with $options.
	 * If DataProvider with the same $value, $options and class exists in cache - returns it.
	 *
	 * @param string $providerClassName
	 * @param mixed $value
	 * @param array $options
	 * @param DataProvider $parentDataProvider
	 * @return DataProvider|false
	 */
	public function getDataProvider($providerClassName, $value, array $options = [], DataProvider $parentDataProvider = null)
	{
		$valueHash = $this->getValueHash($value, $options);
		if(!isset($this->providersCache[$providerClassName][$valueHash]))
		{
			$provider = false;
			if(DataProviderManager::checkProviderName($providerClassName))
			{
				/** @var DataProvider $provider */
				$provider = new $providerClassName($value, $options);
				if($parentDataProvider)
				{
					$provider->setParentProvider($parentDataProvider);
				}
			}

			$this->providersCache[$providerClassName][$valueHash] = $provider;
		}

		return $this->providersCache[$providerClassName][$valueHash];
	}

	/**
	 * Forms multi-level array [placeholder] => [value].
	 * For debug-use only.
	 *
	 * @param DataProvider $dataProvider
	 * @param array $params
	 * @return array
	 * @internal
	 */
	public function getArray(DataProvider $dataProvider, array $params = [], array $stack = [])
	{
		$result = [];
		if(in_array(get_class($dataProvider), $stack))
		{
			return $result;
		}
		$stack[] = get_class($dataProvider);

		foreach($dataProvider->getFields() as $placeholder => $field)
		{
			$value = $dataProvider->getValue($placeholder);
			if(isset($params['rawValue']) && $params['rawValue'] === true && $value instanceof Value)
			{
				$value = $value->getValue();
			}
			elseif($value instanceof ArrayDataProvider && $value->getItemKey())
			{
				$values = $this->getArray($value, $params, $stack);
				foreach($value as $item)
				{
					$values[$value->getItemKey()][] = $this->getArray($item, $params, $stack);
				}
				$value = $values;
			}
			elseif($value instanceof DataProvider)
			{
				$value = $this->getArray($value, $params, $stack);
			}
			elseif(is_array($value))
			{
				if(isset($params['listAsArray']) && $params['listAsArray'] === true)
				{

				}
				elseif(isset($field['PROVIDER']))
				{
					$value = $this->getValueFromList($value);
					$value = $this->createDataProvider($field, $value, $dataProvider, $placeholder);
					if($value instanceof DataProvider)
					{
						$value = $this->getArray($value, $params, $stack);
					}
					else
					{
						$value = null;
					}
				}
			}
			$result[$placeholder] = $value;
		}

		return $result;
	}

	/**
	 * Get list of available DataProviders, filtered by $params
	 *
	 * @param array $params
	 * @return array
	 */
	public function getList(array $params = [])
	{
		$providers = Registry\DataProvider::getList($params);
		$moduleId = null;
		if(isset($params['filter']) && isset($params['filter']['MODULE']) && is_string($params['filter']['MODULE']) && !empty($params['filter']['MODULE']))
		{
			$moduleId = $params['filter']['MODULE'];
		}
		if($moduleId)
		{
			if(!ModuleManager::isModuleInstalled($moduleId) || !Loader::includeModule($moduleId))
			{
				$moduleId = null;
			}
		}
		if($moduleId)
		{
			foreach($providers as $key => $provider)
			{
				if(isset($provider['MODULE']) && $moduleId != $provider['MODULE'])
				{
					unset($providers[$key]);
				}
			}
		}
		if($moduleId === Driver::REST_MODULE_ID)
		{
			$providers[strtolower(Rest::class)] = [
				'CLASS' => Rest::class,
				'NAME' => Driver::REST_MODULE_ID,
				'MODULE' => Driver::REST_MODULE_ID,
			];
		}

		return $providers;
	}

	/**
	 * @param $providerClassName
	 * @param array $placeholders
	 * @param array $mainProviderOptions
	 * @param bool $isAddRootGroups
	 * @param bool $isCopyFields
	 * @return array
	 */
	public function getDefaultTemplateFields($providerClassName, array $placeholders = [], array $mainProviderOptions = [], $isAddRootGroups = true, $isCopyFields = false)
	{
		$fields = [];

		$sourceFields = DataProviderManager::getInstance()->getProviderPlaceholders($providerClassName, $placeholders, $mainProviderOptions, $isCopyFields);
		$documentFields = DataProviderManager::getInstance()->getProviderPlaceholders(DataProvider\Document::class);
		if($isAddRootGroups)
		{
			Loc::loadLanguageFile(__DIR__.'/document.php');
			foreach($documentFields as &$field)
			{
				array_unshift($field['GROUP'], Loc::getMessage('DOCUMENT_GROUP_NAME'));
			}
			foreach($sourceFields as &$field)
			{
				array_unshift($field['GROUP'], Loc::getMessage('DOCUMENT_GROUP_NAME'));
			}
		}
		if(empty($placeholders))
		{
			$placeholders = array_merge(array_keys($sourceFields), array_keys($documentFields));
		}
		foreach($placeholders as $placeholder)
		{
			if(isset($sourceFields[$placeholder]))
			{
				$fields[$placeholder] = $sourceFields[$placeholder];
				$fields[$placeholder]['VALUE'] = Document::THIS_PLACEHOLDER.'.'.Template::MAIN_PROVIDER_PLACEHOLDER.'.'.$fields[$placeholder]['VALUE'];
			}
			elseif(isset($documentFields[$placeholder]))
			{
				$fields[$placeholder] = $documentFields[$placeholder];
				$fields[$placeholder]['VALUE'] = Document::THIS_PLACEHOLDER.'.'.Template::DOCUMENT_PROVIDER_PLACEHOLDER.'.'.$fields[$placeholder]['VALUE'];
			}
		}

		return $fields;
	}

	/**
	 * Returns all possible placeholders for DataProvider.
	 *
	 * @param string $providerClassName
	 * @param array $placeholders
	 * @param array $options
	 * @param bool $isCopyFields
	 * @return array
	 */
	public function getProviderPlaceholders($providerClassName, array $placeholders = [], array $options = [], $isCopyFields = false)
	{
		$result = [];
		$dataProvider = $this->getDataProvider($providerClassName, ' ', $options);
		if(!$dataProvider)
		{
			return $result;
		}

		if(empty($placeholders))
		{
			$placeholders = true;
		}
		$fields = $this->getProviderFields($dataProvider, $placeholders, $isCopyFields);
		foreach($fields as $field)
		{
			$result[$this->valueToPlaceholder($field['VALUE'])] = $field;
		}

		return $result;
	}

	/**
	 * Form a valid placeholder for $value.
	 * For example DATA_PROVIDER.FIELD => DataProviderField
	 *
	 * @param string $value
	 * @return string
	 */
	public function valueToPlaceholder($value)
	{
		$placeholder = strtolower($value);
		$placeholder = str_replace(['_', '.'], ' ', $placeholder);
		$placeholder = ucwords($placeholder);
		$placeholder = str_replace(' ', '', $placeholder);

		return $placeholder;
	}

	/**
	 * @param DataProvider $dataProvider
	 * @param string $placeholder
	 * @return bool|array
	 */
	public function getProviderField(DataProvider $dataProvider, $placeholder)
	{
		$nameParts = explode('.', $placeholder);
		if(count($nameParts) == 1)
		{
			return $dataProvider->getFields()[$placeholder];
		}

		$placeholder = array_shift($nameParts);
		$fieldDescription = $dataProvider->getFields()[$placeholder];
		if($fieldDescription)
		{
			$childDataProvider = $this->createDataProvider($fieldDescription, ' ', $dataProvider);
			if($childDataProvider)
			{
				return $this->getProviderField($childDataProvider, implode('.', $nameParts));
			}
		}

		return false;
	}

	/**
	 * Returns single-level array with all fields of a $parentDataProvider.
	 * Key - path from field names like PROVIDER.PROVIDER.FIELD
	 * Value - field description (VALUE, TITLE, TYPE)
	 *
	 * @param DataProvider $parentDataProvider
	 * @param array|bool $placeholders
	 * @param bool $isCopyFields
	 * @param array $chain
	 * @param array $group
	 * @param bool $isArray
	 * @param array $providers
	 * @param bool $stopRecursion
	 * @return array
	 */
	public function getProviderFields(DataProvider $parentDataProvider, $placeholders = [], $isCopyFields = false, array $chain = [], array $group = [], $isArray = false, array $providers = [], $stopRecursion = false)
	{
		$values = [];
		if($parentDataProvider->isRootProvider())
		{
			$providers[] = get_class($parentDataProvider);
		}
		$fields = $parentDataProvider->getFields();
		if(is_array($placeholders) && empty($placeholders))
		{
			return $values;
		}
		$copyPlaceholders = [];
		if($isCopyFields)
		{
			// build copied placeholders map
			foreach($fields as $placeholder => $field)
			{
				if(isset($field['OPTIONS']) && isset($field['OPTIONS']['COPY']))
				{
					if(is_array($placeholders) && !empty($placeholders))
					{
						$copyChain = $chain;
						$copyChain[] = $placeholder;
						$currentValue = $this->valueToPlaceholder(implode('.', $copyChain));
						foreach($placeholders as $name)
						{
							if(strpos($name, $currentValue) === 0)
							{
								$copyPlaceholders[$placeholder] = $field['OPTIONS']['COPY'];
								$placeholders[] = str_replace($this->valueToPlaceholder($placeholder), $this->valueToPlaceholder($field['OPTIONS']['COPY']), $name);
								break;
							}
						}
					}
					else
					{
						$copyPlaceholders[$placeholder] = $field['OPTIONS']['COPY'];
					}
				}
			}
		}
		foreach($fields as $placeholder => $field)
		{
			$chain[] = $placeholder;
			$goDeeper = true;
			if(is_array($placeholders))
			{
				$goDeeper = false;
				$currentValue = $this->valueToPlaceholder(implode('.', $chain));
				foreach($placeholders as $name)
				{
					if(strpos($name, $currentValue) === 0)
					{
						$goDeeper = true;
						break;
					}
				}
			}
			if(!$goDeeper)
			{
				array_pop($chain);
				continue;
			}
			$dataProvider = $this->createDataProvider($field, ' ', $parentDataProvider);
			if(isset($field['TITLE']) && !empty($field['TITLE']))
			{
				$group[] = $field['TITLE'];
			}
			else
			{
				$group[] = $this->valueToPlaceholder($placeholder);
			}
			if(
				$dataProvider &&
				(($dataProvider->isRootProvider() && !$stopRecursion) ||
				(!$dataProvider->isRootProvider()))
			)
			{
				if($dataProvider instanceof ArrayDataProvider)
				{
					$isArray = true;
				}
				$stopRecursion = false;
				if(count($providers) > self::MAX_DEPTH_LEVEL_ROOT_PROVIDERS)
				{
					$stopRecursion = true;
				}
				$values = array_merge($values, $this->getProviderFields($dataProvider, $placeholders, $isCopyFields, $chain, $group, $isArray, $providers, $stopRecursion));
				$isArray = false;
			}
			else
			{
				if($isArray)
				{
					$field['OPTIONS']['IS_ARRAY'] = true;
				}
				$value = implode('.', $chain);
				if($isCopyFields || (!$isCopyFields && !isset($field['OPTIONS']['COPY'])))
				{
					$values[] = array_merge($field, [
						'VALUE' => $value,
						'GROUP' => $group,
					]);
				}
			}
			array_pop($group);
			array_pop($chain);
		}
		foreach($copyPlaceholders as $destPlaceholder => $sourcePlaceholder)
		{
			foreach($values as $field)
			{
				if(is_string($field['VALUE']) && strpos($field['VALUE'], $sourcePlaceholder) !== false)
				{
					$field['VALUE'] = str_replace($sourcePlaceholder, $destPlaceholder, $field['VALUE']);
					$values[] = $field;
				}
			}
		}

		return $values;
	}

	/**
	 * Returns valid string to use it as a key to store DataProvider instance in the cache
	 *
	 * @param mixed $value
	 * @param array $options
	 * @return string
	 */
	protected function getValueHash($value, array $options = [])
	{
		$valueHash = $value;
		if(is_object($value))
		{
			$valueHash = spl_object_hash($value);
		}
		elseif(is_array($value))
		{
			$valueHash = hash('md5', serialize($value));
		}

		$valueHash .= hash('md5', serialize($options));

		return $valueHash;
	}

	/**
	 * Removes $placeholder from $value names.
	 * Example: $values = [Placeholder.Provider => Value] => [Provider => Value] if $placeholder = 'Placeholder'
	 *
	 * @param array $values
	 * @param string $placeholder
	 * @return array
	 */
	protected function reformOptionValues(array $values, $placeholder)
	{
		$result = [];
		foreach($values as $name => $value)
		{
			$nameParts = explode('.', $name);
			if(count($nameParts) > 1 && $nameParts[0] == $placeholder)
			{
				array_shift($nameParts);
				$name = implode('.', $nameParts);
			}
			$result[$name] = $value;
		}

		return $result;
	}

	/**
	 * Returns true if provider accepts array as main $value.
	 *
	 * @param $providerClassName
	 * @return bool
	 */
	public function isProviderArray($providerClassName)
	{
		return (
			is_a($providerClassName, ArrayDataProvider::class, true) ||
			is_a($providerClassName, HashDataProvider::class, true)
		);
	}

	/**
	 * @param array|mixed $values
	 * @param bool $firstAsDefault
	 * @return mixed
	 */
	public function getValueFromList($values, $firstAsDefault = false)
	{
		if(is_array($values))
		{
			foreach($values as $value)
			{
				if(is_array($value) && $value['SELECTED'])
				{
					return $value['VALUE'];
				}
			}
			if($firstAsDefault === true)
			{
				foreach($values as $value)
				{
					return $value['VALUE'];
				}
			}
		}

		return $values;
	}

	/**
	 * @param DataProvider $dataProvider
	 * @param string $code
	 * @return null|string
	 */
	public function getLangPhraseValue(DataProvider $dataProvider, $code)
	{
		$phrasesPath = $dataProvider->getLangPhrasesPath();
		if($phrasesPath === null)
		{
			return '';
		}
		$region = $this->getRegion();
		$this->loadLangPhrases($phrasesPath, $region);

		if(isset($this->phrases[$region][$code]))
		{
			return $this->phrases[$region][$code];
		}

		return null;
	}

	/**
	 * @param $region
	 * @return $this
	 */
	public function setRegion($region)
	{
		$this->context->setRegion($region);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getRegion()
	{
		return $this->context->getRegion();
	}

	/**
	 * @return string
	 */
	public function getRegionLanguageId()
	{
		return $this->context->getRegionLanguageId();
	}

	/**
	 * @param string $path
	 * @param string $region
	 */
	protected function loadLangPhrases($path, $region)
	{
		if(isset($this->loadedPhrasePath[$path]) && isset($this->loadedPhrasePath[$path][$region]))
		{
			return;
		}

		$this->loadedPhrasePath[$path][$region] = true;
		$phrases = [];
		if(is_numeric($region))
		{
			$phraseList = RegionPhraseTable::getList([
				'filter' => [
					'REGION_ID' => $region,
				]
			]);
			while($phrase = $phraseList->fetch())
			{
				$phrases[$phrase['CODE']] = $phrase['PHRASE'];
			}
		}
		else
		{
			$file = new File($path.'/phrase_'.$region.'.php');
			if(!$file->isExists())
			{
				return;
			}

			/** @noinspection PhpIncludeInspection */
			$phrases = include $file->getPath();
		}
		if(!isset($this->phrases[$region]))
		{
			$this->phrases[$region] = [];
		}
		if(is_array($phrases))
		{
			$phrases = [$region => $phrases];
			$this->phrases = array_merge($this->phrases, $phrases);
		}
	}

	/**
	 * @return Culture
	 */
	public function getCulture()
	{
		return $this->context->getCulture();
	}

	/**
	 * @param DataProvider $dataProvider
	 * @param null $userId
	 * @return bool
	 */
	public function checkDataProviderAccess(DataProvider $dataProvider, $userId = null)
	{
		if(!$userId)
		{
			$userId = Driver::getInstance()->getUserId();
		}

		if($userId === 0)
		{
			return true;
		}

		$providerHash = $this->getValueHash($dataProvider);
		if(!isset($this->accessCache[$providerHash][$userId]))
		{
			$this->accessCache[$providerHash][$userId] = $dataProvider->hasAccess($userId);
		}

		return $this->accessCache[$providerHash][$userId];
	}

	/**
	 * @param $region
	 * @return array
	 */
	public function getRegionPhrases($region)
	{
		$providers = $this->getList();
		$loadedProviders = $phrases = [];
		foreach($providers as $providerDescription)
		{
			$this->getDataProviderRegionPhrases($providerDescription['CLASS'], $region, $loadedProviders);
			$loadedProviders[strtolower($providerDescription['CLASS'])] = true;
		}

		return $this->phrases[$region];
	}

	/**
	 * @param string $providerClassName
	 * @param $region
	 * @param array $loadedProviders
	 * @param array $field
	 */
	public function getDataProviderRegionPhrases($providerClassName, $region, &$loadedProviders = [], array $field = [])
	{
		$providerClassName = strtolower($providerClassName);
		if(isset($loadedProviders[$providerClassName]))
		{
			return;
		}
		if(!empty($field))
		{
			$provider = $this->createDataProvider($field, ' ');
		}
		else
		{
			$provider = $this->getDataProvider($providerClassName, ' ');
		}
		if($provider)
		{
			if($provider instanceof ArrayDataProvider)
			{
				$field = $provider->getFields()[$provider->getItemKey()];
				$provider = $this->createDataProvider($field, ' ');
				if(!$provider)
				{
					return;
				}
			}
			$phrasesPath = $provider->getLangPhrasesPath();
			if($phrasesPath)
			{
				$this->loadLangPhrases($phrasesPath, $region);
			}
			$loadedProviders[$providerClassName] = true;
			foreach($provider->getFields() as $placeholder => $field)
			{
				if(isset($field['PROVIDER']) && !empty($field['PROVIDER']) && !isset($loadedProviders[strtolower($field['PROVIDER'])]))
				{
					$this->getDataProviderRegionPhrases($field['PROVIDER'], $region, $loadedProviders, $field);
				}
			}
		}
	}
}