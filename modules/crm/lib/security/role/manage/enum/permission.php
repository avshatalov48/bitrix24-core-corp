<?php

namespace Bitrix\Crm\Security\Role\Manage\Enum;

use CCrmOwnerType;

enum Permission: string
{
	case Contact = 'contact';
	case Company = 'company';
	case Lead = 'lead';
	case Deal = 'deal';
	case Quote = 'quote';
	case OldInvoice = 'oldInvoice';
	case SmartInvoice = 'smartInvoice';
	case Dynamic = 'dynamic';
	case Order = 'order';
	case WebForm = 'webForm';
	case WebFormConfig = 'webFormConfig';
	case Button = 'button';
	case ButtonConfig = 'buttonConfig';
	case SaleTarget = 'saleTarget';
	case Exclusion = 'exclusion';
	case CopilotCallAssessment = 'copilotCallAssessment';
	case AutomatedSolutionConfig = 'automatedSolutionConfig';
	case AutomatedSolutionList = 'automatedSolutionList';
	case CrmConfig = 'crmConfig';

	public static function fromEntityTypeId(int $entityTypeId): ?self
	{
		$permission = match ($entityTypeId){
			CCrmOwnerType::Lead => self::Lead,
			CCrmOwnerType::Deal => self::Deal,
			CCrmOwnerType::Contact => self::Contact,
			CCrmOwnerType::Company => self::Company,
			CCrmOwnerType::Invoice => self::OldInvoice,
			CCrmOwnerType::SmartInvoice => self::SmartInvoice,
			CCrmOwnerType::Quote => self::Quote,
			CCrmOwnerType::Order => self::Order,
			default => null,
		};

		if (CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$permission = self::Dynamic;
		}

		return $permission;
	}
}
