<?php

namespace Bitrix\Voximplant\Special;

abstract class Action
{
	/**
	 * Should return true if dialed number should be handled by the action,
	 * @param string $phoneNumber Phone Number.
	 * @return bool
	 */
	abstract public function checkPhoneNumber($phoneNumber);

	/**
	 * Should return response for the scenario.
	 * @param string $callId Id of the call.
	 * @param int $userId Id of the user.
	 * @param string $phoneNumber Phone number.
	 * @return array
	 */
	abstract public function getResponse($callId, $userId, $phoneNumber);

	/**
	 * Returns name of the class.
	 * @return string
	 */
	public static function getClass()
	{
		return get_called_class();
	}
}