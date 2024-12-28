<?php

namespace Bitrix\Mobile\AvaMenu;

use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\AvaMenu\Items\Calendar;
use Bitrix\Mobile\AvaMenu\Items\CheckIn;
use Bitrix\Mobile\AvaMenu\Items\EmailConfirm;
use Bitrix\Mobile\AvaMenu\Items\GoToWeb;
use Bitrix\Mobile\AvaMenu\Items\Settings;
use Bitrix\Mobile\AvaMenu\Items\Signing;
use Bitrix\Mobile\AvaMenu\Items\SwitchAccount;
use Bitrix\Mobile\AvaMenu\Items\Timeman;
use Bitrix\Mobile\Context;

class Manager
{
	protected Context $context;
	protected array $menuItems;

	public function __construct(Context $context)
	{
		$this->context = $context;

		$this->menuItems = [
			new EmailConfirm($context),
			new CheckIn($context),
			new Timeman($context),
			new Calendar($context),
			new Signing($context),
			new Settings($context),
			new GoToWeb($context),
			new SwitchAccount($context),
		];
	}

	public function getMenuData(): array
	{
		$result = [];

		/** @var AbstractMenuItem $item */
		foreach ($this->menuItems as $item)
		{
			if (!$item->isAvailable())
			{
				continue;
			}

			if ($item->separatorBefore())
			{
				$result[] = $this->getSeparator();
			}

			$result[] = [
				'title' => $this->getItemTitle($item),
				...$item->getData(),
			];

			if ($item->separatorAfter())
			{
				$result[] = $this->getSeparator();
			}
		}

		return $result;
	}

	public function getTotalCounter(): int
	{
		return array_reduce($this->getMenuData(), fn($acc, $item) => $acc + (int)($item['counter'] ?? 0), 0);
	}

	private function getItemTitle(AbstractMenuItem $item): ?string
	{
		return Loc::getMessage($item->getMessageCode());
	}

	private function getSeparator(): array
	{
		return [
			'type' => 'separator',
		];
	}
}
