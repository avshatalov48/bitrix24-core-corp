<?php

namespace Bitrix\Sign\Access;

use Bitrix\Main\Access\AccessibleItem;
use Bitrix\Main\Access\BaseAccessController;
use Bitrix\Main\Access\Event\EventDictionary;
use Bitrix\Main\Access\User\AccessibleUser;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Sign\Access\Model\UserModel;
use Bitrix\Sign\Access\Model\UserModelRepository;
use Bitrix\Sign\Access\Permission\PermissionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Rule\Factory\SignRuleFactory;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Factory\Access\AccessibleItemFactory;
use Bitrix\Sign\Helper\IterationHelper;
use Bitrix\Sign\Service\Container;

class AccessController extends BaseAccessController
{
	public const RULE_OR = 'OR';
	public const RULE_AND = 'AND';

	private SignRuleFactory $signRuleFactory;
	/** @var array<int, array<string, array{general: bool, toItems: array<int, bool>}>> */
	protected array $userAccessCache = [];
	private readonly AccessibleItemFactory $accessibleItemFactory;
	private readonly UserModelRepository $userModelRepository;

	public function __construct($userId, ?UserModelRepository $userModelRepository = null)
	{
		parent::__construct($userId);
		$container = Container::instance();

		$this->signRuleFactory = new SignRuleFactory();
		$this->accessibleItemFactory = $container->getAccessibleItemFactory();
		$this->userModelRepository = $userModelRepository ?? $container->getAccessUserModelRepository();
	}

	public function checkByItem(string $action, Contract\Item $item, array $params = null): bool
	{
		$accessibleItem = $this->accessibleItemFactory->createFromItem($item);

		return $this->check($action, $accessibleItem, $params);
	}

	/**
	 * Checking access rights by action
	 *
	 * @param string $action
	 * @param AccessibleItem|null $item
	 * @param null $params
	 *
	 * @return bool
	 * @throws ArgumentException
	 */
	public function check(string $action, AccessibleItem|null $item = null, $params = null): bool
	{
		if (!Loader::includeModule('crm'))
		{
			return false;
		}

		if (isset($this->userAccessCache[$this->user->getUserId()][$action]))
		{
			$accessCache = $this->userAccessCache[$this->user->getUserId()][$action];
			if ($item === null && isset($accessCache['general']))
			{
				return $accessCache['general'];
			}
			elseif ($item !== null && isset($accessCache['toItems'][$item->getId()]))
			{
				return $accessCache['toItems'][$item->getId()];
			}
		}

		$ruleObject = $this->signRuleFactory->createFromAction($action, $this);
		if (!$ruleObject)
		{
			throw new ArgumentException($action);
		}

		$event = $this->sendEvent(EventDictionary::EVENT_ON_BEFORE_CHECK, $action, null, $params);
		$isAccess = $event->isAccess();
		if ($isAccess !== null)
		{
			$this->updateCache($item, $action, $isAccess);

			return $isAccess;
		}

		$params['action'] = $action;
		$isAccess = $ruleObject->execute($item, $params);
		if ($isAccess)
		{
			$this->updateCache($item, $action, $isAccess);

			return true;
		}

		$event = $this->sendEvent(EventDictionary::EVENT_ON_AFTER_CHECK,  $action,null, $params, false);
		$isAccess = $event->isAccess() ?? false;
		$this->updateCache($item, $action, $isAccess);

		return $isAccess;
	}

	private function updateCache(?AccessibleItem $item, string $action, bool $hasAccess): void
	{
		$userId = $this->user->getUserId();
		if ($item === null)
		{
			$this->userAccessCache[$userId][$action]['general'] = $hasAccess;
		}
		else
		{
			$this->userAccessCache[$userId][$action]['toItems'][$item->getId()] = $hasAccess;
		}
	}

	protected function loadItem(int $itemId = null): ?AccessibleItem
	{
		return null;
	}

	protected function loadUser(int $userId): AccessibleUser
	{
		return Container::instance()->getAccessUserModelRepository()
			->getByUserId($userId)
		;
	}

	/**
	 * @param list<SignPermissionDictionary::*, PermissionDictionary::*> $actions
	 */
	public function checkAll(array $actions, AccessibleItem|null $item = null, $params = null): bool
	{
		return IterationHelper::all(
			$actions,
			fn(int|string $action): bool => $this->check($action, $item, $params)
		);
	}

	/**
	 * @param list<SignPermissionDictionary::*, PermissionDictionary::*> $actions
	 */
	public function checkAny(array $actions, AccessibleItem|null $item = null, $params = null): bool
	{
		foreach ($actions as $action)
		{
			if ($this->check($action, $item, $params))
			{
				return true;
			}
		}

		return false;
	}
}
