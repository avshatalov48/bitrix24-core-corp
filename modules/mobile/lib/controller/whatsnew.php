<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Config\Option;

class WhatsNew extends Controller
{
	protected const WHATS_NEW_REQUEST_PATH = '/widget2/whats_new_articles.php';
	protected const WHATS_NEW_OPTION_NAME = 'bitrix24.whatsnew';

	public function configureActions(): array
	{
		return [
			'getParams' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'saveWhatsNewOptions' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getParamsAction(): array
	{
		$userData = \CUser::getById($this->getCurrentUser()->getId())->Fetch();

		$portalDateRegister = Option::get('main', '~controller_date_create');
		$url = \Bitrix\UI\InfoHelper::getUrl(self::WHATS_NEW_REQUEST_PATH);
		$userDateRegister = (!empty($userData['DATE_REGISTER'])) ? MakeTimeStamp($userData['DATE_REGISTER']) : (new \Bitrix\Main\Type\DateTime())->getTimestamp();

		$option = \CUserOptions::GetOption('mobile', self::getOptionName());
		$lastChecked = 0;
		$count = 0;
		if ($option && isset($option['lastChecked']))
		{
			$lastChecked = $option['lastChecked'];
			$count = $option['count'];
		}

		return [
			'url' => $url,
			'userDateRegister' => $userDateRegister,
			'portalDateRegister' => $portalDateRegister,
			'lastNewsCheckTime' => $lastChecked,
			'count' => $count,
		];
	}

	public function updateLocalParamsAction(int $count, int $lastChecked): void
	{
		if ($count < 0)
		{
			throw new \InvalidArgumentException('Count must be a non-negative integer.');
		}

		if ($lastChecked < 0)
		{
			throw new \InvalidArgumentException('LastChecked must be a valid timestamp.');
		}

		\CUserOptions::SetOption('mobile', self::getOptionName(), [
			'lastChecked' => $lastChecked,
			'count' => $count,
		]);
	}

	private static function getOptionName(): string
	{
		return defined('LANGUAGE_ID') ? self::WHATS_NEW_OPTION_NAME . '_' . LANGUAGE_ID : self::WHATS_NEW_OPTION_NAME;
	}
}
