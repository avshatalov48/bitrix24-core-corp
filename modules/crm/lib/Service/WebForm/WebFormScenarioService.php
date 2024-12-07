<?php

namespace Bitrix\Crm\Service\WebForm;

use Bitrix\Crm\Integration\Bitrix24\Product;
use Bitrix\Crm\Service\WebForm\Scenario\BaseScenario;
use Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario\DependencyScenarioCreator;
use Bitrix\Crm\Service\WebForm\Scenario\TourScenarioDescription;
use Bitrix\Crm\Volume\Base;
use Bitrix\Crm\WebForm\Entity;
use Bitrix\Crm\WebForm\ResultEntity;
use Bitrix\Crm\WebForm\WhatsApp;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;

class WebFormScenarioService
{
	/**@var Context\Culture $culture */
	private $culture;

	/**@var CurrentUser $currentUser*/
	private $currentUser;

	private static $scenarioList = [];
	/**
	 * @param CurrentUser $currentUser
	 * @param Context\Culture|null $culture
	 */
	public function __construct(CurrentUser $currentUser, Context\Culture $culture = null)
	{
		$this->currentUser = $currentUser;
		$this->culture = $culture;
	}

	/**
	 * Get all usable scenarios
	 * @return array
	 */
	public function getScenarioList(): array
	{
		if (!empty(self::$scenarioList))
		{
			return self::$scenarioList;
		}

		self::$scenarioList = [];
		foreach (BaseScenario::SCENARIOS as $scenario => $item)
		{
			$scenario = $this->getScenario($scenario);

			if ($scenario->canUse())
			{
				self::$scenarioList[] = $scenario->getConfiguration();
			}
		}

		return self::$scenarioList;
	}

	/**
	 * Get all current scenario categories
	 * @return array
	 */
	public function getScenarioCategoryList(): array
	{
		$categories = ScenarioCategory::getCategories();

		$categoryResult = [];
		foreach ($categories as $category)
		{
			$categoryResult[] = [
				'id' => mb_strtolower($category),
				'title' => Loc::getMessage('CRM_SERVICE_FORM_CATEGORY_' . $category),
			];
		}

		return $categoryResult;
	}

	/**
	 * Get all sidebar menu items
	 * @return array
	 */
	public function getSidebarMenuItems(): array
	{
		$menuItems = ScenarioMenuItem::getMenuItems();

		$result = [];
		foreach ($menuItems as $item)
		{
			$itemCode =  mb_strtoupper(str_replace("-", "_", $item['id']));
			$sidebarMenuItemText =
				Loc::getMessage("CRM_SERVICE_FORM_MENU_ITEM_{$itemCode}")
				?? Loc::getMessage("CRM_SERVICE_FORM_MENU_ITEM_{$itemCode}_MSGVER_1")
			;

			$result[] = $item + ['text' => $sidebarMenuItemText];
		}

		return $result;
	}

	/**
	 * prepare options by template id
	 * @param $templateId
	 * @param array $options
	 * @return array
	 */
	public function prepareForm($templateId, array &$options): array
	{
		$scenario = $this->getScenario($templateId);
		$prepared = $scenario->prepare($options);

		$options['templateId'] = $templateId;

		return $prepared;
	}

	/**
	 * Check scenario on possible errors
	 * @param $templateId
	 * @return array
	 */
	public function check($templateId): array
	{
		$scenario = $this->getScenario($templateId);
		return $scenario->check();
	}

	private function getScenario($templateId): BaseScenario
	{
		$baseScenario = new BaseScenario($templateId, $this->culture);
		switch ($templateId)
		{
			case BaseScenario::SCENARIO_EXPERT:
				return $this->prepareExpertScenario($baseScenario);
			case BaseScenario::SCENARIO_CONTACTS:
				return $this->prepareContactsScenario($baseScenario);
			case BaseScenario::SCENARIO_CALLBACK:
				return $this->prepareCallbackScenario($baseScenario);
			case BaseScenario::SCENARIO_FEEDBACK:
				return $this->prepareFeedbackScenario($baseScenario);
			case BaseScenario::SCENARIO_PRODUCT1:
			case BaseScenario::SCENARIO_PRODUCT3:
				return $this->prepareProductsScenario($baseScenario);
			case BaseScenario::SCENARIO_PRODUCT2:
				return $this->prepareProductsScenario(
					$baseScenario,
					false,
					Entity::ENUM_ENTITY_SCHEME_DEAL_INVOICE,
					true,
					Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PRODUCT2')
				);
			case BaseScenario::SCENARIO_PRODUCT4:
				return $this->prepareProductsScenario($baseScenario, true);
			case BaseScenario::SCENARIO_VK:
				return $this->prepareSocialScenario($baseScenario, ScenarioMenuItem::VK['id']);
			case BaseScenario::SCENARIO_FACEBOOK:
				return $this->prepareSocialScenario($baseScenario, ScenarioMenuItem::FACEBOOK['id']);
			case BaseScenario::SCENARIO_PERSONALISATION:
				return $this->preparePersonalisationScenario($baseScenario);
			case BaseScenario::SCENARIO_WHATSAPP:
				return $this->prepareWhatsAppScenario($baseScenario);
			case BaseScenario::SCENARIO_DELIVERY_AND_PAY:
				return $this->prepareDeliveryAddressScenario($baseScenario, true);
			case BaseScenario::SCENARIO_DELIVERY_ADDRESS:
				return $this->prepareDeliveryAddressScenario($baseScenario);
			case BaseScenario::SCENARIO_OFFLINE_EVENT:
				return $this->prepareEventScenario($baseScenario, true, false);
			case BaseScenario::SCENARIO_OFFLINE_REGISTRATION_EVENT:
				return $this->prepareEventScenario($baseScenario, false, true,'live');
			case BaseScenario::SCENARIO_EVENT_REGISTRATION:
				return $this->prepareEventScenario($baseScenario, false, true, 'online');
			case BaseScenario::SCENARIO_FORM_ON_SITE:
			case BaseScenario::SCENARIO_FORM_IN_BUTTON:
			case BaseScenario::SCENARIO_FORM_ON_PAGE:
			case BaseScenario::SCENARIO_FORM_IN_LINK:
			case BaseScenario::SCENARIO_FORM_IN_WIDGET:
			case BaseScenario::SCENARIO_FORM_ON_TIMER:
				return $this->prepareTourScenario($baseScenario, $templateId);
			case BaseScenario::SCENARIO_DEPENDENCY_UNRELATED:
			case BaseScenario::SCENARIO_DEPENDENCY_EXCLUDING:
			case BaseScenario::SCENARIO_DEPENDENCY_RELATED:
				$dependencyScenario = DependencyScenarioCreator::getDependencyScenario($templateId);

				if (!$dependencyScenario)
				{
					$baseScenario->setCanUse(false);
					return $baseScenario;
				}
				return $this->prepareDependencyScenario(
					$baseScenario,
					$dependencyScenario->getFields(),
					$dependencyScenario->getDependencies()
				);
			case BaseScenario::SCENARIO_FILLING_DATA:
				return $this->prepareFillingDataScenario($baseScenario);
			case BaseScenario::SCENARIO_MULTI_PAGE:
				return $this->prepareMultiPageContactsScenario($baseScenario);
		}

		return $baseScenario;
	}

	private function prepareExpertScenario(BaseScenario $baseScenario): BaseScenario
	{
		$optionScenario = new ScenarioOptionBuilder();
		$optionScenario->addPresetSupport()
			->addAgreements(true)
			->addCaptcha(true)
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CALLBACK_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CALLBACK_FAILURE_TEXT')
			)
		;
		$this->prepareDealAndResponsibilitiesConfiguration($optionScenario, false);

		return $baseScenario->setCategory(ScenarioCategory::OTHER)
			->setCreateDateInTitle(true)
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::AGREEMENTS['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::BUTTON_AND_HEADER['id'],
				ScenarioMenuItem::SPAM_PROTECTION['id'],
				ScenarioMenuItem::FIELDS_RULES['id'],
				ScenarioMenuItem::ACTIONS['id'],
				ScenarioMenuItem::DEFAULT_VALUES['id'],
				ScenarioMenuItem::ANALYTICS['id'],
				$this->isRegionRussian(true) ? null : ScenarioMenuItem::FACEBOOK['id'],
				$this->isRegionRussian() ? ScenarioMenuItem::VK['id'] : null,
				ScenarioMenuItem::CALLBACK['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->prepareBuilder($optionScenario)
		;
	}

	private function prepareContactsScenario(BaseScenario $baseScenario): BaseScenario
	{
		$scenarioOptionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_BUTTON'))
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_FAILURE_TEXT'),
			)
			->addFields([
				['name' => 'CONTACT_NAME', 'autocomplete' => true, 'required'=> true],
				['name' => 'CONTACT_LAST_NAME', 'autocomplete' => true, 'required'=> false],
				['name' => 'CONTACT_EMAIL', 'autocomplete' => true, 'required'=> false],
				[
					'name' => 'CONTACT_PHONE',
					'autocomplete' => true,
					'required'=> true,
					'editing' => [
						'editable' => ['valueType' => 'WORK'],
					],
				],
			])
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);

		$this->prepareDealAndResponsibilitiesConfiguration($scenarioOptionBuilder);

		return $baseScenario->setCategory(ScenarioCategory::CRM)
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->prepareBuilder($scenarioOptionBuilder)
		;
	}

	private function prepareMultiPageContactsScenario(BaseScenario $baseScenario): BaseScenario
	{
		$scenarioOptionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_BUTTON'))
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CONTACTS_FAILURE_TEXT'),
			)
			->addFields([
				[
					'type' => 'page',
					'label' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PAGE', ['%page%' => 1]),
					'name' => 'page_' . mt_rand(1000000, 9999999),
				],
				['name' => 'CONTACT_NAME', 'autocomplete' => true, 'required'=> true],
				['name' => 'CONTACT_LAST_NAME', 'autocomplete' => true, 'required'=> false],
				[
					'type' => 'page',
					'label' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PAGE', ['%page%' => 2]),
					'name' => 'page_' . mt_rand(1000000, 9999999),
				],
				['name' => 'CONTACT_EMAIL', 'autocomplete' => true, 'required'=> false],
				[
					'name' => 'CONTACT_PHONE',
					'autocomplete' => true,
					'required'=> true,
					'editing' => [
						'editable' => ['valueType' => 'WORK'],
					],
				],
			])
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);

		$this->prepareDealAndResponsibilitiesConfiguration($scenarioOptionBuilder);

		return $baseScenario->setCategory(ScenarioCategory::CRM)
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->prepareBuilder($scenarioOptionBuilder)
		;
	}

	private function prepareDealAndResponsibilitiesConfiguration(ScenarioOptionBuilder $scenarioOptionBuilder, bool $useDeal = true): void
	{
		$scenarioOptionBuilder
			->addResponsibleCheckWorkTimeMode(true)
			->addResponsibleUsers( [$this->currentUser->getId()])
			;

		if ($useDeal)
		{
			$scenarioOptionBuilder
				->addDuplicateMode(ResultEntity::DUPLICATE_CONTROL_MODE_MERGE)
				->addDealCategory(0)
				->addDealDuplicateMode(true);
		}
	}

	private function prepareCallbackScenario(BaseScenario $baseScenario): BaseScenario
	{
		$callbackMode = !empty(\Bitrix\Crm\WebForm\Callback::getPhoneNumbers());

		$optionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->addCallbackMode($callbackMode)
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CALLBACK_BUTTON'))
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CALLBACK_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_CALLBACK_FAILURE_TEXT'),
			)
			->addFields([
				['name' => 'CONTACT_PHONE', 'required'=> true],
			])
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);

		$this->prepareDealAndResponsibilitiesConfiguration($optionBuilder, false);

		return $baseScenario->setCategory(ScenarioCategory::CRM)
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::AGREEMENTS['id'],
				ScenarioMenuItem::BUTTON_AND_HEADER['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::CALLBACK['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setDefaultSection(ScenarioMenuItem::CALLBACK['id'])
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->prepareBuilder($optionBuilder)
		;
	}

	private function prepareWhatsAppScenario(BaseScenario $baseScenario): BaseScenario
	{
		return $baseScenario->setCategory(ScenarioCategory::CRM)
			->setCanUse(WhatsApp::canUse())
			->setOpenable(false)
			->setActions([
				[
					'id' => 'showHelp',
					'data' => [
						'href' => 'redirect=detail&code='. WhatsApp::getHelpId(),
					],
				],
			]);
	}

	private function prepareSocialScenario(BaseScenario $baseScenario, string $serviceMenuItem): BaseScenario
	{
		$optionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_BUTTON'))
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_FAILURE_TEXT'),
			)
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);

		$this->prepareDealAndResponsibilitiesConfiguration($optionBuilder, false);

		$canUse = ($serviceMenuItem !== ScenarioMenuItem::VK['id'] || $this->isRegionRussian())
			&& ($serviceMenuItem !== ScenarioMenuItem::FACEBOOK['id'] || !$this->isRegionRussian(true))
		;

		return $baseScenario->setCategory(ScenarioCategory::SOCIAL)
			->setCreateDateInTitle(true)
			->setCanUse($canUse)
			->setMenuItems([
				ScenarioMenuItem::CRM['id'],
				$serviceMenuItem,
				ScenarioMenuItem::DEFAULT_VALUES['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setDefaultSection($serviceMenuItem)
			->prepareBuilder($optionBuilder);
	}

	private function prepareFeedbackScenario(BaseScenario $baseScenario): BaseScenario
	{
		$optionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_BUTTON'))
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_FAILURE_TEXT'),
			)
			->addFields([
				['name' => 'CONTACT_NAME', 'autocomplete' => true, 'required'=> true],
				['name' => 'CONTACT_EMAIL', 'autocomplete' => true, 'required'=> false],
				[
					'name' => 'CONTACT_PHONE',
					'autocomplete' => true,
					'required'=> true,
					'editing' => [
						'editable' => ['valueType' => 'WORK'],
					],
				],
				['name' => 'DEAL_COMMENTS', 'required' => true],
			])
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);

		$this->prepareDealAndResponsibilitiesConfiguration($optionBuilder);

		return $baseScenario->setCategory(ScenarioCategory::CRM)
			->setCreateDateInTitle(true)
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->prepareBuilder($optionBuilder)
		;
	}

	private function preparePersonalisationScenario(BaseScenario $baseScenario): BaseScenario
	{
		return $baseScenario->setCategory(ScenarioCategory::CRM_AUTOMATION)
			->prepareBuilder((new ScenarioOptionBuilder())
				->addAgreements(true)
				->addCaptcha(true)
			)
			->setOpenable(false)
			->setActions([
				[
					'id' => 'showHelp',
					'data' => [
						'href' => 'redirect=detail&code=13073742',
					],
				],
			])
		;
	}

	private function prepareTourScenario(BaseScenario $baseScenario, string $templateId): BaseScenario
	{
		return $baseScenario->setCategory(ScenarioCategory::PREPARE_FORM)
			->prepareBuilder((new ScenarioOptionBuilder())
				->addAgreements(true)
				->addCaptcha(true)
			)
			->setOpenable(false)
			->setActions([
					[
						'id' => 'showTour',
						'data' => [
							'steps' => TourScenarioDescription::getScenarioSteps($templateId),
						],
					],
				]
			)
		;
	}

	private function prepareDeliveryAddressScenario(BaseScenario $baseScenario, bool $usePayment = false): BaseScenario
	{
		$scheme = $usePayment ? Entity::ENUM_ENTITY_SCHEME_DEAL_INVOICE : Entity::ENUM_ENTITY_SCHEME_DEAL;
		$optionsBuilder = (new ScenarioOptionBuilder())
			->usePayment($usePayment)
			->addAgreements(true)
			->addCaptcha(true)
			->addRecaptcha()
			->addFields([
				['name' => 'CONTACT_NAME', 'autocomplete' => true, 'required'=> true],
				['name' => 'CONTACT_PHONE','autocomplete' => true, 'required'=> true ],
				['name' => 'CONTACT_DELIVERY_ADDRESS', 'autocomplete' => true, 'required'=> true ],
				['name' => 'DEAL_COMMENTS', 'label' => Loc::getMessage('CRM_WEBFORM_COMMENTS_DELIVERY_DATE')],
				['type' => 'product', 'bigPic' => false,],
			])
			->setButtonCaption(
				$usePayment
					? Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_DELIVERY_AND_PAY_BUTTON')
					: Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_DELIVERY_BUTTON')
			)
			->addDocumentScheme($scheme)
		;
		$this->prepareDealAndResponsibilitiesConfiguration($optionsBuilder, false);

		return $baseScenario->setCategory(ScenarioCategory::DELIVERY)
			->setCreateDateInTitle(true)
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::AGREEMENTS['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::PAY_SYSTEMS['id'],
				ScenarioMenuItem::BUTTON_AND_HEADER['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->prepareBuilder($optionsBuilder)
		;
	}

	private function prepareFillingDataScenario(BaseScenario $baseScenario): BaseScenario
	{
		$scenarioOptionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_FAILURE_TEXT'),
				true
			)
			->addFields([
				['name' => 'CONTACT_NAME', 'autocomplete' => false, 'required'=> true],
				['name' => 'CONTACT_PHONE', 'multiple' => true ,'autocomplete' => false, 'required'=> true],
				['name' => 'CONTACT_EMAIL', 'multiple' => true , 'autocomplete' => false],
				['name' => 'DEAL_COMMENTS', 'autocomplete' => false],
			])
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FILLING_DATA_BUTTON'))
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);
		$this->prepareDealAndResponsibilitiesConfiguration($scenarioOptionBuilder);

		return $baseScenario->setCategory(ScenarioCategory::CRM_FILLING)
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::BUTTON_AND_HEADER['id'],
				ScenarioMenuItem::ACTIONS['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->prepareBuilder($scenarioOptionBuilder)
		;
	}

	private function prepareEventScenario(
		BaseScenario $baseScenario,
		bool $refill,
		bool $useParticipationField,
		$participationFormat = null
	): BaseScenario
	{
		$value = null;
		if ($useParticipationField)
		{
			$field = \CUserTypeEntity::GetList([], [
				'ENTITY_ID' => 'CRM_DEAL',
				'FIELD_NAME' => 'UF_CRM_WEBFORM_PARTICIPATION_FORMAT',
			])->fetch();

			if ($participationFormat && $field)
			{
				$values = \CUserFieldEnum::GetList([], [
						'USER_FIELD_ID' => $field['ID'],
					]
				);

				while ($currentValue = $values->fetch())
				{
					$stringValue = Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PARTICIPATION_FORMAT_' . mb_strtoupper($participationFormat));
					if (mb_strpos($currentValue['VALUE'], $stringValue) > -1)
					{
						$value = $currentValue['ID'];
						break;
					}
				}
			}
		}

		$optionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->addRecaptcha()
			->addResult(
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_SUCCESS_TEXT'),
				Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_FEEDBACK_FAILURE_TEXT'),
				$refill
			)
			->addFields([
				['name' => 'CONTACT_NAME', 'autocomplete' => !$refill, 'required'=> true],
				['name' => 'CONTACT_LAST_NAME', 'autocomplete' => !$refill],
				['name' => 'CONTACT_PHONE', 'autocomplete' => !$refill, 'required'=> true],
				['name' => 'COMPANY_TITLE', 'autocomplete' => !$refill],
				['name' => 'CONTACT_POST', 'autocomplete' => !$refill],
				$useParticipationField ? [
					'name' => 'DEAL_UF_CRM_WEBFORM_PARTICIPATION_FORMAT',
					'autocomplete' => !$refill,
					'inPreparing' => true,
					'required'=> true,
					'value' => $value
				] : [],
			])
			->setButtonCaption(Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_REGISTRATION_BUTTON'))
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL)
			->addPresetField([
				'entityName' => 'DEAL',
				'fieldName' => 'TITLE',
				'value' => $participationFormat === 'online'
					? Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_EVENT_REGISTRATION')
					: Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_OFFLINE_REGISTRATION_EVENT')
				,
			])
		;

		$this->prepareDealAndResponsibilitiesConfiguration($optionBuilder);

		return $baseScenario->setCategory(ScenarioCategory::EVENTS)
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::AGREEMENTS['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::BUTTON_AND_HEADER['id'],
				ScenarioMenuItem::ACTIONS['id'],
				ScenarioMenuItem::DEFAULT_VALUES['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->fieldsToCheck($useParticipationField ? [
				[
					'name' => 'UF_CRM_WEBFORM_PARTICIPATION_FORMAT',
					'entityType' => 'CRM_DEAL',
					'type' => 'enumeration',
					// 'showConfirmation' => true, // show modal confirmation dialog
					'title' => [
						'text' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PARTICIPATION_FORMAT'),
						'locale' => Context::getCurrent()->getLanguage(),
					],
					'items' => [
						'online' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PARTICIPATION_FORMAT_ONLINE'),
						'live' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PARTICIPATION_FORMAT_LIVE'),
						'record' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PARTICIPATION_FORMAT_RECORD'),
					],
				],
			] : [])
			->prepareBuilder($optionBuilder)
			->setActions([[
				'id' => 'check'
			]])
		;
	}

	private function prepareProductsScenario(
		BaseScenario $baseScenario,
		bool $bigPic = false,
		int $schema = Entity::ENUM_ENTITY_SCHEME_DEAL,
		bool $usePayment = false,
		string $title = null
	): BaseScenario
	{
		$optionScenario = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->addRecaptcha()
			->addFields(
				[
					['name' => 'CONTACT_NAME', 'autocomplete' => true, 'required' => true],
					[
						'name' => 'CONTACT_PHONE',
						'autocomplete' => true,
						'required' => true,
						'editing' => [
							'editable' => ['valueType' => 'WORK',],
						],
					],
					[
						'name' => 'CONTACT_EMAIL',
						'autocomplete' => true,
						'required' => false,
						'editing' => [
							'editable' => ['valueType' => 'WORK',],
						],
					],
					['type' => 'product', 'bigPic' => $bigPic,],
				]
			)
			->usePayment($usePayment)
			->addDocumentScheme($schema)
		;
		$this->prepareDealAndResponsibilitiesConfiguration($optionScenario, false);

		return $baseScenario->setCategory(ScenarioCategory::PRODUCTS)
			->setCreateDateInTitle(true)
			->prepareBuilder($optionScenario)
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::AGREEMENTS['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::PAY_SYSTEMS['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setTitle($title)
		;
	}

	private function isRegionRussian(bool $onlyRu = false): bool
	{
		return Product::isRegionRussian($onlyRu);
	}

	private function prepareDependencyScenario(BaseScenario $baseScenario, array $fields, array $dependencies = []): BaseScenario
	{
		$scenarioOptionBuilder = (new ScenarioOptionBuilder())
			->addAgreements(true)
			->addCaptcha(true)
			->addRecaptcha()
			->addFields($fields)
			->addDocumentScheme(Entity::ENUM_ENTITY_SCHEME_DEAL);
		;

		$this->prepareDealAndResponsibilitiesConfiguration($scenarioOptionBuilder);

		foreach ($dependencies as $dependency)
		{
			$scenarioOptionBuilder->addDependency($dependency);
		}

		return $baseScenario->setCategory(ScenarioCategory::DEPENDENCY_FIELD)
			->setMenuItems([
				ScenarioMenuItem::FIELDS['id'],
				ScenarioMenuItem::AGREEMENTS['id'],
				ScenarioMenuItem::CRM['id'],
				ScenarioMenuItem::BUTTON_AND_HEADER['id'],
				ScenarioMenuItem::FIELDS_RULES['id'],
				ScenarioMenuItem::DESIGN['id'],
				ScenarioMenuItem::OTHER['id'],
			])
			->setExpertModeMenuItems($this->getExpertModeDefaultItems())
			->setDefaultSection(
				ScenarioMenuItem::FIELDS_RULES['id'],
			)
			->prepareBuilder($scenarioOptionBuilder);
	}

	private function getExpertModeDefaultItems()
	{
		return[
			ScenarioMenuItem::FIELDS['id'],
			ScenarioMenuItem::AGREEMENTS['id'],
			ScenarioMenuItem::CRM['id'],
			ScenarioMenuItem::PAY_SYSTEMS['id'],
			ScenarioMenuItem::BUTTON_AND_HEADER['id'],
			ScenarioMenuItem::SPAM_PROTECTION['id'],
			ScenarioMenuItem::FIELDS_RULES['id'],
			ScenarioMenuItem::ACTIONS['id'],
			ScenarioMenuItem::DEFAULT_VALUES['id'],
			ScenarioMenuItem::ANALYTICS['id'],
			ScenarioMenuItem::CALLBACK['id'],
			ScenarioMenuItem::DESIGN['id'],
			ScenarioMenuItem::OTHER['id'],
		];
	}
}