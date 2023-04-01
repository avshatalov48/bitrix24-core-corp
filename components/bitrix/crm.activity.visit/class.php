<?php

use Bitrix\Crm;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);
Crm\Service\Container::getInstance()->getLocalization()->loadMessages();

class CrmActivityVisitComponent extends \CBitrixComponent implements Main\Errorable
{
	const ACTION_VIEW = 'VIEW';
	const ACTION_EDIT = 'EDIT';
	const ACTION_SOCIAL = 'SOCIAL';
	const MAX_IMAGE_SIZE = 2097152;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
	}

	public function executeComponent()
	{
		$action = $this->getAction();
		switch ($action)
		{
			case self::ACTION_EDIT:
				if (!Crm\Restriction\RestrictionManager::getVisitRestriction()->hasPermission())
				{
					$this->errorCollection->setError(new Main\Error(Loc::getMessage('CRM_FEATURE_RESTRICTION_ERROR'), 'crm-tariff-lock'));
					return $this->includeComponentTemplate('error');
				}
				return $this->executeEditAction();
				break;
			case self::ACTION_SOCIAL:
				return $this->executeSocialAction();
				break;
			default:
				return $this->executeViewAction();
				break;
		}
	}

	public static function saveActivity($fields, $userId, $siteId)
	{
		$result = new Main\Result();

		$startTime = Main\Type\DateTime::createFromTimestamp($fields['CREATE_TIMESTAMP']);
		$recordFileDescription = Main\Application::getInstance()->getContext()->getRequest()->getFile('RECORD');
		if(!$recordFileDescription)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_NO_RECORD_ERROR')));
			return $result;
		}
		$recordFileDescription['name'] = 'record_' . $startTime->format('Y_m_d_h_i_s') . '.mp3';
		$recordFileDescription['MODULE_ID'] = 'crm';
		$fileId = CFile::SaveFile($recordFileDescription, 'crm');
		
		if(!$fileId)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_NO_RECORD_ERROR')));
			return $result;
		}

		$bindings = array();
		if ($fields['OWNER_ENTITY_ID'] > 0 && $fields['OWNER_ENTITY_TYPE'] != '')
		{
			$ownerTypeId = CCrmOwnerType::ResolveID($fields['OWNER_ENTITY_TYPE']);
			$ownerId = (int)$fields['OWNER_ENTITY_ID'];
		}
		else
		{
			$leadId = self::createLead(array(
				'START_TIME' => $startTime,
				'USER_ID' => $userId,
			));

			if($leadId === false)
			{
				$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_VISIT_LEAD_CREATE_ERROR')));
				return $result;
			}
			$ownerTypeId = CCrmOwnerType::Lead;
			$ownerId = (int)$leadId;
		}

		if($fields['VK_PROFILE'] != '')
		{
			self::saveVkProfile($ownerTypeId, $ownerId, $fields['VK_PROFILE']);
		}

		$bindings[] = array(
			'OWNER_TYPE_ID' => $ownerTypeId,
			'OWNER_ID' => $ownerId
		);

		if($fields['HAS_PHOTO'] === 'Y')
		{
			self::updateRecognizePicture($ownerTypeId, $ownerId);
		}

		if($fields['SAVE_PHOTO'] === 'Y' && $ownerTypeId === CCrmOwnerType::Contact && isset($_FILES['IMAGE']))
		{
			$imageDescriptor = $_FILES['IMAGE'];
			$imageDescriptor['name'] = 'contact_'.$ownerId.'_image.png';
			if(CFile::CheckImageFile($imageDescriptor) === null)
			{
				$imageId = CFile::SaveFile($imageDescriptor, 'crm');
				if ($imageId > 0)
				{
					$contact = new CCrmContact();
					$contactFields = array(
						'PHOTO' => $imageId
					);
					$contact->Update($ownerId, $contactFields);
				}
			}
		}

		if(is_array($fields['CREATED_DEALS']))
		{
			foreach ($fields['CREATED_DEALS'] as $dealId)
			{
				$dealId = (int)$dealId;
				if($dealId > 0)
				{
					$bindings[] = array(
						'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
						'OWNER_ID' => $dealId
					);
				}
			}
		}

		if(is_array($fields['CREATED_INVOICES']))
		{
			foreach ($fields['CREATED_INVOICES'] as $invoiceId)
			{
				$invoiceId = (int)$invoiceId;
				if($invoiceId > 0)
				{
					$bindings[] = array(
						'OWNER_TYPE_ID' => CCrmOwnerType::Invoice,
						'OWNER_ID' => $invoiceId
					);
				}
			}
		}

		$providerParams = array(
			'RECORD_LENGTH' => (int)$fields['RECORD_LENGTH']
		);

		$activityFields = array(
			'PROVIDER_ID' => Activity\Provider\Visit::PROVIDER_ID,
			'PROVIDER_TYPE_ID' => Activity\Provider\Visit::TYPE_VISIT,
			'START_TIME' => $startTime,
			'COMPLETED' => 'Y',
			'PRIORITY' => CCrmActivityPriority::Medium,
			'SUBJECT' => Loc::getMessage('CRM_ACTIVITY_VISIT_SUBJECT'),
			'DESCRIPTION' => Loc::getMessage(
				'CRM_ACTIVITY_VISIT_DESCRIPTION',
				array(
					'#DATE#' => CCrmComponentHelper::TrimDateTimeString($startTime),
			)),
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'LOCATION' => '',
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'BINDINGS' => $bindings,
			'SETTINGS' => array(),
			'AUTHOR_ID' => $userId,
			'RESPONSIBLE_ID' => $userId,
			'STORAGE_TYPE_ID' => Integration\StorageType::File,
			'STORAGE_ELEMENT_IDS' => array($fileId),
			'PROVIDER_PARAMS' => $providerParams
		);
		
		$activityId = CCrmActivity::Add($activityFields, true, true, array('REGISTER_SONET_EVENT' => true));
		if($activityId > 0)
		{
			$communications = array(
				array(
					'ID' => 0,
					'ENTITY_ID' => $ownerId,
					'ENTITY_TYPE_ID' => $ownerTypeId
				)
			);

			CCrmActivity::SaveCommunications($activityId, $communications, $activityFields, true, false);
		}

		if($activityId > 0)
		{
			$result->setData(array(
				'ACTIVITY_ID' => $activityId
			));

			//Execute automation trigger
			\Bitrix\Crm\Automation\Trigger\VisitTrigger::execute($bindings, array('ACTIVITY_ID' => $activityId));
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_CREATE_ERROR') . ': ' . CCrmActivity::GetLastErrorMessage()));
		}
		return $result;
	}

	public static function saveVkProfile($entityTypeId, $entityId, $profile)
	{
		$entityType = CCrmOwnerType::ResolveName($entityTypeId);
		$fieldId = 'n0';
		$cursor = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityId)
		);

		while ($row = $cursor->Fetch())
		{
			if($row['TYPE_ID'] === 'WEB' && $row['VALUE_TYPE'] === 'VK')
			{
				$fieldId = $row['ID'];
				break;
			}
		}

		$record = array(
			'WEB' => array(
				$fieldId => array(
					'VALUE' => $profile,
					'VALUE_TYPE' => 'VK'
				)
			)
		);
		$CCrmFieldMulti = new CCrmFieldMulti();
		$CCrmFieldMulti->SetFields($entityType, $entityId, $record);
	}

	public static function saveRecognizeConsent($fields)
	{
		global $USER;
		$userId = $USER->GetID();
		$result = new Main\Result();
		if(!Main\Loader::includeModule('faceid'))
		{
			$result->addError(new Main\Error('Face detection module is not installed'));
			return $result;
		}

		$checkCursor = \Bitrix\Faceid\AgreementTable::getList(array(
			'filter' => array('=USER_ID' => $userId)
		));
		if(!$checkCursor->fetch())
		{
			\Bitrix\Faceid\AgreementTable::add(array(
				'USER_ID' => $userId,
				'NAME' => $USER->GetFullName(),
				'EMAIL' => $USER->GetEmail(),
				'DATE' => new \Bitrix\Main\Type\DateTime,
				'IP_ADDRESS' => \Bitrix\Main\Context::getCurrent()->getRequest()->getRemoteAddress()
			));
		}
		
		return $result;
	}
	
	public static function recognizeFace($fields, $userId)
	{
		$result = new Main\Result();

		if(!Main\Loader::includeModule('faceid'))
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_VISIT_NO_FACEID')));
			return $result;
		}
		
		if(!isset($_FILES['IMAGE']))
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_VISIT_NO_PICTURE')));
			return $result;
		}

		$uploadedFileDescriptor = $_FILES['IMAGE'];
		$imageFile = new Bitrix\Main\IO\File($uploadedFileDescriptor['tmp_name']);
		$imageFile->open('rb');
		
		if($imageFile->getSize() > self::MAX_IMAGE_SIZE)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_VISIT_FILE_TOO_LARGE')));
			return $result;
		}

		$rawImage = $imageFile->getContents();
		$recognizeResponse = \Bitrix\FaceId\FaceId::identify($rawImage, 'vtracker');
		
		if($recognizeResponse['success'] !== true)
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_ACTIVITY_VISIT_FACE_SERVER_ERROR')));
			return $result;
		}

		if($recognizeResponse['result']['found'] === true)
		{
			// simply(c) using most confident result
			$faceDescriptor = $recognizeResponse['result']['items'][0];
			$faceId = $faceDescriptor['face_id'];
			$entity = self::getEntityByFaceId($faceId);
			$response = array(
				'FACE_ID' => $faceId,
			);
			if($entity != false)
			{
				$response['ENTITY_TYPE'] = $entity['ENTITY_TYPE'];
				$response['ENTITY_ID'] = $entity['ENTITY_ID'];
			}
		}
		else
		{
			$response = array(
				'FACE_ID' => 0
			);
		}

		$response['DEBUG'] = $recognizeResponse;
		$result->setData($response);
		return $result;
	}

	protected function executeEditAction()
	{
		$this->arResult = $this->prepareDataForEdit();
		$this->includeComponentTemplate('edit');
		return $this->arResult;
	}

	protected function executeSocialAction()
	{
		$this->arResult = $this->prepareDataForSocial();
		$this->includeComponentTemplate('social');
		return $this->arResult;
	}

	protected function executeViewAction()
	{
		$this->arResult = $this->prepareDataForView();
		$this->includeComponentTemplate('view');
		return $this->arResult;
	}

	/**
	 * @return array
	 */
	protected function prepareDataForEdit()
	{
		$result = array();
		$userPermissions = CCrmPerms::GetUserPermissions($this->getCurrentUserId());

		$result['CAN_CREATE_CONTACT'] = CCrmContact::CheckCreatePermission($userPermissions);
		$result['CAN_CREATE_LEAD'] = CCrmLead::CheckCreatePermission($userPermissions);
		$result['CAN_CREATE_DEAL'] = CCrmDeal::CheckCreatePermission($userPermissions);
		$result['CAN_CREATE_INVOICE'] = CCrmInvoice::CheckCreatePermission($userPermissions);
		$result['DEAL'] = '';
		if(isset($this->arParams['ENTITY_TYPE']) && isset($this->arParams['ENTITY_ID']))
		{
			if($this->arParams['ENTITY_TYPE'] === CCrmOwnerType::DealName)
			{
				$deal = CCrmDeal::GetByID($this->arParams['ENTITY_ID'], true);
				if($deal)
				{
					if($deal['CONTACT_ID'] > 0)
					{
						$result['OWNER_ENTITY_TYPE'] = CCrmOwnerType::ContactName;
						$result['OWNER_ENTITY_ID'] = (int)$deal['CONTACT_ID'];
					}
					else if ($deal['COMPANY_ID'] > 0)
					{
						$result['OWNER_ENTITY_TYPE'] = CCrmOwnerType::CompanyName;
						$result['OWNER_ENTITY_ID'] = (int)$deal['COMPANY_ID'];
					}
					else if ($deal['LEAD_ID'] > 0)
					{
						$result['OWNER_ENTITY_TYPE'] = CCrmOwnerType::LeadName;
						$result['OWNER_ENTITY_ID'] = (int)$deal['LEAD_ID'];
					}
					$result['DEAL'] = (int)$deal['ID'];
				}
			}
			else
			{
				$result['OWNER_ENTITY_TYPE'] = (string)$this->arParams['ENTITY_TYPE'];
				$result['OWNER_ENTITY_ID'] = (int)$this->arParams['ENTITY_ID'];
			}

			$result['SHOW_ENTITY_SELECTOR'] = false;
		}
		else
		{
			$result['SHOW_ENTITY_SELECTOR'] = true;
		}

		if ($result['CAN_CREATE_LEAD'])
		{
			$result['CREATE_LEAD_CONTEXT'] = 'visit_'.$this->randString();
			$result['CREATE_LEAD_URL'] = CCrmUrlUtil::AddUrlParams(
				CCrmOwnerType::GetEditUrl(CCrmOwnerType::Lead, 0),
				array('external_context' => $result['CREATE_LEAD_CONTEXT'])
			);
		}

		if ($result['CAN_CREATE_CONTACT'])
		{
			$result['CREATE_CONTACT_CONTEXT'] = 'visit_'.$this->randString();
			$result['CREATE_CONTACT_URL'] = CCrmUrlUtil::AddUrlParams(
				CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0),
				array('external_context' => $result['CREATE_CONTACT_CONTEXT'])
			);
		}

		if($result['CAN_CREATE_DEAL'])
		{
			$result['CREATE_DEAL_CONTEXT'] = 'visit_'.$this->randString();
			$result['CREATE_DEAL_URL'] = CCrmUrlUtil::AddUrlParams(
				CCrmOwnerType::GetEditUrl(CCrmOwnerType::Deal, 0),
				array('external_context' => $result['CREATE_DEAL_CONTEXT'])
			);
		}

		if($result['CAN_CREATE_INVOICE'])
		{
			$result['CREATE_INVOICE_CONTEXT'] = 'visit_'.$this->randString();
			$result['CREATE_INVOICE_URL'] = CCrmUrlUtil::AddUrlParams(
				CCrmOwnerType::GetEditUrl(CCrmOwnerType::Invoice, 0),
				array('external_context' => $result['CREATE_INVOICE_CONTEXT'])
			);
		}

		$result['FACEID_ENABLED'] = Main\Loader::includeModule('faceid') && \Bitrix\FaceId\FaceId::isAvailable() && $this->arParams['HAS_RECOGNIZE_CONSENT'];
		return $result;
	}

	/**
	 * @return array
	 */
	protected function prepareDataForSocial()
	{
		$result = array();
		$result['SUCCESS'] = false;
		if(!Main\Loader::includeModule('faceid'))
		{
			$result['ERROR'] = Loc::getMessage('CRM_ACTIVITY_VISIT_NO_FACEID');
			return $result;
		}

		if(!isset($_FILES['IMAGE']))
		{
			$result['ERROR'] = Loc::getMessage('CRM_ACTIVITY_VISIT_NO_PICTURE');
			return $result;
		}

		$uploadedFileDescriptor = $_FILES['IMAGE'];
		$imageFile = new Bitrix\Main\IO\File($uploadedFileDescriptor['tmp_name']);
		$imageFile->open('rb');

		if($imageFile->getSize() > self::MAX_IMAGE_SIZE)
		{
			$result['ERROR'] = Loc::getMessage('CRM_ACTIVITY_VISIT_FILE_TOO_LARGE');
			return $result;
		}

		$rawImage = $imageFile->getContents();
		$recognizeResponse = \Bitrix\FaceId\FaceId::identifyVk($rawImage);
		
		if($recognizeResponse['success'] != true)
		{
			$result['ERROR'] = Loc::getMessage('CRM_ACTIVITY_VISIT_FACE_SERVER_ERROR');
			return $result;
		}
		
		if($recognizeResponse['result']['found'] != true)
		{
			$result['ERROR'] = Loc::getMessage('CRM_ACTIVITY_VISIT_FACE_NOTHING_FOUND');
			return $result;
		}

		$result['SUCCESS'] = true;
		$result['VK_PROFILES'] = $recognizeResponse['result']['items'];
		return $result;
	}

	/**
	 * @return array
	 */
	protected function prepareDataForView()
	{
		$activity = $this->arParams['~ACTIVITY'];
		$result = array(
			'ACTIVITY' => $activity,
			'RECORDS' => array(),
			'PHOTO' => array(),
			'STORAGE_ELEMENTS' => array()
		);

		if (is_array($activity["STORAGE_ELEMENT_IDS"]) && count($activity["STORAGE_ELEMENT_IDS"]) > 0)
		{
			$mediaExtensions = array("flv", "mp3", "mp4", "vp6", "aac");
			foreach($activity["STORAGE_ELEMENT_IDS"] as $elementID)
			{
				$info = Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$elementID, $activity["STORAGE_TYPE_ID"],
					false,
					array('OWNER_TYPE_ID' => CCrmOwnerType::Activity, 'OWNER_ID' => $activity['ID'])
				);
				if(is_array($info) && in_array(GetFileExtension(mb_strtolower($info["NAME"])), $mediaExtensions))
				{
					$recordUrl = CCrmUrlUtil::ToAbsoluteUrl($info["VIEW_URL"]);
					if($activity["STORAGE_TYPE_ID"] == CCrmActivityStorageType::WebDav)
					{
						//Hacks for flv player
						if(mb_substr($recordUrl, -1) !== "/")
						{
							$recordUrl .= "/";
						}
						$recordUrl .= !empty($info["NAME"]) ? $info["NAME"] : "dummy.flv";
					}
					$result["RECORDS"][] = array(
						"URL" =>$recordUrl,
						"NAME" => $info["NAME"],
						"INFO" => $info
					);
				}
				elseif (!empty($activity['PROVIDER_PARAMS']['tracker_photo']) && $elementID == $activity['PROVIDER_PARAMS']['tracker_photo'])
				{
					// it is a photo
					$photoUrl = CCrmUrlUtil::ToAbsoluteUrl($info["VIEW_URL"]);

					if($activity["STORAGE_TYPE_ID"] == CCrmActivityStorageType::WebDav)
					{
						//Hacks for flv player
						if(mb_substr($photoUrl, -1) !== "/")
						{
							$photoUrl .= "/";
						}
						$photoUrl .= !empty($info["NAME"]) ? $info["NAME"] : "";
					}

					$result["PHOTO"] = array(
						"URL" =>$photoUrl,
						"NAME" => $info["NAME"],
						"INFO" => $info
					);
				}

				$result["STORAGE_ELEMENTS"][] = $info;
			}
		}
		
		return $result;
	}
	
	protected function getCurrentUserId()
	{
		global $USER;
		return $USER->GetID();
	}

	protected function getAction()
	{
		return isset($this->arParams['ACTION']) ? $this->arParams['ACTION'] : self::ACTION_VIEW;
	}

	protected static function updateRecognizePicture($entityTypeId, $entityId)
	{
		if(!Main\Loader::includeModule('faceid'))
			return;

		if(!isset($_FILES['IMAGE']))
			return;

		switch ($entityTypeId)
		{
			case CCrmOwnerType::Lead:
				$entityFields = CCrmLead::GetByID($entityId);
				break;
			case CCrmOwnerType::Contact:
				$entityFields = CCrmContact::GetByID($entityId);
				break;
			default:
				return;
				break;
		}

		if(!is_array($entityFields))
			return;

		if($entityFields['FACE_ID'] > 0)
			return;

		$uploadedFileDescriptor = $_FILES['IMAGE'];
		$imageFile = new Bitrix\Main\IO\File($uploadedFileDescriptor['tmp_name']);
		$imageFile->open('rb');

		if($imageFile->getSize() > self::MAX_IMAGE_SIZE)
			return;

		$rawImage = $imageFile->getContents();
		$recognizeResponse = \Bitrix\FaceId\FaceId::add($rawImage, 'vtracker');

		if($recognizeResponse['success'] !== true)
			return;

		if($recognizeResponse['result']['added'] !== true)
			return;

		$faceId = (int)$recognizeResponse['result']['item']['face_id'];

		if($faceId == 0)
			return;

		$fields = array(
			'FACE_ID' => $faceId
		);
		switch ($entityTypeId)
		{
			case CCrmOwnerType::Lead:
				$lead = new CCrmLead();
				$lead->Update($entityId, $fields);
				break;
			case CCrmOwnerType::Contact:
				$contact = new CCrmContact();
				$contact->Update($entityId, $fields);
				break;
		}
	}

	/**
	 * Creates new lead. Returns id of the lead or false in case of errors.
	 * @param $params
	 * @return int|false 
	 */
	protected static function createLead($params)
	{
		$leadFields = array(
			'TITLE' => Loc::getMessage('CRM_ACTIVITY_VISIT_LEAD_TITLE', array('#DATE#' => $params['START_TIME'])),
			'OPENED' => LeadSettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N',
		);

		$crmLead = new CCrmLead();
		$leadId = $crmLead->Add($leadFields, true, array(
			'CURRENT_USER' => $params['USER_ID'],
			'DISABLE_USER_FIELD_CHECK' => true
		));

		return $leadId;
	}

	/**
	 * @param int $faceId
	 * @return array ['ENTITY_TYPE' => string, 'ENTITY_ID' => string] | false
	 */
	public static function getEntityByFaceId($faceId)
	{
		$result = false;
		$cursor = CCrmContact::GetListEx(array(), array('=FACE_ID' => $faceId), false, false, array('ID'));

		if($row = $cursor->Fetch())
		{
			$result = array(
				'ENTITY_TYPE' => CCrmOwnerType::ContactName,
				'ENTITY_ID' => (int)$row['ID']
			);
			return $result;
		}

		$cursor = CCrmLead::GetListEx(array(), array('=FACE_ID' => $faceId), false, false, array('ID'));
		if($row = $cursor->Fetch())
		{
			$result = array(
				'ENTITY_TYPE' => CCrmOwnerType::LeadName,
				'ENTITY_ID' => (int)$row['ID']
			);
			return $result;
		}

		return false;
	}

	protected function getVkProfile($entityType, $entityId)
	{
		$cursor = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityId)
		);
		while ($row = $cursor->Fetch())
		{
			if($row['TYPE_ID'] === 'WEB' && $row['VALUE_TYPE'] === 'VK')
			{
				return $row['VALUE'];
			}
		}
		return '';
	}
	/**
	 * @param string $code
	 * @return Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}
}
