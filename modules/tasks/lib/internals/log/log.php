<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Log;

class Log
{
	private const DEFAULT_MARKER = 'DEBUG_TASKS';

	private $marker;
	private $currentPortal = '';
	private $portals = [];

	public function __construct(string $marker = self::DEFAULT_MARKER)
	{
		$this->marker = $marker;
	}

	/**
	 * @param string|array $portal
	 * @return $this
	 */
	public function addPortals($portals): self
	{
		if (is_string($portals))
		{
			$portals = [$portals];
		}
		if (!is_array($portals))
		{
			return $this;
		}

		$this->portals = array_merge($this->portals, $portals);
		return $this;
	}

	/**
	 * @param $data
	 * @return $this
	 */
	public function collect($data): self
	{
		try
		{
			if (!\Bitrix\Main\Loader::includeModule('intranet'))
			{
				return $this;
			}

			$this->currentPortal = \CIntranetUtils::getHostName();

			$this->checkPortal() && $this->save($data);
		}
		catch (\Exception $e)
		{
			return $this;
		}
		return $this;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function checkPortal(): bool
	{
		if (!$this->currentPortal)
		{
			return true;
		}

		if (empty($this->portals))
		{
			return true;
		}

		return in_array($this->currentPortal, $this->portals);
	}

	/**
	 * @param $data
	 */
	private function save($data)
	{
		if (!is_scalar($data))
		{
			$data = var_export($data, true);
		}

		$message = [$this->marker];
		$message[] = $data;
		$message = implode("\n", $message);

		AddMessage2Log($message, 'tasks');
	}
}