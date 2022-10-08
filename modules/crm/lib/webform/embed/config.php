<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Embed;

use Bitrix\Main;
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
	protected $fields = [];

	/** @var WebForm\Options\Fields $fieldsConfig Fields config. */
	protected $fieldsConfig;

	/** @var WebForm\Options\Deps $deps Dep config. */
	protected $deps;

	/** @var bool $editMode Edit mode with special using fields like `id`.  */
	protected $editMode = false;

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
		$this->fieldsConfig = new WebForm\Options\Fields($this->form);
		$this->deps = new WebForm\Options\Deps($this->form);
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
		$this->fieldsConfig->setEditMode($this->editMode);
		return $this;
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
				'language' => $this->form->getLanguageId(),
				'design' => $this->getDesign(),
				'title' => $data['CAPTION'],
				'desc' => $this->getDescription(),
				'buttonCaption' => $data['BUTTON_CAPTION'],
				'useSign' => $data['COPYRIGHT_REMOVED'] !== 'Y' || !WebForm\Form::canRemoveCopyright(),
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

	public function setDataFromArray(array $data)
	{
		$this->fieldsConfig->setData($data['fields'] ?? []);
		$this->deps->setData($data['dependencies'] ?? []);
		$parameters = [
			'CAPTION' => $data['title'],
			'DESCRIPTION' => $data['desc'],
			'BUTTON_CAPTION' => $data['buttonCaption'],
			'USE_CAPTCHA' => $data['recaptcha']['use'] ? 'Y' : 'N',

			//'FIELDS' => [],
			//'DEPENDENCIES' => [],

			'AGREEMENTS' => array_map(
				function ($agreement)
				{
					return [
						'AGREEMENT_ID' => $agreement['id'],
						'CHECKED' => $agreement['checked'] ? 'Y' : 'N',
						'REQUIRED' => $agreement['required'] ? 'Y' : 'N',
					];
				},
				self::filterAgreements($data['agreements'] ?? [])
			),
			'COPYRIGHT_REMOVED' => (!$data['useSign'] && WebForm\Form::canRemoveCopyright()) ? 'Y' : 'N',
			'LANGUAGE_ID' => $data['language'],
		];
		$this->form->merge($parameters);
		if (!empty($data['design']) && is_array($data['design']))
		{
			$this->form->setDesignOptions($data['design']);
		}
		return $this;
	}

	public function appendAgreement($id)
	{
		$id = (int) $id;
		if (!$id)
		{
			return;
		}

		$agreements = $this->form->get()['AGREEMENTS'];
		$agreements[] = [
			'AGREEMENT_ID' => $id,
			'CHECKED' => true,
			'REQUIRED' => true,
		];
		$this->form->merge(['AGREEMENTS' => $agreements]);
	}

	public function clearFields()
	{
		$this->fieldsConfig->clear();
	}

	public function appendField(array $options)
	{
		if (!empty($options['type']))
		{
			$isSupportedType = in_array(
				$options['type'],
				array_merge(
					array_keys(WebForm\Helper::getFieldNonValueTypes()),
					['product']
				)
			);
			if (!$isSupportedType)
			{
				return; //todo: need ErrorCollection
			}

			$options += [
				'name' => $options['type'] . '_' . mt_rand(1000000, 9999999),
				'label' => WebForm\Internals\FieldTable::getTypeList()[$options['type']]
			];
			switch ($options['type'])
			{
				case 'product':
					/**
					 * @var \CIBlockResult $iblockItems
					 */
					$iblockItems = \CIBlockElement::GetList(
						["SORT"=>"DESC"],
						[
							'=IBLOCK_ID' => \CCrmCatalog::EnsureDefaultExists(),
							'!==PREVIEW_PICTURE' => null,
							'!==DETAIL_PICTURE' => null,
						],
						false,
						[
							'nTopCount' => 3
						],
					);
					$items = [];

					while ($item = $iblockItems->Fetch())
					{
						$items[] = [
							'value' => $item['ID'],
							'label' => $item['NAME'],
							'price' => \CCrmProduct::getPrice($item['ID'])['PRICE'],
						];
					}
					$options += [
						'bigPic' => true,
						'multiple' => true,
						'items' => $items,
					];
					break;
				default:
					$options += [
						'type' => 'layout',
						'content' => ['type' => $options['type']],
					] + $options;
			}
		}

		$this->fieldsConfig->append($options);
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
		if (!$this->editMode)
		{
			unset($design['theme']);
		}

		foreach ($design as $key => $value)
		{
			if (is_array($value))
			{
				$value = array_filter(
					$value,
					function ($v)
					{
						return is_bool($v) ? true : mb_strlen($v) > 0;
					}
				);
				if (count($value) > 0)
				{
					continue;
				}
			}
			else
			{
				if (mb_strlen($value) > 0)
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
		if (!$this->editMode && $this->isDisabled())
		{
			return $this->fields;
		}

		$this->fields = $this->fieldsConfig->toArray();

		return $this->fields;
	}

	/**
	 * Get dependencies.
	 *
	 * @return array
	 */
	public function getDependencies()
	{
		return $this->deps->toArray();
	}

	/**
	 * Get agreements.
	 *
	 * @return array
	 */
	public function getAgreements()
	{
		$result = [];

		if (!$this->editMode && $this->isDisabled())
		{
			return $result;
		}

		$data = $this->form->get();
		if (!$this->editMode && $data['USE_LICENCE'] !== 'Y')
		{
			return $result;
		}

		$agreements = [];
		if ($data['AGREEMENT_ID'])
		{
			$agreements[$data['AGREEMENT_ID']] = [
				'ID' => $data['AGREEMENT_ID'],
				'CHECKED' => $data['LICENCE_BUTTON_IS_CHECKED'] === 'Y',
				'REQUIRED' => true,
			];
		}

		foreach ($data['AGREEMENTS'] as $agreementRow)
		{
			$agreements[$agreementRow['AGREEMENT_ID']] = [
				'ID' => $agreementRow['AGREEMENT_ID'],
				'CHECKED' => $agreementRow['CHECKED'] === 'Y',
				'REQUIRED' => $agreementRow['REQUIRED'] === 'Y',
			];
		}
		$agreements = array_values($agreements);

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

			$content = [
				'title' => $agreement->getTitle(),
				'url' => $agreement->getUrl(),
			];
			if ($agreement->isAgreementTextHtml())
			{
				$content['html'] = $agreement->getHtml();
			}
			else
			{
				$content['text'] = $agreement->getText(true);
			}

			$name = 'AGREEMENT_' . $agreementData['ID'];
			$result[] = [
				'id' => $this->editMode ? $agreementData['ID'] : $name,
				'name' => $name,
				'label' => $agreement->getLabel(),
				'value' => 'Y',
				'required' => $agreementData['REQUIRED'],
				'checked' => $agreementData['CHECKED'],
				'content' => $content,
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

	private static function filterAgreements(array $list)
	{
		$result = [];
		foreach ($list as $item)
		{
			if (empty($item) || empty($item['id']) || !is_array($item))
			{
				continue;
			}

			$id = (int) $item['id'];
			if (!$id)
			{
				continue;
			}

			$result[] = [
				'id' => $id,
				'checked' => (bool) ($item['checked'] ?? true),
				'required' => (bool) ($item['required'] ?? true),
			];
		}

		return $result;
	}

	private static function filterFields()
	{

	}

	private static function filterDependencies(array $deps, array $fields)
	{
		$dict = WebForm\Options\Dictionary::instance()->getDeps();
		$actionTypes = array_column($dict['action']['types'], 'id');
		$conditionEvents = array_column($dict['condition']['events'], 'id');
		$conditionOperations = array_column($dict['condition']['operations'], 'id');
		$conditionOperations[] = '<>';

		$result = [];
		foreach ($deps as $dep)
		{
			if (!is_array($dep))
			{
				continue;
			}

			$condition = $dep['condition'] ?? null;
			$action = $dep['action'] ?? null;
			if (!$condition || !$action)
			{
				continue;
			}

			// TODO: $condition['target'] check existed in fields
			$condition['event'] = $condition['event'] ?? null;
			if (!$condition['event'] || !in_array($condition['event'], $conditionEvents))
			{
				$condition['event'] = $conditionEvents[0];
			}
			$condition['operation'] = $condition['operation'] ?? null;
			if (!$condition['operation'] || !in_array($condition['operation'], $conditionOperations))
			{
				$condition['operation'] = $conditionOperations[0];
			}
			$condition['value'] = $condition['value'] ?? null;

			// TODO: $action['target'] check existed in fields
			$action['type'] = $action['type'] ?? null;
			if (!$action['type'] || !in_array($action['type'], $actionTypes))
			{
				$action['type'] = $actionTypes[0];
			}
			$action['value'] = $action['value'] ?? null;

			$result[] = [
				'condition' => $condition,
				'action' => $action,
			];
		}

		return $result;
	}
}
