<?php

namespace Bitrix\Tasks\Integration\UI\EntitySelector;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Tab;
use Psr\Container\NotFoundExceptionInterface;

class FlowUserProvider extends BaseProvider
{
	private string $entityId = 'flow-user';

	public function __construct(array $options = [])
	{
		parent::__construct();

		$this->options['flowId'] = (int)$options['flowId'];
	}

	public function isAvailable(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		return $GLOBALS['USER']->isAuthorized();
	}

	public function getItems(array $ids): array
	{
		return [];
	}

	public function getSelectedItems(array $ids): array
	{
		return [];
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws FlowNotFoundException
	 */
	public function fillDialog(Dialog $dialog): void
	{
		$flowId = $this->getOption('flowId');

		$teamIds = $this->getTeamIds($flowId);

		foreach (Dialog::getItems($teamIds) as $item)
		{
			$item->addTab($this->entityId);
			$dialog->addItem($item);
		}

		$dialog->addTab(new Tab([
			'id' => $this->entityId,
			'title' => Loc::getMessage('TASKS_UI_ENTITY_SELECTOR_FLOW_USER_TAB'),
		]));
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws FlowNotFoundException
	 * @throws NotFoundExceptionInterface
	 */
	private function getTeamIds(int $flowId): array
	{
		$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
		$teamAccessCodes = $memberFacade->getTeamAccessCodes($flowId);
		$teamIds = (new AccessCodeConverter(...$teamAccessCodes))->getUserIds();

		return array_map(
			static fn($memberId) => ['user', $memberId],
			$teamIds
		);
	}
}