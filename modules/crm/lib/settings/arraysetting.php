<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class ArraySetting extends Setting
{
	/** @var string */
	protected $name = '';
	/** @var array|null */
	protected $default = null;
	/** @var array|null */
	protected $value = null;

	function __construct($name, array $default = null)
	{
		parent::__construct($name);

		$this->default = $default !== null ? $default : array();
	}

	public function set(array $value)
	{
		if($value == $this->default)
		{
			Main\Config\Option::delete('crm', array('name' => $this->name));
		}
		else
		{
			Main\Config\Option::set('crm', $this->name, serialize($value));
		}
	}

	public function get()
	{
		if($this->value !== null)
		{
			return $this->value;
		}

		$value = Main\Config\Option::get('crm', $this->name, '', '');
		$this->value = $value !== '' ? unserialize($value) : $this->default;
		return $this->value;
	}
}
