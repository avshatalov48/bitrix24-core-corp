<?php

namespace Bitrix\Voximplant\Routing;

use Bitrix\Main\Web\Json;

class Action
{
	protected $command;
	protected $parameters = [];

	/**
	 * Class constructor.
	 * @param string $command
	 * @param array $parameters
	 * @return Action
	 */
	public function __construct($command, $parameters = [])
	{
		$this->setCommand($command);
		$this->setParameters($parameters);
	}

	/**
	 * Class constructor.
	 * @param string $command
	 * @param array $parameters
	 * @return Action
	 */
	public static function create($command, $parameters = [])
	{
		return new static($command, $parameters);
	}

	/**
	 * @return string
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * @param string $command
	 */
	public function setCommand($command)
	{
		$this->command = $command;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	public function getParameter($name)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
	}

	/**
	 * @param array $parameters
	 */
	public function setParameters(array $parameters)
	{
		$this->parameters = $parameters;
	}

	public function toJson()
	{
		$action = ['COMMAND' => $this->command] + $this->parameters;

		return Json::encode($action);
	}
}