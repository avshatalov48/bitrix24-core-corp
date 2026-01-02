<?php

namespace Bitrix\Crm\RepeatSale\DataCollector;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\DataCollector\Activity\ActivityType;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Psr\Log\LoggerInterface;

final class DataCollectorManager
{
	private UserPermissions $userPermissions;
	private ?ClientDataCollector $clientCollector = null;
	private ?EntityDataCollector $dealCollector = null;

	public function __construct(
		private readonly ItemIdentifier $entityIdentifier,
		private readonly ItemIdentifier $clientIdentifier,
		private readonly LoggerInterface $logger,
		private readonly ?int $userId = null,
	)
	{
		$this->userPermissions = Container::getInstance()->getUserPermissions($this->userId);
	}

	public function collectCopilotData(): array
	{
		$isClientPermitted = $this->userPermissions->item()->canRead(
			$this->clientIdentifier->getEntityTypeId(),
			$this->clientIdentifier->getEntityId()
		);
		if (!$isClientPermitted)
		{
			$this->logger->error(
				'{date}: Failed to collect copilot data for client {target}: access denied' . PHP_EOL,
				[
					'target' => $this->clientIdentifier,
				],
			);

			return [];
		}

		$isEntityPermitted = $this->userPermissions->item()->canRead(
			$this->entityIdentifier->getEntityTypeId(),
			$this->entityIdentifier->getEntityId()
		);
		if (!$isEntityPermitted)
		{
			$this->logger->error(
				'{date}: Failed to collect copilot data for client {target}: access denied to {entity}' . PHP_EOL,
				[
					'target' => $this->clientIdentifier,
					'entity' => $this->entityIdentifier,
				],
			);

			return [];
		}

		try
		{
			$clientInfo = $this->getClientCollector()->getMarkers([
				'entityId' => $this->clientIdentifier->getEntityId()
			]);

			if (empty($clientInfo))
			{
				return [];
			}

			[$dealsList, $ordersSummary] = $this->getDealCollector()->getMarkers([
				'entityId' => $this->entityIdentifier->getEntityId(),
				'clientEntityTypeId' => $this->clientIdentifier->getEntityTypeId(),
				'clientEntityId' => $this->clientIdentifier->getEntityId(),
			]);

			return [
				'client_info' => $clientInfo,
				'deals_list' => $dealsList ?? [],
				'orders_summary' => $ordersSummary ?? [],
				'preferred_communication_channel' => $this->getPreferredCommunicationChannel($dealsList),
			];
		}
		catch (\Throwable $exception)
		{
			$this->logger->error(
				'{date}: Failed to collect copilot data for client {target}: {exception}' . PHP_EOL,
				[
					'target' => $this->clientIdentifier,
					'exception' => $exception
				],
			);

			return [];
		}
	}

	private function getClientCollector(): ClientDataCollector
	{
		if ($this->clientCollector === null)
		{
			$this->clientCollector = new ClientDataCollector($this->clientIdentifier->getEntityTypeId());
		}

		return $this->clientCollector;
	}

	private function getDealCollector(): EntityDataCollector
	{
		if ($this->dealCollector === null)
		{
			$this->dealCollector = new EntityDataCollector($this->entityIdentifier->getEntityTypeId());
		}

		return $this->dealCollector;
	}

	private function getPreferredCommunicationChannel(array $dealsList): string
	{
		$counts = array_reduce(
			array_filter(array_column($dealsList, 'communication_data')),
			static function($counts, $arr)
			{
				foreach ($arr as $type => $items)
				{
					if (ActivityType::isCommunicationChannel($type))
					{
						$counts[$type] = ($counts[$type] ?? 0) + count($items);
					}
				}

				return $counts;
			},
			[]
		);

		$channel = empty($counts)
			? ''
			: array_keys($counts, max($counts))[0] ?? '';

		return ActivityType::mapCommunicationChannel($channel);
	}
}
