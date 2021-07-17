<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\MessageSender\ICanSendMessage;
use Bitrix\Crm\MessageSender\NotificationsPromoManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Formatter;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Notifications\Account;
use Bitrix\Notifications\Billing;
use Bitrix\Notifications\Model\ErrorCode;
use Bitrix\Notifications\Model\Message;
use Bitrix\Notifications\Model\MessageTable;
use Bitrix\Notifications\Model\MessageHistoryTable;
use Bitrix\Notifications\Model\QueueTable;
use Bitrix\Notifications\MessageStatus;
use Bitrix\Notifications\ProviderEnum;
use Bitrix\Notifications\Integration\Pull;
use Bitrix\ImOpenLines\Common;
use Bitrix\ImConnector\Connectors\Notifications;

Loc::loadMessages(__FILE__);

/**
 * Class NotificationsManager
 * @package Bitrix\Crm\Integration
 * @internal
 */
class NotificationsManager implements ICanSendMessage
{
	/** @var bool */
	private static $canUse;

	/**
	 * @return bool
	 */
	public static function canUse(): bool
	{
		if (static::$canUse === null)
		{
			static::$canUse = Loader::includeModule('notifications');
		}

		return static::$canUse;
	}

	public static function getSenderCode(): string
	{
		return 'bitrix24';
	}

	/**
	 * @inheritDoc
	 */
	public static function isAvailable(): bool
	{
		return (static::canUse() && Account::isServiceAvailable());
	}

	/**
	 * @inheritDoc
	 */
	public static function isConnected(): bool
	{
		return (
			static::canUse()
			&& (
				Account::isConnected()
				|| NotificationsPromoManager::isPromoSession()
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function getConnectUrl(): ?string
	{
		if (
			static::canUse()
			&& Loader::includeModule('imopenlines')
			&& Loader::includeModule('imconnector')
			&& Account::isServiceAvailable()
		)
		{
			return Common::getAddConnectorUrl(Notifications::CONNECTOR_ID);
		}

		return null;
	}

	/**
	 * @return array
	 */
	public static function getUsageErrors(): array
	{
		if (!static::canUse())
		{
			return [];
		}

		$result = [];

		$maxPricePerMessage = Billing::getMaxMessagePrice();
		if (
			!(
				is_array($maxPricePerMessage)
				&& isset($maxPricePerMessage['PRICE'])
				&& Account::getBalance() >= (float)$maxPricePerMessage['PRICE']
			)
		)
		{
			$result[] = Loc::getMessage('CRM_NOTIFICATIONS_MANAGER_INSUFFICIENT_ACCOUNT_BALANCE');
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public static function canSendMessage()
	{
		return (
			static::canUse()
			&& (
				static::isAvailable()
				&& static::isConnected()
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function sendMessage(array $messageFields)
	{
		if (static::canUse() && static::canSendMessage())
		{
			if (NotificationsPromoManager::isPromoSession())
			{
				NotificationsPromoManager::usePromo();
			}

			return Message::create($messageFields)->enqueue();
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function makeMessageFields(array $options, array $commonOptions): array
	{
		$phoneNumber = Parser::getInstance()->parse($commonOptions['PHONE_NUMBER']);

		$result = [
			'TEMPLATE_CODE' => $options['TEMPLATE_CODE'],
			'PLACEHOLDERS' => $options['PLACEHOLDERS'],
			'USER_ID' => $commonOptions['USER_ID'],
			'PHONE_NUMBER' => $phoneNumber->isValid()
				? Formatter::format($phoneNumber, Format::E164)
				: null,
			'LANGUAGE_ID' => LANGUAGE_ID,
			'ADDITIONAL_FIELDS' => array_merge(
				$commonOptions['ADDITIONAL_FIELDS'],
				[
					'ACTIVITY_PROVIDER_TYPE_ID' => $options['ACTIVITY_PROVIDER_TYPE_ID'] ?? null,
					'ACTIVITY_AUTHOR_ID' => $commonOptions['USER_ID'],
					'ACTIVITY_DESCRIPTION' => '',
					'MESSAGE_TO' => $commonOptions['PHONE_NUMBER'],
				]
			),
		];

		if (
			NotificationsPromoManager::isPromoSession()
			&& static::canUse()
			&& static::isAvailable()
			&& !Account::isConnected()
		)
		{
			$result['IS_TEST'] = true;
		}

		return $result;
	}

	/**
	 * @param int $messageId
	 * @param array $options
	 * @return array
	 */
	public static function getMessageByInfoId(int $messageId, array $options = []): array
	{
		$result = [
			'MESSAGE' => null,
			'HISTORY_ITEMS' => null,
			'QUEUE_ITEM' => null,
		];

		if (!static::canUse())
		{
			return $result;
		}

		$needHistory = (bool)($options['needHistory'] ?? true);
		$needQueueItem = (bool)($options['needQueueItem'] ?? false);

		$message = MessageTable::getByPrimary($messageId)->fetch();
		if ($message)
		{
			static::setStatusData($message);
			$result['MESSAGE'] = $message;
		}

		if ($needHistory)
		{
			$result['HISTORY_ITEMS'] = static::getHistory($messageId);
		}

		if ($needQueueItem)
		{
			$result['QUEUE_ITEM'] = static::getQueueItem($messageId);
		}

		return $result;
	}

	/**
	 * @return string|null
	 */
	public static function getPullTagName(): ?string
	{
		return static::canUse() ? Pull::TAG_ANY_MESSAGE : null;
	}

	/**
	 * @param int $messageId
	 * @return array
	 */
	private static function getHistory(int $messageId): array
	{
		$result = [];

		$historyList = MessageHistoryTable::getList([
			'filter' => [
				'MESSAGE_ID' => $messageId,
			],
			'order' => [
				'SERVER_DATE' => 'DESC',
			],
		]);

		while ($historyItem = $historyList->fetch())
		{
			$historyItem['ERROR_MESSAGE'] = $historyItem['ERROR_CODE']
				? ErrorCode::getLocalized($historyItem['ERROR_CODE'])
				: $historyItem['REASON'];

			static::setStatusData($historyItem);
			static::setProviderData($historyItem);

			$result[] = $historyItem;
		}

		return $result;
	}

	/**
	 * @param int $messageId
	 * @return array|null
	 */
	private static function getQueueItem(int $messageId): ?array
	{
		$queueItem = QueueTable::getByPrimary($messageId)->fetch();

		return $queueItem ?: null;
	}

	/**
	 * @param array $item
	 * @param string $statusField
	 * @param string $dataField
	 */
	private static function setStatusData(
		array &$item,
		string $statusField = 'STATUS',
		string $dataField = 'STATUS_DATA'
	): void
	{
		if (!isset($item[$statusField]))
		{
			return;
		}
		$status = $item[$statusField];

		$statusDescriptions = MessageStatus::getDescriptions();
		$statusSemantics = MessageStatus::getSemantics();

		$item[$dataField] = [
			'DESCRIPTION' => $statusDescriptions[$status] ?? null,
			'SEMANTICS' => $statusSemantics[$status] ?? null,
			'IS_FAILURE' => $statusSemantics[$status] === MessageStatus::SEMANTIC_FAILURE,
		];
	}

	/**
	 * @param array $item
	 * @param string $providerField
	 * @param string $dataField
	 */
	private static function setProviderData(
		array &$item,
		string $providerField = 'PROVIDER_CODE',
		string $dataField = 'PROVIDER_DATA'
	): void
	{
		if (!isset($item[$providerField]))
		{
			return;
		}
		$provider = $item[$providerField];

		$providerDescriptions = ProviderEnum::getDescriptions();

		$item[$dataField] = [
			'DESCRIPTION' => $providerDescriptions[$provider] ?? null,
		];
	}

	public static function showSignUpFormOnCrmShopCreated(): void
	{
		$connectUrl = (static::canUse() && !static::isConnected()) ? static::getConnectUrl() : null;
		?>
		<script type="text/javascript">
			BX.ready(
				function()
				{
					var key = 'crmShopMasterJustFinished';
					var crmShopMasterJustFinished = localStorage.getItem(key);
					if (crmShopMasterJustFinished === 'Y')
					{
						<?if ($connectUrl):?>
							BX.SidePanel.Instance.open("<?=\CUtil::JSescape($connectUrl)?>");
						<?endif;?>

						localStorage.removeItem(key);
					}
				}
			);
		</script>
		<?
	}
}
