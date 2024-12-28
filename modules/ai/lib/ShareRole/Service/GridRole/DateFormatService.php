<?php

namespace Bitrix\AI\ShareRole\Service\GridRole;

use Bitrix\AI\Exception\EmptyContextException;
use Bitrix\Main\Context;

class DateFormatService
{

	protected Context $currentContext;
	protected bool $hasRequestForCurrentContext = false;

	public function formatDate(int $timestamp): string
	{
		if (empty($timestamp))
		{
			return '';
		}

		try
		{
			$dateFormat = $this->getHumanDateFormat($timestamp);
			$timeFormat = $this->getHumanTimeFormat($timestamp);
		}
		catch (EmptyContextException $exception)
		{
			$this->log('AI_SHARE_ROLE_GRID: Empty timestamp');

			return '';
		}

		$format = $dateFormat . ($timeFormat ? ", {$timeFormat}" : '');

		return \FormatDate($format, $timestamp);
	}

	protected function getHumanDateFormat(int $timestamp): string
	{
		$culture = $this->getCurrentContext()->getCulture();

		if ($culture === null) {

			$defaultLongDateFormat = 'j F Y';
			$defaultDayMonthFormat = 'j F';

			if (date('Y') !== date('Y', $timestamp)) {
				return $defaultLongDateFormat;
			}

			return $defaultDayMonthFormat;
		}

		if (date('Y') !== date('Y', $timestamp))
		{
			return $culture->getLongDateFormat();
		}

		return $culture->getDayMonthFormat();
	}

	protected function getHumanTimeFormat(int $timestamp): string
	{
		$timeFormat = '';
		$culture = $this->getCurrentContext()->getCulture();

		if ($culture === null)
		{
			return 'H:i';
		}

		if (date('Hi', $timestamp) > 0)
		{
			$timeFormat = $culture->getShortTimeFormat();
		}

		return $timeFormat;
	}

	protected function getCurrentContext(): Context
	{
		if (!$this->hasRequestForCurrentContext && empty($this->currentContext))
		{
			$this->currentContext = Context::getCurrent();
			$this->hasRequestForCurrentContext = true;
		}

		if (empty($this->currentContext))
		{
			throw new EmptyContextException();
		}

		return $this->currentContext;
	}

	protected function log(string $message): void
	{
		AddMessage2Log($message);
	}
}