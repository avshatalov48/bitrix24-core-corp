<?php
namespace Bitrix\Crm\Restriction;
abstract class AccessRestriction extends Restriction
{
	/** @var bool */
	protected $permitted = false;

	public function __construct($name = '', $permitted = false)
	{
		parent::__construct($name);
		$this->permit($permitted);
	}
	/**
	* @return bool
	*/
	public function hasPermission()
	{
		return $this->permitted;
	}
	/**
	* @param bool $permitted Permission Flag.
	* @return void
	*/
	public function permit($permitted)
	{
		$this->permitted = (bool)$permitted;
	}
	/**
	* @return array
	*/
	public function externalize()
	{
		return array('permitted' => $this->permitted ? 'Y' : 'N');
	}
	/**
	* @param array $params Params
	* @return void
	*/
	public function internalize(array $params)
	{
		$this->permitted = isset($params['permitted']) && $params['permitted'] === 'Y';
	}
}