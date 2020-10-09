<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Ad;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

/**
 * Class Builder.
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
abstract class Builder
{
	protected $sourceId;
	/** @var Main\Type\Date */
	protected $dateTo;
	/** @var Main\Type\Date */
	protected $dateFrom;
	protected $data = [];
	protected $errorCollection;
	protected $built = false;

	/**
	 * Builder constructor.
	 */
	public function __construct()
	{
		$this->errorCollection = new Main\ErrorCollection();
	}

	/**
	 * Set source ID.
	 *
	 * @param int $sourceId Source ID.
	 * @return $this
	 */
	public function setSourceId($sourceId)
	{
		if ($this->sourceId !== $sourceId)
		{
			$this->clear();
		}
		$this->sourceId = $sourceId;
		return $this;
	}

	/**
	 * Set period.
	 *
	 * @param Main\Type\Date $dateFrom Date from.
	 * @param Main\Type\Date $dateTo Date to.
	 * @return $this
	 */
	public function setPeriod(Main\Type\Date $dateFrom, Main\Type\Date $dateTo)
	{
		if ($this->dateFrom !== $dateFrom || $this->dateTo !== $dateTo)
		{
			$this->clear();
		}
		$this->dateFrom = $dateFrom;
		$this->dateTo = $dateTo;
		return $this;
	}

	/**
	 * Set data.
	 *
	 * @param array $data Data.
	 * @return $this
	 */
	public function setData(array $data)
	{
		if ($this->data !== $data)
		{
			$this->clear();
		}
		$this->data = $data;
		return $this;
	}

	public function getErrorCollection()
	{
		return $this->errorCollection;
	}

	public function getErrorMessages()
	{
		return array_map(
			function ($error)
			{
				/** @var Main\Error $error */
				return $error->getMessage();
			},
			$this->errorCollection->toArray()
		);
	}

	public function hasErrors()
	{
		return !$this->errorCollection->isEmpty();
	}

	final public function isComplete()
	{
		if ($this->built === null)
		{
			$this->built = $this->isBuilt();
		}

		return $this->built && !$this->hasErrors();
	}

	final public function run()
	{
		$this->errorCollection->clear();
		if (!$this->isComplete())
		{
			$this->build();
		}

		return $this;
	}

	protected function clear()
	{
		$this->built = null;
	}

	/**
	 * Return true if it is built.
	 *
	 * @return bool
	 */
	abstract protected function isBuilt();

	/**
	 * Build.
	 *
	 * @return void
	 */
	abstract protected function build();

	/**
	 * Get complete label.
	 *
	 * @return string|null
	 */
	abstract public function getCompleteLabel();
}