<?php

namespace Bitrix\Crm\Tour\Model;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Data\Cache;
use CCrmCompany;
use CCrmContact;
use CCrmOwnerType;
use CUserOptions;

class ClientVolumeChecker
{
	public const OPTION_CATEGORY = 'crm';
	public const OPTION_NAME_PREFIX = 'crm_client_volume_';
	public const MAX_CLIENT_TO_CHECK = 100000;

	private const ICON_PATH = '/bitrix/images/crm/whats_new/activity_view_mode/';
	private const CACHE_DIR = '/crm/client_volume/';
	private const CACHE_TTL = 3*60*60; // 3 hours
	private const CLIENT_VOLUME_CHECK_OPTION_NAME = 'CLIENT_VOLUME_CHECK_OPTION_NAME_ENABLED';

	private Cache $cache;

	private array $supportedTypes = [
		CCrmOwnerType::Contact,
		CCrmOwnerType::Company
	];

	private array $volumeLevels = [
		'first client' => [
			'CHECKPOINTS' => [1],
			'TITLE' => 'Congratulations!',
			'SUBTITLE' => 'The first client is in the client base',
			'DESCRIPTION' => 'You will never lose his contacts and remind about yourself in time. You are at the beginning of a long journey.',
			'INFO' => '',
			'MEDAL_ICON' => self::ICON_PATH . 'icon.svg',
		],
		'5, 10 clients' => [
			'CHECKPOINTS' => [5, 10],
			'TITLE' => 'Great!',
			'SUBTITLE' => 'You already have #CNT# clients',
			'DESCRIPTION' => 'Attracting each new client is a big job.',
			'INFO' => 'Create fields and fill them with customer preferences, birthday, city, interests and other information in order to segment customers in the future, make special offers for each group and sell more.',
			'MEDAL_ICON' => self::ICON_PATH . 'icon.svg',
		],
		'20, 50 clients' => [
			'CHECKPOINTS' => [20, 50],
			'TITLE' => 'Excellent result!',
			'SUBTITLE' => 'Already #CNT# clients',
			'DESCRIPTION' => 'Your customer base is steadily growing',
			'INFO' => 'Offer goods and services in the interests of the client, congratulate them on their birthday. Just select the required Filter, and CRM will find clients according to the specified parameters.',
			'MEDAL_ICON' => self::ICON_PATH . 'icon.svg',
		],
		'100, 250 clients' => [
			'CHECKPOINTS' => [100, 250],
			'TITLE' => 'Super!',
			'SUBTITLE' => 'You already have such a large client base - #CNT# clients',
			'DESCRIPTION' => 'We carefully store all the data you collect',
			'INFO' => 'If you haven\'t tried the Sales Generator yet, now is the time to try this tool in action. Incentivize repeat sales, arrange promotions for a specific type of customer, or give a birthday discount!',
			'MEDAL_ICON' => self::ICON_PATH . 'icon.svg',
		],
		'a lot of clients' => [
			'CHECKPOINTS' => [500, 1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000],
			'TITLE' => 'Grandiose!',
			'SUBTITLE' => 'Congratulations. #CNT# clients is a success',
			'DESCRIPTION' => 'We wish you further growth of your company',
			'INFO' => 'CRM Marketing will help you drive repeat sales. Do mailings to your entire customer base with new offers. Or select a specific segment of customers and remind them of yourself.',
			'MEDAL_ICON' => self::ICON_PATH . 'icon.svg',
		],
	];

	private array $allCheckpoints = [];
	private array $result = [];

	public function __construct()
	{
		$this->cache = Application::getInstance()->getCache();
	}

	// region Feature ON/OFF
	public static function isCheckEnabled(): bool
	{
		return (bool)Option::get(self::OPTION_CATEGORY, self::CLIENT_VOLUME_CHECK_OPTION_NAME, false);
	}

	public static function setCheckEnabled(bool $isEnabled): void
	{
		Option::set(self::OPTION_CATEGORY, self::CLIENT_VOLUME_CHECK_OPTION_NAME, $isEnabled);
	}
	//endregion

	final public function getCheckResult(): array
	{
		if ($this->isClientVolumeCheckEnabled())
		{
			$allCheckpoints = array_values(array_map(
				static fn($item) => implode(',', $item['CHECKPOINTS']),
				$this->volumeLevels
			));

			$this->allCheckpoints = explode(',' , implode(',', $allCheckpoints));

			$this->checkClientVolume();
		}

		return $this->result;
	}

	protected function isClientVolumeCheckEnabled(): bool
	{
		return static::isCheckEnabled();
	}

	protected function isUserHasAllPermissions(int $entityTypeId): bool
	{
		$perm = Container::getInstance()->getUserPermissions();
		$permissionEntityType = $perm::getPermissionEntityType($entityTypeId);

		return $perm->isAdmin()
			|| $perm->getCrmPermissions()->GetPermType($permissionEntityType) >= $perm::PERMISSION_ALL;
	}

	protected function getClientVolume(int $entityTypeId): int
	{
		$cacheId = 'crm_client_volume_' . $entityTypeId;

		if ($this->cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR))
		{
			return (int)$this->cache->getVars()['count'];
		}

		$count = 0;

		$this->cache->startDataCache();
		if ($entityTypeId === CCrmOwnerType::Contact)
		{
			$count = CCrmContact::GetTotalCount();
		}

		if ($entityTypeId === CCrmOwnerType::Company)
		{
			$count = CCrmCompany::GetTotalCount();
		}
		$this->cache->endDataCache(['count' => $count]);

		return $count;
	}

	protected function getPrevShownCount(int $entityTypeId): int
	{
		$option = CUserOptions::GetOption(
			self::OPTION_CATEGORY,
			self::OPTION_NAME_PREFIX . $entityTypeId,
			[]
		);

		return (int)($option['count'] ?? 0);
	}

	private function checkClientVolume(): void
	{
		foreach ($this->supportedTypes as $entityTypeId)
		{
			if (!$this->isUserHasAllPermissions($entityTypeId))
			{
				continue;
			}

			$entityCount = $this->getClientVolume($entityTypeId);
			$prevShownCount = $this->getPrevShownCount($entityTypeId);
			if (
				$entityCount === 0
				|| $prevShownCount > self::MAX_CLIENT_TO_CHECK
			)
			{
				continue;
			}
			
			if ($entityCount > $prevShownCount)
			{
				[$level, $checkpoint] = $this->lookupVolumeLevel($entityCount, $prevShownCount);
				if (isset($level))
				{
					$this->result = [
						'ENTITY_TYPE_ID' => $entityTypeId,
						'CHECKPOINT' => $checkpoint,
						'TITLE' => $level['TITLE'],
						'SUBTITLE' => str_replace('#CNT#', $checkpoint, $level['SUBTITLE']),
						'DESCRIPTION' => $level['DESCRIPTION'],
						'INFO' => $level['INFO'],
						'MEDAL_ICON' => $level['MEDAL_ICON'],
					];

					break;
				}
			}
		}
	}

	private function lookupVolumeLevel(int $entityCount, int $prevShownCount): ?array
	{
		$closestCheckpoint = null;
		$closestCheckpointIndex = null;
		foreach ($this->allCheckpoints as $index => $checkpoint) {
			if (
				$closestCheckpoint === null
				|| abs($entityCount - $closestCheckpoint) > abs($checkpoint - $entityCount)
			) {
				$closestCheckpoint = $checkpoint;
				$closestCheckpointIndex = $index;
			}
		}

		if (!isset($closestCheckpoint, $closestCheckpointIndex))
		{
			return null;
		}

		$resultCheckpoint = $closestCheckpoint > $entityCount
			? ($this->allCheckpoints[$closestCheckpointIndex - 1])
			: $closestCheckpoint;

		if ($resultCheckpoint > $prevShownCount)
		{
			foreach ($this->volumeLevels as $item)
			{
				if (in_array((int)$resultCheckpoint, $item['CHECKPOINTS'], true)) {
					return [$item, $resultCheckpoint];
				}
			}
		}

		return null;
	}
}
