<?php

namespace Bitrix\Call\Call;


class ConferenceCall extends BitrixCall
{
	/**
	 * Do need to record call.
	 * @return bool
	 */
	public function autoStartRecording(): bool
	{
		return false;
	}
}