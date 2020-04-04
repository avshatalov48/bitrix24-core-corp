<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/*
 * Use for inheritance
 * $runtime = CBPRuntime::GetRuntime();
 * $runtime->IncludeActivityFile('CBPCrmCreateAdsActivityVk');
*/

class CBPCrmCreateAdsActivityVk extends CBPActivity
{
	protected static function getAdsType()
	{
		return 'vkontakte';
	}

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"accountId" => null,
			"audienceId" => null,
			"audienceEmailId" => null,
			"audiencePhoneId" => null,
			"autoRemoveDayNumber" => 7,
		);
	}

	protected static function getAdsProvider()
	{
		$adsType = static::getAdsType();
		$providers = \Bitrix\Crm\Ads\AdsAudience::getProviders();
		$isFound = false;
		$provider = array();
		foreach ($providers as $type => $provider)
		{
			if ($type == $adsType)
			{
				$isFound = true;
				break;
			}
		}

		if (!$isFound)
		{
			return null;
		}

		return $provider;
	}

	/*
	 * Check modules
	 *
	 * */
	public static function isModulesIncluded()
	{
		if (!CModule::IncludeModule("crm"))
		{
			return false;
		}
		if (!CModule::IncludeModule("seo"))
		{
			return false;
		}
		if (!CModule::IncludeModule("socialservices"))
		{
			return false;
		}

		return true;
	}

	/*
	 * On execute
	 *
	 * */
	public function Execute()
	{
		if (!static::isModulesIncluded())
		{
			return CBPActivityExecutionStatus::Closed;
		}


		$documentId = $this->GetDocumentId();
		//$documentId[0] - crm
		//$documentId[1] - CCrmDocumentDeal
		//$documentId[2] - LEAD_123

		$isError = false;
		if (!is_array($documentId))
		{
			$isError = true;
		}
		if (!$documentId[2])
		{
			$isError = true;
		}

		$entity = explode('_', $documentId[2]);
		$entityTypeId = \CCrmOwnerType::ResolveID($entity[0]);
		$entityId = (int) $entity[1];
		if (!$entityTypeId || !$entityId)
		{
			$isError = true;
		}

		$provider = static::getAdsProvider();
		if (!$provider)
		{
			$isError = true;
		}

		if (!$this->accountId && $provider && $provider['IS_SUPPORT_ACCOUNT'])
		{
			$isError = true;
		}

		$audienceList = array();
		if ($this->audienceId)
		{
			$audienceList[] = array(
				'id' => $this->audienceId,
				'contactType' => null
			);
		}
		if ($this->audiencePhoneId)
		{

			$audienceList[] = array(
				'id' => $this->audiencePhoneId,
				'contactType' => \Bitrix\Seo\Retargeting\Audience::ENUM_CONTACT_TYPE_PHONE
			);
		}
		if ($this->audienceEmailId)
		{
			$audienceList[] = array(
				'id' => $this->audienceEmailId,
				'contactType' => \Bitrix\Seo\Retargeting\Audience::ENUM_CONTACT_TYPE_EMAIL
			);
		}

		if (count($audienceList) == 0)
		{
			$isError = true;
		}

		if (!$isError)
		{
			foreach ($audienceList as $audience)
			{
				$config = new \Bitrix\Crm\Ads\AdsAudienceConfig();
				$config->accountId = $this->accountId;
				$config->audienceId = $audience['id'];
				$config->contactType = $audience['contactType'];
				$config->type = static::getAdsType();
				$config->autoRemoveDayNumber = $this->autoRemoveDayNumber;

				\Bitrix\Crm\Ads\AdsAudience::useQueue();
				\Bitrix\Crm\Ads\AdsAudience::addFromEntity($entityTypeId, $entityId, $config);
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}

	/*
	 * Validate
	 *
	 * */
	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		/*
		if (empty($arTestProperties["accountId"]))
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "accountId", "message" => GetMessage("CRM_CREATE_ADS_EMPTY_PROP"));
		}
		*/
		if (
			empty($arTestProperties["audienceId"])
			&&
			empty($arTestProperties["audienceEmailId"])
			&&
			empty($arTestProperties["audiencePhoneId"])
		)
		{
			$arErrors[] = array("code" => "NotExist", "parameter" => "audienceId", "message" => GetMessage("CRM_CREATE_ADS_EMPTY_PROP"));
		}

		if (
			!empty($arTestProperties["autoRemoveDayNumber"])
			&&
			!is_numeric($arTestProperties["autoRemoveDayNumber"])
		)
		{
			$arErrors[] = array("code" => "NotNumber", "parameter" => "autoRemoveDayNumber", "message" => GetMessage("CRM_CREATE_ADS_WRONG_ARM"));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	/*
	 * On show dialog
	 *
	 * */
	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null, $siteId = '')
	{
		if (!static::isModulesIncluded())
			return '';

		$adsType = static::getAdsType();
		$provider = static::getAdsProvider();

		if (!$provider)
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, array(
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId
		));


		$dialog->setMap(array(
			'accountId' => array(
				'Name' => 'Account id',
				'FieldName' => 'ACCOUNT_ID',
				'Type' => 'string',
				'Required' => false
			),
			'audienceId' => array(
				'Name' => 'Audience id',
				'FieldName' => 'AUDIENCE_ID',
				'Type' => 'string',
				'Required' => false
			),
			'audiencePhoneId' => array(
				'Name' => 'Audience id for phones',
				'FieldName' => 'AUDIENCE_PHONE_ID',
				'Type' => 'string',
				'Required' => false
			),
			'audienceEmailId' => array(
				'Name' => 'Audience id for emails',
				'FieldName' => 'AUDIENCE_EMAIL_ID',
				'Type' => 'string',
				'Required' => false
			),
			'autoRemoveDayNumber' => array(
				'Name' => 'Days auto remove from audience',
				'FieldName' => 'AUTO_REMOVE_DAY_NUMBER',
				'Type' => 'string',
				'Required' => false
			),
		));


		if ($dialog->getCurrentValue('AUDIENCE_EMAIL_ID') || $dialog->getCurrentValue('AUDIENCE_PHONE_ID'))
		{
			$audienceId = array(
				\Bitrix\Seo\Retargeting\Audience::ENUM_CONTACT_TYPE_EMAIL => $dialog->getCurrentValue('AUDIENCE_EMAIL_ID'),
				\Bitrix\Seo\Retargeting\Audience::ENUM_CONTACT_TYPE_PHONE => $dialog->getCurrentValue('AUDIENCE_PHONE_ID'),
			);
		}
		else
		{
			$audienceId = $dialog->getCurrentValue('AUDIENCE_ID');
		}
		$dialog->setRuntimeData(array(
			'PROVIDER' => $provider,
			'ACCOUNT_ID' => $dialog->getCurrentValue('ACCOUNT_ID'),
			'AUDIENCE_ID' => $audienceId,
			'AUTO_REMOVE_DAY_NUMBER' => (int) $dialog->getCurrentValue('AUTO_REMOVE_DAY_NUMBER'),
		));

		return $dialog;
	}

	/*
	 * On save
	 * */
	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$accountId = $arCurrentValues['ACCOUNT_ID'];
		$audienceId = $arCurrentValues['AUDIENCE_ID'];
		$audienceEmailId = $arCurrentValues['AUDIENCE_EMAIL_ID'];
		$audiencePhoneId = $arCurrentValues['AUDIENCE_PHONE_ID'];
		$autoRemoveDayNumber = (int) $arCurrentValues['AUTO_REMOVE_DAY_NUMBER'];

		$arProperties = array(
			'accountId' => $accountId,
			'audienceId' => $audienceId,
			'audiencePhoneId' => $audiencePhoneId,
			'audienceEmailId' => $audienceEmailId,
			'autoRemoveDayNumber' => $autoRemoveDayNumber
		);

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}

	/*
	 * Request router
	 *
	 * */
	public static function getAjaxResponse($request)
	{
		$answer = array(
			'data' => array(),
			'errors' => array(),
		);

		return $answer;
	}

	public function useForcedTracking()
	{
		return true;
	}
}