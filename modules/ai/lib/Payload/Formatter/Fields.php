<?php

namespace Bitrix\AI\Payload\Formatter;

class Fields extends Formatter implements IFormatter
{
	private const KEY = 'fields';
	private const MARKER = '{fields}';

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		if (
			!empty($additionalMarkers[self::KEY])
			&& is_array($additionalMarkers[self::KEY])
			&& str_contains($this->text, self::MARKER)
		)
		{
			$this->text = str_replace(self::MARKER, $this->stringify($additionalMarkers[self::KEY]), $this->text);
		}

		return $this->text;
	}

	private function stringify(array $fields): string
	{
		$result = [];
		foreach ($fields as $key => $val)
		{
			$result[] = '"' . $key . '": "' . $val . '"';
		}

		return implode(PHP_EOL, $result);
	}
}
