<?php
namespace Bitrix\ImOpenLines;

use \Bitrix\Main\Loader,
	\Bitrix\Main\ModuleManager,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Crm\Tracking,
	\Bitrix\Crm\EntityManageFacility,
	\Bitrix\Crm\Settings\LeadSettings,
	\Bitrix\Crm\Automation\Trigger\OpenLineTrigger,
	\Bitrix\Crm\Integration\Channel\IMOpenLineTracker,
	\Bitrix\Crm\Automation\Trigger\OpenLineMessageTrigger;

use \Bitrix\Im\User as ImUser;

use \Bitrix\ImOpenLines\Queue,
	\Bitrix\Imopenlines\Widget,
	\Bitrix\ImOpenLines\Crm\Fields,
	\Bitrix\ImOpenLines\Im\Messages,
	\Bitrix\ImOpenLines\Crm\Activity,
	\Bitrix\ImOpenLines\ConfigStatistic,
	\Bitrix\ImOpenLines\Crm\Common as CrmCommon;

Loc::loadMessages(__FILE__);

class Crm
{
	const FIND_BY_CODE = 'IMOL';
	const FIND_BY_NAME = 'NAME';
	const FIND_BY_EMAIL = 'EMAIL';
	const FIND_BY_PHONE = 'PHONE';

	const ENTITY_NONE = 'NONE';
	const ENTITY_LEAD = 'LEAD';
	const ENTITY_COMPANY = 'COMPANY';
	const ENTITY_CONTACT = 'CONTACT';
	const ENTITY_DEAL = 'DEAL';
	const ENTITY_ACTIVITY = 'ACTIVITY';

	const FIELDS_COMPANY = 'COMPANY_ID';
	const FIELDS_CONTACT = 'CONTACT_IDS';

	const ERROR_IMOL_NO_SESSION = 'ERROR IMOPENLINES NO SESSION';
	const ERROR_IMOL_CREATING_LEAD = 'ERROR IMOPENLINES CREATING LEAD';
	const ERROR_IMOL_NOT_LOAD_CRM = 'ERROR IMOPENLINES NOT LOAD CRM';
	const ERROR_IMOL_NOT_LOAD_IM = 'ERROR IMOPENLINES NOT LOAD IM';
	const ERROR_IMOL_NO_CRM_BINDINGS = 'ERROR IMOPENLINES NO CRM BINDINGS';
	const ERROR_IMOL_CRM_ACTIVITY = 'ERROR IMOPENLINES CRM ACTIVITY';
	const ERROR_IMOL_CRM_NO_ID_ACTIVITY = 'ERROR IMOPENLINES CRM NO ID ACTIVITY';
	const ERROR_IMOL_CRM_NO_REQUIRED_PARAMETERS = 'ERROR IMOPENLINES CRM NO REQUIRED PARAMETERS';

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
	protected $ignoreSearchCode = false;
	protected $ignoreSearchEmails = false;
	protected $ignoreSearchPhones = false;
	protected $ignoreSearchPerson = false;

	/**
	 * Crm constructor.
	 * @param Session|null $session
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct($session = null)
	{
		$this->fields = new Fields;

		if(!empty($session))
		{
			$this->fields->setSession($session);
		}

		Loader::includeModule("crm");
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		$result = false;

		try
		{
			if(ModuleManager::isModuleInstalled("crm") && Loader::includeModule("crm"))
			{
				$result = true;
			}
		}
		catch (\Exception $e)
		{

		}

		return $result;
	}

	public static function loadMessages()
	{
		Loc::loadMessages(__FILE__);
	}

	/**
	 * @return Fields
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @return bool
	 */
	public function setSkipCreate()
	{
		$this->skipCreate = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isSkipCreate()
	{
		return $this->skipCreate;
	}

	/**
	 * @return bool
	 */
	public function setSkipSearch()
	{
		$this->skipSearch = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isSkipSearch()
	{
		return $this->skipSearch;
	}

	/**
	 * @return bool
	 */
	public function setSkipAutomationTrigger()
	{
		$this->skipTrigger = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isSkipAutomationTrigger()
	{
		return $this->skipTrigger;
	}

	/**
	 * @return bool
	 */
	public function setIgnoreSearchCode()
	{
		$this->ignoreSearchCode = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchCode()
	{
		return $this->ignoreSearchCode;
	}

	/**
	 * @return bool
	 */
	public function setIgnoreSearchEmails()
	{
		$this->ignoreSearchEmails = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchEmails()
	{
		return $this->ignoreSearchEmails;
	}

	/**
	 * @return bool
	 */
	public function setIgnoreSearchPhones()
	{
		$this->ignoreSearchPhones = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchPhones()
	{
		return $this->ignoreSearchPhones;
	}

	/**
	 * @return bool
	 */
	public function setIgnoreSearchPerson()
	{
		$this->ignoreSearchPerson = true;

		return true;
	}

	/**
	 * @return bool
	 */
	public function isIgnoreSearchPerson()
	{
		return $this->ignoreSearchPerson;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		$result = '';

		$fields = $this->getFields();
		$session = $fields->getSession();

		if(!empty($fields->getCode()))
		{
			$result = $fields->getCode();
		}
		elseif(!empty($session) && $session->getData('USER_CODE'))
		{
			$result = $session->getData('USER_CODE');
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getCodeImol()
	{
		$result = '';

		$code  = $this->getCode();

		if(!empty($code))
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
	public function search()
	{
		$fields = $this->getFields();
		$filter = false;

		$facility = $this->getEntityManageFacility();
		$selector = $facility->getSelector();

		if(!$this->isIgnoreSearchCode())
		{
			if (($code = $this->getCode()) && ($codeImol = $this->getCodeImol()))
			{
				$selector->appendCommunicationCriterion(CrmCommon::getCommunicationType($code), $codeImol);
				$filter = true;
			}
		}

		if(!$this->isIgnoreSearchPerson())
		{
			if($fields->getPersonName() != LiveChat::getDefaultGuestName())
			{
				$personName = $fields->getPersonName();
			}
			else
			{
				$personName = '';
			}
			if($fields->getPersonLastName() != LiveChat::getDefaultGuestName())
			{
				$personLastName = $fields->getPersonLastName();
			}
			else
			{
				$personLastName = '';
			}
			if($fields->getPersonSecondName() != LiveChat::getDefaultGuestName())
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

		if(!$this->isIgnoreSearchEmails())
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

		if(!$this->isIgnoreSearchPhones())
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
	public function getEntityManageFacility()
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
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function registrationChanges()
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

				if($bindingsRaw->isSuccess())
				{
					$bindings = $bindingsRaw->getData();
					$newBindings = [];

					if(empty($bindings[\CCrmOwnerType::ContactName]) && empty($bindings[\CCrmOwnerType::CompanyName]))
					{
						if ($this->isSkipSearch() == false)
						{
							$this->search();
						}

						if($companyId = $facility->getSelector()->getCompanyId())
						{
							$bindings[\CCrmOwnerType::CompanyName] = $newBindings[\CCrmOwnerType::CompanyName] = $companyId;
						}

						if($contactId = $facility->getSelector()->getContactId())
						{
							$bindings[\CCrmOwnerType::ContactName] = $newBindings[\CCrmOwnerType::ContactName] = $contactId;
						}

						if(!empty($newBindings))
						{
							$addActivityBindingsRaw = CrmCommon::addActivityBindings($this->activityId, $newBindings);

							if(!$addActivityBindingsRaw->isSuccess())
							{
								$result->addErrors($addActivityBindingsRaw->getErrors());
							}
						}
					}

					if(!empty($bindings))
					{
						$bindingsForCrm = [];

						foreach ($bindings as $typeEntity=>$idEntity)
						{
							if(!empty($idEntity))
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
						if($rawFlags->isSuccess())
						{
							if(!empty($bindingsForCrm))
							{
								$rawTrigger = $this->executeAutomationTrigger($bindingsForCrm, [
									'CONFIG_ID' => $session->getData('CONFIG_ID')
								]);

								if(!$rawTrigger->isSuccess())
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
				$fieldsAddManager = $this->preparingFieldsAddCrmEntity();

				if($fieldsAddManager->isSuccess())
				{
					$fieldsAdd = $fieldsAddManager->getData();

					if ($this->isSkipSearch() == false)
					{
						$this->search();
					}

					if ($this->isSkipCreate())
					{
						$facility->setRegisterMode($facility::REGISTER_MODE_ONLY_UPDATE);
					}

					$facility->setUpdateClientMode($facility::UPDATE_MODE_NONE);
					$isSuccessful = $facility->registerTouch(\CCrmOwnerType::Lead, $fieldsAdd, true, [
						'CURRENT_USER' => $this->getResponsibleCrmId(),
						'DISABLE_USER_FIELD_CHECK' => true
					]);

					if ($isSuccessful)
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

							if(\CCrmOwnerType::ResolveName($registeredEntity->getTypeId()) == \CCrmOwnerType::LeadName)
							{
								ConfigStatistic::getInstance($session->getData('CONFIG_ID'))->addLead();
							}
						}
					}
					else
					{
						if ($facility->hasErrors())
						{
							$errorDescriptions = implode(';', $facility->getErrorMessages());
							$result->addError(new Error($errorDescriptions, self::ERROR_IMOL_CREATING_LEAD, __METHOD__, $fieldsAdd));
						}
					}

					if($result->isSuccess() && !empty($this->getEntityManageFacility()->getActivityBindings()))
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
							if($rawFlags->isSuccess())
							{
								$resultUpdateUser = $this->updateUserConnector();

								if(!$resultUpdateUser->isSuccess())
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

						if($result->isSuccess())
						{
							$rawTrigger = $this->executeAutomationTrigger($this->getEntityManageFacility()->getActivityBindings(), [
								'CONFIG_ID' => $session->getData('CONFIG_ID')
							]);

							if(!$rawTrigger->isSuccess())
							{
								$result->addErrors($rawTrigger->getErrors());
							}
						}
					}
				}
				else
				{
					$result->addErrors($fieldsAddManager->getErrors());
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function preparingFieldsAddCrmEntity()
	{
		$result = new Result;

		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$fieldsAdd = [];
			$fieldsFmAdd = [];

			$fieldsAdd['OPENED'] = 'Y';

			$rawSourceId = $this->getSourceId();
			if ($rawSourceId->isSuccess())
			{
				$fieldsAdd['SOURCE_ID'] = $rawSourceId->getResult();

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
					if(!empty($fieldsFmAdd['EMAIL']['WORK']))
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
					if(!empty($fieldsFmAdd['PHONE']['WORK']))
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
					if (strlen($fields->getPersonWebsite()) > 250)
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

				if(!empty($fieldsFmAdd))
				{
					$fieldsAdd['FM'] = CrmCommon::formatMultifieldFields($fieldsFmAdd);
				}

				if(!empty($fieldsAdd))
				{
					$result->setData($fieldsAdd);
				}
			}
			else
			{
				$result->addErrors($rawSourceId->getErrors());
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendCrmImMessages()
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$messageManager = Messages\Crm::init($session->getData('CHAT_ID'), $session->getData('OPERATOR_ID'));

			if(!empty($this->registeredEntites))
			{
				foreach ($this->registeredEntites as $entity)
				{
					if($entity['SAVE'] == 'Y')
					{
						$messageManager->sendMessageAboutAddEntity($entity['ENTITY_TYPE'], $entity['ENTITY_ID']);
					}
				}
			}

			if(!empty($this->updateEntites))
			{
				foreach ($this->updateEntites as $entity)
				{
					if($entity['SAVE'] == 'Y')
					{
						$messageManager->sendMessageAboutExtendEntity($entity['ENTITY_TYPE'], $entity['ENTITY_ID']);
					}
					elseif($entity['ADD'] == 'Y')
					{
						$messageManager->sendMessageAboutUpdateEntity($entity['ENTITY_TYPE'], $entity['ENTITY_ID']);
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function registerActivity()
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
				if($resultAddActivity->isSuccess())
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
	 * @param $type
	 * @param $id
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function updateEntity($type, $id)
	{
		$result = false;

		$updateFields = [];
		$updateFm = [];

		$fields = $this->getFields();

		$entity = CrmCommon::get($type, $id, true);

		if(!empty($entity))
		{
			$phones = $fields->getPhones();
			$emails = $fields->getEmails();

			$code = $this->getCode();
			$codeImol = $this->getCodeImol();

			$personName = $fields->getPersonName();
			$personLastName = $fields->getPersonLastName();
			$personSecondName = $fields->getPersonSecondName();
			if(!empty($fields->getPersonEmail()) && !Tools\Email::isInArray($emails, $fields->getPersonEmail()))
			{
				$emails[] = $fields->getPersonEmail();
			}
			if(!empty($fields->getPersonPhone()) && !Tools\Phone::isInArray($phones, $fields->getPersonPhone()))
			{
				$phones[] = $fields->getPersonPhone();
			}
			$personWebsite = $fields->getPersonWebsite();

			if ($type != Crm::ENTITY_DEAL && !empty($code) && !empty($codeImol))
			{
				$communicationType = CrmCommon::getCommunicationType($code);
				if(empty($entity['FM']['IM'][$communicationType]) || !in_array($codeImol, $entity['FM']['IM'][$communicationType]))
				{
					$updateFm['IM'][$communicationType][] = $codeImol;
				}

			}

			if(!empty($phones) && $type != Crm::ENTITY_DEAL)
			{
				foreach ($phones as $phone)
				{
					if(empty($entity['FM']['PHONE']['WORK']) || !Tools\Phone::isInArray($entity['FM']['PHONE']['WORK'], $phone))
					{
						$updateFm['PHONE']['WORK'][] = $phone;
					}
				}
			}

			if(!empty($emails) && $type != Crm::ENTITY_DEAL)
			{
				foreach ($emails as $email)
				{
					if(empty($entity['FM']['EMAIL']['WORK']) || !Tools\Email::isInArray($entity['FM']['EMAIL']['WORK'], $email))
					{
						$updateFm['EMAIL']['WORK'][] = $email;
					}
				}
			}

			if(!empty($personName))
			{
				if($type != Crm::ENTITY_DEAL && $type != Crm::ENTITY_COMPANY)
				{
					if(empty($entity['NAME']) && LiveChat::getDefaultGuestName() != $personName)
					{
						$updateFields['NAME'] = $personName;
					}
				}
			}

			if(!empty($personLastName))
			{
				if($type != Crm::ENTITY_DEAL && $type != Crm::ENTITY_COMPANY)
				{
					if(empty($entity['LAST_NAME']) && LiveChat::getDefaultGuestName() != $personLastName)
					{
						$updateFields['LAST_NAME'] = $personLastName;
					}
				}
			}

			if(!empty($personSecondName))
			{
				if($type != Crm::ENTITY_DEAL && $type != Crm::ENTITY_COMPANY)
				{
					if(empty($entity['SECOND_NAME']) && LiveChat::getDefaultGuestName() != $personSecondName)
					{
						$updateFields['SECOND_NAME'] = $personSecondName;
					}
				}
			}

			if(!empty($personWebsite))
			{
				if (strlen($personWebsite) > 250 || $type == Crm::ENTITY_DEAL)
				{
					if($type != Crm::ENTITY_COMPANY)
					{
						if(empty($entity['SOURCE_DESCRIPTION']))
						{
							$updateFields['SOURCE_DESCRIPTION'] = $personWebsite;
						}
					}
				}
				else
				{
					if(empty($entity['FM']['WEB']['HOME']) || !in_array($personWebsite, $entity['FM']['WEB']['HOME']))
					{
						$updateFm['WEB']['HOME'][] = $personWebsite;
					}
				}
			}

			if(!empty($updateFm))
			{
				$updateFields['FM'] = CrmCommon::formatMultifieldFields($updateFm);
			}

			if($type == Crm::ENTITY_LEAD && LeadSettings::getCurrent()->isAutoGenRcEnabled())
			{
				$facility = $this->getEntityManageFacility();

				if(!empty($facility->getSelector()->getCompanyId()))
				{
					$updateFields['COMPANY_ID'] = $facility->getSelector()->getCompanyId();
				}
				if(!empty($facility->getSelector()->getContactId()))
				{
					$updateFields['CONTACT_ID'] = $facility->getSelector()->getContactId();
				}
			}

			if(!empty($updateFields))
			{
				$result = CrmCommon::update($type, $id, $updateFields);
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function updateUserConnector()
	{
		$result = new Result();

		$fields = $this->getFields();
		$session = $fields->getSession();

		if (!empty($session))
		{
			$entitys = array_merge($this->registeredEntites, $this->updateEntites);

			if($session->getData('SOURCE') == Connector::TYPE_LIVECHAT &&
				ImUser::getInstance($session->getData('USER_ID'))->isConnector() &&
				ImUser::getInstance($session->getData('USER_ID'))->getName() == '' &&
				!empty($entitys))
			{
				$entityID = 0;
				$entityType = null;

				foreach ($entitys as $entity)
				{
					if($entity['ENTITY_TYPE'] != 'DEAL' &&
						(empty($entityID) || empty($entityType) || $entity['IS_PRIMARY'] == 'Y')
					)
					{
						$entityID = $entity['ENTITY_ID'];
						$entityType = $entity['ENTITY_TYPE'];
					}
				}

				if(!empty($entityID) && !empty($entityType))
				{
					$entityData = CrmCommon::get($entityType, $entityID, false);

					if(!empty($entityData) && (!empty($entityData['NAME']) || !empty($entityData['LAST_NAME']) || !empty($entityData['SECOND_NAME'])))
					{
						$user = new \CUser();
						$user->Update($session->getData('USER_ID'), Array(
							'NAME' => $entityData['NAME'],
							'LAST_NAME' => $entityData['LAST_NAME'],
							'SECOND_NAME' => $entityData['SECOND_NAME'],
						));

						$relations = \CIMChat::GetRelationById($session->getData('CHAT_ID'));
						\Bitrix\Pull\Event::add(array_keys($relations), Array(
							'module_id' => 'im',
							'command' => 'updateUser',
							'params' => Array(
								'user' => ImUser::getInstance($session->getData('USER_ID'))->getFields()
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getSourceId()
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
				$crmSource = $session->getData('CONFIG_ID') . '|' . CrmCommon::getCommunicationType($session->getData('USER_CODE'), true);

				if (!isset($statuses[$config['CRM_SOURCE']]))
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getOperatorId()
	{
		$id = 0;

		if(Loader::includeModule('crm'))
		{
			$id = $this->getEntityManageFacility()->getPrimaryAssignedById();
		}

		return $id;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getResponsibleCrmId()
	{
		$result = 0;

		$session = $this->getFields()->getSession();

		if(!empty($session))
		{
			if(!empty($session->getData('OPERATOR_ID')) && $session->getData('OPERATOR_ID') > 0)
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
					]
				]);

				while($queueUser = $res->fetch())
				{
					if(ImUser::getInstance($queueUser['USER_ID'])->isActive())
					{
						$queueUserList[] = $queueUser['USER_ID'];
					}
				}

				if(!empty($queueUserList) && is_array($queueUserList))
				{
					$result = current($queueUserList);
				}

				if(empty($result) && Loader::includeModule('bitrix24'))
				{
					$adminList = \CBitrix24::getAllAdminId();

					if(!empty($adminList) && is_array($adminList))
					{
						$result = current($adminList);
					}
				}
				//TODO: END fix

				if(empty($result))
				{
					$result = $session->getData('USER_ID');
				}
			}
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function updateFlags()
	{
		$result = new Result;
		$fields = $this->getFields();
		$session = $fields->getSession();
		$updateSession = [];
		$updateChat = [];

		if (!empty($session))
		{
			if($this->activityId > 0)
			{
				//update session
				$updateSession['CRM_ACTIVITY_ID'] = $this->activityId;

				if(!empty($this->registeredEntites))
				{
					foreach ($this->registeredEntites as $entity)
					{
						switch ($entity['ENTITY_TYPE'])
						{
							case \CCrmOwnerType::LeadName:
								$updateSession['CRM_CREATE_LEAD'] = 'Y';
								break;

							case \CCrmOwnerType::DealName:
								$updateSession['CRM_CREATE_DEAL'] = 'Y';
								break;

							case \CCrmOwnerType::ContactName:
								$updateSession['CRM_CREATE_CONTACT'] = 'Y';
								break;

							case \CCrmOwnerType::CompanyName:
								$updateSession['CRM_CREATE_COMPANY'] = 'Y';
								break;
						}
					}
				}

				//update chat
				$entites = array_merge($this->registeredEntites, $this->updateEntites);

				foreach ($entites as $entity)
				{
					switch ($entity['ENTITY_TYPE'])
					{
						case \CCrmOwnerType::LeadName:
							$updateChat['LEAD'] = $entity['ENTITY_ID'];
							break;

						case \CCrmOwnerType::DealName:
							$updateChat['DEAL'] = $entity['ENTITY_ID'];
							break;

						case \CCrmOwnerType::ContactName:
							$updateChat['CONTACT'] = $entity['ENTITY_ID'];
							break;

						case \CCrmOwnerType::CompanyName:
							$updateChat['COMPANY'] = $entity['ENTITY_ID'];
							break;
					}
				}

				if(!empty($updateChat))
				{
					if(!empty($updateChat['DEAL']))
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

			if(!empty($updateSession))
			{
				$session->updateCrmFlags($updateSession);
			}
			if(!empty($updateChat))
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function setDefaultFlags()
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

		if($this->isSkipAutomationTrigger() != true)
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

		if($this->isSkipAutomationTrigger() != true)
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setSessionAnswered($params = [])
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
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function setSessionClosed($params = [])
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
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function setSessionDataClose($dataClose = null)
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
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setOperatorId($id, $autoMode = false)
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
				if($session->getConfig('CRM') == 'Y' && $session->getConfig('CRM_TRANSFER_CHANGE') == 'Y' && $activityId = $session->getData('CRM_ACTIVITY_ID'))
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

	/**
	 * @deprecated
	 *
	 * @param $type
	 * @param null $id
	 * @return bool|mixed|string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getLink($type, $id = null)
	{
		CrmCommon::getLink($type, $id);
	}
}
