<?php
namespace Bitrix\Timeman\Security;

use Bitrix\Main\Loader;
use Bitrix\Timeman\Model\Security\TaskAccessCodeTable;

class UserOperationChecker implements IUserOperationChecker
{
	/**
	 * @var \CUser
	 */
	private $user;
	private $availableOperations = [];

	public function __construct($user)
	{
		$this->user = $user;
		$userAccessCodes = $this->getUserAccessCodes();
		if (empty($userAccessCodes))
		{
			return;
		}

		$userAccessCodes = array_filter($userAccessCodes, static fn($code) => mb_strpos($code, 'CHAT') !== 0);

		if ($this->user && is_object($this->user) && $this->user->getId() > 0)
		{
			$this->availableOperations = array_column(
				TaskAccessCodeTable::query()
					->addSelect('TASK_OPERATION.OPERATION.NAME', 'OPNAME')
					->whereIn('ACCESS_CODE', $userAccessCodes)
					->where('USER_ACCESS.USER_ID', $this->user->getId())
					->setCacheTtl(3600 * 24)
					->cacheJoins(true)
					->exec()
					->fetchAll(),
				'OPNAME'
			);
		}
	}

	public function canDoOperation($operationName, $options = [])
	{
		return $this->isUserAdmin() || $this->hasAccessToOperation($operationName);
	}

	private function hasAccessToOperation($operationName)
	{
		return in_array($operationName, $this->availableOperations, true);
	}

	private function getUserAccessCodes()
	{
		return is_object($this->user) ? $this->user->getAccessCodes() : [];
	}

	private function isUserAdmin()
	{
		return (is_object($this->user) ? $this->user->isAdmin() : false)
			   || (Loader::includeModule('bitrix24') && \CBitrix24::isPortalAdmin($this->user->getId()));
	}

	public function canDoAnyOperation()
	{
		return $this->isUserAdmin();
	}
}