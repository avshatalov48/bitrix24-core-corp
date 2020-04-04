<?php
namespace Bitrix\Crm\Color;
use Bitrix\Main;
class PhaseColorSchemeElement
{
	/** @var string */
	private $name = '';
	/** @var string */
	private $color = '';

	/**
	 * @param string $name Name.
	 * @param string $color Color.
	 */
	public function __construct($name = '', $color = '')
	{
		$this->setName($name);
		$this->setColor($color);
	}

	/**
	 * Get item name.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * Set item name.
	 * @param string $name Item name.
	 * @return void
	 * @throws Main\ArgumentTypeException
	 */
	public function setName($name)
	{
		if(!is_string($name))
		{
			$name = strval($name);
		}

		$this->name = $name;
	}
	/**
	 * Get item color.
	 * @return string
	 */
	public function getColor()
	{
		return $this->color;
	}
	/**
	 * Set item color.
	 * @param string $color Item color.
	 * @return void
	 * @throws Main\ArgumentTypeException
	 */
	public function setColor($color)
	{
		if(!is_string($color))
		{
			throw new Main\ArgumentTypeException('color', 'string');
		}

		$this->color = $color;
	}
	/**
	 * Get external representation of this object
	 * @param array $params Destination.
	 * @return void
	 */
	public function externalize(array &$params)
	{
		if($this->name !== '')
		{
			$params[$this->name] = array('COLOR' => $this->color);
		}
	}
	/**
	 * Setup this object from external representation.
	 * @param string $name Name.
	 * @param array $params Source.
	 * @return void
	 */
	public function internalize($name, array $params)
	{
		$this->name = $name;
		if(is_array($params))
		{
			$this->color = isset($params['COLOR']) ? $params['COLOR'] : '';
		}
	}
}