<?php

namespace Bitrix\AI\Payload\Formatter;

class Markers extends Formatter implements IFormatter
{
	private string $currentResultKey = 'current_result';

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		// current result's array
		if (!empty($additionalMarkers[$this->currentResultKey]))
		{
			foreach (array_values($additionalMarkers[$this->currentResultKey]) as $key => $val)
			{
				if (!is_array($val))
				{
					$this->text = str_replace('{'.$this->currentResultKey.$key.'}', $val, $this->text);
				}
			}
		}

		// all rest markers
		if (!empty($additionalMarkers))
		{
			foreach ($additionalMarkers as $key => $val)
			{
				if (!is_array($val))
				{
					$this->text = str_replace('{'.$key.'}', $val, $this->text);
				}
			}
		}

		return $this->text;
	}
}
