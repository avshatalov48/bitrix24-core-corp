<?php

namespace Bitrix\Crm\Service\WebForm\Scenario;

use Bitrix\Crm\Service\WebForm\ScenarioMenuItem;
use Bitrix\Crm\Service\WebForm\ScenarioOptionBuilder;
use Bitrix\Crm\Volume\Base;
use Bitrix\Main\Context\Culture;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserFieldTable;

class BaseScenario
{
	/**
	 * @var string
	 */
	protected $id;
	
	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $icon;

	/**
	 * @var array
	 */
	protected $menuItems = [
		ScenarioMenuItem::FIELDS['id'],
		ScenarioMenuItem::AGREEMENTS['id'],
		ScenarioMenuItem::BUTTON_AND_HEADER['id'],
		ScenarioMenuItem::CRM['id'],
		ScenarioMenuItem::DESIGN['id'],
		ScenarioMenuItem::OTHER['id'],
	];

	protected $expertModeMenuItems;

	protected $actions;
	protected $category;
	protected $openable = true;
	protected $soon = false;
	protected $active = true;
	protected $canUse = true;
	protected $defaultSection = ScenarioMenuItem::FIELDS['id'];
	protected $fields = [];
	protected $supportPreset = false;

	/**@var bool $titleCreateDate */
	protected $titleCreateDate = true;

	public const SCENARIO_CALLBACK = 'callback';
	public const SCENARIO_CONTACTS = 'contacts';
	public const SCENARIO_EXPERT = 'expert';
	public const SCENARIO_FACEBOOK = 'facebook';
	public const SCENARIO_FEEDBACK = 'feedback';
	public const SCENARIO_PERSONALISATION = 'personalisation';
	public const SCENARIO_PRODUCT1 = 'product1';
	public const SCENARIO_PRODUCT2 = 'product2';
	public const SCENARIO_PRODUCT3 = 'product3';
	public const SCENARIO_PRODUCT4 = 'product4';
	public const SCENARIO_VK = 'vk';
	public const SCENARIO_DELIVERY_ADDRESS = 'delivery_address';
	public const SCENARIO_DELIVERY_AND_PAY = 'delivery_and_pay';
	public const SCENARIO_WHATSAPP = 'whatsapp';
	public const SCENARIO_EVENT_REGISTRATION = 'event_registration';
	public const SCENARIO_OFFLINE_EVENT = 'offline_event';
	public const SCENARIO_ONLINE_EVENT = 'online_event';
	public const SCENARIO_OFFLINE_REGISTRATION_EVENT = 'offline_registration_event';
	public const SCENARIO_FORM_IN_BUTTON = 'form_in_button';
	public const SCENARIO_FORM_ON_TIMER = 'form_on_timer';
	public const SCENARIO_FORM_IN_LINK = 'form_in_link';
	public const SCENARIO_FORM_IN_WIDGET = 'form_in_widget';
	public const SCENARIO_FORM_ON_PAGE = 'form_on_page';
	public const SCENARIO_FORM_ON_SITE = 'form_on_site';
	public const SCENARIO_DEPENDENCY_UNRELATED = 'dependency_unrelated';
	public const SCENARIO_DEPENDENCY_RELATED = 'dependency_related';
	public const SCENARIO_DEPENDENCY_EXCLUDING = 'dependency_excluding';
	public const SCENARIO_FILLING_DATA = 'filling_data';
	public const SCENARIO_MULTI_PAGE = 'multi_page';

	public const SCENARIOS = [
		self::SCENARIO_CALLBACK => '/bitrix/images/crm/webform/icons/revertcall.svg',
		self::SCENARIO_CONTACTS => '/bitrix/images/crm/webform/icons/contacts.svg',
		self::SCENARIO_EXPERT => '/bitrix/images/crm/webform/icons/service.svg',
		self::SCENARIO_FACEBOOK => '/bitrix/images/crm/webform/icons/facebook.svg',
		self::SCENARIO_FEEDBACK => '/bitrix/images/crm/webform/icons/feedback.svg',
		self::SCENARIO_PERSONALISATION => '/bitrix/images/crm/webform/icons/personalization.svg',
		self::SCENARIO_PRODUCT1 => '/bitrix/images/crm/webform/icons/products1.svg',
		self::SCENARIO_PRODUCT2 => '/bitrix/images/crm/webform/icons/products2.svg',
		self::SCENARIO_PRODUCT3 => '/bitrix/images/crm/webform/icons/products3.svg',
		self::SCENARIO_PRODUCT4 => '/bitrix/images/crm/webform/icons/products4.svg',
		self::SCENARIO_VK => '/bitrix/images/crm/webform/icons/vk.svg',
		self::SCENARIO_DELIVERY_ADDRESS => '/bitrix/images/crm/webform/icons/deliveryaddress.svg',
		self::SCENARIO_DELIVERY_AND_PAY => '/bitrix/images/crm/webform/icons/deliveryandpay.svg',
		// self::SCENARIO_WHATSAPP => '/bitrix/images/crm/webform/icons/revertcall.svg',
		self::SCENARIO_EVENT_REGISTRATION => '/bitrix/images/crm/webform/icons/online.svg',
		self::SCENARIO_OFFLINE_EVENT => '/bitrix/images/crm/webform/icons/offlineevent.svg',
		self::SCENARIO_OFFLINE_REGISTRATION_EVENT => '/bitrix/images/crm/webform/icons/eventregistration.svg',
		// self::SCENARIO_FORM_IN_BUTTON => '/bitrix/images/crm/webform/icons/smart.svg',
		// self::SCENARIO_FORM_ON_TIMER => '/bitrix/images/crm/webform/icons/smart.svg',
		// self::SCENARIO_FORM_IN_LINK => '/bitrix/images/crm/webform/icons/smart.svg',
		// self::SCENARIO_FORM_IN_WIDGET => '/bitrix/images/crm/webform/icons/smart.svg',
		// self::SCENARIO_FORM_ON_PAGE => '/bitrix/images/crm/webform/icons/smart.svg',
		// self::SCENARIO_FORM_ON_SITE => '/bitrix/images/crm/webform/icons/smart.svg',
		self::SCENARIO_DEPENDENCY_UNRELATED => '/bitrix/images/crm/webform/icons/dependencyunrelated.svg',
		self::SCENARIO_DEPENDENCY_RELATED => '/bitrix/images/crm/webform/icons/dependencyrelated.svg',
		self::SCENARIO_DEPENDENCY_EXCLUDING => '/bitrix/images/crm/webform/icons/dependencyexcluding.svg',
		self::SCENARIO_FILLING_DATA => '/bitrix/images/crm/webform/icons/filldata.svg',
		self::SCENARIO_MULTI_PAGE => '/bitrix/images/crm/webform/icons/multipage.svg',
	];

	/**
	 * @var ScenarioOptionBuilder
	 */
	protected $prepareBuilder;

	/** @var Culture|null $culture */
	protected $culture;

	/**
	 * Base scenario constructor. Load messages for all scenarios.
	 */
	public function __construct(string $id, Culture $culture = null)
	{
		Loc::loadMessages(__FILE__);
		$this->id = $id;
		$this->icon = self::SCENARIOS[$id];
		$this->prepareBuilder = new ScenarioOptionBuilder();
		$this->culture = $culture;
	}

	/**
	 * get scenario title
	 * @return string
	 */
	public function getTitle(): string
	{
		if (!$this->title)
		{
			return Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_'.mb_strtoupper($this->getId())) ?? '';
		}
		
		return $this->title;
	}
	
	/**
	 * @param string|null $title
	 * @return BaseScenario
	 */
	public function setTitle(?string $title): BaseScenario
	{
		$this->title = $title;
		
		return $this;
	}

	/**
	 * get scenario description
	 * @return string
	 */
	public function getDescription(): string
	{
		return Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_'.mb_strtoupper($this->getId()).'_DESCRIPTION') ?? '';
	}

	public function check(): array
	{
		$checkResult = $this->checkFields();

		$message = $checkResult['messages']
			? Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_ERRORS', [
				'%scenario_name%' => $this->getTitle(),
				'%notice%' => implode("<br/>", $checkResult['messages']),
			])
			: '';
		return [
			'success' => !$checkResult['messages'],
			'message' => $checkResult['messages']
				? [
					'title' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FIELD_ERROR_TITLE'),
					'description' => $message,
					'confirmButton' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FIELD_ERROR_CONFIRM'),
					'cancelButton' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FIELD_ERROR_CANCEL'),
				] : [],
		];
	}

	/**
	 * Create configuration for scenario
	 * @return array
	 */
	public function getConfiguration(): array
	{
		$scenario = [];

		$scenario['id'] = $this->getId();
		$scenario['title'] = $this->getTitle();
		$scenario['description'] = $this->getDescription();

		if ($this->getCategory())
		{
			$scenario['category'] = $this->getCategory();
		}

		if ($this->getIcon())
		{
			$scenario['icon'] = $this->getIcon();
		}

		if ($this->getMenuItems())
		{
			$scenario['items'] = $this->getMenuItems();
		}

		if ($this->getExpertModeMenuItems())
		{
			$scenario['expertModeItems'] = $this->getExpertModeMenuItems();
		}

		if ($this->getDefaultSection())
		{
			$scenario['defaultSection'] = $this->getDefaultSection();
		}

		if ($this->isSoon())
		{
			$scenario['soon'] = $this->isSoon();
		}

		if (!$this->isActive())
		{
			$scenario['disabled'] = $this->isActive();
		}

		$scenario['openable'] = $this->isOpenable();

		if ($this->getActions())
		{
			$scenario['actions'] = $this->getActions();
		}

		if ($this->getActions())
		{
			$scenario['payment'] = $this->getActions();
		}

		return $scenario;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @param string $id
	 * @return BaseScenario
	 */
	public function setId(string $id): BaseScenario
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIcon(): string
	{
		return $this->icon;
	}

	/**
	 * @param string $icon
	 * @return BaseScenario
	 */
	public function setIcon(string $icon): BaseScenario
	{
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getMenuItems(): array
	{
		return $this->menuItems;
	}

	/**
	 * Set available menu items of the scenario.
	 * @param array $menuItems
	 * @return BaseScenario
	 */
	public function setMenuItems(array $menuItems): BaseScenario
	{
		$this->menuItems = $menuItems;
		return $this;
	}


	/**
	 * Set available exprert mode menu items of the scenario.
	 * @param array $menuItems
	 * @return BaseScenario
	 */
	public function setExpertModeMenuItems(array $menuItems): BaseScenario
	{
		$this->expertModeMenuItems = $menuItems;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getActions()
	{
		return $this->actions;
	}

	/**
	 * @param mixed $actions
	 * @return BaseScenario
	 */
	public function setActions($actions)
	{
		$this->actions = $actions;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCategory()
	{
		return $this->category;
	}

	/**
	 * @param mixed $category
	 * @return BaseScenario
	 */
	public function setCategory($category)
	{
		$this->category = $category;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isOpenable(): bool
	{
		return $this->openable;
	}

	/**
	 * @param bool $openable
	 * @return BaseScenario
	 */
	public function setOpenable(bool $openable): BaseScenario
	{
		$this->openable = $openable;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSoon(): bool
	{
		return $this->soon;
	}

	/**
	 * @param bool $soon
	 * @return BaseScenario
	 */
	public function setSoon(bool $soon): BaseScenario
	{
		$this->soon = $soon;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isActive(): bool
	{
		return $this->active;
	}

	/**
	 * @param bool $active
	 * @return BaseScenario
	 */
	public function setActive(bool $active): BaseScenario
	{
		$this->active = $active;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function canUse(): bool
	{
		return $this->canUse;
	}

	/**
	 * @param bool $canUse
	 * @return BaseScenario
	 */
	public function setCanUse(bool $canUse): BaseScenario
	{
		$this->canUse = $canUse;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDefaultSection()
	{
		return $this->defaultSection;
	}

	/**
	 * @param mixed $defaultSection
	 * @return BaseScenario
	 */
	public function setDefaultSection($defaultSection): BaseScenario
	{
		$this->defaultSection = $defaultSection;
		return $this;
	}

	/**
	 *
	 * @param bool $titleCreateDate
	 * @return $this
	 */
	public function setCreateDateInTitle(bool $titleCreateDate) : BaseScenario
	{
		$this->titleCreateDate = $titleCreateDate;

		return $this;
	}

	/**
	 * Preparing scenario behaviour
	 * @param array $options
	 * @return array
	 */
	public function prepare(array &$options): array
	{
		$checkResult = $this->checkFields();
		if (!$checkResult['fieldsAdded'])
		{
			$this->addFields();
		}

		$title = $this->getTitle();

		if ($this->titleCreateDate)
		{
			$title = Loc::getMessage(
				'CRM_SERVICE_FORM_SCENARIO_NAME_TEMPLATE',
				[
					'#NAME#' => $title,
					'#DATE#' => FormatDate($this->culture->getDayMonthFormat(), new Date())
				]
			);
		}

		$options['name'] = $title;

		return $this->prepareBuilder->prepare($options);
	}

	/**
	 * @param ScenarioOptionBuilder $builder
	 * @return BaseScenario
	 */
	public function prepareBuilder(ScenarioOptionBuilder $builder): BaseScenario
	{
		$this->prepareBuilder = $builder;
		return $this;
	}

	/**
	 * Check available scenario fields.
	 * Returns message if the field not exists in CRM entities.
	 * @return array
	 */
	public function checkFields()
	{
		$messages = [];
		$fieldsAdded = true;
		foreach ($this->fields as $field)
		{
			$rs = \CUserTypeEntity::GetList(array(), array(
				"ENTITY_ID" => $field["entityType"],
				"FIELD_NAME" => $field["name"],
			));

			if($rs->Fetch())
			{
				continue;
			}

			$fieldsAdded = false;

			if (isset($field['showConfirmation']) && $field['showConfirmation'] === true)
			{
				$messages[] = Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FIELD_NOT_EXISTS', [
					'%entity_type%' => Loc::getMessage('CRM_SERVICE_FORM_ENTITY_TYPE_' . $field['entityType']),
					'%field%' => $field['title']['text'],
				]);
			}
		}

		return [
			'fieldsAdded' => $fieldsAdded,
			'messages' => $messages,
		];
	}

	protected function addFields()
	{
		$fields = $this->prepareFields();

		foreach ($fields as $field)
		{
			$userTypeEntity = new \CUserTypeEntity();
			$id = $userTypeEntity->Add($field);
			if($id > 0)
			{
				if($field['USER_TYPE_ID'] === 'enumeration')
				{
					$this->updateEnums($id, $field['ENUM']);
				}
			}
		}
	}

	/**
	 * @param array $fields
	 * @return $this
	 */
	public function fieldsToCheck(array $fields): BaseScenario
	{
		$this->fields = $fields;
		return $this;
	}

	protected function prepareFields(): array
	{
		$resultFields = [];
		foreach ($this->fields as $field)
		{
			$resultField = [];
			$resultField['ENTITY_ID'] = $field['entityType'];
			$resultField['USER_TYPE_ID'] = $field['type'];
			$resultField['FIELD_NAME'] = $field['name'];
			$resultField['EDIT_FORM_LABEL'][$field['title']['locale']] = $field['title']['text'];
			$resultField['LIST_COLUMN_LABEL'][$field['title']['locale']] = $field['title']['text'];

			if ($field['items'])
			{
				$sortOrder = 100;
				$counter = 0;
				foreach ($field['items'] as $item)
				{
					$resultField['ENUM']['n'.$counter++] = [
						'DEF' => 'N',
						'VALUE' => $item,
						'SORT' => $sortOrder,
					];
					$sortOrder += 100;
				}
			}
			$resultFields[] = $resultField;
		}

		return $resultFields;
	}

	private function updateEnums(int $id, array $items)
	{
		$enumValuesManager = new \CUserFieldEnum();
		$enumValuesManager->setEnumValues($id, $items);
	}

	/**
	 * @return array | null
	 */
	public function getExpertModeMenuItems()
	{
		return $this->expertModeMenuItems;
	}
}