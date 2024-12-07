<?php

namespace Bitrix\Crm\Integration\UI\EntitySelector;

use Bitrix\Crm\Activity\Provider\Sms\PlaceholderContext;
use Bitrix\Crm\Activity\Provider\Sms\PlaceholderManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\MessageService;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;

final class MessageTemplateProvider extends BaseProvider
{
	public const ENTITY_ID = 'message_template';

	private int $entityTypeId;
	private int $entityId;
	private ?int $categoryId;

	private ?MessageService\Sender\Base $sender;

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->entityTypeId = (int)($options['entityTypeId'] ?? 0);
		$this->entityId = (int)($options['entityId'] ?? 0);
		$this->categoryId = isset($options['categoryId']) ? (int)$options['categoryId'] : null;

		if (SmsManager::canUse())
		{
			$this->sender = SmsManager::getSenderById((string)($options['senderId'] ?? ''));
		}
	}

	public function isAvailable(): bool
	{
		return !is_null($this->sender)
			&& SmsManager::isEdnaWhatsAppSendingEnabled($this->sender->getId())
			&& $this->sender->canUse()
			&& $this->sender->isConfigurable()
			&& $this->sender->isTemplatesBased()
			&& Container::getInstance()
				->getUserPermissions()
				->checkUpdatePermissions($this->entityTypeId, $this->entityId, $this->categoryId)
		;
	}

	public function fillDialog(Dialog $dialog): void
	{
		$items = $this->makeItems();

		array_walk(
			$items,
			static function (Item $item, int $index) use ($dialog) {
				if (empty($dialog->getContext()))
				{
					$item->setSort($index);
				}
				$dialog->addRecentItem($item);
			}
		);
	}

	public function getItems(array $ids): array
	{
		return $this->makeItems();
	}

	public function getSelectedItems(array $ids): array
	{
		return $this->makeItems();
	}

	private function makeItems(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$templates = $this->getTemplatesList();
		if (empty($templates))
		{
			return [];
		}

		$items = [];
		foreach ($templates as $template)
		{
			$items[] = new Item([
				'id' => $template['ORIGINAL_ID'],
				'entityId' => self::ENTITY_ID,
				'title' => $template['TITLE'],
				'subtitle' => html_entity_decode($template['PREVIEW'] ?? ''),
				'customData' => [
					'template' => $template,
				],
			]);
		}

		return $items;
	}

	private function getTemplatesList(): array
	{
		$list = $this->sender->getTemplatesList([
			'module' => 'crm',
			'entityTypeId' => $this->entityTypeId,
			'entityId' => $this->entityId,
			'entityCategoryId' => $this->categoryId,
		]);

		if (empty($list))
		{
			return [];
		}

		if ($this->entityTypeId <= 0)
		{
			return $list;
		}

		$placeholderManager = new PlaceholderManager();
		$ids = [];
		foreach ($list as $template)
		{
			$ids[] = $template['ORIGINAL_ID'];
		}

		$placeholderContext = PlaceholderContext::createInstance($this->entityTypeId, $this->categoryId);
		$filledPlaceholders = $placeholderManager->getPlaceholders($ids, $placeholderContext);

		foreach ($list as &$template)
		{
			foreach ($filledPlaceholders as $filledPlaceholder)
			{
				if ($template['ORIGINAL_ID'] !== (int)$filledPlaceholder['TEMPLATE_ID'])
				{
					continue;
				}

				if (!isset($template['FILLED_PLACEHOLDERS']))
				{
					$template['FILLED_PLACEHOLDERS'] = [];
				}

				$template['FILLED_PLACEHOLDERS'][] = $filledPlaceholder;
			}
		}
		unset($template);

		return $list;
	}
}
