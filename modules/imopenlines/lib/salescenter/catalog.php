<?php
namespace Bitrix\ImOpenLines\SalesCenter;

use Bitrix\ImOpenLines\Im;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\ImConnector\InteractiveMessage\Output;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Catalog extends Base
{
	private $productIds = [];

	/**
	 * Checks if the interactive message is available.
	 *
	 * @param $lineId
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function isAvailable($lineId): bool
	{
		$result = false;

		if (Loader::includeModule('imconnector'))
		{
			$result = Output::getInstance($this->chatId)->isAvailable($lineId);
		}

		return $result;
	}

	/**
	 * Sets an array of catalog products external (facebook) ID's for a message.
	 *
	 * @param array $ids External (facebook) ids of catalog products.
	 */
	public function setProductIds(array $ids = []): void
	{
		$this->productIds = $ids;
	}

	/**
	 * Sends a message with catalog products.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function send(): Result
	{
		if (Loader::includeModule('imconnector') && $this->isValidProductList())
		{
			Output::getInstance($this->chatId)->setProductIds($this->productIds);
		}

		return $this->sendMessage();
	}

	private function isValidProductList(): bool
	{
		return !empty($this->productIds) && is_array($this->productIds);
	}

	public static function OnChatAnswer(Event $event): void
	{
		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		if ($region === null || $region === 'ru')
		{
			return;
		}

		if (\Bitrix\Main\Config\Option::get('catalog', 'fb_product_export_enabled', 'N') !== 'Y')
		{
			return;
		}

		/** @var \Bitrix\ImOpenLines\Session $runtimeSession */
		$runtimeSession = $event->getParameter('RUNTIME_SESSION');
		if ($runtimeSession->getData('SOURCE') !== 'facebook')
		{
			return;
		}

		$userId = $event->getParameter('USER_ID');
		$session = $event->getParameter('RUNTIME_SESSION');

		$facebookOpenlinesInformationMessageWatched = \CUserOptions::GetOption(
			'imopenlines',
			'facebook_openlines_information_message_watched',
			'N'
		);

		if ($facebookOpenlinesInformationMessageWatched !== 'Y')
		{
			\CUserOptions::SetOption(
				'imopenlines',
				'facebook_openlines_information_message_watched',
				'Y'
			);

			$keyboard = new \Bitrix\Im\Bot\Keyboard();
			$keyboard->addButton(Array(
				"TEXT" => Loc::getMessage('IMOL_SALESCENTER_CATALOG_FACEBOOK_OPENLINES_INFORMATION_LINK_TEXT'),
				"FUNCTION" => "BX.MessengerCommon.openStore()",
				"BG_COLOR" => "#727475",
				"TEXT_COLOR" => "#fff",
				"CONTEXT" => "DESKTOP",
			));

			Im::addMessage([
				'FROM_USER_ID' => $userId,
				'TO_CHAT_ID' => $session->getData('CHAT_ID'),
				'MESSAGE' => Loc::getMessage('IMOL_SALESCENTER_CATALOG_FACEBOOK_OPENLINES_INFORMATION_MESSAGE_1'),
				'SYSTEM' => 'Y',
				'PARAMS' => [
					'CLASS' => 'bx-messenger-content-item-system'
				],
				'KEYBOARD' => $keyboard
			]);
		}
	}
}
