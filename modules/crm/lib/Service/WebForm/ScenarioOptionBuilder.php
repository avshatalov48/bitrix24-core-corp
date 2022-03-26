<?php

namespace Bitrix\Crm\Service\WebForm;

use Bitrix\Crm\Service\WebForm\Scenario\BaseBuilder;
use Bitrix\Crm\Service\WebForm\Scenario\BaseScenario;
use Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario\DependencyAction;
use Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario\DependencyCondition;
use Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario\DependencyItem;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Agreement;

class ScenarioOptionBuilder extends BaseBuilder
{
	static $agreements;

	/**
	 * @var bool
	 */
	private $supportPreset = false;

	private static function getAgreements(): array
	{
		if (!self::$agreements)
		{
			self::$agreements = array_reverse(array_keys(Agreement::getActiveList()));
		}

		return self::$agreements;
	}

	public function __construct()
	{
		$this->prepared = [];
		$this->prepared['data'] = [];
		$this->prepared['data']['buttonCaption'] = Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_BUTTON');
	}

	/**
	 * @return $this
	 */
	public function addPresetSupport(): ScenarioOptionBuilder
	{
		$this->supportPreset = true;
		return $this;
	}
	/**
	 * Add agreements to the prepared object.
	 * @param bool $use
	 * @return $this
	 */
	public function addAgreements(bool $use = false): ScenarioOptionBuilder
	{
		if (!$this->prepared['agreements'])
		{
			$this->prepared['agreements'] = [];
		}

		$this->prepared['agreements']['use'] = $use;

		$agreements = self::getAgreements();
		$this->prepared['data']['agreements'][0]['id'] = $agreements[0];
		return $this;
	}

	/**
	 * Add captcha to the prepared object.
	 * @param bool $use
	 * @return $this
	 */
	public function addCaptcha(bool $use = false): ScenarioOptionBuilder
	{
		if (!$this->prepared['data']['captcha'])
		{
			$this->prepared['data']['captcha'] = [];
		}
		$this->prepared['data']['captcha']['use'] = $use;
		return $this;
	}

	/**
	 * @param bool $use
	 * @return $this
	 */
	public function usePayment(bool $use = false): ScenarioOptionBuilder
	{
		if (!$this->prepared['payment'])
		{
			$this->prepared['payment'] = [];
		}
		$this->prepared['payment']['use'] = $use;
		$this->prepared['payment']['payer'] = "";
		$this->prepared['payment']['systems'] = [];
		return $this;
	}

	/**
	 * Add recaptcha to the prepared object.
	 * @param bool $use
	 * @return $this
	 */
	public function addRecaptcha(bool $use = false): ScenarioOptionBuilder
	{
		if (!$this->prepared['data']['recaptcha'])
		{
			$this->prepared['data']['recaptcha'] = [];
		}
		$this->prepared['data']['recaptcha']['use'] = $use;
		return $this;
	}

	/**
	 * Add field to be displayed on the web from page.
	 * @param array $field
	 * @return $this
	 */
	public function addField(array $field): ScenarioOptionBuilder
	{
		if (!$this->prepared['data']['fields'])
		{
			$this->prepared['data']['fields'] = [];
		}

		$this->prepared['data']['fields'][]  = $field;
		return $this;
	}

	public function addDependency(DependencyItem $dependencyItem): ScenarioOptionBuilder
	{

		if (!$this->prepared['data']['dependencies'])
		{
			$this->prepared['data']['dependencies'] = [];
		}

		$this->prepared['data']['dependencies'][] = $dependencyItem->toArray();
		return $this;
	}

	/**
	 * Add sending button caption
	 *
	 * @param string|null $caption
	 * @return $this
	 */
	public function setButtonCaption(?string $caption): ScenarioOptionBuilder
	{
		$this->prepared['data']['buttonCaption']  = $caption ?? '';
		return $this;
	}

	/**
	 * Multiple adding fields to be displayed on the web form page.
	 * @param array $fields
	 * @return $this
	 */
	public function addFields(array $fields = []): ScenarioOptionBuilder
	{
		foreach ($fields as $field)
		{
			$this->addField($field);
		}

		return $this;
	}

	/**
	 * This block determining web form behaviour after it will be sent.
	 *
	 * @param string|null $successCaption
	 * @param string|null $failureCaption
	 * @param bool $refill
	 * @return $this
	 */
	public function addResult(
		?string $successCaption,
		?string $failureCaption,
		bool $refill = false
	): ScenarioOptionBuilder
	{
		if (!$this->prepared['result'])
		{
			$this->prepared['result'] = [];
		}

		$this->prepared['result']['success'] = ['text' => $successCaption ?? ''];
		$this->prepared['result']['failure'] = ['text' => $failureCaption ?? ''];

		if ($refill)
		{
			$this->prepared['result']['refill'] = [
				'active' => true,
				'caption' => Loc::getMessage('CRM_SERVICE_FORM_REFILL_BUTTON')
			];
		}

		return $this;
	}

	/**
	 * Document scheme version.
	 * @param int $schemeVersion
	 * @return $this
	 */
	public function addDocumentScheme(int $schemeVersion): ScenarioOptionBuilder
	{
		if (!$this->prepared['document'])
		{
			$this->prepared['document'] = [];
		}

		$this->prepared['document']['scheme'] = $schemeVersion;

		return $this;
	}

	/**
	 * Deal category.
	 * @param int $category
	 * @return $this
	 */
	public function addDealCategory(int $category): ScenarioOptionBuilder
	{
		if (!$this->prepared['document'])
		{
			$this->prepared['document'] = [];
		}

		if (!$this->prepared['document']['deal'])
		{
			$this->prepared['document']['deal'] = [];
		}

		$this->prepared['document']['deal']['category'] = $category;

		return $this;
	}

	/**
	 * Deal Duplicate mode
	 * @param bool $mode
	 * @return $this
	 */
	public function addDealDuplicateMode(bool $mode): ScenarioOptionBuilder
	{
		if (!$this->prepared['document'])
		{
			$this->prepared['document'] = [];
		}
		if (!$this->prepared['document']['deal'])
		{
			$this->prepared['document']['deal'] = [];
		}
		$this->prepared['document']['deal']['duplicatesEnabled'] = $mode;

		return $this;
	}

	/**
	 * Responsible users
	 *
	 * @param array $userIds
	 * @return $this
	 */
	public function addResponsibleUsers(array $userIds): ScenarioOptionBuilder
	{
		if (!$this->prepared['responsible'])
		{
			$this->prepared['responsible'] = [];
		}
		if (!$this->prepared['responsible']['users'])
		{
			$this->prepared['responsible']['users'] = [];
		}
		$this->prepared['responsible']['users'] = array_merge($this->prepared['responsible']['users'], $userIds);

		return $this;
	}

	/**
	 * Responsible check work time.
	 * @param bool $mode
	 * @return $this
	 */
	public function addResponsibleCheckWorkTimeMode(bool $mode): ScenarioOptionBuilder
	{
		if (!$this->prepared['responsible'])
		{
			$this->prepared['responsible'] = [];
		}
		$this->prepared['responsible']['checkWorkTime'] = $mode;

		return $this;
	}

	/**
	 * Use callback mode.
	 * @param bool $use
	 * @return $this
	 */
	public function addCallbackMode(bool $use): ScenarioOptionBuilder
	{
		if (!$this->prepared['callback'])
		{
			$this->prepared['callback'] = [];
		}
		$this->prepared['callback']['use'] = $use;

		return $this;
	}

	/**
	 * Duplicate mode.
	 * @param string $mode
	 * @return $this
	 */
	public function addDuplicateMode(string $mode): ScenarioOptionBuilder
	{
		if (!$this->prepared['document'])
		{
			$this->prepared['document'] = [];
		}
		$this->prepared['document']['duplicateMode'] = $mode;

		return $this;
	}

	/**
	 * Preparing web form options to built final scenario
	 * @param array $options
	 * @return array
	 */
	public function prepare(array &$options): array
	{
		$this->resetOption($options);
		$options = array_replace_recursive($options, $this->prepared);

		return parent::prepare($options);
	}

	private function resetOption(array &$options): void
	{
		$options['whatsapp'] = [];
		$options['result'] = [
			'success' => [
				'text' => '',
			],
			'failure' => [
				'text' => '',
			],
		];
		
		$options['payment'] = [];
		$options['captcha'] = [];
		$options['data']['recaptcha'] = [];
		$options['data']['agreements'] = [];
		$options['data']['dependencies'] = [];
		$options['integration']['cases'] = [];

		if (!$this->supportPreset)
		{
			$options['presetFields'] = [];
		}

		if ($this->prepared['data']['fields'])
		{
			$options['data']['fields'] = [];
		}
	}

	/**
	 * Adding preset fields.
	 * @param array $field
	 * @return $this
	 */
	public function addPresetField(array $field): ScenarioOptionBuilder
	{
		if (!$this->prepared['presetFields'])
		{
			$this->prepared['presetFields'] = [];
		}

		$this->prepared['presetFields'][] = $field;

		return $this;
	}
}