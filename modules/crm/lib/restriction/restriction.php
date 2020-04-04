<?php
namespace Bitrix\Crm\Restriction;
use Bitrix\Main;
use \Bitrix\Crm\Integration;

abstract class Restriction
{
	/** @var string  */
	protected $name = '';
	/** @var bool  */
	protected $isPersistent = false;
	public function __construct($name)
	{
		$this->setName($name);
	}
	/**
	* @return string
	*/
	public function getName()
	{
		return $this->name;
	}
	/**
	* @param string $name Name
	* @return void
	*/
	public function setName($name)
	{
		$this->name = (string)$name;
	}
	/**
	* @return bool
	*/
	public function isPersitent()
	{
		return $this->isPersistent;
	}
	/**
	* @return array
	*/
	abstract public function externalize();
	/**
	* @param array $params Params
	* @return void
	*/
	abstract public function internalize(array $params);
	/**
	* @return string
	*/
	abstract public function preparePopupScript();
	/**
	* @return string
	*/
	abstract public function getHtml();

	public function save()
	{
		$this->isPersistent = false;
		if($this->name !== '')
		{
			Main\Config\Option::set('crm', $this->name, serialize($this->externalize()), '');
			$this->isPersistent = true;
		}
		return $this->isPersistent;
	}
	public function load()
	{
		$this->isPersistent = false;
		if($this->name !== '')
		{
			$s = Main\Config\Option::get('crm', $this->name, '', '');
			$params = $s !== '' ? unserialize($s) : null;
			if(is_array($params) && !empty($params))
			{
				$this->internalize($params);
				$this->isPersistent = true;
			}
		}
		return $this->isPersistent;
	}
	public function reset()
	{
		$this->isPersistent = false;
		if($this->name !== '')
		{
			Main\Config\Option::delete('crm', array('name' => $this->name));
		}
	}
}