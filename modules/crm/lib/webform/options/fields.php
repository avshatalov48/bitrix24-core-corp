<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Options;

use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Localization\Loc;

/**
 * Class Fields
 * @package Bitrix\Crm\WebForm\Options
 */
final class Fields
{
	private static $fields = [];

	private $form;

	/** @var bool $editMode Edit mode with special using fields like `id`.  */
	protected $editMode = false;

	/** @var bool  */
	protected $changed = false;

	/** @var array|null  */
	protected $cachedResult = null;

	/**
	 * Clear fields.
	 *
	 * @return void
	 */
	public static function clearCache()
	{
		self::$fields = [];
	}

	public function __construct(WebForm\Form $form)
	{
		$this->form = $form;
		if (!$this::$fields)
		{
			self::$fields = WebForm\EntityFieldProvider::getAllFieldsDescription();
			self::$fields = array_combine(
				array_column(self::$fields, 'CODE'),
				array_values(self::$fields)
			);
		}
	}

	/**
	 * Set edit mode.
	 *
	 * @param bool $mode Mode.
	 * @return $this
	 */
	public function setEditMode($mode)
	{
		$this->editMode = $mode;
		return $this;
	}

	/**
	 * Get fields.
	 *
	 * @return array
	 */
	public function toArray()
	{
		if ($this->cachedResult !== null && !$this->changed)
		{
			return $this->cachedResult;
		}

		$this->changed = false;
		$this->cachedResult = [];
		foreach ($this->form->getFields() as $field)
		{
			$data = self::$fields[$field['CODE']] ?? [];

			$options = [
				'id' =>  $field['CODE'],
				'name' => $field['CODE'],
				'label' => $field['CAPTION'] ?: $data['ENTITY_FIELD_CAPTION'],
				'visible' => true,
				'required' => $field['REQUIRED'] === 'Y',
				'multiple' => $field['MULTIPLE'] === 'Y',
			];
			if ($this->editMode)
			{
				$options['editing'] = $this->getFieldEditing($field, $data);
			}

			$gotoNextField = false;
			$type = $field['TYPE'];
			switch ($type)
			{
				case 'resourcebooking':
					if (!$options['multiple'])
					{
						$options['booking'] = [
							'name' => $field['CODE'],
							'caption' => $field['CAPTION'],
							'entity_field_name' => $data['ENTITY_FIELD_NAME'],
							'settings_data' => $field['SETTINGS_DATA'],
						];
					}
					break;
				case 'rq':
					if (!$options['multiple'])
					{
						$options['requisite'] = WebForm\Requisite::instance()
							->convertSettingsToOptions($field['SETTINGS_DATA'] ?? [])
						;
					}
					break;
				case 'address':
					if (!$options['multiple'])
					{
						$options['fields'] = WebForm\Requisite::instance()
							->getAddressField(
								\CCrmOwnerType::ResolveID($data['ENTITY_NAME']),
								(string)$data['ENTITY_FIELD_NAME']
							)['fields'] ?? []
						;
						if (!$options['fields'])
						{
							$gotoNextField = true;
						}

						foreach ($options['fields'] as $subFieldIndex => $subField)
						{
							$subField['required'] = $options['required']
								&& in_array($subField['name'], ['ADDRESS_1', 'CITY'])
							;
							$options['fields'][$subFieldIndex] = $subField;
						}
					}
					break;
				case 'bool':
				case 'radio':
				case 'checkbox':
					if ($data['TYPE_ORIGINAL'] == 'radio' || ($data['TYPE_ORIGINAL'] == 'checkbox' && $data['MULTIPLE_ORIGINAL']))
					{
						$type = $options['multiple'] ? 'checkbox' : 'radio';
					}
					else
					{
						$type = 'bool';
						$options['checked'] = false;
						$options['value'] = 'Y';
					}

					break;
				case 'typed_string':
					$stringType = strtolower($data['ENTITY_FIELD_NAME']);
					switch ($stringType)
					{
						case 'phone':
						case 'email':
							$type = $stringType;
							break;
						default:
							$type = 'string';
							break;
					}
					break;

				case 'hr':
				case 'br':
				case 'section':
					$this->cachedResult[] = $options + [
						'type' => 'layout',
						'content' => [
							'type' => $type
						]
					];
					$gotoNextField = true;
					break;

				default:
					$type = isset(WebForm\Internals\FieldTable::getTypeList()[$type])
						? $type
						:'string';
					break;
			}

			if ($gotoNextField)
			{
				continue;
			}

			switch ($options['name'])
			{
				case 'LEAD_NAME':
				case 'CONTACT_NAME':
					$type = 'name';
					break;
				case 'LEAD_LAST_NAME':
				case 'CONTACT_LAST_NAME':
					$type = 'last-name';
					break;
				case 'LEAD_SECOND_NAME':
				case 'CONTACT_SECOND_NAME':
					$type = 'second-name';
					break;
				case 'COMPANY_TITLE':
				case 'LEAD_COMPANY_TITLE':
					$type = 'company-name';
					break;
			}

			if (self::isFieldFileImage($options['name']))
			{
				$contentTypes = ['image/*'];
			}
			else
			{
				$contentTypes = $field['SETTINGS_DATA']['CONTENT_TYPES'] ?? null;
			}

			$enableAutocomplete = in_array($type, [
				'name',
				'second-name',
				'last-name',
				'email',
				'phone',
			]);

			$this->cachedResult[] = $options + [
				'type' => $type,
				'placeholder' => $field['PLACEHOLDER'],
				'value' => $field['VALUE'],
				'items' => $this->getFieldItems($field, $data),
				'hint' => $field['SETTINGS_DATA']['HINT'],
				'hintOnFocus' => !empty($field['SETTINGS_DATA']['HINT_ON_FOCUS'])
					&& $field['SETTINGS_DATA']['HINT_ON_FOCUS'] === 'Y',
				'autocomplete' => !empty($field['SETTINGS_DATA']['AUTOCOMPLETE'])
					&& $field['SETTINGS_DATA']['AUTOCOMPLETE'] === 'Y'
					|| $enableAutocomplete && empty($field['SETTINGS_DATA']['AUTOCOMPLETE']),
				'bigPic' => ($field['SETTINGS_DATA']['BIG_PIC'] ?? 'N') === 'Y',
				'size' => ($field['SETTINGS_DATA']['SIZE'] ?? null),
				'contentTypes' => $contentTypes,
			];
		}

		return $this->cachedResult;
	}

	protected function getFieldItems(array $field, array $data)
	{
		$items = is_array($field['ITEMS']) ? $field['ITEMS'] : [];
		switch ($field['TYPE'])
		{
			case 'product':
				$items = array_map(
					function ($item) use ($field)
					{
						$data = [
							'label' => $item['VALUE'],
							'value' => $item['ID'],
							'selected' => ($item['SELECTED'] ?? 'N') === 'Y',
							'price' => $item['PRICE'] ?: 0,
							'discount' => $item['DISCOUNT'] ?: 0,
							'pics' => [],
							'quantity' => [],
							'changeablePrice' => isset($item['CUSTOM_PRICE'])
								&& $item['CUSTOM_PRICE'] === 'Y'
								&& WebForm\Manager::isOrdersAvailable(),
							//quantity: {min: 2, max: 50, step: 2, unit: 'unt.'},
							//'discount' => isset($item['discount']) ? $item['discount'] : 0,
						];

						if ($field['SETTINGS_DATA']['QUANTITY_MIN'])
						{
							$data['quantity']['min'] = $field['SETTINGS_DATA']['QUANTITY_MIN'];
						}
						if ($field['SETTINGS_DATA']['QUANTITY_MAX'])
						{
							$data['quantity']['max'] = $field['SETTINGS_DATA']['QUANTITY_MAX'];
						}
						if ($field['SETTINGS_DATA']['QUANTITY_STEP'])
						{
							$data['quantity']['step'] = $field['SETTINGS_DATA']['QUANTITY_STEP'];
						}
						if (!empty($item['QUANTITY']) && is_array($item['QUANTITY']))
						{
							$data['quantity']['min'] = $item['QUANTITY']['min'] ?? 1;
							$data['quantity']['max'] = $item['QUANTITY']['max'] ?? null;
							$data['quantity']['step'] = $item['QUANTITY']['step'] ?? 1;
							$data['quantity']['unit'] = $item['QUANTITY']['unit'] ?? null;
						}

						if (!$item['ID'] || (is_string($item['ID']) && !is_numeric($item['ID'])))
						{
							return $data;
						}

						$product = \CCrmProduct::getByID($item['ID']);
						if ($product)
						{
							if (!empty($product['MEASURE']))
							{
								static $measures;
								if (!is_array($measures))
								{
									$measures = Crm\Measure::getMeasures();
									$measures = array_combine(
										array_column($measures, 'ID'),
										array_column($measures, 'SYMBOL')
									);
								}
								if (isset($measures[$product['MEASURE']]))
								{
									$data['quantity']['unit'] = $measures[$product['MEASURE']];
								}
							}
						}

						$pics = [];
						if (Main\Loader::includeModule('catalog'))
						{
							$repositoryFacade = Catalog\v2\IoC\ServiceContainer::getRepositoryFacade();
							if ($repositoryFacade)
							{
								$variation = $repositoryFacade->loadVariation($item['ID']);
								if ($variation)
								{
									foreach ($variation->getImageCollection()->toArray() as $file)
									{
										if (empty($file['SRC']))
										{
											continue;
										}

										$uri = $file['SRC'];
										if (!preg_match('/^http(s?):/i', $uri))
										{
											$uri = Main\Web\WebPacker\Builder::getDefaultSiteUri() . $uri;
										}

										$pics[] = $uri;
									}
								}
							}
						}

						if (!$pics)
						{
							$fileId = null;
							$useBigPic = ($field['SETTINGS_DATA']['BIG_PIC'] ?? 'N') === 'Y';
							if ($product['DETAIL_PICTURE'] && $useBigPic)
							{
								$fileId = $product['DETAIL_PICTURE'];
							}
							elseif (!$product['PREVIEW_PICTURE'] && $product['DETAIL_PICTURE'])
							{
								$fileId = $product['DETAIL_PICTURE'];
							}
							elseif ($product['PREVIEW_PICTURE'])
							{
								$fileId = $product['PREVIEW_PICTURE'];
							}

							if ($fileId)
							{
								$file = \CFile::getByID($fileId)->fetch();
								if ($file)
								{
									$uri = $file['~src'];
									if (empty($uri))
									{
										$uri = \CFile::GetFileSRC($file);
										if (!preg_match('/^http(s?):/i', $uri))
										{
											$uri = Main\Web\WebPacker\Builder::getDefaultSiteUri() . $uri;
										}
									}
									$pics[] = $uri;
								}
							}
						}

						$data['pics'] = $pics;

						return $data;
					},
					$items
				);
				if ($field['required'] && count($items) === 1)
				{
					$items[0]['selected'] = true;
				}
				return $items;

			default:
				$isListableType = isset(WebForm\Helper::getFieldListableTypes()[$field['TYPE']]);
				if (!$isListableType && (!isset($data['ITEMS']) || !is_array($data['ITEMS'])))
				{
					return [];
				}

				$result = array_map(
					function ($item) use ($field)
					{
						$result = [
							'label' => $item['VALUE'],
							'value' => $item['ID'],
							'selected' => ($item['SELECTED'] ?? 'N') === 'Y' || $field['VALUE'] === $item['ID'],
							//'discount' => isset($item['discount']) ? $item['discount'] : 0,
							//'pics' => [],
							//quantity: {min: 2, max: 50, step: 2, unit: 'unt.'},
						];

						$disabled = ($item['DISABLED'] ?? 'N') === 'Y';
						if ($disabled)
						{
							$result['disabled'] = $disabled;
						}

						return $result;
					},
					$items
				);

				if (!$this->editMode)
				{
					$result = array_values(array_filter(
						$result,
						function ($item)
						{
							return empty($item['disabled']);
						}
					));
				}

				return $result;
		}
	}

	private static function isTypeHasLabel($type)
	{
		return !in_array($type, ['br', 'hr']);
	}

	private function getFieldEditing($field, array $data)
	{
		$isCommonStringType = in_array($data['TYPE_ORIGINAL'], ['typed_string', 'string']);
		$hasLabel = self::isTypeHasLabel($field['TYPE']);
		$isValuableType = !isset(WebForm\Helper::getFieldNonValueTypes()[$field['TYPE']]);

		$items = [];
		$hasListableItems = isset(WebForm\Helper::getFieldListableTypes()[$field['TYPE']]);
		if ($hasListableItems)
		{
			foreach (($data['ITEMS'] ?? []) as $item)
			{
				$items[] = array_change_key_case($item);
			}
		}

		$catalog = $field['TYPE'] === 'product'
			? WebForm\Catalog::create()->setItems($field['ITEMS'])->getSelectorProducts()
			: []
		;

		$types = [];
		if (($data['TYPE_ORIGINAL'] ?? null) === 'typed_string')
		{
			foreach (WebForm\Helper::getFieldStringTypes() as $key => $val)
			{
				$types[] = ['id' => $key, 'name' => $val];
			}
		}
		$valueTypes = [];
		if (is_array($data['VALUE_TYPE_ORIGINAL']))
		{
			$valueTypes = array_map(
				function ($item)
				{
					return [
						'id' => $item['ID'],
						'name' => $item['VALUE'],
					];
				},
				$data['VALUE_TYPE_ORIGINAL']
			);
		}

		$supportAutocomplete = !in_array($field['TYPE'], [
			WebForm\Internals\FieldTable::TYPE_ENUM_RESOURCEBOOKING,
			WebForm\Internals\FieldTable::TYPE_ENUM_FILE,
			WebForm\Internals\FieldTable::TYPE_ENUM_PRODUCT,
			WebForm\Internals\FieldTable::TYPE_ENUM_SECTION,
			WebForm\Internals\FieldTable::TYPE_ENUM_PAGE,
		]);

		$defaultValueType = array_filter(
			$valueTypes,
			function (array $item)
			{
				return $item['id'] === 'OTHER';
			}
		);
		$defaultValueType = current($defaultValueType)['id'] ?? '';

		return [
			'id' => $field['ID'] ?? null,
			'entityId' => \CCrmOwnerType::resolveID($data['ENTITY_NAME']),
			'entityName' => $data['ENTITY_NAME'] ?? null,
			'name' => $data['ENTITY_FIELD_NAME'] ?? null,
			'types' => $types,
			'hasLabel' => $hasLabel,
			'hasHint' => $isValuableType,
			'supportHintOnFocus' => $isCommonStringType,
			'hasPlaceholder' => $isCommonStringType,
			'hasStringDefaultValue' => $isCommonStringType,
			'valueTypes' => $valueTypes,
			'canBeMultiple' => $data['MULTIPLE_ORIGINAL'] ?? $isValuableType,
			'canBeRequired' => $isValuableType,
			'supportContentTypes' => $field['TYPE'] === 'file' && !self::isFieldFileImage($data['CODE']),
			'supportListableItems' => $hasListableItems,
			'supportAutocomplete' => $supportAutocomplete,
			'supportCustomItems' => $field['TYPE'] === 'product',
			'catalog' => $field['TYPE'] === 'product' ? $catalog : null,
			'items' => $items,
			'editable' => [
				'valueType' => $field['VALUE_TYPE'] ?: $defaultValueType,
			],
		];
	}

	public function setData(array $data)
	{
		$this->changed = true;

		$result = [];
		foreach ($data as $index => $field)
		{
			if (!is_array($field))
			{
				continue;
			}

			$field = $this->filterFieldOptions($field);
			if (!$field)
			{
				continue;
			}

			$field['sort'] = ($index + 1) * 10;
			$result[] = self::getTabletFormattedField($field);
		}

		$this->form->merge(['FIELDS' => $result]);

		return $this;
	}

	public function clear()
	{
		$this->form->merge([
			'FIELDS' => [],
			'DEPENDENCIES' => [],
			'DEP_GROUPS' => [],
		]);
	}

	public function append(array $options)
	{
		$field = self::$fields[$options['name']];

		if (empty($options['type']) && !empty($field['TYPE']))
		{
			$options['type'] = $field['TYPE'];
		}
		if (empty($options['items']) && !empty($field['ITEMS']))
		{
			$options['items'] = array_map(
				function ($item)
				{
					return [
						'value' => $item['ID'],
						'label' => $item['VALUE'],
					];
				},
				$field['ITEMS']
			);
		}
		if (empty($options['size']) && !empty($field['SIZE']))
		{
			$options['size'] = is_array($field['SIZE']) ? $field['SIZE'] : null;
		}

		$data = $this->getTabletFormattedField([
			'name' => $options['name'],
			'sort' => (count($this->form->getFields()) + 1) * 10,
		] + $options);
		$this->form->merge(['FIELDS' => array_merge(
			$this->form->getFields(),
			[$data]
		)]);
	}

	public static function filterFieldOptions(array &$options)
	{
		if (empty($options['name']) || $options['inPreparing'])
		{
			return null;
		}

		$field = self::getFieldByName($options['name']);

		if (!$options['type'])
		{
			$options['type'] = $field['TYPE'];
		}

		return $options;
	}

	public static function getFieldByName($name)
	{
		$field = self::$fields[$name] ?? null;

		return $field;
	}

	private function getTabletFieldType(array $options, $field)
	{
		$type = $options['type'];
		switch ($type)
		{
			case 'phone':
			case 'email':
			case 'string':
			case 'page':
			case 'money':
				return $type;

			case 'bool':
				if ($field['TYPE_ORIGINAL'] === 'checkbox')
				{
					return $field['MULTIPLE_ORIGINAL'] ? 'checkbox' : 'bool';
				}
				if ($field['TYPE_ORIGINAL'] === 'radio')
				{
					return 'radio';
				}
				return $type;

			case 'layout':
				switch ($options['content']['type'])
				{
					case 'hr':
					case 'br':
					case 'section':
						return $options['content']['type'];
				}
				return 'string';

			default:
				return isset(WebForm\Internals\FieldTable::getTypeList()[$type])
					? $type
					:'string';
		}
	}

	private function getTabletFormattedField(array $options)
	{
		$options = self::filterFieldOptions($options);
		$field = self::$fields[$options['name']];

		$type = $this->getTabletFieldType($options, $field);
		$data = array(
			'ID' => $options['editing']['id'] ?? null,
			'CODE' => $options['name'],
			'TYPE' => $type,
			'CAPTION' => self::isTypeHasLabel($type)
				? $options['label']
				: '',
			'SORT' => (int) $options['sort'],
			'ITEMS' => $this->getTabletFormattedFieldItems($options),
			'SETTINGS_DATA' => [],
			'VALUE_TYPE' => $options['editing']['editable']['valueType'],
			'VALUE' => $options['value']
		);

		$multipleOriginal = $field['MULTIPLE_ORIGINAL'] ?? false;
		if($data['TYPE'] == 'product')
		{
			$data['SETTINGS_DATA']['BIG_PIC'] = ($options['bigPic'] ?? false) ? 'Y' : 'N';
			$multipleOriginal = true;
		}
		if($data['TYPE'] == 'file' && !self::isFieldFileImage($options['name']))
		{
			$data['SETTINGS_DATA']['CONTENT_TYPES'] = is_array($options['contentTypes'] ?? null)
				? $options['contentTypes']
				: []
			;
		}
		if($data['TYPE'] === 'rq')
		{
			$data['SETTINGS_DATA']['REQUISITE'] = WebForm\Requisite::instance()
				->convertOptionsToSettings($options['requisite'] ?? [])
			;
		}

		if (isset($options['autocomplete']))
		{
			$data['SETTINGS_DATA']['AUTOCOMPLETE'] =  ($options['autocomplete'] ?? false) ? 'Y' : 'N';
		}

		if (isset($options['hint']))
		{
			$data['SETTINGS_DATA']['HINT'] = $options['hint'];
		}

		if (isset($options['hintOnFocus']))
		{
			$data['SETTINGS_DATA']['HINT_ON_FOCUS'] = ($options['hintOnFocus'] ?? false) ? 'Y' : 'N';
		}

		if (isset($options['size']) && is_array($options['size']))
		{
			$data['SETTINGS_DATA']['SIZE'] = [
				'min' => (int)($options['size']['min'] ?? 0),
				'max' => (int)($options['size']['max'] ?? 0),
			];
		}

		$data['REQUIRED'] = $options['required'] ? 'Y' : 'N';
		$data['MULTIPLE'] = $options['multiple'] && $multipleOriginal ? 'Y' : 'N';

		if($data['TYPE'] == 'section' || $data['TYPE'] == 'page')
		{
			$data['REQUIRED'] = 'N';
			$data['MULTIPLE'] = 'N';
		}
		else
		{
			$data['PLACEHOLDER'] = $options['PLACEHOLDER'];
		}

		if(isset($data['VALUE_TYPE']) && isset($field['VALUE_TYPE_ORIGINAL']))
		{
			$isValueTypeExisted = false;
			foreach($field['VALUE_TYPE_ORIGINAL'] as $valueTypeItem)
			{
				if($valueTypeItem['ID'] == $field['VALUE_TYPE'])
				{
					$isValueTypeExisted = true;
					break;
				}
			}

			if($isValueTypeExisted)
			{
				$data['VALUE_TYPE'] = $field['VALUE_TYPE'];
			}
		}

		if(is_array($data['ITEMS']))
		{
			foreach($data['ITEMS'] as $itemId => $item)
			{
				$unknownItemKeys = array_diff(
					array_keys($item),
					array('ID', 'VALUE', 'PRICE', 'CUSTOM_PRICE', 'DISCOUNT', 'NAME', 'SELECTED', 'DISABLED')
				);
				if(count($unknownItemKeys) == 0)
				{
					continue;
				}

				foreach($unknownItemKeys as $unknownItemKey)
				{
					unset($data['ITEMS'][$itemId][$unknownItemKey]);
				}
			}

			$data['ITEMS'] = array_values($data['ITEMS']);
		}

		if($data['CAPTION'] === ($field['ENTITY_FIELD_CAPTION'] ?? null))
		{
			$data['CAPTION'] = '';
		}

		if(is_array($field['SETTINGS_DATA']))
		{
			$data['SETTINGS_DATA'] = $field['SETTINGS_DATA'];
		}

		if($data['TYPE'] === WebForm\Internals\FieldTable::TYPE_ENUM_RESOURCEBOOKING)
		{
			$settingsData = $options['booking']['settings_data'] ?? [];
			$data['SETTINGS_DATA'] = $settingsData;
		}

		if($data['TYPE'] === WebForm\Internals\FieldTable::TYPE_ENUM_RQ)
		{
			$data['SETTINGS_DATA'] = WebForm\Requisite::instance()
				->convertSettingsToOptions($options['requisite'] ?? [])
			;
		}

		return $data;
	}

	private function getTabletFormattedFieldItems(array $options)
	{
		$result = [];
		if (empty($options['items']))
		{
			return $result;
		}

		foreach ($options['items'] as $item)
		{
			$data = [
				'ID' => $item['value'],
				'VALUE' => $item['label'],
				'SELECTED' => ($item['selected'] ?? false) ? 'Y' : 'N',
			];

			$disabled = $item['disabled'] ?? false;
			if ($disabled)
			{
				$data['DISABLED'] = $disabled ? 'Y' : 'N';
			}

			if ($options['type'] === 'product')
			{
				$data += [
					'PRICE' => $item['price'],
					'CUSTOM_PRICE' => ($item['changeablePrice'] && WebForm\Manager::isOrdersAvailable()) ? 'Y' : 'N',
					'DISCOUNT' => $item['discount'],
					'QUANTITY' => [
						'min' => (int)($item['quantity']['min'] ?? 0),
						'max' => (int)($item['quantity']['min'] ?? null),
						'step' => (int)($item['quantity']['min'] ?? 1),
						'unit' => ($item['value'] && is_numeric($item['value']))
							? ($item['quantity']['unit'] ?? null)
							: null,
					],
				];
			}

			$result[] = $data;
		}

		return $result;
	}

	private static function isFieldFileImage($name)
	{
		return in_array(
			$name,
			['CONTACT_PHOTO', 'COMPANY_LOGO']
		);
	}
}
