<?php
namespace Bitrix\Crm\Restriction;
abstract class SqlRestriction extends Restriction
{
	/** @var int */
	protected $rowCountThreshold = 0;

	public function __construct($name = '', $threshold = 0)
	{
		parent::__construct($name);
		$this->setRowCountThreshold($threshold);
	}
	/**
	* @return int
	*/
	public function getRowCountThreshold()
	{
		return $this->rowCountThreshold;
	}
	/**
	* @param int $threshold Select query threshold.
	* @return void
	*/
	public function setRowCountThreshold($threshold)
	{
		$this->rowCountThreshold = (int)$threshold;
	}
	/**
	* @return array
	*/
	public function externalize()
	{
		return array('rowCountThreshold' => $this->rowCountThreshold);
	}
	/**
	* @param array $params Params
	* @return void
	*/
	public function internalize(array $params)
	{
		$this->rowCountThreshold = isset($params['rowCountThreshold']) ? (int)$params['rowCountThreshold'] : 0;
	}
}