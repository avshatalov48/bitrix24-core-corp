<?php

namespace Bitrix\Recyclebin\Internals;

use Bitrix\Main\Session\SessionInterface;

class BatchActionManager
{
	public const DELETION_PROGRESS_SESSION_NAME = 'RECYCLEBIN_DELETION_PROGRESS';
	public const DELETION_DATA_SESSION_NAME = 'RECYCLEBIN_DELETION_DATA';
	public const RESTORE_PROGRESS_SESSION_NAME = 'RECYCLEBIN_RESTORE_PROGRESS';
	public const RESTORE_DATA_SESSION_NAME = 'RECYCLEBIN_RESTORE_DATA';

	public function deleteFromSession(string $name, string $hash): self
	{
		$session = $this->getSession();
		$data = $session->get($name);

		if (is_array($data))
		{
			unset($data[$hash]);
			$session->set($name, $data);
		}

		return $this;
	}

	public function addToSession(string $name, string $hash, array $data): self
	{
		$session = $this->getSession();
		$sessionData = $session->get($name);
		if (!is_array($sessionData))
		{
			$sessionData = [];
		}

		$sessionData[$hash] = $data;
		$session->set($name, $sessionData);

		return $this;
	}

	public function getFromSession(string $name, string $hash): ?array
	{
		$session = $this->getSession();
		if (!$session->has($name))
		{
			return null;
		}

		$data = $session->get($name);
		if (!is_array($data))
		{
			return null;
		}

		return ($data[$hash] ?? null);
	}

	private function getSession(): SessionInterface
	{
		return \Bitrix\Main\Application::getInstance()->getSession();
	}
}
