<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Event;

class EventResourceCollection
{

	private static $instance;

	private $originData 	= [];
	private $modifiedData 	= [];

	/**
	 * CounterService constructor.
	 */
	private function __construct()
	{

	}

	/**
	 * @return static
	 */
	public static function getInstance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param int $taskId
	 */
	public function collectOrigin(int $taskId, array $resourceData = null): void
	{
		if (!$taskId || array_key_exists($taskId, $this->originData))
		{
			return;
		}

		if (!$resourceData)
		{
			$this->originData[$taskId] = (new EventResource($taskId))->fill();
			return;
		}

		$resource = EventResource::invokeFromArray($resourceData);
		if (!$resource)
		{
			return;
		}

		$this->originData[$taskId] = $resource;
	}

	/**
	 * @param int $taskId
	 */
	public function collectModified(int $taskId): void
	{
		if ($taskId && !array_key_exists($taskId, $this->modifiedData))
		{
			$this->modifiedData[$taskId] = (new EventResource($taskId))->fill();
		}
	}

	/**
	 * @return array
	 */
	public function getOrigin(): array
	{
		return $this->originData;
	}

	/**
	 * @return array
	 */
	public function getModified(): array
	{
		return $this->modifiedData;
	}

}