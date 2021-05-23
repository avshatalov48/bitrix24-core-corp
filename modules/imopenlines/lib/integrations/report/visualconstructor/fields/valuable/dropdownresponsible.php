<?php
namespace Bitrix\ImOpenLines\Integrations\Report\VisualConstructor\Fields\Valuable;

use Bitrix\Report\VisualConstructor\Fields\Valuable\DropDown;

/**
 * Class DropDownImOl
 * @package Bitrix\ImOpenLines\Integrations\Report\VisualConstructor\Fields\Valuable
 */
class DropDownResponsible extends DropDown
{
	protected $linesOperators = [];
	protected $openLines = null;

	/**
	 * Drop down field constructor.
	 *
	 * @param string $key Unique key.
	 */
	public function __construct(string $key)
	{
		parent::__construct($key);
	}

	/**
	 * Load field component with baseselect template.
	 *
	 * @return void
	 */
	public function printContent()
	{
		$this->includeFieldComponent('selectimol');
	}

	/**
	 * @return array
	 */
	public function getLinesOperators()
	{
		return $this->linesOperators;
	}

	/**
	 * @options array $options.
	 * @param $options
	 * @param bool $setDefault
	 * @return void
	 */
	public function setLinesOperators($options, $setDefault = true)
	{
		unset($this->linesOperators);

		foreach ($options as $idLines => $operators)
		{
			if($setDefault === true)
			{
				$this->linesOperators[$idLines] = $this->getDefaultOptions();
			}

			foreach ($operators as $id => $name)
			{
				$this->linesOperators[$idLines][$id] = $name;
			}
		}
	}

	public function getOpenLines()
	{
		return $this->openLines;
	}

	/**
	 * @param $openLines
	 */
	public function setOpenLines($openLines)
	{
		$this->openLines = $openLines;
	}
}