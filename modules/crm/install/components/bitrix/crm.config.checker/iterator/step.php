<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Type\Dictionary;

abstract class Step
{
	use \Bitrix\Crm\ConfigChecker\Storable;

	protected static $moduleId = "crm";

	private $started = false;
	private $finished = false;
	private $actual = null;
	private $correct = null;

	protected $errorCollection;
	protected $noteCollection;
	protected $request;

	protected static $title = "";
	protected static $description = "";
	protected static $url = "";

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
		$this->noteCollection = new Dictionary();
		$this->request = \Bitrix\Main\Context::getCurrent()->getRequest();

		$data = $this->invokeData();
		$this->init($data);
	}

	protected function init(array $data)
	{
		return $this;
	}

	protected function pack()
	{
		return [];
	}

	public function check()
	{
		$this->start();
		if (!$this->isFinished())
		{
			if (is_null($this->actual))
			{
				$this->actual = $this->checkActuality();
			}
			if ($this->actual === true)
			{
				$this->correct = $this->checkCorrectness();
			}

			if ($this->actual === true && is_bool($this->correct) || $this->actual === false)
			{
				$this->finish();
			}
			else
			{
				$this->save();
			}
		}
		return $this;
	}

	public function execute(string $method, $data = [])
	{
		if (method_exists($this, "action".$method))
		{
			$result = $this->{"action".$method}($data);
			if ($result === true)
			{
				$this->errorCollection->clear();
				$this->correct = $this->checkCorrectness();
				$this->save();
			}
		}
	}
	/**
	 * Returs true or false if checking is finished, null in case it needs one more step
	 * @return bool|null
	 */
	abstract protected function checkActuality();

	/**
	 * Returs true or false if checking is finished, null if it needs one more step
	 * @return bool | null
	 */
	abstract protected function checkCorrectness();

	/**
	 * Returns "true" in case this step is finished, "false" = if
	 * @return []
	 */
	public function reset()
	{
		$this->actual = null;
		$this->correct = null;
		$this->started = false;
		$this->finished = false;
		$this->errorCollection->clear();
		$this->noteCollection->clear();
		$this->deleteData();
	}

	public function save()
	{
		$data = $this->pack();
		$this->saveData($data);
	}

	public function start()
	{
		$this->started = true;
	}

	public function finish()
	{
		$this->started = true;
		$this->finished = true;
		$data = $this->pack();
		$this->saveData($data);
	}

	public function isStarted()
	{
		return $this->started;
	}

	public function isFinished()
	{
		return $this->finished;
	}

	public function isActual()
	{
		return $this->actual;
	}

	public function isCorrect()
	{
		return $this->correct;
	}

	public function getId()
	{
		return static::class;
	}

	public function getTitle()
	{
		return static::$title;
	}

	public function getDescription()
	{
		return static::$description;
	}

	public function getUrl()
	{
		return static::$url;
	}

	/**
	 * @return ErrorCollection
	 */
	public function getErrors()
	{
		return $this->errorCollection;
	}

	/**
	 * @return Dictionary
	 */
	public function getNotes()
	{
		return $this->noteCollection;
	}
}

