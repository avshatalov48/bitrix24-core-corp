<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main;
use Bitrix\Crm;

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
		return [
			'data' => $config['data'],

			'id' => $this->form->getId(),
			'name' => $formData['NAME'],

			'templateId' => $formData['TEMPLATE_ID'],
			'presetFields' => $this->getPresetFields(),
			'payment' => [
				'use' => $formData['IS_PAY'] === 'Y',
				'payer' => '',
				'systems' => []
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
				],
			],

			'responsible' => [
				'users' => $formData['ASSIGNED_BY_ID'],
				'checkWorkTime' => $formData['ASSIGNED_WORK_TIME'],
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
				'redirectDelay' => $formData['FORM_SETTINGS']['REDIRECT_DELAY']
			],
			'callback' => [
				'use' => $formData['IS_CALLBACK_FORM'] === 'Y',
				'from' => $formData['CALL_FROM'],
				'text' => $formData['CALL_TEXT'],
			],
			'analytics' => $this->getAnalytics(),
			'integration' => $this->getIntegration(),
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

	private function getIntegration()
	{
		$list = [];
		$providers = [];

		if (Crm\Ads\AdsForm::canUse())
		{
			$adTypes = Crm\Ads\AdsForm::getServiceTypes();
			$providerList = Crm\Ads\AdsForm::getProviders();
			$providerIcons = Crm\Ads\AdsForm::getAdsIconMap();
			foreach ($adTypes as $adType)
			{
				if (empty($providerList[$adType]))
				{
					continue;
				}

				$provider = [
					'code' => $adType,
					'title' => Crm\Ads\AdsForm::getServiceTypeName($adType),
					'icon' => $providerIcons[$adType] ?? null,
					'hasAuth' => $providerList[$adType]['HAS_AUTH'] ?? false,
				];

				$providers[] = $provider;

				$links = Crm\Ads\AdsForm::getFormLinks($this->form->getId(), $adType);
				foreach ($links as $link)
				{
					$list[] = [
						'active' => $provider['hasAuth'],
						'providerCode' => $adType,
						'date' => $link['DATE_INSERT'],
						'form' => [
							'id' => $link['ADS_FORM_ID'],
							'title' => $link['ADS_FORM_NAME'],
						],
						'account' => [
							'id' => $link['ADS_ACCOUNT_ID'],
							'name' => $link['ADS_ACCOUNT_NAME'],
						],
					];
				}
			}
		}
		
		return [
			'canUse' => Crm\Ads\AdsForm::canUse(),
			'cases' => $list,
			'providers' => $providers,
		];
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
		$viewTypes = array_keys($this->getEmbedding()['scripts']);
		foreach ($options['embedding']['views'] as $viewType => $view)
		{
			if (!in_array($viewType, $viewTypes))
			{
				continue;
			}

			foreach ($view as $viewKey => $viewValue)
			{
				if (!in_array($viewKey, ['type', 'position', 'delay', 'vertical']))
				{
					continue;
				}
				if (!is_string($viewValue) && !is_integer($viewValue))
				{
					$viewValue = null;
				}

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

			'CAPTCHA_KEY' => $options['captcha']['key'],
			'CAPTCHA_SECRET' => $options['captcha']['secret'],
			'CAPTCHA_VERSION' => 2,

			'AGREEMENT_ID' => null,

			'ENTITY_SCHEME' => (int) $options['document']['scheme'],
			'DUPLICATE_MODE' => $options['document']['duplicateMode'],

			'FORM_SETTINGS' => [
				'DYNAMIC_CATEGORY' => $options['document']['dynamic']['category'] ?? null,
				'DEAL_CATEGORY' => $options['document']['deal']['category'],
				'DEAL_DC_ENABLED' => $options['document']['deal']['duplicatesEnabled'] ? 'Y' : 'N',
				'REDIRECT_DELAY' => $options['result']['redirectDelay'],
				'VIEWS' => $views,
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
}