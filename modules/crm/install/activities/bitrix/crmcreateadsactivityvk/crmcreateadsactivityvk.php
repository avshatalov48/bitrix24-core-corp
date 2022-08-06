<?php

use Bitrix\Crm\Ads\AdsAudience;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

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
			"clientId" => null,
			"accountId" => null,
			"audienceId" => null,
			"audienceEmailId" => null,
			"audiencePhoneId" => null,
			"autoRemoveDayNumber" => 7,
		);
	}

	protected static function getAdsProvider($clientId = null)
	{
		$adsType = static::getAdsType();

		$service = AdsAudience::getService();
		$service->setClientId($clientId);
		$providers = AdsAudience::getProviders([$adsType]);
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

		$clientId = $this->clientId;
		$accountId = $this->accountId;
		$audienceId = $this->audienceId;

		$this->logDebug($clientId, $accountId, $audienceId);

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

		$provider = static::getAdsProvider($clientId);
		if (!$provider)
		{
			$isError = true;
		}

		if (!$accountId && $provider && $provider['IS_SUPPORT_ACCOUNT'])
		{
			$isError = true;
		}

		$audienceList = array();
		if ($audienceId)
		{
			$audienceList[] = array(
				'id' => $audienceId,
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
				$config = new \Bitrix\Seo\Retargeting\AdsAudienceConfig();
				$config->clientId = $clientId;
				$config->accountId = $accountId;
				$config->audienceId = $audience['id'];
				$config->contactType = $audience['contactType'];
				$config->type = static::getAdsType();
				$config->autoRemoveDayNumber = $this->autoRemoveDayNumber;

				AdsAudience::useQueue();
				AdsAudience::addFromEntity($entityTypeId, $entityId, $config);
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


		$dialog->setMap(static::getPropertiesMap($documentType));

		$provider = static::getAdsProvider($dialog->getCurrentValue('CLIENT_ID'));

		if (!$provider)
		{
			return '';
		}

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
			'CLIENT_ID' =>  $dialog->getCurrentValue('CLIENT_ID'),
			'ACCOUNT_ID' => $dialog->getCurrentValue('ACCOUNT_ID'),
			'AUDIENCE_ID' => $audienceId,
			'AUTO_REMOVE_DAY_NUMBER' => (int) $dialog->getCurrentValue('AUTO_REMOVE_DAY_NUMBER'),
		));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'clientId' => [
				'Name' => GetMessage('CRM_CREATE_ADS_CLIENT_ID'),
				'FieldName' => 'CLIENT_ID',
				'Type' => 'string',
				'Required' => false
			],
			'accountId' => [
				'Name' => GetMessage('CRM_CREATE_ADS_ACCOUNT_ID'),
				'FieldName' => 'ACCOUNT_ID',
				'Type' => 'string',
				'Required' => false
			],
			'audienceId' => [
				'Name' => GetMessage('CRM_CREATE_ADS_AUDIENCE_ID'),
				'FieldName' => 'AUDIENCE_ID',
				'Type' => 'string',
				'Required' => false
			],
			'audiencePhoneId' => [
				'Name' => 'Audience id for phones',
				'FieldName' => 'AUDIENCE_PHONE_ID',
				'Type' => 'string',
				'Required' => false
			],
			'audienceEmailId' => [
				'Name' => 'Audience id for emails',
				'FieldName' => 'AUDIENCE_EMAIL_ID',
				'Type' => 'string',
				'Required' => false
			],
			'autoRemoveDayNumber' => [
				'Name' => 'Days auto remove from audience',
				'FieldName' => 'AUTO_REMOVE_DAY_NUMBER',
				'Type' => 'string',
				'Required' => false
			],
		];
	}

	/*
	 * On save
	 * */
	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = Array();

		$clientId = $arCurrentValues['CLIENT_ID'];
		$accountId = $arCurrentValues['ACCOUNT_ID'];
		$audienceId = $arCurrentValues['AUDIENCE_ID'];
		$audienceEmailId = $arCurrentValues['AUDIENCE_EMAIL_ID'];
		$audiencePhoneId = $arCurrentValues['AUDIENCE_PHONE_ID'];
		$autoRemoveDayNumber = (int) $arCurrentValues['AUTO_REMOVE_DAY_NUMBER'];

		$arProperties = array(
			'clientId' => $clientId,
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

	private function logDebug($clientId, $accountId, $audienceId)
	{
		if (!$this->workflow->isDebug())
		{
			return;
		}

		if ($clientId && $accountId && $audienceId)
		{
			$audienceId = $this->getAudienceName($clientId, $accountId, $audienceId);
		}

		if ($clientId)
		{
			$clientId = $this->getProfileName($clientId);
		}

		$map = $this->getDebugInfo([
			'clientId' => $clientId,
			'audienceId' => $audienceId,
		]);

		$this->writeDebugInfo([
			'clientId' => $map['clientId'],
			'accountId' => $map['accountId'],
			'audienceId' => $map['audienceId'],
		]);
	}

	private function getProfileName($clientId)
	{
		$service = AdsAudience::getService();
		$service->setClientId($clientId);

		$authAdapter = $service::getAuthAdapter(static::getAdsType());
		$authAdapter->setService($service);

		$account = $service::getAccount(static::getAdsType());
		$account->setService($service);
		$account->getRequest()->setAuthAdapter($authAdapter);

		$profile = $account->getProfileCached();

		return $profile ? $profile['NAME'] : $clientId;
	}

	private function getAudienceName($clientId, $accountId, $audienceId)
	{
		$service = AdsAudience::getService();
		$service->setClientId($clientId);

		$audience = $service::getAudience(static::getAdsType());
		$audience->setService($service);
		$audience->setAccountId($accountId);

		$item = $audience->getById($audienceId);

		if ($item)
		{
			return $item['NAME'] ?: $item['ID'];
		}

		return $audienceId;
	}
}
