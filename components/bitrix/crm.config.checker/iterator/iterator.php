<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Config\Option;

class Iterator
{
	use \Bitrix\Crm\ConfigChecker\Storable;

	protected static $moduleId = "default";
	protected static $steps = [];

	protected $id = "default";
	protected $code = "default";
	protected $title = null;
	protected $description = null;
	protected $icon = null;
	protected $color = null;

	private $started = false;
	private $finished = false;
	/*@var Steps[]*/
	private $finishedSteps = [];
	/*@var Steps[]*/
	private $newSteps = [];

	private const STATUS_FINISHED = "finished";

	public function __construct()
	{
		\Bitrix\Main\Loader::includeModule(static::$moduleId);

		$steps = array_unique(static::$steps);

		if ($data = $this->invokeData())
		{
			$finishedSteps = $data["finishedSteps"];
			foreach ($finishedSteps as $stepClass)
			{
				if (in_array($stepClass, $steps))
				{
					$steps = array_diff($steps, [$stepClass]);
					if (class_exists($stepClass))
					{
						/* @var $step Step*/
						$this->finishedSteps[$stepClass] = new $stepClass($this);
					}
				}
			}
		}
		foreach ($steps as $stepClass)
		{
			if (class_exists($stepClass))
			{
				$this->newSteps[$stepClass] = new $stepClass();
			}
		}
	}

	public function checkStep()
	{
		if ($this->isFinished())
		{
			return null;
		}
		$this->start();
		/**@var \Bitrix\Crm\ConfigChecker\Step $step */
		if ($step = reset($this->newSteps))
		{
			$step->check();

			if ($step->isFinished())
			{
				array_shift($this->newSteps);
				$this->finishStep(get_class($step), $step);
			}
			return $step;
		}
		$this->finish();
		return null;
	}

	public function execStep($stepId, $method, $data)
	{
		$steps = $this->getSteps();

		if (array_key_exists($stepId, $steps))
		{
			$step = $steps[$stepId];
			$step->execute($method, $data);
			return $step;
		}
		return null;
	}

	private function finishStep($key, $step)
	{
		$this->finishedSteps[$key] = $step;
		$data = [
			"finishedSteps" => array_keys($this->finishedSteps),
			"iteratorData" => $this->pack()
		];

		$this->saveData($data);
	}

	public function reset()
	{
		$this->started = false;
		$this->finished = false;

		$this->deleteData();

		$steps = $this->finishedSteps + $this->newSteps;

		$this->finishedSteps = [];
		$this->newSteps = [];

		$stepClasses = array_unique(static::$steps);
		foreach ($stepClasses as $stepClass)
		{
			$step = (array_key_exists($stepClass, $steps) ? $steps[$stepClass] : new $stepClass());
			if ($step->isStarted())
			{
				$step->reset();
			}
			$this->newSteps[$stepClass] = $step;
		}

		$data = [
			"finishedSteps" => [],
			"iteratorData" => $this->pack()
		];
		$this->saveData($data);
	}

	public function start()
	{
		if ($this->started !== true)
		{
			$this->started = true;
			$data = [
				"finishedSteps" => array_keys($this->finishedSteps),
				"iteratorData" => $this->pack()
			];
			$this->saveData($data);
		}
	}

	public function finish()
	{
		$this->finished = true;
		$data = [
			"finishedSteps" => array_keys($this->finishedSteps),
			"iteratorData" => $this->pack()
		];
		$this->saveData($data);
	}

	protected function pack()
	{
		return [];
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function getIcon()
	{
		return $this->icon;
	}

	public function getColor()
	{
		return $this->color;
	}

	public function getNewSteps()
	{
		$this->newSteps;
	}

	public function getFinishedSteps()
	{
		return $this->finishedSteps;
	}

	public function getSteps()
	{
		return ($this->finishedSteps + $this->newSteps);
	}

	public function isStarted()
	{
		return $this->started;
	}

	public function isFinished()
	{
		return $this->finished;
	}

	public function isDefault()
	{
		return $this->code === "default";
	}
}