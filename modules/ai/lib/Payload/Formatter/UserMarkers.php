<?php

namespace Bitrix\AI\Payload\Formatter;

class UserMarkers extends Formatter implements IFormatter
{
	/**
	 * @inheritDoc
	 */
	public function format(array $additionalMarkers = []): string
	{
		$userId = $this->engine->getContext()->getUserId();

		foreach ($this->getUserDataById($userId) as $key => $val)
		{
			if (!is_array($val))
			{
				$key = mb_strtolower($key);
				$this->text = str_replace('{user.' . $key . '}', $val, $this->text);
			}
		}

		return $this->text;
	}
}
