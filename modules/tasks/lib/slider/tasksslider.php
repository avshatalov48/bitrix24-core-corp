<?php

namespace Bitrix\Tasks\Slider;

class TasksSlider implements TasksSliderInterface
{
	private string $openUrl;
	private string $closeUrl;
	private string $js;
	private int $width = 0;

	public function __construct(string $openUrl, string $closeUrl)
	{
		$this->openUrl = $openUrl;
		$this->closeUrl = $closeUrl;
	}

	public function open(): void
	{
		echo $this->getJs();
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
		$this->js = "
			<script>
				BX.ready(function() {
					BX.SidePanel.Instance.open(
						'{$this->openUrl}',
						{
							{$this->getWidth()}
							events: {
								onCloseComplete: function() {
									setTimeout(function() {
										window.history.replaceState({}, '', '{$this->closeUrl}');
									}, 500);
								},
							},
						},
					);
				});
			</script>
		";
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