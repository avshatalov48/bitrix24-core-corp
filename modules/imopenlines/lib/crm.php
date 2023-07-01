<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Tracking;
use Bitrix\Crm\Communication\Type;
use Bitrix\Crm\EntityManageFacility;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\Automation\Trigger\OpenLineTrigger;
use Bitrix\Crm\Integration\Channel\IMOpenLineTracker;
use Bitrix\Crm\Automation\Trigger\OpenLineAnswerTrigger;
use Bitrix\Crm\Automation\Trigger\OpenLineMessageTrigger;
use Bitrix\Crm\Automation\Trigger\OpenLineAnswerControlTrigger;

use Bitrix\Im;

use Bitrix\ImOpenLines\Crm\Fields;
use Bitrix\ImOpenLines\Im\Messages;
use Bitrix\ImOpenLines\Crm\Activity;
use Bitrix\ImOpenLines\Crm\Common as CrmCommon;

Loc::loadMessages(__FILE__);

class Crm
{
	public const
		FIND_BY_CODE = 'IMOL',
		FIND_BY_NAME = 'NAME',
		FIND_BY_EMAIL = 'EMAIL',
		FIND_BY_PHONE = 'PHONE'
	;

	public const
		ENTITY_NONE = 'NONE',
		ENTITY_LEAD = 'LEAD',
		ENTITY_COMPANY = 'COMPANY',
		ENTITY_CONTACT = 'CONTACT',
		ENTITY_DEAL = 'DEAL',
		ENTITY_ACTIVITY = 'ACTIVITY'
	;

	public const
		FIELDS_COMPANY = 'COMPANY_ID',
		FIELDS_CONTACT = 'CONTACT_IDS'
	;

	public const
		ERROR_IMOL_NO_SESSION = 'ERROR IMOPENLINES NO SESSION',
		ERROR_IMOL_CREATING_CRM_ENTITY = 'ERROR IMOPENLINES CREATING CRM ENTITY',
		ERROR_IMOL_NOT_LOAD_CRM = 'ERROR IMOPENLINES NOT LOAD CRM',
		ERROR_IMOL_NOT_LOAD_IM = 'ERROR IMOPENLINES NOT LOAD IM',
		ERROR_IMOL_NO_CRM_BINDINGS = 'ERROR IMOPENLINES NO CRM BINDINGS',
		ERROR_IMOL_CRM_ACTIVITY = 'ERROR IMOPENLINES CRM ACTIVITY',
		ERROR_IMOL_CRM_NO_ID_ACTIVITY = 'ERROR IMOPENLINES CRM NO ID ACTIVITY',
		ERROR_IMOL_CRM_NO_REQUIRED_PARAMETERS = 'ERROR IMOPENLINES CRM NO REQUIRED PARAMETERS'
	;

	/** @var EntityManageFacility */
	protected $facility;

	/** @var Fields */
	protected $fields;

	protected $registeredEntites = [];
	protected $updateEntites = [];

	protected $activityId = 0;

	protected $skipCreate = false;
	protected $skipSearch = false;
	protected $skipTrigger = false;
	protected $skipTriggerFirstMessage = false;
	protected $ignoreSearchCode = false;
	protected $ignoreSearchUserId = false;
	protected $ignoreSearchEmails = false;
	protected $ignoreSearchPhones = false;
	protected $ignoreSearchPerson = false;

	/**
	 * Crm constructor.
	 * @param Session|null $session
	 */
	public function __construct(?Session $session = null)
	{
		$this->fields = new Fields;

		if ($session instanceof Session)
		{
			$this->fields->setSession($session);
		}

		Loader::includeModule("crm");
	}

	/**
	 * @return bool
	 */
	public function isLoaded(): bool
	{
		$result = false;

		try
		{
			if (
				ModuleManager::isModuleInstalled('crm')
				&& Loader::includeModule('crm')
			)
			{
				$result = true;
			}
		}
		catch (\Exception $e)
		{

		}

		return $result;
	}

	public static function loadMessages(): void
	{
		Loc::loadMessages(__FILE__);
	}

	/**
	 * @return Fields
	 */
	public function getFields(): Fields
	{
		return $this->fields;
	}

	/**
	 * @return self
	 */
	public function setSkipCreate(): self
	{
		$this->skipCreate = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipCreate(): bool
	{
		return $this->skipCreate;
	}

	/**
	 * @param string $mode
	 * @return self
	 */
	public function setModeCreate($mode = Config::CRM_CREATE_NONE): self
	{
		if (
			$mode !== Config::CRM_CREATE_LEAD &&
			$mode !== Config::CRM_CREATE_DEAL
		)
		{
			$this->setSkipCreate();
		}

		return $this;
	}

	/**
	 * @return self
	 */
	public function setSkipSearch(): self
	{
		$this->skipSearch = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipSearch(): bool
	{
		return $this->skipSearch;
	}

	/**
	 * @return self
	 */
	public function setSkipAutomationTrigger(): self
	{
		$this->skipTrigger = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipAutomationTrigger(): bool
	{
		return $this->skipTrigger;
	}

	/**
	 * @return self
	 */
	public function setSkipAutomationTriggerFirstMessage(): self
	{
		$this->skipTriggerFirstMessage = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkipAutomationTriggerFirstMessage(): bool
	{
		return $this->skipTrigger || $this->skipTriggerFirstMessage;
	}

	/**
	 * @return self
	 */
	public function setIgnoreSearchCode(): self
	{
		$this->ignoreSearchCode = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchCode(): bool
	{
		return $this->ignoreSearchCode;
	}

	/**
	 * @return self
	 */
	public function setIgnoreSearchUserId(): self
	{
		$this->ignoreSearchUserId = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchUserId(): bool
	{
		return $this->ignoreSearchUserId;
	}

	/**
	 * @return self
	 */
	public function setIgnoreSearchEmails(): self
	{
		$this->ignoreSearchEmails = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchEmails(): bool
	{
		return $this->ignoreSearchEmails;
	}

	/**
	 * @return self
	 */
	public function setIgnoreSearchPhones(): self
	{
		$this->ignoreSearchPhones = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchPhones(): bool
	{
		return $this->ignoreSearchPhones;
	}

	/**
	 * @return self
	 */
	public function setIgnoreSearchPerson(): self
	{
		$this->ignoreSearchPerson = true;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchPerson(): bool
	{
		return $this->ignoreSearchPerson;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		$result = '';

		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($fields->getCode()))
		{
			$result = $fields->getCode();
		}
		elseif (
			!empty($session)
			&& $session->getData('USER_CODE')
		)
		{
			$result = $session->getData('USER_CODE');
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		$result = 0;

		$fields = $this->getFields();

		if (!empty($fields->getUserId()))
		{
			$result = $fields->getUserId();
		}
		elseif (!empty($this->getCode()))
		{
			$result = Chat::parseLinesChatEntityId($this->getCode())['connectorUserId'];
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getCodeImol(): string
	{
		$result = '';

		$code  = $this->getCode();

		if (!empty($code))
		{
			$result = 'imol|' . $code;
		}

		return $result;
	}

	/**
	 * Searches the specified crm entity fields.
	 *
	 * @return bool
	 */
	public function search(): bool
	{
		$fields = $this->getFields();
		$filter = false;

		$facility = $this->getEntityManageFacility();
		$selector = $facility->getSelector();

		if (!$this->isIgnoreSearchCode())
		{
			if (($code = $this->getCode()) && ($codeImol = $this->getCodeImol()))
			{
				$selector->appendCommunicationCriterion(CrmCommon::getCommunicationType($code), $codeImol);
				$filter = true;
			}
		}

		if (!$this->isIgnoreSearchUserId())
		{
			if ($userId = $this->getUserId())
			{
				$selector->appendCommunicationCriterion(Type::SLUSER_NAME, (string)$userId);
				$filter = true;
			}
		}

		if (!$this->isIgnoreSearchPerson())
		{
			if ($fields->getPersonName() != LiveChat::getDefaultGuestName())
			{
				$personName = $fields->getPersonName();
			}
			else
			{
				$personName = '';
			}
			if ($fields->getPersonLastName() != LiveChat::getDefaultGuestName())
			{
				$personLastName = $fields->getPersonLastName();
			}
			else
			{
				$personLastName = '';
			}
			if ($fields->getPersonSecondName() != LiveChat::getDefaultGuestName())
			{
				$personSecondName = $fields->getPersonSecondName();
			}
			else
			{
				$personSecondName = '';
			}

			if (!empty($personName) || !empty($personLastName) || !empty($personSecondName))
			{
				$selector->appendPersonCriterion($personLastName, $personName, $personSecondName);

				$filter = true;
			}

			if (!empty($fields->getPersonEmail()))
			{
				$selector->appendEmailCriterion($fields->getPersonEmail());

				$filter = true;
			}

			if (!empty($fields->getPersonPhone()))
			{
				$selector->appendPhoneCriterion($fields->getPersonPhone());

				$filter = true;
			}
		}

		if (!$this->isIgnoreSearchEmails())
		{
			if (!empty($fields->getEmails()))
			{
				foreach ($fields->getEmails() as $email)
				{
					$selector->appendEmailCriterion($email);

					$filter = true;
				}
			}
		}

		if (!$this->isIgnoreSearchPhones())
		{
			if (!empty($fields->getPhones()))
			{
				foreach ($fields->getPhones() as $phone)
				{
					$selector->appendPhoneCriterion($phone);

					$filter = true;
				}
			}
		}

		if ($filter !== false)
		{
			$selector->search();
		}

		return true;
	}

	/**
	 * @return EntityManageFacility
	 */
	public function getEntityManageFacility(): EntityManageFacility
	{
		if (empty($this->facility))
		{
			$this->facility = new EntityManageFacility();
		}

		$connectorCode = $this->fields->getSession()
			? $this->fields->getSession()->getData('SOURCE')
			: null;
		if (empty($this->facility->getTrace()) && $connectorCode)
		{
			$this->facility->setTrace(
				Tracking\Trace::create(
					$this->fields->getSession()->getData('CRM_TRACE_DATA')
				)->addChannel(
					new Tracking\Channel\Imol($connectorCode)
				)
			);
		}

		return $this->facility;
	}

	/**
	 *
	 * @return Result
	 */
	public function registrationChanges(): Result
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), self::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}

		if ($result->isSuccess() && !empty($session))
		{
			$this->activityId = $session->getData('CRM_ACTIVITY_ID');

			$facility = $this->getEntityManageFacility();

			//update Activity
			if ($session->getData('CRM') == 'Y' && $this->activityId > 0)
			{
				$bindingsRaw = CrmCommon::getActivityBindings($this->activityId);

				if ($bindingsRaw->isSuccess())
				{
					$bindings = $bindingsRaw->getData();
					$newBindings = [];

					if (empty($bindings[\CCrmOwnerType::ContactName]) && empty($bindings[\CCrmOwnerType::CompanyName]))
					{
						if ($this->isSkipSearch() == false)
						{
							$this->search();
						}

						if ($companyId = $facility->getSelector()->getCompanyId())
						{
							$bindings[\CCrmOwnerType::CompanyName] = $newBindings[\CCrmOwnerType::CompanyName] = $companyId;
						}

						if ($contactId = $facility->getSelector()->getContactId())
						{
							$bindings[\CCrmOwnerType::ContactName] = $newBindings[\CCrmOwnerType::ContactName] = $contactId;
						}

						if (!empty($newBindings))
						{
							$addActivityBindingsRaw = CrmCommon::addActivityBindings($this->activityId, $newBindings);

							if (!$addActivityBindingsRaw->isSuccess())
							{
								$result->addErrors($addActivityBindingsRaw->getErrors());
							}
						}
					}

					if (!empty($bindings))
					{
						$bindingsForCrm = [];

						foreach ($bindings as $typeEntity=>$idEntity)
						{
							if (!empty($idEntity))
							{
								$resultUpdateEntity = $this->updateEntity($typeEntity, $idEntity);

								$this->updateEntites[] = [
									'ENTITY_TYPE' => $typeEntity,
									'ENTITY_ID' => $idEntity,
									'IS_PRIMARY' => (\CCrmOwnerType::ResolveID($typeEntity) == $facility->getPrimaryTypeId() && $idEntity == $facility->getPrimaryId()) ? 'Y' : 'N',
									'SAVE' => $resultUpdateEntity ? 'Y' : 'N',
									'ADD' => $newBindings[$typeEntity] ? 'Y' : 'N',
 								];

								$bindingsForCrm[] = [
									'OWNER_TYPE_ID' => \CCrmOwnerType::ResolveID($typeEntity),
									'OWNER_ID' => $idEntity,
								];
							}
						}

						$rawFlags = $this->updateFlags();
						if ($rawFlags->isSuccess())
						{
							if (!empty($bindingsForCrm))
							{
								$rawTrigger = $this->executeAutomationTrigger($bindingsForCrm, [
									'CONFIG_ID' => $session->getData('CONFIG_ID')
								]);

								if (!$rawTrigger->isSuccess())
								{
									$result->addErrors($rawTrigger->getErrors());
								}
							}
						}
						else
						{
							$result->addErrors($rawFlags->getErrors());
						}
					}
				}
				else
				{
					$result->addErrors($bindingsRaw->getErrors());
				}
			}
			//add Activity
			else
			{
				$isCorrectEntity = $this->isFieldsCrmEntityCorrect();

				if ($isCorrectEntity->isSuccess())
				{
					if ($this->isSkipSearch() === false)
					{
						$this->search();
					}

					$resultRegisterTouch = $this->registerTouch();

					if ($resultRegisterTouch->isSuccess())
					{
						/** @var \Bitrix\Crm\Entity\Identificator\Complex $registeredEntity */
						foreach ($facility->getRegisteredEntities() as $registeredEntity)
						{
							$this->registeredEntites[] = [
								'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($registeredEntity->getTypeId()),
								'ENTITY_ID' => $registeredEntity->getId(),
								'IS_PRIMARY' => ($registeredEntity->getTypeId() == $facility->getPrimaryTypeId() && $registeredEntity->getId() == $facility->getPrimaryId()) ? 'Y' : 'N',
								'SAVE' => 'Y'
							];

							//TODO: deprecated
							if (\CCrmOwnerType::ResolveName($registeredEntity->getTypeId()) == \CCrmOwnerType::LeadName)
							{
								ConfigStatistic::getInstance((int)$session->getData('CONFIG_ID'))->addLead();
							}
						}
					}
					else
					{
						$result->addErrors($resultRegisterTouch->getErrors());
					}

					if (
						$result->isSuccess() &&
						!empty($this->getEntityManageFacility()->getActivityBindings())
					)
					{
						/** @var \Bitrix\Crm\Entity\Identificator\ComplexCollection $updateEntites */
						$updateEntites = $facility->getBindingCollection()->diff($facility->getRegisteredEntities());

						foreach ($updateEntites as $updateEntity)
						{
							$resultUpdateEntity = $this->updateEntity(\CCrmOwnerType::ResolveName($updateEntity->getTypeId()), $updateEntity->getId());

							$this->updateEntites[] = [
								'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($updateEntity->getTypeId()),
								'ENTITY_ID' => $updateEntity->getId(),
								'IS_PRIMARY' => ($updateEntity->getTypeId() == $facility->getPrimaryTypeId() && $updateEntity->getId() == $facility->getPrimaryId()) ? 'Y' : 'N',
								'SAVE' => $resultUpdateEntity ? 'Y' : 'N',
								'ADD' => 'Y',
							];
						}

						$resultActivity = $this->registerActivity();

						if ($resultActivity->isSuccess())
						{
							$this->activityId = $resultActivity->getResult();

							$rawFlags = $this->updateFlags();
							if ($rawFlags->isSuccess())
							{
								$resultUpdateUser = $this->updateUserConnector();

								if (!$resultUpdateUser->isSuccess())
								{
									$result->addErrors($resultUpdateUser->getErrors());
								}
							}
							else
							{
								$result->addErrors($rawFlags->getErrors());
							}
						}
						else
						{
							$result->addErrors($resultActivity->getErrors());
						}

						if ($result->isSuccess())
						{
							$rawTrigger = $this->executeAutomationTrigger($this->getEntityManageFacility()->getActivityBindings(), [
								'CONFIG_ID' => $session->getData('CONFIG_ID')
							]);

							if (!$rawTrigger->isSuccess())
							{
								$result->addErrors($rawTrigger->getErrors());
							}
						}
					}
				}
				else
				{
					$result->addErrors($isCorrectEntity->getErrors());
				}
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 */
	protected function isFieldsCrmEntityCorrect(): Result
	{
		$result = new Result;

		$fields = $this->getFields();

		if ($fields->getSession() === null)
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}
		else
		{
			$rawSourceId = $this->getSourceId();
			if (!$rawSourceId->isSuccess())
			{
				$result->addErrors($rawSourceId->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function getFieldsAddLead(): Result
	{
		$result = new Result;

		$isCorrectEntity = $this->isFieldsCrmEntityCorrect();

		if ($isCorrectEntity->isSuccess())
		{
			$fields = $this->getFields();
			$session = $fields->getSession();

			$fieldsAdd = [];
			$fieldsFmAdd = [];

			$fieldsAdd['SOURCE_ID'] = $this->getSourceId()->getResult();

			if (!empty($fields->getTitle()))
			{
				$fieldsAdd['TITLE'] = $fields->getTitle();
			}
			else
			{
				$fieldsAdd['TITLE'] = $session->getChat()->getData('TITLE');
			}

			if (!empty($fields->getPersonName()))
			{
				$fieldsAdd['NAME'] = $fields->getPersonName();
			}

			if (!empty($fields->getPersonLastName()))
			{
				$fieldsAdd['LAST_NAME'] = $fields->getPersonLastName();
			}

			if (!empty($fields->getPersonSecondName()))
			{
				$fieldsAdd['SECOND_NAME'] = $fields->getPersonSecondName();
			}

			if (!empty($fields->getPersonEmail()))
			{
				$fieldsFmAdd['EMAIL']['WORK'][] = $fields->getPersonEmail();
			}

			if (!empty($fields->getPersonPhone()))
			{
				$fieldsFmAdd['PHONE']['WORK'][] = $fields->getPersonPhone();
			}

			if (!empty($fields->getEmails()))
			{
				if (!empty($fieldsFmAdd['EMAIL']['WORK']))
				{
					$fieldsFmAdd['EMAIL']['WORK'] = array_merge($fieldsFmAdd['EMAIL']['WORK'], $fields->getEmails());
					$fieldsFmAdd['EMAIL']['WORK'] = Tools\Email::getArrayUniqueValidate($fieldsFmAdd['EMAIL']['WORK']);
				}
				else
				{
					$fieldsFmAdd['EMAIL']['WORK'] = $fields->getEmails();
				}
			}

			if (!empty($fields->getPhones()))
			{
				if (!empty($fieldsFmAdd['PHONE']['WORK']))
				{
					$fieldsFmAdd['PHONE']['WORK'] = array_merge($fieldsFmAdd['PHONE']['WORK'], $fields->getPhones());
					$fieldsFmAdd['PHONE']['WORK'] = Tools\Phone::getArrayUniqueValidate($fieldsFmAdd['PHONE']['WORK']);
				}
				else
				{
					$fieldsFmAdd['PHONE']['WORK'] = $fields->getPhones();
				}
			}

			if (!empty($fields->getPersonWebsite()))
			{
				if (mb_strlen($fields->getPersonWebsite()) > 250)
				{
					$fieldsAdd['SOURCE_DESCRIPTION'] = $fields->getPersonWebsite();
				}
				else
				{
					$fieldsFmAdd['WEB']['HOME'][] = $fields->getPersonWebsite();
				}
			}

			if (($userCode = $this->getCode()) && ($userCodeImol = $this->getCodeImol()))
			{
				$fieldsFmAdd['IM'][CrmCommon::getCommunicationType($userCode)][] = $userCodeImol;
			}

			if ($userId = $this->getUserId())
			{
				$fieldsFmAdd['LINK']['USER'][] = $userId;
			}

			if (!empty($fieldsFmAdd))
			{
				$fieldsAdd['FM'] = CrmCommon::formatMultifieldFields($fieldsFmAdd);
			}

			if (!empty($fieldsAdd))
			{
				$result->setData($fieldsAdd);
			}
		}
		else
		{
			$result->addErrors($isCorrectEntity->getErrors());
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function getFieldsAddDeal(): Result
	{
		$result = new Result;

		$isCorrectEntity = $this->isFieldsCrmEntityCorrect();

		if ($isCorrectEntity->isSuccess())
		{
			$fields = $this->getFields();
			$session = $fields->getSession();

			$fieldsAdd = [];

			$fieldsAdd['SOURCE_ID'] = $this->getSourceId()->getResult();

			if (!empty($fields->getTitle()))
			{
				$fieldsAdd['TITLE'] = $fields->getTitle();
			}
			else
			{
				$fieldsAdd['TITLE'] = $session->getChat()->getData('TITLE');
			}

			if (!empty($fields->getPersonWebsite()))
			{
				$fieldsAdd['SOURCE_DESCRIPTION'] = $fields->getPersonWebsite();
			}

			if (!empty($session->getConfig('CRM_CREATE_SECOND')))
			{
				$fieldsAdd['CATEGORY_ID'] = $session->getConfig('CRM_CREATE_SECOND');
			}

			if (!empty($fieldsAdd))
			{
				$result->setData($fieldsAdd);
			}
		}
		else
		{
			$result->addErrors($isCorrectEntity->getErrors());
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function getFieldsAddContact(): Result
	{
		$result = new Result;

		$isCorrectEntity = $this->isFieldsCrmEntityCorrect();

		if ($isCorrectEntity->isSuccess())
		{
			$fields = $this->getFields();
			$session = $fields->getSession();

			$fieldsAdd = [];
			$fieldsFmAdd = [];

			$fieldsAdd['SOURCE_ID'] = $this->getSourceId()->getResult();

			if (!empty($fields->getPersonName()))
			{
				$fieldsAdd['NAME'] = $fields->getPersonName();
			}

			if (!empty($fields->getPersonLastName()))
			{
				$fieldsAdd['LAST_NAME'] = $fields->getPersonLastName();
			}

			if (!empty($fields->getPersonSecondName()))
			{
				$fieldsAdd['SECOND_NAME'] = $fields->getPersonSecondName();
			}

			if (
				!isset($fieldsAdd['NAME']) &&
				!isset($fieldsAdd['LAST_NAME']) &&
				!isset($fieldsAdd['SECOND_NAME'])
			)
			{
				$fieldsAdd['NAME'] = LiveChat::getDefaultGuestName();
			}

			if (!empty($fields->getPersonEmail()))
			{
				$fieldsFmAdd['EMAIL']['WORK'][] = $fields->getPersonEmail();
			}

			if (!empty($fields->getPersonPhone()))
			{
				$fieldsFmAdd['PHONE']['WORK'][] = $fields->getPersonPhone();
			}

			if (!empty($fields->getEmails()))
			{
				if (!empty($fieldsFmAdd['EMAIL']['WORK']))
				{
					$fieldsFmAdd['EMAIL']['WORK'] = array_merge($fieldsFmAdd['EMAIL']['WORK'], $fields->getEmails());
					$fieldsFmAdd['EMAIL']['WORK'] = Tools\Email::getArrayUniqueValidate($fieldsFmAdd['EMAIL']['WORK']);
				}
				else
				{
					$fieldsFmAdd['EMAIL']['WORK'] = $fields->getEmails();
				}
			}

			if (!empty($fields->getPhones()))
			{
				if (!empty($fieldsFmAdd['PHONE']['WORK']))
				{
					$fieldsFmAdd['PHONE']['WORK'] = array_merge($fieldsFmAdd['PHONE']['WORK'], $fields->getPhones());
					$fieldsFmAdd['PHONE']['WORK'] = Tools\Phone::getArrayUniqueValidate($fieldsFmAdd['PHONE']['WORK']);
				}
				else
				{
					$fieldsFmAdd['PHONE']['WORK'] = $fields->getPhones();
				}
			}

			if (!empty($fields->getPersonWebsite()))
			{
				if (mb_strlen($fields->getPersonWebsite()) > 250)
				{
					$fieldsAdd['SOURCE_DESCRIPTION'] = $fields->getPersonWebsite();
				}
				else
				{
					$fieldsFmAdd['WEB']['HOME'][] = $fields->getPersonWebsite();
				}
			}

			if (($userCode = $this->getCode()) && ($userCodeImol = $this->getCodeImol()))
			{
				$fieldsFmAdd['IM'][CrmCommon::getCommunicationType($userCode)][] = $userCodeImol;
			}

			if ($userId = $this->getUserId())
			{
				$fieldsFmAdd['LINK']['USER'][] = $userId;
			}

			if (!empty($fieldsFmAdd))
			{
				$fieldsAdd['FM'] = CrmCommon::formatMultifieldFields($fieldsFmAdd);
			}

			if (!empty($fieldsAdd))
			{
				$result->setData($fieldsAdd);
			}
		}
		else
		{
			$result->addErrors($isCorrectEntity->getErrors());
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function sendCrmImMessages(): Result
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$messageManager = Messages\Crm::init($session->getData('CHAT_ID'), $session->getData('OPERATOR_ID'));

			if (!empty($this->registeredEntites))
			{
				$entities = [];
				foreach ($this->registeredEntites as $entity)
				{
					if ($entity['SAVE'] == 'Y')
					{
						$entities[$entity['ENTITY_TYPE']][] = $entity['ENTITY_ID'];
					}
				}
				if (!empty($entities))
				{
					$messageManager->sendMessageAboutAddEntity($entities);
				}
			}

			if (!empty($this->updateEntites))
			{
				$updatedEntities = [];
				$createdEntities = [];
				foreach ($this->updateEntites as $entity)
				{
					if ($entity['SAVE'] == 'Y')
					{
						$updatedEntities[$entity['ENTITY_TYPE']][] = $entity['ENTITY_ID'];
					}
					elseif($entity['ADD'] == 'Y')
					{
						$createdEntities[$entity['ENTITY_TYPE']][] = $entity['ENTITY_ID'];
					}
				}

				if(!empty($createdEntities))
				{
					$messageManager->sendMessageAboutUpdateEntity($createdEntities);
				}

				if (!empty($updatedEntities))
				{
					$messageManager->sendMessageAboutExtendEntity($updatedEntities);
				}
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function registerActivity(): Result
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!Loader::includeModule('crm'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), self::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}

		if (empty($this->getEntityManageFacility()->getActivityBindings()))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_CRM_BINDINGS'), self::ERROR_IMOL_NO_CRM_BINDINGS, __METHOD__));
		}

		if ($result->isSuccess() && !empty($session) && !empty($session->getData('ID')))
		{
			if ($session->getData('CRM_ACTIVITY_ID') > 0)
			{
				$result->setResult($session->getData('CRM_ACTIVITY_ID'));
			}
			else
			{
				$parsedUserCode = Session\Common::parseUserCode($session->getData('USER_CODE'));
				$connectorId = $parsedUserCode['CONNECTOR_ID'];
				$lineId = $parsedUserCode['CONFIG_ID'];

				$addFields = [
					'LINE_ID' => $lineId,
					'NAME' => Loc::getMessage('IMOL_CRM_CREATE_ACTIVITY_2', Array('#LEAD_NAME#' => $session->getChat()->getData('TITLE'), '#CONNECTOR_NAME#' => CrmCommon::getSourceName($session->getData('USER_CODE')))),
					'SESSION_ID' => $session->getData('ID'),
					'MODE' => $session->getData('MODE'),
					'BINDINGS' => $this->getEntityManageFacility()->getActivityBindings(),
					'OPERATOR_ID' => $this->getResponsibleCrmId(),
					'USER_CODE' => $session->getData('USER_CODE'),
					'CONNECTOR_ID' => $connectorId,
				];

				foreach (array_merge($this->updateEntites, $this->registeredEntites) as $item)
				{
					$addFields['ENTITES'][] = [
						'ENTITY_ID' => $item['ENTITY_ID'],
						'ENTITY_TYPE_ID' => \CCrmOwnerType::ResolveId($item['ENTITY_TYPE'])
					];
				}

				$resultAddActivity = Activity::add($addFields);
				if ($resultAddActivity->isSuccess())
				{
					$result->setResult($resultAddActivity->getResult());
				}
				else
				{
					$result->addErrors($resultAddActivity->getErrors());
				}
			}
		} else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function registerTouch(): Result
	{
		$result = new Result;

		$facility = $this->getEntityManageFacility();
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (Loader::includeModule('crm'))
		{
			if ($session !== null)
			{
				if ($this->isSkipCreate())
				{
					$facility->setRegisterMode($facility::REGISTER_MODE_ONLY_UPDATE);
				}

				$facility->setUpdateClientMode($facility::UPDATE_MODE_NONE);

				$isCorrectEntity = $this->isFieldsCrmEntityCorrect();
				if ($isCorrectEntity->isSuccess())
				{
					$oldRegisterMode = $facility->getRegisterMode();

					//The creation mode of the deal
					if ($session->getConfig('CRM_CREATE') === Config::CRM_CREATE_DEAL)
					{
						$crmOwnerType = \CCrmOwnerType::Deal;
						$contactId = $facility->getSelector()->getContactId();

						if (!$contactId)
						{
							$fieldsContactAdd = $this->getFieldsAddContact()->getData();

							$isRegisterContact = $facility->registerContact($fieldsContactAdd, true, [
								'CURRENT_USER' => $this->getResponsibleCrmId(),
								'DISABLE_USER_FIELD_CHECK' => true
							]);

							if (
								$isRegisterContact &&
								$facility->getRegisteredId() &&
								$facility->getRegisteredTypeId() === \CCrmOwnerType::Contact
							)
							{
								$contactId = $facility->getRegisteredId();
							}
							elseif ($facility->hasErrors())
							{
								$errorDescriptions = implode(';', $facility->getErrorMessages());
								$result->addError(new Error($errorDescriptions, self::ERROR_IMOL_CREATING_CRM_ENTITY, __METHOD__, $fieldsContactAdd));
							}
						}

						if ($contactId)
						{
							$facility->getSelector()->setEntity(\CCrmOwnerType::Contact, $contactId);

							$fieldsAdd = $this->getFieldsAddDeal()->getData();

							if ($session->getConfig('CRM_CREATE_THIRD') === 'N')
							{
								$facility->setRegisterMode($facility::REGISTER_MODE_ALWAYS_ADD);
							}
						}
					}
					//Mode for creating leads. By default.
					else
					{
						$crmOwnerType = \CCrmOwnerType::Lead;
						$fieldsAdd = $this->getFieldsAddLead()->getData();
					}

					if (!empty($fieldsAdd))
					{
						$isRegisterEntity = $facility->registerTouch($crmOwnerType, $fieldsAdd, true, [
							'CURRENT_USER' => $this->getResponsibleCrmId(),
							'DISABLE_USER_FIELD_CHECK' => true
						]);

						if (
							$isRegisterEntity !== true &&
							$facility->hasErrors()
						)
						{
							$errorDescriptions = implode(';', $facility->getErrorMessages());
							$result->addError(new Error($errorDescriptions, self::ERROR_IMOL_CREATING_CRM_ENTITY, __METHOD__, $fieldsAdd));
						}
					}
					else
					{
						$result->addError(new Error(self::ERROR_IMOL_CREATING_CRM_ENTITY, '', __METHOD__));
					}

					//Resetting the entity registration mode
					if ($oldRegisterMode !== $facility->getRegisterMode())
					{
						$facility->setRegisterMode($oldRegisterMode);
					}
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), self::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param $id
	 * @return bool
	 */
	public function updateEntity($type, $id): bool
	{
		$result = false;

		$updateFields = [];
		$updateFm = [];

		$fields = $this->getFields();

		$entity = CrmCommon::get($type, $id, true);

		if (!empty($entity))
		{
			$phones = $fields->getPhones();
			$emails = $fields->getEmails();

			$code = $this->getCode();
			$codeImol = $this->getCodeImol();
			$userId = $this->getUserId();

			$personName = $fields->getPersonName();
			$personLastName = $fields->getPersonLastName();
			$personSecondName = $fields->getPersonSecondName();
			if (
				!empty($fields->getPersonEmail())
				&& !Tools\Email::isInArray($emails, $fields->getPersonEmail())
			)
			{
				$emails[] = $fields->getPersonEmail();
			}
			if (
				!empty($fields->getPersonPhone())
				&& !Tools\Phone::isInArray($phones, $fields->getPersonPhone())
			)
			{
				$phones[] = $fields->getPersonPhone();
			}
			$personWebsite = $fields->getPersonWebsite();

			if (
				$type !== self::ENTITY_DEAL
				&& !empty($code)
				&& !empty($codeImol)
			)
			{
				$communicationType = CrmCommon::getCommunicationType($code);
				if (
					empty($entity['FM']['IM'][$communicationType])
					|| !in_array($codeImol, $entity['FM']['IM'][$communicationType])
				)
				{
					$updateFm['IM'][$communicationType][] = $codeImol;
				}
			}

			if (
				!empty($userId)
				&& $type !== self::ENTITY_DEAL
			)
			{
				if (
					empty($entity['FM']['LINK']['USER'])
					|| !in_array($userId, $entity['FM']['LINK']['USER'], true)
				)
				{
					$updateFm['LINK']['USER'][] = $userId;
				}
			}

			if (
				!empty($phones)
				&& $type !== self::ENTITY_DEAL
			)
			{
				foreach ($phones as $phone)
				{
					if (empty($entity['FM']['PHONE']['WORK']) || !Tools\Phone::isInArray($entity['FM']['PHONE']['WORK'], $phone))
					{
						$updateFm['PHONE']['WORK'][] = $phone;
					}
				}
			}

			if (
				!empty($emails)
				&& $type !== self::ENTITY_DEAL
			)
			{
				foreach ($emails as $email)
				{
					if (empty($entity['FM']['EMAIL']['WORK']) || !Tools\Email::isInArray($entity['FM']['EMAIL']['WORK'], $email))
					{
						$updateFm['EMAIL']['WORK'][] = $email;
					}
				}
			}

			if (!empty($personName))
			{
				if (
					$type !== self::ENTITY_DEAL
					&& $type !== self::ENTITY_COMPANY
				)
				{
					if (
						(
							empty($entity['NAME'])
							|| $entity['NAME'] === LiveChat::getDefaultGuestName()
						)
						&& LiveChat::getDefaultGuestName() !== $personName
					)
					{
						$updateFields['NAME'] = $personName;
					}
				}
			}

			if (!empty($personLastName))
			{
				if (
					$type !== self::ENTITY_DEAL
					&& $type !== self::ENTITY_COMPANY
				)
				{
					if (
						(
							empty($entity['LAST_NAME'])
							|| $entity['LAST_NAME'] === LiveChat::getDefaultGuestName()
						)
						&& LiveChat::getDefaultGuestName() !== $personLastName
					)
					{
						$updateFields['LAST_NAME'] = $personLastName;
					}
				}
			}

			if (!empty($personSecondName))
			{
				if (
					$type !== self::ENTITY_DEAL
					&& $type !== self::ENTITY_COMPANY
				)
				{
					if (
						(
							empty($entity['SECOND_NAME'])
							|| $entity['SECOND_NAME'] === LiveChat::getDefaultGuestName()
						)
						&& LiveChat::getDefaultGuestName() !== $personSecondName
					)
					{
						$updateFields['SECOND_NAME'] = $personSecondName;
					}
				}
			}

			if (!empty($personWebsite))
			{
				if (mb_strlen($personWebsite) > 250 || $type === self::ENTITY_DEAL)
				{
					if ($type !== self::ENTITY_COMPANY)
					{
						if (empty($entity['SOURCE_DESCRIPTION']))
						{
							$updateFields['SOURCE_DESCRIPTION'] = $personWebsite;
						}
					}
				}
				else
				{
					if (
						empty($entity['FM']['WEB']['HOME'])
						|| !in_array($personWebsite, $entity['FM']['WEB']['HOME'])
					)
					{
						$updateFm['WEB']['HOME'][] = $personWebsite;
					}
				}
			}

			if (!empty($updateFm))
			{
				$updateFields['FM'] = CrmCommon::formatMultifieldFields($updateFm);
			}

			if (
				$type === self::ENTITY_LEAD
				&& LeadSettings::getCurrent()->isAutoGenRcEnabled()
			)
			{
				$facility = $this->getEntityManageFacility();

				if (!empty($facility->getSelector()->getCompanyId()))
				{
					$updateFields['COMPANY_ID'] = $facility->getSelector()->getCompanyId();
				}
				if (!empty($facility->getSelector()->getContactId()))
				{
					$updateFields['CONTACT_ID'] = $facility->getSelector()->getContactId();
				}
			}

			if (!empty($updateFields))
			{
				$result = CrmCommon::update($type, $id, $updateFields);
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function updateUserConnector(): Result
	{
		$result = new Result();

		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$entitys = array_merge($this->registeredEntites, $this->updateEntites);

			if (
				$session->getData('SOURCE') == Connector::TYPE_LIVECHAT
				&& Im\User::getInstance($session->getData('USER_ID'))->isConnector()
				&& Im\User::getInstance($session->getData('USER_ID'))->getName() == ''
				&& !empty($entitys)
			)
			{
				$entityID = 0;
				$entityType = null;

				foreach ($entitys as $entity)
				{
					if ($entity['ENTITY_TYPE'] != 'DEAL' &&
						(empty($entityID) || empty($entityType) || $entity['IS_PRIMARY'] == 'Y')
					)
					{
						$entityID = $entity['ENTITY_ID'];
						$entityType = $entity['ENTITY_TYPE'];
					}
				}

				if (!empty($entityID) && !empty($entityType))
				{
					$entityData = CrmCommon::get($entityType, $entityID, false);

					if (!empty($entityData) && (!empty($entityData['NAME']) || !empty($entityData['LAST_NAME']) || !empty($entityData['SECOND_NAME'])))
					{
						$user = new \CUser();
						$user->Update($session->getData('USER_ID'), Array(
							'NAME' => $entityData['NAME'],
							'LAST_NAME' => $entityData['LAST_NAME'],
							'SECOND_NAME' => $entityData['SECOND_NAME'],
						));

						$relations = \CIMChat::GetRelationById($session->getData('CHAT_ID'), false, true, false);
						\Bitrix\Pull\Event::add(array_keys($relations), Array(
							'module_id' => 'im',
							'command' => 'userUpdate',
							'params' => Array(
								'user' => Im\User::getInstance($session->getData('USER_ID'))->getFields()
							),
							'extra' => \Bitrix\Im\Common::getPullExtra()
						));
					}

				}
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function getSourceId(): Result
	{
		$result = new Result;

		$session = $this->getFields()->getSession();

		if (!empty($session))
		{
			$statuses = \CCrmStatus::GetStatusList("SOURCE");

			if (
				$session->getConfig('CRM_SOURCE') == Config::CRM_SOURCE_AUTO_CREATE ||
				!isset($statuses[$session->getConfig('CRM_SOURCE')])
			)
			{
				$crmSource = $session->getData('CONFIG_ID') . '|' .
					CrmCommon::getCommunicationType(
						$session->getData('USER_CODE'), true
					);
				$crmSource = mb_substr($crmSource, 0, 50);

				if (!isset($statuses[$crmSource]))
				{
					$entity = new \CCrmStatus("SOURCE");
					$entity->Add(array(
						'NAME' => CrmCommon::getSourceName($session->getData('USER_CODE'), $session->getConfig('LINE_NAME')),
						'STATUS_ID' => $crmSource,
						'SORT' => 115,
						'SYSTEM' => 'N'
					));
				}
				$result->setResult($crmSource);
			}
			else
			{
				$result->setResult($session->getConfig('CRM_SOURCE'));
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @return int|null
	 */
	public function getOperatorId()
	{
		$id = 0;

		if (Loader::includeModule('crm'))
		{
			$id = $this->getEntityManageFacility()->getPrimaryAssignedById();
		}

		return $id;
	}

	/**
	 * @return int
	 */
	public function getResponsibleCrmId()
	{
		$result = 0;

		$session = $this->getFields()->getSession();

		if (!empty($session))
		{
			if (!empty($session->getData('OPERATOR_ID')) && $session->getData('OPERATOR_ID') > 0)
			{
				$result = $session->getData('OPERATOR_ID');
			}
			else
			{
				//TODO: fix
				$session->getConfig('ID');
				$res = Queue::getList([
					'select' => [
						'USER_ID'
					],
					'filter' => [
						'=CONFIG_ID' => $session->getConfig('ID')
					],
					'order' => [
						'SORT' => 'ASC',
						'ID' => 'ASC'
					],
				]);

				while ($queueUser = $res->fetch())
				{
					if (Im\User::getInstance($queueUser['USER_ID'])->isActive())
					{
						$queueUserList[] = $queueUser['USER_ID'];
					}
				}

				if (!empty($queueUserList) && is_array($queueUserList))
				{
					$result = current($queueUserList);
				}

				if (empty($result))
				{
					$adminList = Common::getAdministrators();

					if (
						!empty($adminList)
						&& is_array($adminList)
					)
					{
						$result = current($adminList);
					}
				}
				//TODO: END fix

				if (empty($result))
				{
					$result = $session->getData('USER_ID');
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function updateFlags(): Result
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();
		$updateSession = [];
		$updateChat = [];

		if (!empty($session))
		{
			if ($this->activityId > 0)
			{
				//update session
				$updateSession['CRM_ACTIVITY_ID'] = $this->activityId;

				if (!empty($this->registeredEntites))
				{
					foreach ($this->registeredEntites as $entity)
					{
						switch ($entity['ENTITY_TYPE'])
						{
							case \CCrmOwnerType::LeadName:
								if (empty($updateChat['LEAD']))
								{
									$updateChat['LEAD'] = $entity['ENTITY_ID'];
									$updateSession['CRM_CREATE_LEAD'] = 'Y';
								}
								break;

							case \CCrmOwnerType::DealName:
								if (empty($updateChat['DEAL']))
								{
									$updateChat['DEAL'] = $entity['ENTITY_ID'];
									$updateSession['CRM_CREATE_DEAL'] = 'Y';
								}
								break;

							case \CCrmOwnerType::ContactName:
								if (empty($updateChat['CONTACT']))
								{
									$updateChat['CONTACT'] = $entity['ENTITY_ID'];
									$updateSession['CRM_CREATE_CONTACT'] = 'Y';
								}
								break;

							case \CCrmOwnerType::CompanyName:
								if (empty($updateChat['COMPANY']))
								{
									$updateChat['COMPANY'] = $entity['ENTITY_ID'];
									$updateSession['CRM_CREATE_COMPANY'] = 'Y';
								}
								break;
						}
					}
				}

				//update chat
				foreach ($this->updateEntites as $entity)
				{
					switch ($entity['ENTITY_TYPE'])
					{
						case \CCrmOwnerType::LeadName:
							if (empty($updateChat['LEAD']))
							{
								$updateChat['LEAD'] = $entity['ENTITY_ID'];
							}
							break;

						case \CCrmOwnerType::DealName:
							if (empty($updateChat['DEAL']))
							{
								$updateChat['DEAL'] = $entity['ENTITY_ID'];
							}
							break;

						case \CCrmOwnerType::ContactName:
							if (empty($updateChat['CONTACT']))
							{
								$updateChat['CONTACT'] = $entity['ENTITY_ID'];
							}
							break;

						case \CCrmOwnerType::CompanyName:
							if (empty($updateChat['COMPANY']))
							{
								$updateChat['COMPANY'] = $entity['ENTITY_ID'];
							}
							break;
					}
				}

				//For backward compatibility, the most up-to-date entity.
				if (!empty($updateChat))
				{
					if (!empty($updateChat['DEAL']))
					{
						$updateChat['ENTITY_TYPE'] = \CCrmOwnerType::DealName;
						$updateChat['ENTITY_ID'] = $updateChat['DEAL'];
					}
					elseif(!empty($updateChat['LEAD']))
					{
						$updateChat['ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
						$updateChat['ENTITY_ID'] = $updateChat['LEAD'];
					}
					elseif(!empty($updateChat['COMPANY']))
					{
						$updateChat['ENTITY_TYPE'] = \CCrmOwnerType::CompanyName;
						$updateChat['ENTITY_ID'] = $updateChat['COMPANY'];
					}
					elseif(!empty($updateChat['CONTACT']))
					{
						$updateChat['ENTITY_TYPE'] = \CCrmOwnerType::ContactName;
						$updateChat['ENTITY_ID'] = $updateChat['CONTACT'];
					}

					$updateChat['CRM'] = 'Y';
				}
			}

			if (!empty($updateSession))
			{
				$session->updateCrmFlags($updateSession);
			}
			if (!empty($updateChat))
			{
				$session->getChat()->setCrmFlag($updateChat);
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function setDefaultFlags(): Result
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$updateSession = [
				'CRM' => 'N',
				'CRM_CREATE' => 'N',
				'CRM_CREATE_LEAD' => 'N',
				'CRM_CREATE_COMPANY' => 'N',
				'CRM_CREATE_CONTACT' => 'N',
				'CRM_CREATE_DEAL' => 'N',
				'CRM_ACTIVITY_ID' => '0',
			];
			$updateChat = [
				'CRM' => 'N',
				'CRM_ENTITY_TYPE' => 'NONE',
				'CRM_ENTITY_ID' => '0',
				'LEAD' => 0,
				'COMPANY' => 0,
				'CONTACT' => 0,
				'DEAL' => 0,
			];

			$session->updateCrmFlags($updateSession);
			$session->getChat()->setCrmFlag($updateChat);
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $bindings
	 * @param $data
	 * @return Result|\Bitrix\Main\Result
	 */
	public function executeAutomationTrigger($bindings, $data)
	{
		$result = new Result();

		if (!\Bitrix\Crm\Automation\Factory::canUseAutomation())
		{
			return $result;
		}

		if (!$this->isSkipAutomationTriggerFirstMessage())
		{
			if(is_array($bindings) || is_array($data))
			{
				$result = OpenLineTrigger::execute($bindings, $data);
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_CRM_NO_REQUIRED_PARAMETERS, __METHOD__));
			}
		}

		return $result;
	}

	/**
	 * @param Session $session
	 * @return Result|\Bitrix\Main\Result
	 */
	public function executeAutomationAnswerTrigger(Session $session)
	{
		return self::executeAnswerTriggerInternal(OpenLineAnswerTrigger::class, $session);
	}

	/**
	 * @param Session $session
	 * @return Result|\Bitrix\Main\Result
	 */
	public function executeAutomationAnswerControlTrigger(Session $session)
	{
		return self::executeAnswerTriggerInternal(OpenLineAnswerControlTrigger::class, $session);
	}

	private function executeAnswerTriggerInternal($className, Session $session)
	{
		/** @var OpenLineAnswerTrigger | OpenLineAnswerControlTrigger $className */
		$result = new Result();

		if (
			!class_exists($className)
			||
			!\Bitrix\Crm\Automation\Factory::canUseAutomation()
			||
			$this->isSkipAutomationTrigger()
		)
		{
			return $result;
		}

		$bindings = CrmCommon::getActivityBindingsFormatted($session->getData('CRM_ACTIVITY_ID'));

		$answerTimeSec = null;
		$dateCreate = $session->getData('DATE_CREATE');
		if ($dateCreate instanceof Main\Type\Date)
		{
			$answerTimeSec = max(0, time() - $dateCreate->getTimestamp());
		}

		if(is_array($bindings))
		{
			$data = $session->getData();
			$data['ANSWER_TIME_SEC'] = $answerTimeSec;
			$result = $className::execute($bindings, $data);
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_CRM_NO_REQUIRED_PARAMETERS, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $bindings
	 * @param $data
	 *
	 * @return Result
	 */
	public function executeAutomationMessageTrigger($bindings, $data)
	{
		$result = new Result();

		if (!\Bitrix\Crm\Automation\Factory::canUseAutomation())
		{
			return $result;
		}

		if (!$this->isSkipAutomationTrigger())
		{
			if(is_array($bindings) || is_array($data))
			{
				//Temporary check for compatibility
				if (class_exists('\Bitrix\Crm\Automation\Trigger\OpenLineMessageTrigger'))
				{
					$result = OpenLineMessageTrigger::execute($bindings, $data);
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_CRM_NO_REQUIRED_PARAMETERS, __METHOD__));
			}
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return Result
	 */
	public function setSessionAnswered($params = []): Result
	{
		$result = new Result();
		$result->setResult(false);
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$activityId = $session->getData('CRM_ACTIVITY_ID');

			if(!empty($activityId))
			{
				$updateParams = ['ANSWERED' => 'Y'];

				if(!empty($params['DATE_CLOSE']))
				{
					$updateParams['DATE_CLOSE'] = $params['DATE_CLOSE'];
				}

				$resultUpdate = Activity::update($activityId, $updateParams);

				if($resultUpdate->isSuccess())
				{
					$result->setResult(true);
				}
				else
				{
					$result->addErrors($resultUpdate->getErrors());
				}
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return Result
	 */
	public function setSessionClosed($params = []): Result
	{
		$result = new Result();
		$result->setResult(false);
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$activityId = $session->getData('CRM_ACTIVITY_ID');

			if(!empty($activityId))
			{
				$updateParams = ['COMPLETED' => 'Y'];

				if(!empty($params['DATE_CLOSE']))
				{
					$updateParams['DATE_CLOSE'] = $params['DATE_CLOSE'];
				}
				else
				{
					$updateParams['DATE_CLOSE'] = new \Bitrix\Main\Type\DateTime();
				}

				$resultUpdate = Activity::update($activityId, $updateParams);

				if($resultUpdate->isSuccess())
				{
					$result->setResult(true);
				}
				else
				{
					$result->addErrors($resultUpdate->getErrors());
				}
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param \Bitrix\Main\Type\DateTime|null $dataClose
	 * @return Result
	 */
	public function setSessionDataClose($dataClose = null): Result
	{
		$result = new Result();
		$result->setResult(false);
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$activityId = $session->getData('CRM_ACTIVITY_ID');

			if(!empty($activityId))
			{
				if(!empty($dataClose))
				{
					$updateParams = ['DATE_CLOSE' => $dataClose];
				}
				else
				{
					$updateParams = ['DATE_CLOSE' => new \Bitrix\Main\Type\DateTime()];
				}

				$resultUpdate = Activity::update($activityId, $updateParams);

				if($resultUpdate->isSuccess())
				{
					$result->setResult(true);
				}
				else
				{
					$result->addErrors($resultUpdate->getErrors());
				}
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param bool $autoMode
	 * @return Result
	 */
	public function setOperatorId($id, $autoMode = false): Result
	{
		$result = new Result();
		$result->setResult(false);
		$editorId = 0;

		if (Loader::includeModule('crm'))
		{
			$fields = $this->getFields();
			$session = $fields->getSession();

			if (!empty($session))
			{
				if (
					$session->getConfig('CRM') === 'Y'
					&& $session->getConfig('CRM_TRANSFER_CHANGE') === 'Y'
					&& $session->getData('CRM') === 'Y'
					&& ($activityId = $session->getData('CRM_ACTIVITY_ID'))
				)
				{
					if($autoMode === true)
					{
						if(!empty($id))
						{
							$editorId = $id;
						}
						else
						{
							$editorId = 1;
						}
					}

					$updateActivityData = ['OPERATOR_ID' => $id];
					if(!empty($editorId))
					{
						$updateActivityData['EDITOR_ID'] = $editorId;
					}
					$resultUpdateActivity = Activity::update($activityId, $updateActivityData);

					if($resultUpdateActivity->isSuccess())
					{
						$result->setResult(true);

						if($session->getData('CRM_CREATE') == 'Y')
						{
							$crmBindingManager = CrmCommon::getActivityBindings($activityId);

							if($crmBindingManager->isSuccess())
							{
								$binding = $crmBindingManager->getData();

								$updateEntityData = ['ASSIGNED_BY_ID' => $id];
								if(!empty($editorId))
								{
									$updateEntityData['EDITOR_ID'] = $editorId;
								}
								if($session->getData('CRM_CREATE_LEAD') == 'Y' && !empty($binding[\CCrmOwnerType::LeadName]))
								{
									CrmCommon::update(\CCrmOwnerType::LeadName, $binding[\CCrmOwnerType::LeadName], $updateEntityData);
								}

								if($session->getData('CRM_CREATE_CONTACT') == 'Y' && !empty($binding[\CCrmOwnerType::ContactName]))
								{
									CrmCommon::update(\CCrmOwnerType::ContactName, $binding[\CCrmOwnerType::ContactName], $updateEntityData);
								}

								if($session->getData('CRM_CREATE_COMPANY') == 'Y' && !empty($binding[\CCrmOwnerType::CompanyName]))
								{
									CrmCommon::update(\CCrmOwnerType::CompanyName, $binding[\CCrmOwnerType::CompanyName], $updateEntityData);
								}

								if($session->getData('CRM_CREATE_DEAL') == 'Y' && !empty($binding[\CCrmOwnerType::DealName]))
								{
									CrmCommon::update(\CCrmOwnerType::DealName, $binding[\CCrmOwnerType::DealName], $updateEntityData);
								}
							}
							else
							{
								$result->addErrors($crmBindingManager->getErrors());
							}
						}
					}
					else
					{
						$result->addErrors($resultUpdateActivity->getErrors());
					}
				}

			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), self::ERROR_IMOL_NO_SESSION, __METHOD__));
			}
		}
		else
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NOT_LOAD_CRM'), self::ERROR_IMOL_NOT_LOAD_CRM, __METHOD__));
		}

		return $result;
	}
}
