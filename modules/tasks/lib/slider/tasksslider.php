<?php

namespace Bitrix\Tasks\Slider;

class TasksSlider implements TasksSliderInterface
{
	private string $openUrl;
	private string $closeUrl;
	private string $js;
	private int $width = 0;
	private bool $skipEvents;

	public function __construct(string $openUrl, string $closeUrl, bool $skipEvents = false)
	{
		$this->openUrl = $openUrl;
		$this->closeUrl = $closeUrl;
		$this->skipEvents = $skipEvents;
	}

	public function open(): void
	{
		echo '<script>' . $this->getJs() . '</script>';
	}

	public function getJs(): string
	{
		$this->setJs();
		return $this->js;
	}

	public function setWidth(int $width): void
	{
		$this->width = $width;
	}

	private function setJs(): void
	{
		if ($this->skipEvents)
		{
			$this->js = "
				BX.ready(function() {
					BX.SidePanel.Instance.open(
						'{$this->openUrl}',
						{
							{$this->getWidth()}
						},
					);
				});
		";
		}
		else
		{
			$this->js = "
				BX.ready(function() {
					BX.SidePanel.Instance.open(
						'{$this->openUrl}',
						{
							{$this->getWidth()}
							events: {
								onClose: function() {
									location.href = '{$this->closeUrl}';
								},
							},
						},
					);
				});
		";
		}


	}

	public function getOpenUrl(): string
	{
		return $this->openUrl;
	}

	public function getCloseUrl(): string
	{
		return $this->closeUrl;
	}

	private function getWidth(): string
	{
		if ($this->width > 0)
		{
			return 'width: ' . $this->width . ',';
		}

		return '';
	}
}