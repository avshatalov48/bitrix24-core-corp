<?php
namespace Bitrix\Crm\Automation\Converter;

use Bitrix\Crm\Conversion\DealConversionConfig;
use Bitrix\Crm\Conversion\DealConversionWizard;
use Bitrix\Crm\Conversion\LeadConversionConfig;
use Bitrix\Crm\Conversion\LeadConversionWizard;
use Bitrix\Main\NotSupportedException;

class Factory
{
	public static function create($entityTypeId, $entityId)
	{
		$entityTypeId = (int) $entityTypeId;
		$entityId = (int) $entityId;

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$config = new LeadConversionConfig();
			$wizard = new LeadConversionWizard($entityId, $config);
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$config = new DealConversionConfig();
			$wizard = new DealConversionWizard($entityId, $config);
		}
		else
		{
			throw new NotSupportedException("Entity '{$entityTypeId}' not supported in current context.");
		}

		$config->enablePermissionCheck(false);
		$wizard->enableUserFieldCheck(false);
		$wizard->enableBizProcCheck(false);
		$wizard->setSkipBizProcAutoStart(true);

		return new Converter($entityTypeId, $entityId, $config, $wizard);
	}
}