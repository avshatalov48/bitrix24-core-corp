<?php

namespace Bitrix\Crm\Integration\Catalog;

use Bitrix\Catalog\Config\State;
use Bitrix\Main\Loader;

class WarehouseOnboarding
{
	private const ONBOARDING_CATEGORY_NAME = 'warehouse-onboarding';

	/**
	 * @var int
	 * ID of User who belongs onoboarding data
	 */
	private int $userID;

	/**
	 * @var array
	 * User onboarding data in array format
	 * [
	 *	'chainStage' => int, - the number of chain, that showing to user in present time. If chainStage = 2 -  The user is completed all onboarding chains
	 *	'firstChainStage' => int, - step of the first onboarding chain
	 *	'secondChainStage' => int, - step of the second onboarding chain
	 *	'successDealGuideIsOver' => bool, - equals true if success deal hint is already showed to user, false otherwise
	 * ]
	 */
	private array $onboardingData;

	/**
	 * WarehouseOnboarding constructor.
	 *
	 * @param int $userID ID of User who belongs onoboarding data
	 */
	public function __construct(int $userID)
	{
		$this->userID = $userID;
	}

	/**
	 * Returns onboarding data for user
	 *
	 * @return array
	 */
	public function getOnboardingData(): array
	{
		if (!isset($this->onboardingData))
		{
			$this->onboardingData = \CUserOptions::GetOption(
				'crm',
				self::ONBOARDING_CATEGORY_NAME,
				[],
				$this->userID,
			);

			if (empty($this->onboardingData))
			{
				$this->onboardingData = $this->getDefaultOnboardingData();
				$this->setOnboardingData($this->onboardingData);
			}
		}

		return $this->onboardingData;
	}

	private function getDefaultOnboardingData(): array
	{
		return [
			'chainStage' => 0,
			'firstChainStage' => 0,
			'secondChainStage' => 0,
			'successDealGuideIsOver' => false,
		];
	}

	private function setOnboardingData(array $onboardingData): void
	{
		$this->onboardingData = $onboardingData;
		\CUserOptions::SetOption(
			'crm',
			'warehouse-onboarding',
			$onboardingData,
			false,
			$this->userID,
		);
	}

	/**
	 * Returns true value if the user has nothing more to show from the hints
	 *
	 * @return bool
	 */
	public function isOver(): bool
	{
		$onboardingData = $this->getOnboardingData();

		$chainStage = $onboardingData['chainStage'] ?? 0;
		$successDealGuideIsOver = $onboardingData['successDealGuideIsOver'] ?? false;

		return ($chainStage >= 2) && $successDealGuideIsOver;
	}

	private function updateChainStage(): void
	{
		$onboardingData = $this->getOnboardingData();
		if
		(
			isset($onboardingData['secondChainTimestamp'])
			&& time() > $onboardingData['secondChainTimestamp']
		)
		{
			$onboardingData['chainStage'] = 1;
			$onboardingData['secondChainStage'] = 0;
			unset($onboardingData['secondChainTimestamp']);
			$this->setOnboardingData($onboardingData);
		}
	}

	/**
	 * Set step for the current onboarding chain
	 *
	 * @param int $step Chain step
	 * @return void
	 */
	public function setChainStep(int $step): void
	{
		$onboardingData = $this->getOnboardingData();
		if ($onboardingData['chainStage'] === 0)
		{
			$onboardingData['firstChainStage'] = $step;
		}
		else
		{
			$onboardingData['secondChainStage'] = $step;
		}

		$this->setOnboardingData($onboardingData);
	}

	/**
	 * End first onboarding chain if it is current and fix timestamp to start second onboarding chain
	 *
	 * @param int $startNextChainTimestamp Timestamp for start second onboarding chain
	 * @return void
	 */
	public function endFirstChain(int $startNextChainTimestamp = 0): void
	{
		$onboardingData = $this->getOnboardingData();
		if (!isset($onboardingData['secondChainTimestamp']))
		{
			$onboardingData['secondChainTimestamp'] = $startNextChainTimestamp;
		}

		$this->setOnboardingData($onboardingData);
	}

	/**
	 * Returns formatted onboarding data for user
	 *
	 * @return array
	 */
	public function getCurrentChainData(): array
	{
		$this->updateChainStage();
		$chainData = [
			'CHAIN' => $this->getOnboardingData()['chainStage'],
			'SUCCESS_DEAL_GUIDE_IS_OVER' => $this->getOnboardingData()['successDealGuideIsOver'],
		];

		if ($chainData['CHAIN'] === 0)
		{
			$chainData['STAGE'] = $this->getOnboardingData()['firstChainStage'];
		}
		else
		{
			$chainData['STAGE'] = $this->getOnboardingData()['secondChainStage'];
		}

		return $chainData;
	}

	/**
	 * Returns true if onboarding is available to show hint in the store document products grid
	 *
	 * @return bool
	 */
	public function isStoreDocumentChainStepAvailable(): bool
	{
		$currentChainData = $this->getCurrentChainData();
		if ((int)$currentChainData['STAGE'] === 1 && (int)$currentChainData['CHAIN'] === 1)
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns true if onboarding is available for shown to user
	 *
	 * @param int $userID
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function isCrmWarehouseOnboardingAvailable(int $userID): bool
	{
		$inventoryManagementEnabled = false;
		if (Loader::includeModule('catalog'))
		{
			$inventoryManagementEnabled = State::isUsedInventoryManagement();
		}

		if (!$inventoryManagementEnabled)
		{
			return false;
		}

		return !(new WarehouseOnboarding($userID))->isOver();
	}
}