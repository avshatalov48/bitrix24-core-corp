<?php


namespace Bitrix\Crm\Format;


use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class Date
{
	protected $dateFormats = [
		'short' => [
			'en' => 'F j',
			'de' => 'j. F',
			'ru' => 'j F',
		],
		'full' => [
			'en' => 'F j, Y',
			'de' => 'j. F Y',
			'ru' => 'j F Y',
		],
	];

	public function format($date, bool $formatTime = false): string
	{
		if ($date === '')
		{
			return '';
		}

		if ($date instanceof Date || $date instanceof \DateTime)
		{
			$timestamp = $date->getTimestamp();
		}
		else
		{
			$timestamp = \MakeTimeStamp($date);
		}

		$now = time() + \CTimeZone::GetOffset();
		$isShortDateFormat = (date('Y') === date('Y', $timestamp));
		$dateFormat = $this->getDateFormat($isShortDateFormat ? 'short' : 'full');

		if (!$formatTime)
		{
			return \FormatDate($dateFormat, $timestamp, $now);
		}

		$offset = ($now - $timestamp);
		$isLessThanOneMinute = $offset / 60 < 1;
		if ($isLessThanOneMinute)
		{
			return Loc::getMessage('CRM_FORMAT_DATE_NOW');
		}

		$dealDate = DateTime::createFromTimestamp($timestamp)->toUserTime()->setTime(0, 0);
		$nowDate = (new DateTime())->toUserTime()->setTime(0, 0);
		$diff = $nowDate->getDiff($dealDate);

		$isTwoOrMoreDays = ($diff->days > 1);
		$format = ($isTwoOrMoreDays ? $dateFormat : 'x');
		return \FormatDate($format, $timestamp, $now);
	}

	/**
	 * @param string|null $type
	 * @return array|string
	 */
	public function getDateFormat(?string $type)
	{
		$lang = 'ru';
		if (LANGUAGE_ID === 'de' || LANGUAGE_ID === 'en')
		{
			$lang = LANGUAGE_ID;
		}

		return ($type === null ? $this->dateFormats : $this->dateFormats[$type][$lang]);
	}
}
