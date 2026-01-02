<?php
namespace Bitrix\Tasks\Integration\Pull;

use Bitrix\Main;
use Bitrix\Pull\Event;
use Bitrix\Tasks\V2\FormV2Feature;

/**
 * Class PushService
 *
 * @package Bitrix\Tasks\Integration\Pull
 */
class PushService
{
	public const MODULE_NAME = 'tasks';

	private static $instance;
	private static $isJobOn = false;

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
		$params = self::preparePullManagerParams($params);

		$parameters = [
			'RECIPIENTS' => $recipients,
			'PARAMS' => $params,
		];

		self::getInstance()->registerEvent($parameters)->flush();
	}

	public static function addEventByTag(string $tag, array $params): void
	{
		$params = self::preparePullManagerParams($params);

		$parameters = [
			'TAG' => $tag,
			'PARAMS' => $params,
		];
		self::getInstance()->registerEvent($parameters)->flush();
	}

	/**
	 * @throws Main\LoaderException
	 */
	public static function proceed(): void
	{
		if (!Main\Loader::includeModule('pull'))
		{
			return;
		}

		self::getInstance()->sendEvents();
	}

	private function addBackgroundJob(): void
	{
		if (!self::$isJobOn)
		{
			$application = Main\Application::getInstance();
			$application && $application->addBackgroundJob([__CLASS__, 'proceed'], [], 0);

			self::$isJobOn = true;
		}
	}

	/**
	 * @param array $parameters
	 */
	private function registerEvent(array $parameters): static
	{
		$this->registry[] = [
			'TAG' => $parameters['TAG'] ?? null,
			'RECIPIENTS' => $parameters['RECIPIENTS'] ?? null,
			'PARAMS' => $parameters['PARAMS'] ?? null,
		];

		return $this;
	}

	private function sendEvents(): void
	{
		foreach ($this->registry as $k => $event)
		{
			if (isset($event['TAG']) && $event['TAG'] !== '')
			{
				$eventName = $event['PARAMS']['params']['eventName'] ?? null;
				$userId = $event['PARAMS']['params']['userId'] ?? null;
				$isPullUnsubscribe = $eventName === PushCommand::TASK_PULL_UNSUBSCRIBE;

				($isPullUnsubscribe && $userId)
					? \CPullWatch::Delete($userId, $event['TAG'])
					: \CPullWatch::AddToStack($event['TAG'], $event['PARAMS'])
				;
			}
			else
			{
				Event::add($event['RECIPIENTS'], $event['PARAMS']);
			}

			unset($this->registry[$k]);
		}
	}

	private function flush(): void
	{
		if (FormV2Feature::isOn('miniform'))
		{
			self::proceed();
		}
		else
		{
			self::getInstance()->addBackgroundJob();
		}
	}

	private static function preparePullManagerParams(array $params): array
	{
		$pullManagerParams = [
			'eventName' => $params['command'],
			'item' => [],
			'skipCurrentUser' => false,
			'eventId' => null,
			'ignoreDelay' => false,
		];

		$params['params'] = $params['params'] + $pullManagerParams;

		return $params;
	}
}
