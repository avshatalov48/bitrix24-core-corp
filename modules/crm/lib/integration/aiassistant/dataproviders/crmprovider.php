<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\AiAssistant\DataProviders;

use Bitrix\AiAssistant\Context\Contract\AbstractDataProvider;
use Bitrix\Main\Loader;
use CCrmOwnerType;
use Bitrix\Crm\Integration\AI\ContextCollector\EntityCollector;
use Bitrix\Crm\Integration\AI\ContextCollector\Context;

class CrmProvider extends AbstractDataProvider
{
	protected string $moduleId = 'crm';

	protected array $supportedKeys = [
		'LEAD' => 1,
		'DEAL' => 2,
		'CONTACT' => 3,
		'COMPANY' => 4,
		'INVOICE' => 5,
		'ACTIVITY' => 6,
		'QUOTE' => 7,
		'REQUISITE' => 8,
		'DEALCATEGORY' => 9,
		'CUSTOMACTIVITYTYPE' => 10,
		'WAIT' => 11,
		'CALLLIST' => 12,
		'DEALRECURRING' => 13,
		'ORDER' => 14,
		'ORDERCHECK' => 15,
		'ORDERSHIPMENT' => 16,
		'ORDERPAYMENT' => 17,
	];

	public function getSupportedKeys(): array
	{
		return array_keys($this->supportedKeys);
	}

	public function getData(int $userId, string $entityId, array $keys): array
	{
		// TODO: remove after moving to CRM module

		Loader::requireModule('crm');

		$keys = $this->sanitizeKeys($keys);

		if (empty($keys))
		{
			return [];
		}

		$result = [];

		foreach ($keys as $key)
		{
			$entityTypeId = $this->supportedKeys[$key];

			if (CCrmOwnerType::IsDefined($entityTypeId))
			{
				$context = new Context($userId);

				$collector = (new EntityCollector($entityTypeId, $context))
					->configure(static function (EntityCollector\Settings $settings) {
						$settings
							->setIsCollectCategories(true)
							->configureUserFieldsSettings(static function (EntityCollector\UserFieldsSettings $userFieldsSettings) {
								$userFieldsSettings
									->setIsCollect(true)
									->setIsCollectName(false)
								;
							})
							->configureStageSettings(static function (EntityCollector\StageSettings $stageSettings) {
								$stageSettings
									->setIsCollect(true)
									->setIsCollectItemsCount(false)
									->setIsCollectItemsSum(false)
								;
							})
						;
					})
				;

				$result[$key] = $collector->collect();
			}
		}

		return $result;
	}

	public function getEntityType(): string
	{
		return 'CRM';
	}
}
