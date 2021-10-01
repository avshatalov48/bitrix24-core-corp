<?php
namespace Bitrix\Crm\Color;
use Bitrix\Main;
use Bitrix\Crm\PhaseSemantics;

class PhaseColorScheme
{
	const PROCESS_COLORS = array('#39A8EF', '#2FC6F6', '#55D0E0', '#47E4C2', '#FFA900');
	const PROCESS_COLOR = '#39A8EF';
	const SUCCESS_COLOR = '#7BD500';
	const FAILURE_COLOR = '#FF5752';

	/** @var string  */
	protected $optionName = '';
	/** @var PhaseColorSchemeElement[]|null  */
	protected $elements = null;
	/** @var bool */
	protected $isPersistent = false;

	/**
	 * @param string $optionName Option name.
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 */
	public function __construct($optionName)
	{
		if(!is_string($optionName))
		{
			throw new Main\ArgumentTypeException('optionName', 'string');
		}

		if($optionName === '')
		{
			throw new Main\ArgumentException('Must be not empty string', 'optionName');
		}

		$this->optionName = $optionName;
	}
	/**
	 * Get default element color by semantic ID.
	 * @param int $semanticID Semantic ID.
	 * @param array $options.
	 * @return string
	 */
	public static function getDefaultColorBySemantics($semanticID, array $options = null)
	{
		if($semanticID === PhaseSemantics::SUCCESS)
		{
			return self::SUCCESS_COLOR;
		}
		elseif($semanticID === PhaseSemantics::FAILURE)
		{
			return PhaseColorScheme::FAILURE_COLOR;
		}

		$maxIndex = count(self::PROCESS_COLORS) - 1;
		$offset = is_array($options) && isset($options['offset']) ? (int)$options['offset'] : -1;
		if($offset < 0)
		{
			$offset = 0;
		}
		elseif ($offset > $maxIndex)
		{
			$offset %= ($maxIndex + 1);
		}
		return self::PROCESS_COLORS[$offset];
	}
	/**
	 * @deprecated
	 * @return bool
	 */
	public function isPersistent()
	{
		return $this->isPersistent;
	}
	/**
	 * Add element
	 * @param PhaseColorSchemeElement $element
	 * @return void
	 */
	public function addElement(PhaseColorSchemeElement $element)
	{
		if($this->elements === null)
		{
			$this->elements = array();
		}
		$this->elements[$element->getName()] = $element;
	}
	/**
	 * Get scheme element by name.
	 * @param string $name Item Name.
	 * @return PhaseColorSchemeElement|null
	 */
	public function getElementByName($name)
	{
		return isset($this->elements[$name]) ? $this->elements[$name] : null;
	}
	/**
	 * Reset scheme.
	 */
	public function reset()
	{
		$this->elements = array();
		$this->isPersistent = false;
	}
	/**
	 * Get external representation of this object
	 * @return array
	 */
	public function externalize()
	{
		$results = array();
		foreach($this->elements as $item)
		{
			$item->externalize($results);
		}
		return $results;
	}
	/**
	 * Setup this object from external representation.
	 * @param array $params External params.
	 * @return void
	 */
	public function internalize(array $params)
	{
		foreach($params as $k => $v)
		{
			$element = new PhaseColorSchemeElement();
			$element->internalize($k, is_array($v) ? $v : array());
			$this->elements[$k] = $element;
		}
		$this->isPersistent = true;
	}
	/**
	 * Save scheme to options
	 * @return void
	 */
	public function save()
	{
		Main\Config\Option::set('crm', $this->optionName, serialize($this->externalize()), '');
		if(!$this->isPersistent)
		{
			$this->isPersistent = true;
		}
	}
	/**
	 * Try to load scheme from options
	 * @return bool
	 * @throws Main\ArgumentNullException
	 * @throws Main\NotImplementedException
	 */
	public function load()
	{
		$s = Main\Config\Option::get('crm', $this->optionName, '', '');
		$params = $s !== '' ? unserialize($s, [
			'allowed_classes' => false,
		]) : null;
		if(!is_array($params))
		{
			return false;
		}

		$this->internalize($params);
		return true;
	}

	/**
	 * Get Element Names
	 * @return array
	 */
	public function getElementNames()
	{
		return array();
	}

	/**
	 * Setup scheme by default
	 * @return void
	 */
	public function setupByDefault()
	{
		$this->reset();
		$names = $this->getElementNames();
		for($i = 0, $length = count($names); $i < $length; $i++)
		{
			$this->addElement(new PhaseColorSchemeElement($names[$i], $this->getDefaultColor($names[$i], $i)));
		}
	}
	/**
	 * Get default color for element.
	 * @param string $name Element Name.
	 * @param int $index Element Index.
	 * @return string
	 */
	public function getDefaultColor($name, $index = -1)
	{
		return '';
	}
	/**
	 * Remove scheme from options
	 * @return void
	 */
	public function remove()
	{
		Main\Config\Option::delete('crm', array('name' => $this->optionName));
		if($this->isPersistent)
		{
			$this->isPersistent = false;
		}
	}

	/**
	 * Remove scheme from options
	 * @param $optionName
	 * @return void
	 * @throws Main\ArgumentNullException
	 */
	protected static function removeByName($optionName)
	{
		Main\Config\Option::delete('crm', array('name' => $optionName));
	}

	public static function fillDefaultColors(array $stages): array
	{
		$offset = -1;
		foreach($stages as &$stage)
		{
			$semantics = $stage['SEMANTICS'] ?? '';
			if(!PhaseSemantics::isFinal($semantics))
			{
				$offset++;
			}
			$color = $stage['COLOR'] ?? '';
			if(empty($color) || mb_strlen($stage['COLOR']) < 4)
			{
				$stage['COLOR'] = static::getDefaultColorBySemantics($semantics, [
					'offset' => $offset,
				]);
			}
			elseif(mb_strpos($stage['COLOR'], '#') !== 0)
			{
				$stage['COLOR'] = '#' . $stage['COLOR'];
			}
		}

		return $stages;
	}
}