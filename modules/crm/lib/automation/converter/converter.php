<?php
namespace Bitrix\Crm\Automation\Converter;

use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Conversion\EntityConversionWizard;
use Bitrix\Crm\Conversion\LeadConversionScheme;
use Bitrix\Crm\Conversion\LeadConversionType;
use Bitrix\Crm\Conversion\LeadConversionWizard;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Converter
{
	protected $entityTypeId;
	protected $entityId;
	protected $config;
	protected $wizard;

	public function __construct(
		$entityTypeId,
		$entityId,
		EntityConversionConfig $config,
		EntityConversionWizard $wizard
	)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
		$this->config = $config;
		$this->wizard = $wizard;
	}

	public function setTargetItem($itemTypeId, array $options = [])
	{
		if (
			$this->entityTypeId === \CCrmOwnerType::Lead
			&&
			!LeadConversionScheme::isTargetTypeSupported($itemTypeId, array(
				'TYPE_ID' => LeadConversionType::resolveByEntityID($this->entityId)
			))
		)
		{
			return $this;
		}

		$item = $this->config->getItem($itemTypeId);
		if ($item)
		{
			$item->setActive(true);
			$item->enableSynchronization(true);

			if ($itemTypeId === \CCrmOwnerType::Contact)
			{
				$item->setInitData(array(
					'defaultName' => (
						isset($options['defaultName'])
							? $options['defaultName']
							: Loc::getMessage('CRM_AUTOMATION_CONVERTER_DEFAULT_NAME')
					),
				));
			}
			elseif ($itemTypeId === \CCrmOwnerType::Deal &&	isset($options['categoryId']))
			{
				$item->setInitData(array (
					'categoryId' => (int) $options['categoryId'],
				));
			}
		}

		return $this;
	}

	public function enableActivityCompletion($flag)
	{
		if ($this->wizard instanceof LeadConversionWizard)
		{
			$this->wizard->enableActivityCompletion($flag);
		}
		return $this;
	}

	public function execute(array $contextData = null)
	{
		$result = new Result();

		$this->synchronizeFields();

		if (!$this->wizard->execute($contextData))
		{
			$errorText = $this->wizard->getErrorText();
			$result->addError(new Error($errorText));
		}
		else
		{
			$result->setConverterResultData($this->wizard->getResultData());
		}

		return $result;
	}

	private function synchronizeFields()
	{
		foreach ($this->config->getItems() as $item)
		{
			$srcEntityTypeId = $this->entityTypeId;
			$dstEntityTypeId = (int)$item->getEntityTypeID();
			if(!UserFieldSynchronizer::needForSynchronization($srcEntityTypeId, $dstEntityTypeId))
			{
				continue;
			}

			if ($item->isSynchronizationEnabled())
			{
				UserFieldSynchronizer::synchronize($srcEntityTypeId, $dstEntityTypeId);
			}
			else
			{
				UserFieldSynchronizer::markAsSynchronized($srcEntityTypeId, $dstEntityTypeId);
			}
		}
	}
}