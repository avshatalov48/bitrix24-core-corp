<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Integration\Pull;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class PushService
{

	private static $instance;
	private static $jobOn = false;

	private $registry = [];

	/**
	 * CounterService constructor.
	 */
	private function __construct()
	{

	}

	/**
	 * @return PushService
	 */
	public static function getInstance(): PushService
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param $recipients
	 * @param array $params
	 */
	public static function addEvent($recipients, array $params): void
	{
		self::getInstance()->registerEvent($recipients, $params);

		if (!self::$jobOn)
		{
			$application = Application::getInstance();
			$application && $application->addBackgroundJob(
				['\Bitrix\Tasks\Integration\Pull\PushService', 'proceed'],
				[],
				0
			);

			self::$jobOn = true;
		}
	}

	/**
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function proceed()
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}
		self::getInstance()->sendEvents();
	}

	/**
	 * @param $recipients
	 * @param array $params
	 */
	private function registerEvent($recipients, array $params)
	{
		$this->registry[] = [
			'RECIPIENTS' => $recipients,
			'PARAMS' => $params
		];
	}

	/**
	 *
	 */
	private function sendEvents()
	{
		foreach ($this->registry as $event)
		{
			\Bitrix\Pull\Event::add($event['RECIPIENTS'], $event['PARAMS']);
		}
	}

}