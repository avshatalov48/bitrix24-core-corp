<?php declare(strict_types=1);

namespace Bitrix\AI\SharePrompt\Service\GridPrompt;

use Bitrix\AI\Exception\EmptyContextException;
use Bitrix\Main\Context;

class DateFormatService
{
	protected Context $currentContext;
	protected bool $hasRequestForCurrentContext = false;

	/**
	 * @param int $timestamp
	 * @return string
	 */
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
			$this->log('AI_SHARE_PROMPT_GRID: Empty timestamp');

			return '';
		}

		$format = $dateFormat . ($timeFormat ? ", {$timeFormat}" : '');

		return \FormatDate($format, $timestamp);
	}

	/**
	 * @param int $timestamp
	 * @return string
	 * @throws EmptyContextException
	 */
	protected function getHumanDateFormat(int $timestamp): string
	{
		$culture = $this->getCurrentContext()->getCulture();

		if (date('Y') !== date('Y', $timestamp))
		{
			return $culture->getLongDateFormat();
		}

		return $culture->getDayMonthFormat();
	}

	/**
	 * @param int $timestamp
	 * @return string
	 * @throws EmptyContextException
	 */
	protected function getHumanTimeFormat(int $timestamp): string
	{
		$timeFormat = '';
		$culture = $this->getCurrentContext()->getCulture();

		if (date('Hi', $timestamp) > 0)
		{
			$timeFormat = $culture->getShortTimeFormat();
		}

		return $timeFormat;
	}

	/**
	 * @return Context
	 * @throws EmptyContextException
	 */
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
