<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Notifications;

use Bitrix\Booking\Internals\Service\Notifications\NotificationTemplateType;
use Bitrix\Booking\Internals\Service\Notifications\NotificationType;
use Bitrix\Booking\Provider\NotificationsAvailabilityProvider;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Notifications\ApiClient;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/booking/lib/Integration/Notifications/TemplateRepository.php');

class TemplateRepository
{
	private array|null $templates = null;

	private const CACHE_TIME = 86400;
	private const CACHE_DIR = '/booking/template_repository/';

	public function getTemplatesByNotificationType(NotificationType $notificationType): array
	{

		self::loadTemplates();

		$notificationToTemplateTypeMap = self::getNotificationToTemplateTypeMap();

		if (!isset($notificationToTemplateTypeMap[$notificationType->value]))
		{
			return [];
		}

		$result = [];

		foreach ($notificationToTemplateTypeMap[$notificationType->value] as $notificationTemplateType)
		{
			$template = $this->getTemplateData($notificationType, $notificationTemplateType);
			if ($template)
			{
				$result[] = $template;
			}
		}

		return $result;
	}

	public static function getTemplateCode(
		NotificationType $notificationType,
		NotificationTemplateType $notificationTemplateType
	): string
	{
		return implode(
			'_',
			[
				'BOOKING',
				mb_strtoupper($notificationType->value),
				mb_strtoupper($notificationTemplateType->value)
			]
		);
	}

	private function getTemplateData(
		NotificationType $notificationType,
		NotificationTemplateType $notificationTemplateType
	): array|null
	{
		$templateCode = self::getTemplateCode(
			$notificationType,
			$notificationTemplateType
		);

		if (!isset($this->templates[$templateCode]))
		{
			return null;
		}

		return [
			'type' => $notificationTemplateType->value,
			'text' => self::replaceTemplateVars(
				self::replaceLineBreaks(
					$this->templates[$templateCode]['TEXT']
				)
			),
			'textSms' => self::replaceTemplateVars(
				self::replaceLineBreaks(
					$this->templates[$templateCode]['TEXT_SMS']
				)
			),
		];
	}

	private static function replaceLineBreaks(string $text): string
	{
		return str_replace("\n", '<br/>', $text);
	}

	private static function replaceTemplateVars(string $text): string
	{
		$map = [
			'CLIENT_NAME' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_CLIENT_NAME'),
			'DATE_FROM' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_DATE_FROM'),
			'DATE_TO' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_DATE_TO'),
			'DATE_TIME_FROM' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_DATE_TIME_FROM'),
			'DATE_TIME_TO' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_DATE_TIME_TO'),
			'RESOURCE_TYPE_NAME' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_RESOURCE_TYPE_NAME'),
			'RESOURCE_NAME' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_RESOURCE_NAME'),
			'MANAGER_NAME' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_MANAGER_NAME'),
			'COMPANY_NAME' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_COMPANY_NAME'),
			'CONFIRMATION_LINK' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_CONFIRMATION_LINK'),
			'DELAYED_CONFIRMATION_LINK' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_DELAYED_CONFIRMATION_LINK'),
			'FEEDBACK_LINK' => Loc::getMessage('BOOKING_TEMPLATE_REPLACE_FEEDBACK_LINK'),
		];

		//@todo needs to be removed after we fix issue with edna template
		$text = str_replace('#SOME_TEXT#', ' ', $text);

		return str_replace(
			array_map(static fn($item) => '#' . $item . '#', array_keys($map)),
			array_map(static fn($item) => '<span>[' . $item . ']</span>', array_values($map)),
			$text
		);
	}

	public static function getAllKnowTemplateCodes(): array
	{
		$result = [];

		foreach (NotificationType::cases() as $notificationType)
		{
			$notificationToTemplateTypeMap = self::getNotificationToTemplateTypeMap();
			if (!isset($notificationToTemplateTypeMap[$notificationType->value]))
			{
				continue;
			}

			foreach ($notificationToTemplateTypeMap[$notificationType->value] as $notificationTemplateType)
			{
				$result[] = implode(
					'_',
					[
						'BOOKING',
						mb_strtoupper($notificationType->value),
						mb_strtoupper($notificationTemplateType->value)
					]
				);
			}
		}

		return $result;
	}

	private static function getNotificationToTemplateTypeMap(): array
	{
		return [
			NotificationType::Info->value => [
				NotificationTemplateType::Animate,
				NotificationTemplateType::Inanimate,
				//NotificationTemplateType::InanimateLong,
			],
			NotificationType::Confirmation->value => [
				NotificationTemplateType::Animate,
				NotificationTemplateType::Inanimate,
				//NotificationTemplateType::InanimateLong,
			],
			NotificationType::Reminder->value => [
				NotificationTemplateType::Base,
			],
			NotificationType::Delayed->value => [
				NotificationTemplateType::Animate,
				NotificationTemplateType::Inanimate,
			],
			NotificationType::Feedback->value => [
				NotificationTemplateType::Animate,
				NotificationTemplateType::Inanimate,
				//NotificationTemplateType::InanimateLong,
			],
		];
	}

	private function loadTemplates(): void
	{
		if (
			!Loader::includeModule('notifications')
			|| !NotificationsAvailabilityProvider::isAvailable()
		)
		{
			return;
		}

		if ($this->templates !== null)
		{
			return;
		}

		$langId = 'ru';
		$cache = Cache::createInstance();
		$cacheId = md5('booking-template-repository' . '-lang-' . $langId);

		$result = null;
		if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR))
		{
			$resultData = $cache->getVars();
			$result = new Result();
			$result->setData($resultData);
		}
		elseif ($cache->startDataCache())
		{
			$result = (new ApiClient())->getTemplatesByCodes(self::getAllKnowTemplateCodes(), $langId);
			if ($result->isSuccess())
			{
				if (
					!(
						is_array($result->getData())
						&& $this->isTemplatesResultDataValid($result->getData())
					)
				)
				{
					$cache->abortDataCache();
				}
				else
				{
					$cache->endDataCache($result->getData());
				}
			}
			else
			{
				$cache->abortDataCache();
			}
		}

		if ($result === null || !$result->isSuccess())
		{
			return;
		}

		$translationsResultData = $result->getData();
		if (!is_array($translationsResultData))
		{
			return;
		}

		$this->templates = [];

		foreach ($translationsResultData as $item)
		{
			if (
				!(
					isset($item['CODE'])
					&& isset($item['TRANSLATIONS'][0])
				)
			)
			{
				continue;
			}

			$translationItem = $item['TRANSLATIONS'][0];
			if (
				!(
					is_array($translationItem)
					&& !empty($translationItem['TEXT'])
					&& !empty($translationItem['TEXT_SMS'])
				)
			)
			{
				continue;
			}

			$this->templates[$item['CODE']] = $translationItem;
		}
	}

	private function isTemplatesResultDataValid(array $resultData): bool
	{
		if (empty($resultData) || !isset($resultData[0]))
		{
			return false;
		}

		$firstItem = $resultData[0];

		return (
			isset($firstItem['CODE'])
			&& isset($firstItem['TRANSLATIONS'][0])
		);
	}
}
