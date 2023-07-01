<?php

namespace Bitrix\CalendarMobile\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use CCalendar;

class Sharing extends Controller
{
	const ITEM = 'sharing';

	public function enableAction(): ?array
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(CCalendar::GetCurUserId());

		$result = $sharing->enable();

		$errors = [];
		if (!$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$errors[] = $error->getCode();
			}

			if (in_array(\Bitrix\Calendar\Sharing\Sharing::ERROR_CODE_100010, $errors, true) === false)
			{
				$this->addErrors($result->getErrors());
				return null;
			}
		}

		return [
			static::ITEM => [
				'isEnabled' => true,
				'shortUrl' => \Bitrix\Calendar\Sharing\Helper::getShortUrl($sharing->getActiveLinkUrl()),
				'isRestriction' => $this->isRestriction(),
			]
		];
	}

	public function disableAction(): ?array
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(CCalendar::GetCurUserId());
		$result = $sharing->disable();

		$errors = [];
		if (!$result->isSuccess())
		{
			foreach ($result->getErrors() as $error)
			{
				$errors[] = $error->getCode();
			}

			if (in_array(\Bitrix\Calendar\Sharing\Sharing::ERROR_CODE_100020, $errors, true) === false)
			{
				$this->addErrors($result->getErrors());
				return null;
			}
		}

		return [
			static::ITEM => [
				'isEnabled' => false,
				'isRestriction' => $this->isRestriction(),
			]
		];
	}

	public function isEnabledAction(): ?array
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(CCalendar::GetCurUserId());
		$activeLinkUrl = $sharing->getActiveLinkUrl();

		$result = [
			static::ITEM => [
				'isEnabled' => !empty($activeLinkUrl),
				'isRestriction' => $this->isRestriction(),
			]
		];

		if ($activeLinkUrl)
		{
			$result[static::ITEM]['shortUrl'] = \Bitrix\Calendar\Sharing\Helper::getShortUrl($activeLinkUrl);
		}

		return $result;
	}

	public function getPublicUserLinkAction(): ?array
	{
		$sharing = new \Bitrix\Calendar\Sharing\Sharing(CCalendar::GetCurUserId());
		$activeLinkUrl = $sharing->getActiveLinkUrl();

		if(empty($activeLinkUrl))
		{
			$this->addError(new Error('Sharing is disabled', 100040));
			return null;
		}

		return [
			static::ITEM => [
				'shortUrl'=> \Bitrix\Calendar\Sharing\Helper::getShortUrl($activeLinkUrl)
			]
		];
	}

	public function isRestriction(): bool
	{
		return !\Bitrix\Calendar\Integration\Bitrix24Manager::isFeatureEnabled('calendar_sharing');
	}
}