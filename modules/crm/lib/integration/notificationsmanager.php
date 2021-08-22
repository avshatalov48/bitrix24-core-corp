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
use Bitrix\Notifications\Limit;
use Bitrix\Notifications\Model\ErrorCode;
use Bitrix\Notifications\Model\Message;
use Bitrix\Notifications\Model\MessageTable;
use Bitrix\Notifications\Model\MessageHistoryTable;
use Bitrix\Notifications\Model\QueueTable;
use Bitrix\Notifications\MessageStatus;
use Bitrix\Notifications\ProviderEnum;
use Bitrix\Notifications\Integration\Pull;
use Bitrix\ImConnector;
use Bitrix\ImOpenLines\Common;
//use Bitrix\Main\DI\ServiceLocator;

Loc::loadMessages(__FILE__);

/**
 * Class NotificationsManager
 * @package Bitrix\Crm\Integration
 * @internal
 */
class NotificationsManager implements ICanSendMessage
{
	private const CONTACT_NAME_TEMPLATE_PLACEHOLDER = 'NAME';

	/** @var bool */
	private static $canUse;

	/**
	 * @return bool
	 */
	public static function canUse(): bool
	{
		if (static::$canUse === null)
		{
			static::$canUse = (
				Loader::includeModule('notifications')
				&& Loader::includeModule('imconnector')
			);
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
		if (!static::canUse())
		{
			return false;
		}

		//@TODO temporarily getting rid of imconnector dependency in crm 21.600.0
//		/** @var \Bitrix\ImConnector\Tools\Connectors\Notifications $toolsNotifications */
//		$toolsNotifications = ServiceLocator::getInstance()->get('ImConnector.toolsNotifications');
//
//		return $toolsNotifications->isEnabled();

		return static::isEnabledTmp();
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
	public static function getConnectUrl()
	{
		if (!static::canUse())
		{
			return null;
		}

		//@TODO temporarily getting rid of imconnector dependency in crm 21.600.0
//		/** @var \Bitrix\ImConnector\Tools\Connectors\Notifications $toolsNotifications */
//		$toolsNotifications = ServiceLocator::getInstance()->get('ImConnector.toolsNotifications');
//		if (!$toolsNotifications->isEnabled())
//		{
//			return null;
//		}

		if (!static::isEnabledTmp())
		{
			return null;
		}

		//@TODO temporarily getting rid of imconnector dependency in crm 21.600.0
//		if ($toolsNotifications->canUse())
		if (static::canUseTmp())
		{
			if (
				Loader::includeModule('imopenlines')
				&& Account::isServiceAvailable()
			)
			{
				return Common::getAddConnectorUrl(
					defined('\Bitrix\ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR')
						? ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR
						: 'notifications'
				);
			}
		}
		else
		{
			return [
				'type' => 'ui_helper',
				//@TODO temporarily getting rid of imconnector dependency in crm 21.600.0
				//'value' => \Bitrix\ImConnector\Limit::INFO_HELPER_LIMIT_CONNECTOR_NOTIFICATIONS,
				'value' => 'limit_crm_sales_sms_whatsapp',
			];
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
		$e164PhoneNumber = $phoneNumber->isValid()
			? Formatter::format($phoneNumber, Format::E164)
			: null;

		$templateCode = $options['TEMPLATE_CODE'] ?? '';

		if (
			isset($options['PLACEHOLDERS'][self::CONTACT_NAME_TEMPLATE_PLACEHOLDER])
			&& self::doesTemplateUtilizeName($templateCode)
			&& \CAllCrmContact::isDefaultName($options['PLACEHOLDERS'][self::CONTACT_NAME_TEMPLATE_PLACEHOLDER])
		)
		{
			$options['PLACEHOLDERS'][self::CONTACT_NAME_TEMPLATE_PLACEHOLDER] = $e164PhoneNumber;
		}

		$result = [
			'TEMPLATE_CODE' => $templateCode,
			'PLACEHOLDERS' => $options['PLACEHOLDERS'],
			'USER_ID' => $commonOptions['USER_ID'],
			'PHONE_NUMBER' => $e164PhoneNumber,
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
	 * @param string $templateCode
	 * @return bool
	 */
	private static function doesTemplateUtilizeName(string $templateCode): bool
	{
		$nameUtilizingTemplates = [
			'ORDER_LINK',
			'ORDER_PAID',
			'ORDER_COMPLETED',
			'ORDER_IN_WORK',
			'ORDER_READY_2',
			'ORDER_IN_TRANSIT',
			'ORDER_ISSUED_COURIER',
		];

		return in_array($templateCode, $nameUtilizingTemplates, true);
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
						<?if (is_string($connectUrl)):?>
							BX.SidePanel.Instance.open("<?=\CUtil::JSescape($connectUrl)?>");
						<?elseif (is_array($connectUrl) && isset($connectUrl['type'])):?>
							<?if ($connectUrl['type'] === 'ui_helper'):?>
								BX.loadExt('ui.info-helper').then(() =>
								{
									BX.UI.InfoHelper.show("<?=\CUtil::JSescape($connectUrl['value'])?>");
								});
							<?endif;?>
						<?endif;?>

						localStorage.removeItem(key);
					}
				}
			);
		</script>
		<?
	}

	/**
	 * Temp method for getting rid of imconnector dependency in crm 21.600.0
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 * @see \Bitrix\ImConnector\Tools\Connectors\Notifications::isEnabled
	 */
	private static function isEnabledTmp(): bool
	{
		if (!Loader::includeModule('notifications'))
		{
			return false;
		}

		return !Loader::includeModule('bitrix24') || \CBitrix24::getPortalZone() === 'ru';
	}

	/**
	 * Temp method for getting rid of imconnector dependency in crm 21.600.0
	 *
	 * @return bool
	 * @see \Bitrix\ImConnector\Tools\Connectors\Notifications::canUse
	 */
	private static function canUseTmp(): bool
	{
		return Loader::includeModule('notifications') && Limit::isAvailable();
	}
}
