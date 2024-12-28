<?php

namespace Bitrix\Tasks\Flow\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Dto\FlowDto;
use Bitrix\Tasks\Flow\Controllers\Trait\ControllerTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\UserTrait;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Flow\Provider\FlowMemberFacade;
use Throwable;

class Team extends Controller
{
	use MessageTrait;
	use UserTrait;
	use ControllerTrait;

	protected FlowProvider $provider;
	protected Converter $converter;
	protected FlowMemberFacade $memberFacade;
	protected int $userId;

	protected function init(): void
	{
		parent::init();

		$this->provider = new FlowProvider();
		$this->converter = new Converter(Converter::OUTPUT_JSON_FORMAT);
		$this->userId = (int)CurrentUser::get()->getId();
		$this->memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
	}

	/**
	 * @restMethod tasks.flow.team.list
	 */
	public function listAction(FlowDto $flowData, PageNavigation $pageNavigation): ?array
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowData->id))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		try
		{
			$teamAccessCodes = $this->memberFacade->getTeamAccessCodes(
				$flowData->id,
				$pageNavigation->getOffset(),
				$pageNavigation->getLimit()
			);

			$teamIds = (new AccessCodeConverter(...$teamAccessCodes))
				->getUserIds()
			;
		}
		catch (Throwable $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		if (empty($teamIds))
		{
			return [];
		}

		$team = $this->getUsers(...$teamIds);

		return array_values($this->converter->process($team));
	}
}