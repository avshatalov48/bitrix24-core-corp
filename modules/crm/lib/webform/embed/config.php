<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Embed;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;

/**
 * Class Config
 * @package Bitrix\Crm\WebForm\Embed
 */
class Config
{
	/** @var WebForm\Form $form Form. */
	protected $form;

	/** @var array $fields Fields. */
	protected $fields;

	/**
	 * Create config by form ID.
	 *
	 * @param int $formId Form ID.
	 * @return static
	 */
	public static function createById($formId)
	{
		return new static(new Webform\Form($formId));
	}

	/**
	 * Config constructor.
	 *
	 * @param WebForm\Form $form
	 */
	public function __construct(WebForm\Form $form)
	{
		$this->form = $form;
	}

	/**
	 * Convert config to array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$data = $this->form->get();

		return [
			'id' => $data['ID'],
			'sec' => $data['SECURITY_CODE'],
			'lang' => $this->form->getLanguageId(),
			'address' => Main\Web\WebPacker\Builder::getDefaultSiteUri(),
			'views' => $this->getViews(),
			'data' => [
				'design' => $this->getDesign(),
				'title' => $data['CAPTION'],
				'desc' => $this->getDescription(),
				'buttonCaption' => $data['BUTTON_CAPTION'],
				'useSign' => $data['COPYRIGHT_REMOVED'] !== 'Y',
				'date' => [
					'dateFormat' => Main\Context::getCurrent()->getCulture()->getDateFormat(),
					'dateTimeFormat' => Main\Context::getCurrent()->getCulture()->getDateTimeFormat(),
					'sundayFirstly' => Main\Context::getCurrent()->getCulture()->getWeekStart() == 0,
				],
				'currency' => $this->getCurrency(),
				'fields' => $this->getFields(),
				'agreements' => $this->getAgreements(),
				'dependencies' => $this->getDependencies(),
				'recaptcha' => [
					'use' => $this->form->isUsedCaptcha()
				],
			]
		];
	}

	/**
	 * Get views.
	 *
	 * @return array
	 */
	public function getViews()
	{
		$data = $this->form->get();
		return $data['FORM_SETTINGS']['VIEWS'];
	}

	/**
	 * Get design.
	 *
	 * @return array
	 */
	public function getDesign()
	{
		$design = $this->form->getDesignOptions(true);
		unset($design['theme']);
		foreach ($design as $key => $value)
		{
			if (is_array($value))
			{
				$value = array_filter(
					$value,
					function ($v)
					{
						return is_bool($v) ? true : $v <> '';
					}
				);
				if (count($value) > 0)
				{
					continue;
				}
			}
			else
			{
				if ($value <> '')
				{
					continue;
				}
			}

			unset($design[$key]);
		}

		return $design;
	}

	/**
	 * Return true if disabled.
	 *
	 * @return bool
	 */
	public function isDisabled()
	{
		return !$this->form->isActive();
	}

	/**
	 * Get fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if ($this->isDisabled())
		{
			return $this->fields;
		}

		if (empty($this->fields))
		{
			$this->fields = array_map(
				function ($field)
				{
					$options = [];
					$type = $field['type'];
					switch ($type)
					{
						case 'resourcebooking':
							if (!$field['multiple'])
							{
								$options['booking'] = [
									'name' => $field['name'],
									'caption' => $field['caption'],
									'entity_field_name' => $field['entity_field_name'],
									'settings_data' => $field['settings_data'],
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
							$stringType = mb_strtolower($field['entity_field_name']);
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
							return [
								'id' =>  $field['name'],
								'name' => $field['name'],
								'type' => 'layout',
								'label' => $field['caption'],
								'content' => [
									'type' => $type
								]
							];

						default:
							$type = isset(WebForm\Internals\FieldTable::getTypeList()[$type])
								? $type
								:'string';
							break;
					}

					switch ($field['name'])
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

					return $options + [
						'id' =>  $field['name'],
						'name' => $field['name'],
						'type' => $type,
						'label' => $field['caption'],
						'visible' => !$field['hidden'],
						'required' => $field['required'],
						'multiple' => $field['multiple'],
						'placeholder' => $field['placeholder'],
						'value' => $field['value'],
						'items' => $this->getFieldItems($field),
						'bigPic' => !empty($field['settings_data']['BIG_PIC'])
							? $field['settings_data']['BIG_PIC'] === 'Y'
							: false,
					];
				},
				$this->form->getFieldsMap()
			);
		}

		return $this->fields;
	}

	protected function getFieldItems(array $field)
	{
		$items = is_array($field['items']) ? $field['items'] : [];
		switch ($field['type'])
		{
			case 'product':
				$items = array_map(
					function ($item) use ($field)
					{
						$data = [
							'label' => $item['title'],
							'value' => $item['value'],
							'selected' => false,
							'price' => $item['price'],
							'discount' => $item['discount'],
							'pics' => [],
							'quantity' => [],
							'changeablePrice' => !empty($item['changeablePrice']),
							//quantity: {min: 2, max: 50, step: 2, unit: 'רע.'},
							//'discount' => isset($item['discount']) ? $item['discount'] : 0,
						];

						if ($field['settings_data']['QUANTITY_MIN'])
						{
							$data['quantity']['min'] = $field['settings_data']['QUANTITY_MIN'];
						}
						if ($field['settings_data']['QUANTITY_MAX'])
						{
							$data['quantity']['max'] = $field['settings_data']['QUANTITY_MAX'];
						}
						if ($field['settings_data']['QUANTITY_STEP'])
						{
							$data['quantity']['step'] = $field['settings_data']['QUANTITY_STEP'];
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
				return array_map(
					function ($item)
					{
						return [
							'label' => $item['title'],
							'value' => $item['value'],
							'selected' => false,
							//'discount' => isset($item['discount']) ? $item['discount'] : 0,
							//'pics' => [],
							//quantity: {min: 2, max: 50, step: 2, unit: 'רע.'},
						];
					},
					$items
				);
		}
	}

	/**
	 * Get dependencies.
	 *
	 * @return array
	 */
	public function getDependencies()
	{
		$deps = $this->form->get()['DEPENDENCIES'];
		if (empty($deps))
		{
			return [];
		}

		$fieldsBySection = [];
		$currentSection = false;
		foreach ($this->getFields() as $field)
		{
			if ($field['type'] === 'layout' && $field['content']['type'] === 'section')
			{
				$currentSection = $field['name'];
			}
			elseif ($field['type'] === 'page')
			{
				$currentSection = null;
			}

			if($currentSection)
			{
				$fieldsBySection[$currentSection][] = $field['name'];
			}
		}

		$list = [];
		foreach ($deps as $dep)
		{
			$condition = [
				'target' => $dep['IF_FIELD_CODE'],
				'event' => $dep['IF_ACTION'],
				'value' => $dep['IF_VALUE'],
				'operation' => $dep['IF_VALUE_OPERATION'],
			];

			if (!empty($fieldsBySection[$dep['DO_FIELD_CODE']]))
			{
				$fieldNames = $fieldsBySection[$dep['DO_FIELD_CODE']];
			}
			else
			{
				$fieldNames = [$dep['DO_FIELD_CODE']];
			}

			foreach ($fieldNames as $fieldName)
			{
				$action = [
					'target' => $fieldName,
					'type' => $dep['DO_ACTION'],
					'value' => $dep['DO_VALUE'],
				];

				$list[] = [
					'condition' => $condition,
					'action' => $action,
				];
			}
		}

		return $list;
	}

	/**
	 * Get agreements.
	 *
	 * @return array
	 */
	public function getAgreements()
	{
		$result = [];

		if ($this->isDisabled())
		{
			return $result;
		}

		$data = $this->form->get();
		if ($data['USE_LICENCE'] !== 'Y')
		{
			return $result;
		}

		$agreements = [];
		if ($data['AGREEMENT_ID'])
		{
			$agreements[] = [
				'ID' => $data['AGREEMENT_ID'],
				'CHECKED' => $data['LICENCE_BUTTON_IS_CHECKED'] === 'Y',
			];
		}
		$agreementRows = WebForm\Internals\AgreementTable::getList([
			'select' => ['AGREEMENT_ID', 'CHECKED'],
			'filter' => ['=FORM_ID' => $this->form->getId()]
		]);
		foreach ($agreementRows as $agreementRow)
		{
			$agreements[] = [
				'ID' => $agreementRow['AGREEMENT_ID'],
				'CHECKED' => $agreementRow['CHECKED'] === 'Y',
			];
		}

		if (empty($agreements))
		{
			return $result;
		}

		$replace = array(
			'button_caption' => $data['BUTTON_CAPTION'],
			'fields' => array_column($this->getFields(), 'label')
		);

		foreach ($agreements as $agreementData)
		{
			$agreement = new Main\UserConsent\Agreement($agreementData['ID'], $replace);
			if (!$agreement->isActive() || !$agreement->isExist())
			{
				continue;
			}

			$name = "AGREEMENT_" . $agreementData['ID'];
			$result[] = [
				'id' => $name,
				'name' => $name,
				'label' => $agreement->getLabel(),
				'value' => 'Y',
				'required' => true,
				'checked' => $agreementData['CHECKED'],
				'content' => [
					'title' => $agreement->getTitle(),
					'text' => $agreement->getText(true),
					'url' => $agreement->getUrl(),
				],
			];
		}

		return $result;
	}

	/**
	 * Get Currency.
	 *
	 * @return array
	 */
	public function getCurrency()
	{
		$parameters = \CCrmCurrency::GetCurrencyFormatParams($this->form->getCurrencyId());
		if(!is_array($parameters))
		{
			$result = [
				'code' => $this->form->getCurrencyId(),
				'title' => $this->form->getCurrencyId(),
				'format' => '# ' . $this->form->getCurrencyId(),
				/*
				'DEC_POINT' => '.',
				'DECIMALS' => 2,
				'THOUSANDS_SEP' => ' ',
				*/
			];
		}
		else
		{
			$result = [
				'code' => $parameters['CURRENCY'],
				'title' => $parameters['FULL_NAME'],
				'format' => $parameters['FORMAT_STRING'],
				/*
				'DEC_POINT' => $parameters['DEC_POINT'],
				'DECIMALS' => $parameters['DECIMALS'],
				'THOUSANDS_SEP' => $parameters['THOUSANDS_SEP'],
				*/
			];
		}

		return $result;
	}

	/**
	 * Get description.
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return (new \CTextParser())->convertText($this->form->get()['DESCRIPTION']);
	}
}
