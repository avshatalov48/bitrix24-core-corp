<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Options;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;

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
					if (!$field['multiple'])
					{
						$options['booking'] = [
							'name' => $field['CODE'],
							'caption' => $field['CAPTION'],
							'entity_field_name' => $data['ENTITY_FIELD_NAME'],
							'settings_data' => $field['SETTINGS_DATA'],
						];
					}
					break;
				case 'checkbox':
					if (!$field['multiple'])
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

			$this->cachedResult[] = $options + [
				'type' => $type,
				'placeholder' => $field['PLACEHOLDER'],
				'value' => $field['VALUE'],
				'items' => $this->getFieldItems($field),
				'bigPic' => !empty($field['SETTINGS_DATA']['BIG_PIC'])
					? $field['SETTINGS_DATA']['BIG_PIC'] === 'Y'
					: false,
			];
		}

		return $this->cachedResult;
	}

	protected function getFieldItems(array $field)
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

						$product = \CCrmProduct::getByID($item['value']);
						if (!$product)
						{
							return $data;
						}

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

						$pics = [];
						if ($product['DETAIL_PICTURE'] && isset($item['bigPic']) && $item['bigPic'])
						{
							$pics[] = $product['DETAIL_PICTURE'];
						}
						elseif (!$product['PREVIEW_PICTURE'] && $product['DETAIL_PICTURE'])
						{
							$pics[] = $product['DETAIL_PICTURE'];
						}
						elseif ($product['PREVIEW_PICTURE'])
						{
							$pics[] = $product['PREVIEW_PICTURE'];
						}

						if (!empty($pics))
						{
							foreach ($pics as $fileId)
							{
								$file = \CFile::getByID($fileId)->fetch();
								if (!$file)
								{
									continue;
								}
								$uri = $file['~src'];
								if (empty($uri))
								{
									$uri = Main\Web\WebPacker\Builder::getDefaultSiteUri() . \CFile::GetFileSRC($file);
								}

								$data['pics'][] = $uri;
							}
						}

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
				$result = array_map(
					function ($item)
					{
						$result = [
							'label' => $item['VALUE'],
							'value' => $item['ID'],
							'selected' => ($item['SELECTED'] ?? 'N') === 'Y',
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

		return [
			'id' => $field['ID'] ?? null,
			'entityId' => \CCrmOwnerType::resolveID($data['ENTITY_NAME']),
			'entityName' => $data['ENTITY_NAME'] ?? null,
			'name' => $data['ENTITY_FIELD_NAME'] ?? null,
			'types' => $types,
			'hasLabel' => $hasLabel,
			'hasPlaceholder' => $isCommonStringType,
			'hasStringDefaultValue' => $isCommonStringType,
			'valueTypes' => $valueTypes,
			'canBeMultiple' => $data['MULTIPLE_ORIGINAL'] ?? $isValuableType,
			'canBeRequired' => $isValuableType,
			'supportListableItems' => $hasListableItems,
			'supportCustomItems' => $field['TYPE'] === 'product',
			'items' => $items,
			'editable' => [
				'valueType' => $field['VALUE_TYPE'],
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

		$data = $this->getTabletFormattedField([
			'name' => $options['name'],
			'sort' => (count($this->form->getFields()) + 1) * 10,
		] + $options);
		$this->form->merge(['FIELDS' => array_merge(
			$this->form->getFields(),
			[$data]
		)]);
	}

	public static function filterFieldOptions(array $options)
	{
		if (empty($options['name']))
		{
			return null;
		}

		$field = self::getFieldByName($options['name']);

		return $options;
	}

	public static function getFieldByName($name)
	{
		$field = self::$fields[$name] ?? null;

		return $field;
	}

	private function getTabletFieldType(array $options)
	{
		$type = $options['type'];
		switch ($type)
		{
			case 'phone':
			case 'email':
			case 'string':
			case 'page':
			case 'bool':
			case 'money':
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

		$type = $this->getTabletFieldType($options);
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

		$data['REQUIRED'] = $options['required'] ? 'Y' : 'N';
		$data['MULTIPLE'] = $options['multiple'] && $field['MULTIPLE_ORIGINAL'] ? 'Y' : 'N';

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
						'min' => (int) $item['quantity']['min'] ?? 0,
						'max' => (int) $item['quantity']['min'] ?? null,
						'step' => (int) $item['quantity']['min'] ?? 1,
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
}
