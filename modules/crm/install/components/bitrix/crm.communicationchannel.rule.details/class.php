<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Service\Communication\Channel\ChannelFactory;
use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesManager;
use Bitrix\Crm\Service\Communication\Channel\Queue\QueueConfig;
use Bitrix\Crm\Service\Communication\Controller\ChannelController;
use Bitrix\Crm\Service\Communication\Entity\CommunicationChannelRuleTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ResponsibleQueue\Controller\QueueConfigController;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

if (!Main\Loader::includeModule('crm'))
{
	return;
}

class CCrmCommunicationChannelDetailsComponent extends Base
{
	private ?array $rule = null;

	public function executeComponent(): void
	{
		$GLOBALS['APPLICATION']->SetTitle('Настройка маршрутов обращения в каналы');

		$this->arResult['RULE'] = $this->getRule();
		$this->arResult['CHANNELS'] = $this->getPreparedChannels();
		$this->arResult['CHANNEL_ID'] = $this->getChannelId();
		$this->arResult['ENTITIES'] = $this->getEntitiesInfo();
		$this->arResult['SEARCH_TARGET_ENTITIES'] = $this->getSearchTargetEntities();

		$this->includeComponentTemplate();
	}

	private function getRule(): array
	{
		if ($this->rule === null)
		{
			$rule = CommunicationChannelRuleTable::getById( $this->getChannelId())?->fetch();

			$this->rule = $rule ?: [];
			if (!empty($this->rule))
			{
				$this->rule['QUEUE_CONFIG'] = QueueConfigController::getInstance()->get($this->rule['QUEUE_CONFIG_ID'] ?? 0);
			}
		}

		return $this->rule;
	}

	private function getChannelId(): int
	{
		return (int)($this->arParams['ID'] ?? 0);
	}

	private function getPreparedChannels(): array
	{
		$channels = ChannelController::getInstance()->getChannels();
		$commonProperties = PropertiesManager::getInstance()->getCommonProperties()->toArray();
		$queueConfigInstance = QueueConfig::getInstance();

		$result = [];
		foreach ($channels as $channel)
		{
			$handlerInstance = ChannelFactory::getInstance()->getChannelHandlerInstance($channel);
			if (!$handlerInstance)
			{
				continue;
			}

			$result[] = [
				'id' => $channel->getId(),
				'title' => $handlerInstance->getTitle(),
				'properties' => [
					...$handlerInstance->getPropertiesCollection()->toArray(),
					...$commonProperties,
				],
				'queueConfig' => $queueConfigInstance->get($handlerInstance),
			];
		}

		return $result;
	}

	private function getEntitiesInfo(): array
	{
		$result = [];
		$entityDescriptions = CCrmOwnerType::GetAllDescriptions();

		foreach ($entityDescriptions as $entityTypeId => $entityDescription)
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if (!$factory || !$factory->isCommunicationRoutingSupported())
			{
				continue;
			}

			$categories = [];
			if (
				$factory->isCategoriesSupported()
				&& !in_array($entityTypeId, [CCrmOwnerType::Contact, CCrmOwnerType::Company], true)
			)
			{
				foreach ($factory->getCategories() as $category)
				{
					$categories[] = [
						'id' => $category->getId(),
						'name' => $category->getName(),
					];
				}
			}

			$result[] = [
				'entityTypeId' => $entityTypeId,
				'name' => $entityDescription,
				'categories' => $categories,
				'data' => [
					'isCustomSection' => $factory->isInCustomSection(),
				],
			];
		}

		$this->extendEntitiesInfo($result);

		return $result;
	}

	private function extendEntitiesInfo(array &$entitiesInfo): void
	{
		foreach ($entitiesInfo as $entityInfo)
		{
			if ($entityInfo['entityTypeId'] === CCrmOwnerType::Lead)
			{
				$item = $entityInfo;
				$item['name'] = Loc::getMessage('CRM_COMMON_RETURN_CUSTOMER_LEAD');
				$item['data']['isReturnCustomer'] = true;

				$entitiesInfo[] = $item;

				break;
			}
		}
	}

	private function getSearchTargetEntities(): array
	{
		$entityDescriptions = CCrmOwnerType::GetAllDescriptions();

		return [
			[
				'section' => [
					'id' => 'crm',
					'title' => 'CRM',
				],
				'entities' => [
					[
						'id' => CCrmOwnerType::Contact,
						'title' => $entityDescriptions[CCrmOwnerType::Contact],
					],
					[
						'id' => CCrmOwnerType::Company,
						'title' => $entityDescriptions[CCrmOwnerType::Company],
					],
					[
						'id' => CCrmOwnerType::Lead,
						'title' => $entityDescriptions[CCrmOwnerType::Lead],
					],
				],
			],
		];
	}
}
