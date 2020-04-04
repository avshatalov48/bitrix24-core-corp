<?php
namespace Bitrix\Crm\Settings;

use Bitrix\Main;

class Setting
{
	/** @var string */
	protected $name = '';
	function __construct($name)
	{
		$this->name = $name;
	}

	public function remove()
	{
		Main\Config\Option::delete('crm', array('name' => $this->name));
	}
}
