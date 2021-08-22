<?php declare(strict_types=1);

namespace Bitrix\ImOpenLines\Services;

use Bitrix\ImOpenLines\Session;

/**
 * Message sessions service.
 *
 * @package Bitrix\ImOpenLines\Services
 */
class SessionManager
{
	/** @var bool */
	private $isEnabled;

	public function __construct()
	{
		$this->isEnabled = \Bitrix\Main\Loader::includeModule('imopenlines');
	}

	/**
	 * Creates new instance of OL session.
	 *
	 * @param array $config Session config parameters.
	 *
	 * @return Session|null
	 */
	public function create(array $config = []): ?Session
	{
		if ($this->isEnabled)
		{
			return new Session($config);
		}

		return null;
	}

	/**
	 * The vote of the user.
	 *
	 * @param int $sessionId
	 * @param string $action
	 * @param int|null $userId
	 * @return bool
	 */
	public function voteAsUser(int $sessionId, string $action, ?int $userId = null): bool
	{
		if ($this->isEnabled)
		{
			return Session::voteAsUser($sessionId, $action, $userId);
		}

		return false;
	}
}
