<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\Loader;
use Bitrix\Main\Security\Sign\TimeSigner;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Config\Option;

class ReactionProvider
{
	static private ?array $intranetUsers = null;
	private ?PageNavigation $pageNavigation;
	private ?int $defaultLimitPerPage;
	private ?int $defaultPage;

	private const LIKE_TEMPLATE = 'like';
	private const LIKE_GRAPHIC_TEMPLATE = 'like_graphic';

	/**
	 * @param PageNavigation|null $pageNavigation
	 * @param int|null $defaultLimitPerPage
	 * @param int|null $defaultPage
	 */
	public function __construct(
		?PageNavigation $pageNavigation = null,
		?int $defaultLimitPerPage = 20,
		?int $defaultPage = 1,
	)
	{
		$this->pageNavigation = $pageNavigation;
		$this->defaultLimitPerPage = $defaultLimitPerPage;
		$this->defaultPage = $defaultPage;
	}

	public static function getReactionsData(string $entityType, int $entityId): array
	{
		$ratesResult = self::getRatesResult($entityType, $entityId);
		$positiveUserIds = self::getPositiveUserIds($ratesResult['USER_VOTE_LIST'] ?? []);
		$negativeUserIds = self::getNegativeUserIds($ratesResult['USER_VOTE_LIST'] ?? []);

		$reactions = self::getReactions($entityType, $entityId, $ratesResult, $positiveUserIds, $negativeUserIds);

		return $reactions;
	}

	public static function getReactionsTemplate(int $userId): array
	{
		$templateName = self::getUserTemplate($userId);
		$likeMessage = '';

		if ($templateName === self::LIKE_TEMPLATE || $templateName === self::LIKE_GRAPHIC_TEMPLATE)
		{
			$likeMessage = \CRatingsComponentsMain::getRatingLikeMessage(self::LIKE_TEMPLATE);
		}

		return [
			[
				'ID' => 'reactions_template',
				'VALUE' => $templateName,
			],
			[
				'ID' => 'reactions_like_message',
				'VALUE' => $likeMessage,
			],
		];
	}

	public function getUserList(
		string $entityType,
		int $entityId,
		?string $selectedTab,
		?int $page = null,
		?int $limit = null,
	): array
	{
		$paginationParams = $this->getPaginationParams($page, $limit);
		$params = $this->prepareParams($entityType, $entityId, $selectedTab, $paginationParams);
		$result = \CRatings::getRatingVoteList($params);

		if ($result === null)
		{
			return ['items' => []];
		}

		$items = [];
		$userIds = [];

		foreach ($result['items'] as $item)
		{
			$userIds[] = (int) $item['USER_ID'];
			$items[] = [
				'id' => (int) $item['USER_ID'],
				'reactionId' => (string) $item['REACTION'],
			];
		}

		$users = self::getUserDataByIds($userIds);

		return [
			'items' => $items,
			'users' => $users,
		];
	}

	public static function getReactionsVoteSignToken(string $entityType, int $entityId): array
	{
		$voteSignToken = (new TimeSigner())->sign(
			$entityType . '-' . $entityId,
			'+1 day',
			'main.rating.vote'
		);

		$reactionsVoteSignToken = [
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
			'VOTE_SIGN_TOKEN' => $voteSignToken,
		];

		return $reactionsVoteSignToken;
	}

	private static function getRatesResult(string $entityType, int $entityId): array
	{
		return \CRatings::getRatingVoteResult($entityType, $entityId);
	}

	private static function buildReactionMap(array $userReactionList): array
	{
		$reactionUserMap = [];
		foreach ($userReactionList as $userId => $reactionType)
		{
			$reactionUserMap[$reactionType][] = $userId;
		}

		return $reactionUserMap;
	}

	private static function getUserDataByIds(array $userIds): array
	{
		return UserRepository::getByIds($userIds);
	}

	private function prepareParams(
		string $entityType,
		int $entityId,
		?string $selectedTab,
		array $paginationParams,
	): array
	{
		return [
			'LIST_TYPE' => 'plus',
			'INCLUDE_REACTION' => 'Y',
			'LIST_LIMIT' => $paginationParams['limit'],
			'ENTITY_TYPE_ID' => $entityType,
			'ENTITY_ID' => $entityId,
			'REACTION' => $selectedTab,
			'LIST_PAGE' => $paginationParams['page'],
		];
	}

	private function getPaginationParams(?int $page, ?int $limit): array
	{
		$currentPage = $page ?? ($this->pageNavigation ? $this->pageNavigation->getCurrentPage() : $this->defaultPage);
		$currentLimit = $limit ?? ($this->pageNavigation ? $this->pageNavigation->getLimit() : $this->defaultLimitPerPage);

		return [
			'page' => $currentPage,
			'limit' => $currentLimit,
		];
	}

	private static function isCollaber(int $userId): bool
	{
		if (!Loader::includeModule('extranet') || $userId <= 0)
		{
			return false;
		}

		$container = class_exists(ServiceContainer::class) ? ServiceContainer::getInstance() : null;

		return $container?->getCollaberService()?->isCollaberById($userId) ?? false;
	}

	private static function isExtranet(int $userId): bool
	{
		if (!Loader::includeModule('extranet'))
		{
			return false;
		}

		if (!is_array(self::$intranetUsers))
		{
			self::$intranetUsers = \CExtranet::GetIntranetUsers();
			Collection::normalizeArrayValuesByInt(self::$intranetUsers);
		}

		return !in_array($userId, self::$intranetUsers, true);
	}

	private static function getUserTemplate(int $userId): string
	{
		if (self::isCollaber($userId) || self::isExtranet($userId))
		{
			return Option::get('main', 'rating_vote_template', self::LIKE_TEMPLATE, 'ex');
		}

		return Option::get('main', 'rating_vote_template', self::LIKE_TEMPLATE);
	}

	private static function getPositiveUserIds(array $userVoteList): array
	{
		return array_keys(array_filter($userVoteList, static fn($vote) => $vote > 0));
	}

	private static function getNegativeUserIds(array $userVoteList): array
	{
		return array_keys(array_filter($userVoteList, static fn($vote) => $vote < 0));
	}

	private static function getReactions(
		string $entityType,
		int $entityId,
		array $ratesResult,
		array $positiveUserIds,
		array $negativeUserIds
	): array {
		$reactionMap = self::buildReactionMap($ratesResult['USER_REACTION_LIST'] ?? []);
		$reactionList = $ratesResult['REACTIONS_LIST'] ?? [];
		$reactions = [];

		foreach ($reactionList as $key => $count)
		{
			$userIds = $reactionMap[$key] ?? [];

			$reactionData = [
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId,
				'REACTION_ID' => $key,
				'USER_IDS' => $userIds,
				'POSITIVE_USER_IDS' => $key === self::LIKE_TEMPLATE ? $positiveUserIds : [],
				'NEGATIVE_USER_IDS' => $key === self::LIKE_TEMPLATE ? $negativeUserIds : [],
			];

			$reactions[] = $reactionData;
		}

		return $reactions;
	}
}