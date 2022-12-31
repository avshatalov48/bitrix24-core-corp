<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main;

/**
 * Class Options
 * @package Bitrix\Crm\WebForm
 */
class Options
{
	/** @var Form $form Form. */
	private $form;

	/** @var Embed\Config $config Config. */
	private $config;

	/**
	 * Create.
	 *
	 * @param int|null $formId Form ID.
	 * @return static
	 */
	public static function create($formId = null)
	{
		return new static(new Form($formId));
	}

	/**
	 * Merge options.
	 *
	 * @param array $options Array of options.
	 * @return $this
	 */
	public static function createFromArray(array $options = [])
	{
		$instance = static::create($options['id'] ?? null);
		$instance->merge($options);
		return $instance;
	}

	/**
	 * Options constructor.
	 * @param Form $form Form.
	 */
	public function __construct(Form $form)
	{
		$this->form = $form;
		$this->config = (new Embed\Config($this->form))
			->setEditMode(true);
	}

	/**
	 * Merge options.
	 *
	 * @param array $options Array of options.
	 * @return $this
	 */
	public function merge(array $options = [])
	{
		$this->form->merge($this->convertToFormOptions($options));
		$this->form->getIntegration()->setData($options['integration'] ?? null);
		$this->config->setDataFromArray($options['data'] ?? []);
		return $this;
	}

	/**
	 * Save.
	 *
	 * @return Main\Result
	 */
	public function save()
	{
		$result = new Main\Result();
		$this->form->save();

		foreach ($this->form->getErrors() as $errorMessage)
		{
			$result->addError(new Main\Error($errorMessage));
		}

		return $result;
	}

	/**
	 * Get options as array.
	 *
	 * @return array
	 */
	public function getArray()
	{
		$formData = $this->form->get();
		$config = $this->config->toArray();
		$dynamicCategory = $formData['FORM_SETTINGS']['DYNAMIC_CATEGORY'] ?? null;
		$dynamicDcEnabled = ($formData['FORM_SETTINGS']['DYNAMIC_DC_ENABLED'] ?? 'N') === 'Y';
		$refill = ($formData['FORM_SETTINGS']['REFILL']['ACTIVE'] ?? 'N') === 'Y';

		return [
			'data' => $config['data'],

			'id' => $this->form->getId(),
			'name' => $formData['NAME'],

			'templateId' => $formData['TEMPLATE_ID'],
			'presetFields' => $this->getPresetFields(),
			'payment' => [
				'use' => $formData['IS_PAY'] === 'Y',
				'payer' => '',
				'disabledSystems' => $this->convertPaySystemIds(
					$formData['FORM_SETTINGS']['DISABLED_PAY_SYSTEMS'] ?? []
				),
			],
			'captcha' => [
				'key' => ReCaptcha::getKey(2),
				'secret' => ReCaptcha::getSecret(2),
				'hasDefaults' => ReCaptcha::getDefaultKey(2) && ReCaptcha::getDefaultSecret(2)
			],
			'document' => [
				'scheme' => $formData['ENTITY_SCHEME'],
				'duplicateMode' => $formData['DUPLICATE_MODE'],
				'deal' => [
					'category' => $formData['FORM_SETTINGS']['DEAL_CATEGORY'],
					'duplicatesEnabled' => $formData['FORM_SETTINGS']['DEAL_DC_ENABLED'] === 'Y',
				],
				'dynamic' => [
					'category' => $dynamicCategory === null ? null : (int)$dynamicCategory,
					'duplicatesEnabled' => $dynamicDcEnabled,
				],
			],

			'responsible' => [
				'users' => $formData['ASSIGNED_BY_ID'],
				'checkWorkTime' => $formData['ASSIGNED_WORK_TIME'] === 'Y',
				'supportWorkTime' => ResponsibleQueue::isSupportedWorkTime(),
			],
			'agreements' => [
				'use' => $formData['USE_LICENCE'] === 'Y',
			],
			'result' => [
				'success' => [
					'url' => $formData['RESULT_SUCCESS_URL'],
					'text' => $formData['RESULT_SUCCESS_TEXT'],
				],
				'failure' => [
					'url' => $formData['RESULT_FAILURE_URL'],
					'text' => $formData['RESULT_FAILURE_TEXT'],
				],
				'redirectDelay' => $formData['FORM_SETTINGS']['REDIRECT_DELAY'],
				'refill' => [
					'caption' => $refill ? $formData['FORM_SETTINGS']['REFILL']['CAPTION'] : '',
					'active' => $refill
				]
			],
			'callback' => [
				'use' => $formData['IS_CALLBACK_FORM'] === 'Y',
				'from' => $formData['CALL_FROM'],
				'text' => $formData['CALL_TEXT'],
			],
			'whatsapp' => [
				'use' => $formData['IS_WHATSAPP_FORM'] === 'Y',
			],
			'analytics' => $this->getAnalytics(),
			'integration' => $this->form->getIntegration()->toArray(),
			'embedding' => $this->getEmbedding(),
		];
	}

	/**
	 * Get config.
	 *
	 * @return Embed\Config
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Get form.
	 *
	 * @return Form
	 */
	public function getForm()
	{
		return $this->form;
	}

	private function getAnalytics()
	{
		$result = $this->form->getExternalAnalyticsData();
		$result['STEPS'] = array_map(
			function ($item)
			{
				return array_change_key_case($item);
			},
			$result['STEPS']
		);
		return array_change_key_case($result);
	}

	private function getEmbedding()
	{
		if (!$this->form->getId())
		{
			return [];
		}

		$scripts = [];
		foreach (Script::getListContext($this->form->get(), []) as $type => $script)
		{
			$type = strtolower($type);
			$scripts[$type] = [
				'text' => $script['text'],
			];
		}
		return [
			'link' => Script::getUrlContext($this->form->get()),
			'scripts' => $scripts,
			'views' => $this->config->toArray()['views'] ?? [],
		];
	}

	private function getPresetFields()
	{
		$fields = [];
		foreach ($this->form->getPresetFields() as $field)
		{
			$fields[] = [
				'entityName' => $field['ENTITY_NAME'],
				'fieldName' => $field['FIELD_NAME'],
				'value' => $field['VALUE'],
			];
		}

		return $fields;
	}

	private function convertToFormOptions(array $options)
	{
		$views = [];
		$viewTypes = array_keys($this->getEmbedding()['scripts'] ?? []);
		$availableOptions = self::getViewOptions();
		foreach ($options['embedding']['views'] as $viewType => $view)
		{
			if (!in_array($viewType, $viewTypes))
			{
				continue;
			}

			$typeOptions = $availableOptions[$viewType] ?? [];

			foreach ($view as $viewKey => $viewValue)
			{
				if (!self::checkViewOptions($viewKey, $viewValue, $typeOptions))
				{
					continue;
				}

				self::filterViewOptionValues($viewValue);
				$views[$viewType][$viewKey] = $viewValue;
			}
		}

		return [
			'NAME' => $options['name'],
			'TEMPLATE_ID' => $options['templateId'],
			'PRESET_FIELDS' => array_map(
				function ($field)
				{
					return [
						'ENTITY_NAME' => $field['entityName'],
						'FIELD_NAME' => $field['fieldName'],
						'VALUE' => $field['value'],
					];
				},
				$options['presetFields'] ?? []
			),
			'IS_PAY' => $options['payment']['use'] ? 'Y' : 'N',

			'AGREEMENT_ID' => null,

			'ENTITY_SCHEME' => (int) $options['document']['scheme'],
			'DUPLICATE_MODE' => $options['document']['duplicateMode'],

			'FORM_SETTINGS' => [
				'DYNAMIC_CATEGORY' => $options['document']['dynamic']['category'] ?? null,
				'DYNAMIC_DC_ENABLED' => ($options['document']['dynamic']['duplicatesEnabled'] ?? false) ? 'Y' : 'N',
				'DEAL_CATEGORY' => $options['document']['deal']['category'],
				'DEAL_DC_ENABLED' => $options['document']['deal']['duplicatesEnabled'] ? 'Y' : 'N',
				'REDIRECT_DELAY' => $options['result']['redirectDelay'],
				'REFILL' => [
						'ACTIVE' => ($options['result']['refill']['active'] ?? false) ? 'Y' : 'N',
						'CAPTION' => $options['result']['refill']['caption'] ?? '',
					],
				'VIEWS' => $views,
				'DISABLED_PAY_SYSTEMS' =>	$this->convertPaySystemIds(
					$options['payment']['disabledSystems'] ?? []
				),
			],

			'ASSIGNED_BY_ID' => array_filter(
				array_map(
					'intval',
					$options['responsible']['users'] ?? []
				)
			),
			'ASSIGNED_WORK_TIME' => ($options['responsible']['checkWorkTime'] ?? false) ? 'Y' : 'N',

			'USE_LICENCE' => $options['agreements']['use'] ? 'Y' : 'N',

			'RESULT_SUCCESS_URL' => $options['result']['success']['url'],
			'RESULT_SUCCESS_TEXT' => $options['result']['success']['text'],
			'RESULT_FAILURE_URL' => $options['result']['failure']['url'],
			'RESULT_FAILURE_TEXT' => $options['result']['failure']['text'],

			'IS_CALLBACK_FORM' => $options['callback']['use'] ? 'Y' : 'N',
			'CALL_FROM' => $options['callback']['from'],
			'CALL_TEXT' => $options['callback']['text'],
		];
	}

	/**
	 * @param array $paySystemsIds
	 * @return int[]
	 */
	private function convertPaySystemIds(array $paySystemsIds): array
	{
		$result = [];
		foreach ($paySystemsIds as $paySystemsId)
		{
			$result[] = (int)$paySystemsId;
		}

		return $result;
	}

	public static function getViewOptions(): array
	{
		return [
			'inline' => [],
			'auto' => ['type', 'position', 'vertical', 'delay'],
			'click' => [
				'type', 'position', 'vertical',
				'button' => [
					'use', // 1|0
					'text',
					'font', // modern|classic|elegant
					'align', // left|right|center|inline
					'plain', // 1|0, link-mode
					'rounded', // 1|0 border-radius
					'outlined', // 1|0 without background
					'decoration', // '', 'dotted', 'solid'
					'color' => [ // hexA
						'text',
						'textHover',
						'background',
						'backgroundHover',
					],
				],
			],
		];
	}

	private static function checkViewOptions(string $key, $value, array $options): bool
	{
		if (is_array($value))
		{
			foreach ($value as $innerKey => $innerValue)
			{
				if (!is_array($options[$key]) || !self::checkViewOptions($innerKey, $innerValue, $options[$key]))
				{
					return false;
				}
			}

			return true;
		}

		return self::checkViewOption($key, $options);
	}

	private static function checkViewOption(string $key, array $options): bool
	{
		return in_array($key, $options, true);
	}

	private static function filterViewOptionValues(&$value): void
	{
		if (is_array($value))
		{
			foreach ($value as $innerValue)
			{
				self::filterViewOptionValues($innerValue);
			}
		}
		elseif (!is_string($value) && !is_int($value))
		{
			$value = null;
		}
	}
}
