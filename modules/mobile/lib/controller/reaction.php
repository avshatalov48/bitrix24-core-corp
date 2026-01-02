<?php

namespace Bitrix\Mobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Error;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\TimeSigner;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Mobile\Provider\ReactionProvider;
use Bitrix\Main\Engine\Controller;

class Reaction extends Controller
{
	public function configureActions(): array
	{
		return [
			'getUserList' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getUserListAction(
		array $params = [],
		PageNavigation $pageNavigation = null,
	): ?array
	{
		$entityId = (int) ($params['ENTITY_ID'] ?? 0);
		$entityType = (string) ($params['ENTITY_TYPE'] ?? '');
		$selectedTab = (isset($params['SELECTED_TAB']) && $params['SELECTED_TAB'] !== 'all') ? $params['SELECTED_TAB'] : null;

		if (!$this->hasAccess($params))
		{
			$this->addError(new Error('Access denied'));

			return null;
		}

		if ($entityId && $entityType)
		{
			$reactionProvider = new ReactionProvider($pageNavigation);

			return $reactionProvider->getUserList($entityType, $entityId, $selectedTab);
		}

		return [];
	}

	private function hasAccess(array $params): bool
	{
		$signer = new TimeSigner();
		$signKey = (string) ($params['VOTE_SIGN_TOKEN'] ?? '');
		$entityId = (int) ($params['ENTITY_ID'] ?? 0);
		$entityType = (string) ($params['ENTITY_TYPE'] ?? '');
		$contentId = "$entityType-$entityId";

		try
		{
			if (
				$signKey === ''
				|| $signer->unsign($signKey, 'main.rating.vote') !== $contentId
			)
			{
				return false;
			}
		}
		catch (BadSignatureException $e)
		{
			return false;
		}

		return true;
	}
}