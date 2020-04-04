<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class IntegerSetting
{
	/** @var string */
	protected $name = '';
	/** @var int  */
	protected $default = 0;
	function __construct($name, $default = 0)
	{
		$this->name = $name;
		$this->default = (int)$default;
	}

	public function set($value)
	{
		$value = (int)$value;
		if($value === $this->default)
		{
			Main\Config\Option::delete('crm', array('name' => $this->name));
		}
		else
		{
			Main\Config\Option::set('crm', $this->name, $value, '');
		}
	}

	public function get()
	{
		return (int)Main\Config\Option::get('crm', $this->name, $this->default, '');
	}
}
