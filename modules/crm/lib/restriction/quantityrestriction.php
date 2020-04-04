<?php
namespace Bitrix\Crm\Restriction;
abstract class QuantityRestriction extends Restriction
{
	/** @var int */
	protected $quantityLimit = 0;

	public function __construct($name = '', $limit = 0)
	{
		parent::__construct($name);
		$this->setQuantityLimit($limit);
	}
	/**
	 * Get quantity limit
	 * @return int
	 */
	public function getQuantityLimit()
	{
		return $this->quantityLimit;
	}
	/**
	 * Setup quantity limit.
	 * @param int $limit Maximum allowed quantity.
	 * @return void
	 */
	public function setQuantityLimit($limit)
	{
		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		$this->quantityLimit = $limit;
	}
	/**
	 * Externalize
	 * @return array
	 */
	public function externalize()
	{
		return array('quantityLimit' => $this->quantityLimit);
	}
	/**
	 * Internalize
	 * @param array $params Params
	 * @return void
	 */
	public function internalize(array $params)
	{
		$this->quantityLimit = isset($params['quantityLimit']) ? (int)$params['quantityLimit'] : 0;
	}
}