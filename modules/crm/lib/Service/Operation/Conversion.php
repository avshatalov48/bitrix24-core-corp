<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Crm\Conversion\ConversionManager;
use Bitrix\Crm\Conversion\EntityConversionConfig;
use Bitrix\Crm\Conversion\EntityConversionWizard;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Uri;

class Conversion extends Operation
{
	/** @var EntityConversionConfig */
	protected $configs;

	public function setConfigs(EntityConversionConfig $configs): Operation
	{
		$this->configs = $configs;

		return $this;
	}

	public function checkAccess(): Result
	{
		$result = new Result();

		$userPermissions = Container::getInstance()->getUserPermissions($this->getContext()->getUserId());

		$canUpdateSourceItem = $userPermissions->canUpdateItem($this->item);
		if (!$canUpdateSourceItem)
		{
			$result->addError(
				new Error(
					Loc::getMessage('CRM_OPERATION_CONVERSION_UPDATE_ACCESS_DENIED'),
					static::ERROR_CODE_ITEM_UPDATE_ACCESS_DENIED
				)
			);
		}

		foreach ($this->configs->getActiveItems() as $configItem)
		{
			$canAddDestinationItem = EntityAuthorization::checkCreatePermission($configItem->getEntityTypeID());
			if(!$canAddDestinationItem)
			{
				$entityDescription = \CCrmOwnerType::GetDescription($configItem->getEntityTypeID());
				$result->addError(
					new Error(
						Loc::getMessage(
							'CRM_COMMON_ADD_ACCESS_DENIED',
							['#ENTITY_DESCRIPTION#' => $entityDescription]
						),
						static::ERROR_CODE_ITEM_ADD_ACCESS_DENIED
					)
				);
			}
		}

		return $result;
	}

	/**
	 * @return ConversionResult
	 */
	public function launch(): Result
	{
		return $this->changeResultClass(parent::launch(), ConversionResult::class);
	}

	/**
	 * @return ConversionResult
	 */
	protected function save(): Result
	{
		$this->synchronizeUserFields();

		$result = new ConversionResult();

		$wizard = ConversionManager::getWizard($this->item->getEntityTypeId(), $this->item->getId(), $this->configs);
		if (!$wizard)
		{
			$result->addError(new Error('An instance of '.EntityConversionWizard::class.' for this entity is not found'));
			return $result;
		}

		$this->applySettingsToWizard($wizard);

		$wizard->execute();
		$isSuccess = ($wizard->getErrorText() === '');
		if ($isSuccess)
		{
			$this->configs->save();
		}
		else
		{
			$result->addError(new Error($wizard->getErrorText()));
		}

		$redirectUrl = $wizard->getRedirectUrl();
		if ($redirectUrl)
		{
			$result->setRedirectUrl(new Uri($wizard->getRedirectUrl()));
		}
		$result->setIsConversionFinished($wizard->isFinished());
		$result->setData($wizard->getResultData());

		return $result;
	}

	protected function synchronizeUserFields(): void
	{
		foreach ($this->configs->getActiveItems() as $dstEntityTypeId => $configItem)
		{
			if(UserFieldSynchronizer::needForSynchronization($this->item->getEntityTypeId(), $dstEntityTypeId))
			{
				if ($configItem->isSynchronizationEnabled())
				{
					UserFieldSynchronizer::synchronize($this->item->getEntityTypeId(), $dstEntityTypeId);
				}
				else
				{
					UserFieldSynchronizer::markAsSynchronized($this->item->getEntityTypeId(), $dstEntityTypeId);
				}
			}
		}
	}

	private function applySettingsToWizard(EntityConversionWizard $wizard): void
	{
		$wizard->enableUserFieldCheck($this->isCheckFieldsEnabled());
		$wizard->enableBizProcCheck($this->isCheckWorkflowsEnabled());
	}

	public function isFieldProcessionEnabled(): bool
	{
		//no need to process fields for source item since it doesn't change
		return false;
	}
}
