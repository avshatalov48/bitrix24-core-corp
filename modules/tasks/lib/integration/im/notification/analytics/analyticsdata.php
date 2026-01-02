<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\IM\Notification\Analytics;

use Bitrix\Tasks\Helper\Analytics;

final class AnalyticsData
{
	private string $section = Analytics::SECTION['chat'];
	private string $element = Analytics::ELEMENT['title_click'];
	private string $event = '';
	private string $type = '';

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function setEvent(string $event): self
	{
		$this->event = $event;

		return $this;
	}

	public function setSection(string $section): self
	{
		$this->section = $section;

		return $this;
	}

	public function setElement(string $element): self
	{
		$this->element = $element;

		return $this;
	}

	public function getData(): array
	{
		$data = [
			'ta_sec' => $this->section,
			'ta_el' => $this->element,
		];

		if (!empty($this->event))
		{
			$data['ta_ev'] = $this->event;
		}

		if (!empty($this->type))
		{
			$data['ta_type'] = $this->type;
		}

		return $data;
	}
}
