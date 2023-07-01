<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main;
use Bitrix\Voximplant\Tts;

class CBPVoximplantCallActivity extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener
{
	private $callId;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title'         => '',
			'OutputNumber'  => '',
			'Number'        => '',
			'Text'          => '',
			'VoiceLanguage' => '',
			'VoiceSpeed'    => '',
			'VoiceVolume'   => '',

			'UseAudioFile'  => 'N',
			'AudioFile'     => null,

			"WaitForResult" => 'N',
			"UseDocumentPhoneNumber" => 'N',

			//return
			'Result' => null,
			'ResultText' => '',
			'ResultCode' => '',
		);

		$this->SetPropertiesTypes(array(
			'ResultText' => array(
				'Type' => 'string',
			),
			'ResultCode' => array(
				'Type' => 'string',
			)
		));
	}

	public function Cancel()
	{
		if ($this->WaitForResult == 'Y')
			$this->Unsubscribe($this);
		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("voximplant"))
			return CBPActivityExecutionStatus::Closed;

		if ($this->UseAudioFile != 'Y')
		{
			$callResult = CVoxImplantOutgoing::StartInfoCallWithText(
				$this->OutputNumber,
				$this->getPhoneNumber(),
				$this->prepareTexts($this->ParseValue($this->getRawProperty('Text'), 'text')),
				$this->VoiceLanguage,
				$this->VoiceSpeed,
				$this->VoiceVolume
			);
		}
		else
		{
			$callResult = CVoxImplantOutgoing::StartInfoCallWithSound(
				$this->OutputNumber,
				$this->getPhoneNumber(),
				$this->prepareFiles($this->ParseValue($this->getRawProperty('AudioFile'), 'file'))
			);
		}

		if($callResult->isSuccess())
		{
			$callData = $callResult->getData();
			$this->callId = $callData['CALL_ID'];
			$this->Result = true;
		}
		else
		{
			$this->Result = false;
			foreach ($callResult->getErrorMessages() as $errorMessage)
			{
				$this->WriteToTrackingService($errorMessage, 0, CBPTrackingType::Error);
			}
			return CBPActivityExecutionStatus::Closed;
		}

		if ($this->WaitForResult != 'Y')
			return CBPActivityExecutionStatus::Closed;

		$this->Subscribe($this);
		$this->WriteToTrackingService(GetMessage("BPVICA_TRACK_SUBSCR"));

		return CBPActivityExecutionStatus::Executing;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->SubscribeOnEvent(
			$this->workflow->GetInstanceId(),
			$this->name,
			"voximplant",
			"OnInfoCallResult",
			$this->callId
		);

		$this->workflow->AddEventHandler($this->name, $eventHandler);
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		if ($eventHandler == null)
			throw new Exception("eventHandler");

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnEvent(
			$this->workflow->GetInstanceId(),
			$this->name,
			"voximplant",
			"OnInfoCallResult",
			$this->callId
		);

		$this->workflow->RemoveEventHandler($this->name, $eventHandler);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		$parameters = $arEventParameters[1];

		if(!is_array($parameters))
			return;
		
		if ($this->callId != $arEventParameters[0])
			return;

		$this->Result = ($parameters['RESULT'] ? 'Y' : 'N');

		$this->ResultText = sprintf('%s (%s)',
			GetMessage($parameters['RESULT'] ? 'BPVICA_RESULT_TRUE' : 'BPVICA_RESULT_FALSE'),
			$parameters['CODE']
		);
		$this->ResultCode = $parameters['CODE'];

		$this->Unsubscribe($this);
		$this->workflow->CloseActivity($this);
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $currentValues = null, $formName = "", $popupWindow = null, $currentSiteId = null)
	{
		if (!CModule::IncludeModule("voximplant"))
			return '<tr><td colspan="2" style="color: red">'.GetMessage('BPVICA_INCLUDE_MODULE').'</td></tr>';

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'formName' => $formName,
			'siteId' => $currentSiteId,
		));

		$outputNumber = CVoxImplantConfig::GetPortalNumbers(false);
		$voiceLanguage = Tts\Language::getList();
		$voiceSpeed = Tts\Speed::getList();
		$voiceVolume = Tts\Volume::getList();

		$propertiesMap = array(
			"OutputNumber"  => array(
				'Type' => 'select',
				'FieldName' => "output_number",
				'Required' => true,
				'Default' => CVoxImplantConfig::GetPortalNumber(),
				'Options' => $outputNumber
			),
			"Number" => array(
				'Type' => 'string',
				'FieldName' => "number",
				'Required' => true
			),
			"UseAudioFile"  => array(
				'Type' => 'bool',
				'FieldName' => "use_audio_file",
				'Required' => true,
				'Default' => \Bitrix\Voximplant\Limits::hasAccountBalance() ? 'N' : 'Y',
			),
			"Text" => array(
				'Description' => GetMessage('BPVICA_PROPERTY_TEXT'),
				'Type' => 'text',
				'FieldName' => "text",
				'Required' => true
			),
			"VoiceLanguage" => array(
				'Type' => 'select',
				'FieldName' => "voice_language",
				'Default' => Tts\Language::getDefaultVoice(Main\Context::getCurrent()->getLanguage()),
				'Options' => $voiceLanguage
			),
			"VoiceSpeed"    => array(
				'Type' => 'select',
				'FieldName' => "voice_speed",
				'Default' => Tts\Speed::getDefault(),
				'Options' => $voiceSpeed
			),
			"VoiceVolume"   => array(
				'Type' => 'select',
				'FieldName' => "voice_volume",
				'Default' => Tts\Volume::getDefault(),
				'Options' => $voiceVolume
			),
			"AudioFile"     => array(
				'Description' => 'https://',
				'Type' => 'string',
				'FieldName' => "audio_file",
			),
			"WaitForResult" => array(
				'Type' => 'bool',
				'FieldName' => "wait_for_result",
				'Default' => 'N'
			),
			"UseDocumentPhoneNumber" => array(
				'Type' => 'bool',
				'FieldName' => "use_document_phone_number",
				'Default' => 'N'
			),
		);

		$dialog->setMap($propertiesMap);

		if (!is_array($currentValues))
		{
			$currentValues = $dialog->getCurrentValues(false, true);
		}

		$dialog->setRuntimeData(array(
			"currentValues" => $currentValues,
			"outputNumber" => $outputNumber,
			"voiceLanguage" => $voiceLanguage,
			"voiceSpeed" => $voiceSpeed,
			"voiceVolume" => $voiceVolume,
			"isEnableText" => \Bitrix\Voximplant\Limits::hasAccountBalance(),
		));

		return $dialog;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$workflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$propertiesMap = array(
			"output_number" => "OutputNumber",
			"number" => "Number",
			"use_audio_file" => "UseAudioFile",
			"text" => "Text",
			"voice_language" => "VoiceLanguage",
			"voice_speed" => "VoiceSpeed",
			"voice_volume" => "VoiceVolume",
			"audio_file" => "AudioFile",
			"wait_for_result" => "WaitForResult",
			"use_document_phone_number" => "UseDocumentPhoneNumber",
		);

		$properties = array();
		foreach ($propertiesMap as $key => $value)
		{
			$properties[$value] = $arCurrentValues[$key];
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (!empty($errors))
			return false;

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}

	public static function ValidateProperties($testProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = array();

		if (empty($testProperties['OutputNumber']))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "OutputNumber", "message" => GetMessage("BPVICA_ERROR_OUTPUT_NUMBER"));
		}

		if (empty($testProperties['Number']) && $testProperties['UseDocumentPhoneNumber'] !== 'Y')
		{
			$errors[] = array("code" => "NotExist", "parameter" => "Number", "message" => GetMessage("BPVICA_ERROR_NUMBER"));
		}

		if ($testProperties['UseAudioFile'] != 'Y' && empty($testProperties['Text']))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "Text", "message" => GetMessage("BPVICA_ERROR_TEXT"));
		}

		if ($testProperties['UseAudioFile'] == 'Y' && empty($testProperties['AudioFile']))
		{
			$errors[] = array("code" => "NotExist", "parameter" => "AudioFile", "message" => GetMessage("BPVICA_ERROR_AUDIO_FILE"));
		}

		return array_merge($errors, parent::ValidateProperties($testProperties, $user));
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();

		$this->callId = null;
		$this->Result = null;
		$this->ResultText = '';
		$this->ResultCode = '';
	}

	private function prepareNumbers($numbers)
	{
		if(is_string($numbers))
			$numbers = explode(',', $numbers);
		else
			$numbers = (array) $numbers;


		$result = array();

		foreach ($numbers as $number)
		{
			if (!$number || !is_scalar($number))
				continue;

			$normalized = \NormalizePhone($number, 1);
			if ($normalized)
				$result[] = $normalized;
		}

		return sizeof($result) > 0 ? $result[0] : '';
	}

	private function prepareTexts($texts)
	{
		$texts = (array) $texts;
		$result = array();

		foreach ($texts as $text)
		{
			if (!$text || !is_scalar($text))
				continue;

			$result[] = strip_tags($text);
		}

		return sizeof($result) == 1 ? $result[0] : $result;
	}

	private function prepareFiles($files)
	{
		$files = (array) $files;
		$result = array();

		foreach ($files as $file)
		{
			if (!$file || !is_scalar($file))
				continue;

			if (preg_match("#^(http[s]?)://#", $file))
			{
				$result[] = $file;
			}
			elseif (intval($file) > 0)
			{
				$fileArray = \CFile::GetFileArray($file);
				if (!is_array($fileArray))
					continue;

				$result[] = $fileArray['SRC']; // append protocol & domain?
			}
		}

		return sizeof($result) == 1 ? $result[0] : $result;
	}

	private function getPhoneNumber()
	{
		if ($this->UseDocumentPhoneNumber === 'Y')
		{
			$number = $this->getDocumentPhoneNumber();
		}
		else
		{
			$number = $this->ParseValue($this->getRawProperty('Number'), 'string');
		}

		return $this->prepareNumbers($number);
	}

	private function getDocumentPhoneNumber()
	{
		$number = '';
		$documentId = $this->GetDocumentId();
		if ($documentId[0] == 'crm' && CModule::IncludeModule('crm'))
		{
			list($entityTypeName, $entityId) = mb_split('_(?=[^_]*$)', $documentId[2]);
			$communications = array();
			switch ($entityTypeName)
			{
				case \CCrmOwnerType::LeadName:
					$communications = $this->getLeadCommunications($entityId);
					break;
				case \CCrmOwnerType::ContactName:
				case \CCrmOwnerType::CompanyName:
					$communications = $this->getPhoneFromFM($entityTypeName, $entityId);
					break;
				case \CCrmOwnerType::DealName:
					$communications = $this->getDealCommunications($entityId);
					break;
				case \CCrmOwnerType::OrderName:
					$communications = $this->getOrderCommunications($entityId);
					break;

				default:
					if (class_exists(\Bitrix\Crm\Service\Container::class))
					{
						$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(
							CCrmOwnerType::ResolveID($entityTypeName)
						);
						if ($factory)
						{
							$item = $factory->getItem((int)$entityId);
							if ($item)
							{
								$communications = $this->getCommunicationsFromItem($item);
							}
						}
					}
			}

			$communications = array_slice($communications, 0, 1);
			$number = $communications? $communications[0]['VALUE'] : '';
		}

		return $number;
	}

	private function getDealCommunications($id)
	{
		$communications = array();

		$entity = CCrmDeal::GetByID($id, false);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getPhoneFromFM(CCrmOwnerType::ContactName, $entityContactID);
		}

		if (empty($communications))
		{
			$dealContactIds = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($id);
			if ($dealContactIds)
			{
				foreach ($dealContactIds as $contId)
				{
					if ($contId !== $entityContactID)
					{
						$communications = $this->getPhoneFromFM(CCrmOwnerType::ContactName, $contId);
						if ($communications)
						{
							break;
						}
					}
				}
			}
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getPhoneFromFM(CCrmOwnerType::CompanyName, $entityCompanyID);
		}

		return $communications;
	}

	private function getOrderCommunications($id)
	{
		$communications = [];

		$dbRes = \Bitrix\Crm\Order\ContactCompanyCollection::getList(array(
			'select' => array('ENTITY_ID', 'ENTITY_TYPE_ID'),
			'filter' => array(
				'=ORDER_ID' => $id,
				'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
				'IS_PRIMARY' => 'Y'
			),
			'order' => ['ENTITY_TYPE_ID' => 'ASC']
		));
		while ($row = $dbRes->fetch())
		{
			$communications = $this->getPhoneFromFM(CCrmOwnerType::ResolveName($row['ENTITY_TYPE_ID']), $row['ENTITY_ID']);
			if ($communications)
			{
				break;
			}
		}

		return $communications;
	}

	private function getLeadCommunications($id)
	{
		$communications = $this->getPhoneFromFM(CCrmOwnerType::LeadName, $id);

		if ($communications)
		{
			return $communications;
		}

		$entity = CCrmLead::GetByID($id, false);
		if(!$entity)
		{
			return array();
		}

		$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;

		if ($entityContactID > 0)
		{
			$communications = $this->getPhoneFromFM(CCrmOwnerType::ContactName, $entityContactID);
		}

		if (empty($communications) && $entityCompanyID > 0)
		{
			$communications = $this->getPhoneFromFM(CCrmOwnerType::CompanyName, $entityCompanyID);
		}

		return $communications;
	}

	private function getCommunicationsFromItem(\Bitrix\Crm\Item $item): array
	{
		$contactBindings = $item->getContactBindings();
		$communications = [];
		foreach ($contactBindings as $binding)
		{
			$contactId = (int)($binding['CONTACT_ID'] ?? 0);
			if ($contactId > 0)
			{
				$communications = $this->getPhoneFromFM(CCrmOwnerType::ContactName, $contactId);
				if (!empty($communications))
				{
					break;
				}
			}
		}

		if (empty($communications) && $item->getCompanyId() > 0)
		{
			$communications = $this->getPhoneFromFM(CCrmOwnerType::CompanyName, $item->getCompanyId());
		}

		return $communications;
	}

	private function getPhoneFromFM($entityTypeName, $entityId)
	{
		$communications = array();

		$iterator = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityTypeName,
				  'ELEMENT_ID' => $entityId,
				  'TYPE_ID' => 'PHONE'
			)
		);

		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
				continue;

			$communications[] = array(
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => 'PHONE',
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE']
			);
		}

		return $communications;
	}
}