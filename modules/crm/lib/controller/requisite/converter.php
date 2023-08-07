<?php
namespace Bitrix\Crm\Controller\Requisite;

use Bitrix\Crm\Agent\Requisite\CompanyAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\CompanyUfAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Main\Localization\Loc;
use CCrmAuthorizationHelper;
use CCrmOwnerType;
use CUserTypeEntity;

Loc::loadMessages(__FILE__);

class Converter extends Controller
{
	public function ufAddressConvertAction($entityTypeId, $fieldName)
	{
		$actionIndex = 1;
		$error = '';
		if(!EntityAuthorization::isAuthorized()
			|| !CCrmAuthorizationHelper::CheckConfigurationUpdatePermission())
		{
			$error = 'ERR_ACCESS_DENIED';
		}

		$entityTypeMap = [
			'CRM_COMPANY' => CCrmOwnerType::Company,
			'CRM_CONTACT' => CCrmOwnerType::Contact,
			'CRM_DEAL' => CCrmOwnerType::Deal,
			'CRM_LEAD' => CCrmOwnerType::Lead
		];

		if (!$error && (!is_string($entityTypeId) || $entityTypeId === '' || !isset($entityTypeMap[$entityTypeId])))
		{
			$error = 'ERR_ENTITY_TYPE';
		}

		if (!$error && !is_string($fieldName) || $fieldName === '')
		{
			$error = 'ERR_FIELD_NAME';
		}

		$res = CUserTypeEntity::GetList(
			[],
			[
				'ENTITY_ID' => $entityTypeId,
				'FIELD_NAME' => $fieldName,
				'USER_TYPE_ID' => 'address',
				'MULTIPLE' => 'N',
			]
		);
		$row = null;
		if (is_object($res))
		{
			$row = $res->Fetch();
		}
		if (!is_array($row))
		{
			$error = 'ERR_FIELD_NOT_FOUND';
		}
		unset($res, $row);

		if (!$error)
		{
			$sourceEntityTypeId = $entityTypeMap[$entityTypeId];
			$companyAgent = CompanyAddressConvertAgent::getInstance();
			$companyUfAgent = CompanyUfAddressConvertAgent::getInstance();
			$companyUfAgentReady = ($companyUfAgent->isEnabled() && !$companyUfAgent->isActive());
			$companyUfAgentAllowed = (!$companyAgent->isEnabled() && $companyUfAgentReady
				&& in_array($sourceEntityTypeId, $companyUfAgent->getAllowedEntityTypes(), true));
			$contactAgent = ContactAddressConvertAgent::getInstance();
			$contactUfAgent = ContactUfAddressConvertAgent::getInstance();
			$contactUfAgentReady = ($contactUfAgent->isEnabled() && !$contactUfAgent->isActive());
			$contactUfAgentAllowed = (!$contactAgent->isEnabled() && $contactUfAgentReady
				&& in_array($sourceEntityTypeId, $contactUfAgent->getAllowedEntityTypes(), true));
			$agentOptions = [
				'ALLOWED_ENTITY_TYPES' => [],
				'SOURCE_ENTITY_TYPE_ID' => $sourceEntityTypeId,
				'SOURCE_USER_FIELD_NAME' => $fieldName
			];
			if ($sourceEntityTypeId === CCrmOwnerType::Deal || $sourceEntityTypeId === CCrmOwnerType::Lead)
			{
				if ($companyUfAgentAllowed && $contactUfAgentAllowed)
				{
					$companyUfAgent->setAllowedEntityTypes();
					$contactUfAgent->setAllowedEntityTypes();
					$companyUfAgent->activate(5, $agentOptions);
					$contactUfAgent->activate(5, $agentOptions);
				}
				else
				{
					$error = 'ERR_CONVERTER_NOT_ALLOWED';
				}
			}
			else    // Company or Contact
			{
				switch($sourceEntityTypeId)
				{
					case CCrmOwnerType::Company:
						$agent = $companyUfAgent;
						$agentAllowed = $companyUfAgentAllowed;
						$altEntityTypeId = CCrmOwnerType::Contact;
						$altAgentReady = $contactUfAgentReady;
						$altAgent = $contactUfAgent;
						break;
					case CCrmOwnerType::Contact:
						$agent = $contactUfAgent;
						$agentAllowed = $contactUfAgentAllowed;
						$altEntityTypeId = CCrmOwnerType::Company;
						$altAgentReady = $companyUfAgentReady;
						$altAgent = $companyUfAgent;
						break;
				}
				if ($agentAllowed)
				{
					if ($altAgentReady)
					{
						$altAgent->setAllowedEntityTypes([$altEntityTypeId]);
					}
					$agent->activate(5, $agentOptions);
				}
				else
				{
					$error = 'ERR_CONVERTER_NOT_ALLOWED';
				}
			}
		}

		if ($error)
		{
			if (!is_array($error))
			{
				$error = [$error];
			}
			foreach ($error as $errorCode)
			{
				$this->addError(
					new Error(
						Loc::getMessage('CRM_CONTROLLER_REQUISITE_CONVERTER_'.$actionIndex.'_'.$errorCode),
						$errorCode
					)
				);
			}
			return null;
		}

		return true;
	}
}