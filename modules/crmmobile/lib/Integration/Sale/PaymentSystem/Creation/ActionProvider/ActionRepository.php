<?php

namespace Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider;

use Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider;
use Sale\Handlers\PaySystem\YandexCheckoutHandler;
use Bitrix\Sale\PaySystem\Manager;

class ActionRepository
{
	private static ?ActionRepository $instance = null;

	private function __construct()
	{}

	public static function getInstance(): ActionRepository
	{
		if (is_null(static::$instance))
		{
			static::$instance = new self();
		}

		return static::$instance;
	}

	public function getOauthProviders(): ?array
	{
		$result = [];

		/**
		 * @var $handlerClass
		 * @var ActionProvider\ActionProvider $oauthProviderClass
		 */
		foreach ($this->getOauthProvidersMap() as $handlerClass => $oauthProviderClass)
		{
			$result[(string)Manager::getFolderFromClassName($handlerClass)] = (new $oauthProviderClass())->provide();
		}

		return $result;
	}

	public function getBeforeProviders(): ?array
	{
		$result = [];

		/**
		 * @var $handlerClass
		 * @var ActionProvider\ActionProvider $oauthProviderClass
		 */
		foreach ($this->getBeforeProvidersMap() as $handlerClass => $oauthProviderClass)
		{
			$result[(string)Manager::getFolderFromClassName($handlerClass)] = (new $oauthProviderClass())->provide();
		}

		return $result;
	}

	private function getOauthProvidersMap(): array
	{
		return [
			YandexCheckoutHandler::class => ActionProvider\Oauth\YandexCheckoutProvider::class,
		];
	}

	private function getBeforeProvidersMap(): array
	{
		return [
			YandexCheckoutHandler::class => ActionProvider\Before\YandexCheckoutProvider::class,
		];
	}
}
