<?php

namespace Bitrix\AI\Payload\Formatter;

class Clean extends Formatter implements IFormatter
{
	private const MARKERS_TO_REMOVE = [
		'{user_message}',
		'{original_message}',
		'{author_message}',
		'{context_messages}',
		'{current_result0}',
		'{current_result1}',
		'{current_result2}',
		'{fields}',
	];

	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		$this->text = str_replace(self::MARKERS_TO_REMOVE, '', $this->text);
		$this->text = trim($this->text);
		return $this->text;
	}
}
