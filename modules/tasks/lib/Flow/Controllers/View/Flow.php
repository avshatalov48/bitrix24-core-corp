<?php

namespace Bitrix\Tasks\Flow\Controllers\View;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Trait\ControllerTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\UserTrait;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use CFile;
use Throwable;

class Flow extends Controller
{
	use MessageTrait;
	use UserTrait;
	use ControllerTrait;

	protected int $userId;
	protected FlowProvider $provider;
	protected Converter $converter;

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
		$this->provider = new FlowProvider();
		$this->converter = new Converter(Converter::OUTPUT_JSON_FORMAT);
	}

	public function configureActions(): array
	{
		return [
			'get' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getAction(int $flowId): ?array
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowId))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		try
		{
			$flow = $this->provider->getFlow($flowId, ['*']);
			$memberFacade = ServiceLocator::getInstance()->get('tasks.flow.member.facade');
			$teamAccessCodes = $memberFacade->getTeamAccessCodes($flowId);

			$teamIds = (new AccessCodeConverter(...$teamAccessCodes))
				->getUserIds()
			;
		}
		catch (FlowNotFoundException $e)
		{
			return $this->buildErrorResponse($e->getMessage());
		}
		catch (Throwable $e)
		{
			$this->log($e);
			return $this->buildErrorResponse($this->getUnknownError(__LINE__));
		}

		$teamIds = array_slice($teamIds, 0, 8);
		$users = $this->getUsers($flow->getCreatorId(), $flow->getOwnerId(), ...$teamIds);
		$team = array_map(static fn (int $memberId) => $users[$memberId], $teamIds);

		$this->sendAnalytics();

		return [
			'flow' => $flow->toArray(),
			'team' => array_values($this->converter->process($team)),
			'teamCount' => count($teamIds),
			'creator' => $this->converter->process($users[$flow->getCreatorId()]),
			'owner' => $this->converter->process($users[$flow->getOwnerId()]),
			'project' => $this->getProject($flow->getGroupId()),
		];
	}

	protected function getProject(int $groupId): ?array
	{
		$project = GroupRegistry::getInstance()->get($groupId);
		if (!$project)
		{
			return null;
		}

		if (!$project['VISIBLE'] && !$this->isMember($this->userId, $groupId))
		{
			return null;
		}

		return [
			'name' => $project['NAME'],
			'url' => "/workgroups/group/$groupId/tasks/",
			'avatar' => $this->getProjectAvatar($project),
		];
	}

	protected function isMember(int $userId, int $projectId): bool
	{
		return Group::isUserMember($projectId, $userId);
	}

	protected function getProjectAvatar(array $project): string
	{
		if ((int)$project['IMAGE_ID'] > 0)
		{
			return CFile::ResizeImageGet(
				$project['IMAGE_ID'],
				[100, 100],
				BX_RESIZE_IMAGE_EXACT
			)['src'] ?? '';
		}

		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		$avatarTypes = Workgroup::getAvatarTypes();

		return $avatarTypes[$project['AVATAR_TYPE']]['mobileUrl'] ?? '';
	}

	private function sendAnalytics(): void
	{
		$demoSuffix = FlowFeature::isFeatureEnabledByTrial() ? 'Y' : 'N';

		Analytics::getInstance($this->userId)->onFlowsView(
			Analytics::SECTION['tasks'],
			Analytics::ELEMENT['title_click'],
			Analytics::SUB_SECTION['flows'],
			['p1' => 'isDemo_' . $demoSuffix]
		);
	}
}
