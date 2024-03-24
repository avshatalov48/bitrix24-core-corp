<?php

namespace Bitrix\Tasks\Integration\CRM\Fields;

use Bitrix\Main\Text\HtmlFilter;

class Emulator
{
	private EmulationData $data;

	public function __construct(EmulationData $data)
	{
		$this->data = $data;
	}

	public function getHtml(): string
	{
		$class = $this->data->getClass();
		$type = $this->data->getType();
		$name = $this->data->getName();
		$value = HtmlFilter::encode($this->data->getValue(), ENT_QUOTES);

		if (!empty($value))
		{
			return "<input class=\"{$class}\" type=\"{$type}\" name=\"{$name}\" value=\"{$value}\">";
		}

		return '';
	}

	public function render(): void
	{
		echo $this->getHtml();
	}
}