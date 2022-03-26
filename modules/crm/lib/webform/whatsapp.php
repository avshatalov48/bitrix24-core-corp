<?php

namespace Bitrix\Crm\WebForm;

use Bitrix\Main;
use Bitrix\ImOpenLines;
use Bitrix\ImConnector;
use Bitrix\Notifications;

class WhatsApp
{
	/**
	 * Can use WhatsApp.
	 *
	 * @return bool
	 */
	public static function canUse(): bool
	{
		return false //disabled
			&& Main\Loader::includeModule('imopenlines')
			&& Main\Loader::includeModule('imconnector')
			&& Main\Loader::includeModule('notifications')
			&& class_exists('\Bitrix\Notifications\Settings')
		;
	}

	/**
	 * Send WhatsApp event.
	 *
	 * @param array $eventParameters Event parameters.
	 * @return void
	 */
	public static function sendEvent(array $eventParameters): void
	{
		$whatsAppEvent = new Main\Event(
			'crm',
			'OnCrmWhatsAppFormSubmitted',
			array($eventParameters)
		);

		Main\EventManager::getInstance()->send($whatsAppEvent);
	}

	/**
	 * Get Link to set up WhatsApp.
	 *
	 * @return string
	 */
	public static function getSetupLink(): ?string
	{
		if (self::canUse())
		{
			return ImOpenLines\Common::getAddConnectorUrl(ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR);
		}

		return null;
	}

	/**
	 * Check was whatsapp configured.
	 *
	 * @return bool
	 */
	public static function isSetupCompleted(): bool
	{
		return self::canUse()
			&& class_exists('\Bitrix\Notifications\Settings')
			&& Notifications\Settings::isScenarioEnabled(Notifications\Settings::SCENARIO_REVERSE_WHATSAPP)
		;
	}

	/**
	 * Get URL of form editor.
	 *
	 * @return string|null
	 */
	public static function getDefaultFormEditUrl(): ?string
	{
		if (!self::canUse())
		{
			return null;
		}

		return Internals\LandingTable::getLandingEditUrl(self::getDefaultFormId());
	}

	/**
	 * Get URL of form editor.
	 *
	 * @return int|null
	 */
	public static function getDefaultFormId(): ?int
	{
		if (!self::canUse())
		{
			return null;
		}

		$id = Internals\FormTable::getRow([
			"select" => ["ID"],
			"order" => ["ID" => "DESC"],
			"filter" => ["IS_WHATSAPP_FORM" => "Y"],
		])['ID'] ?? null;

		if (!$id)
		{
			Preset::installWhatsAppDefaultForm();
			$id = Internals\FormTable::getRow([
					"select" => ["ID"],
					"order" => ["ID" => "DESC"],
					"filter" => ["IS_WHATSAPP_FORM" => "Y"],
				])['ID'] ?? null;
			if (!$id)
			{
				return null;
			}
		}

		return (int)$id;
	}

	/**
	 * Get array of phrases for editor.
	 *
	 * @return array
	 */
	public static function getMessages(): array
	{
		if (!self::canUse())
		{
			return [];
		}

		$messages = [];
		$messages[] = [
			'text' => 'message',
			'langId' => 'en',
			'default' => true,
		];
		return $messages;
	}

	/**
	 * Get help ID.
	 *
	 * @return string
	 */
	public static function getHelpId(): string
	{
		return '';
	}
}
