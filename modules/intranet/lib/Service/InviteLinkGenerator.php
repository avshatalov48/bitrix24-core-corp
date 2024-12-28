<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Infrastructure\LinkCodeGenerator;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Intranet\Command\AttachJwtTokenToUrlCommand;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;

class InviteLinkGenerator
{
	public function __construct(
		private AttachJwtTokenToUrlCommand $jwtTokenUrlService,
	)
	{}

	public static function createByPayload(array $payload): ?self
	{
		if (Option::get("socialservices", "new_user_registration_secret", null) === null)
		{
			return null;
		}
		$inviteToken = ServiceContainer::getInstance()->inviteTokenService()->create($payload);

		return new self(AttachJwtTokenToUrlCommand::createDefaultInstance($inviteToken));
	}

	public static function createByCollabId(int $collabId): ?self
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$entity = CollabProvider::getInstance()->getCollab($collabId);
		if (!$entity)
		{
			return null;
		}
		$linkCodeGenerator = LinkCodeGenerator::createByCollabId($collabId);
		$payload = [
			'collab_id' => $collabId,
			'collab_name' => $entity->getName(),
			'inviting_user_id' => CurrentUser::get()?->getId() ?? null,
			'link_code' => $linkCodeGenerator->getOrGenerate()->getCode(),
		];

		return self::createByPayload($payload);
	}

	public static function createByDepartmentsIds(array $departmentsIds): ?self
	{
		if (empty($departmentsIds))
		{
			return null;
		}

		$payload = [
			'departments_ids' => $departmentsIds,
			'inviting_user_id' => CurrentUser::get()?->getId() ?? null,
		];

		return self::createByPayload($payload);
	}

	private function create(): Uri
	{
		return $this->jwtTokenUrlService->attach();
	}

	public function getCollabLink(): string
	{
		$uri = $this->create();

		return $uri->getUri();
	}

	public function getShortCollabLink(): string
	{
		$uri = $this->create();

		return $uri->getScheme().'://'.$uri->getHost().\CBXShortUri::GetShortUri($uri->getUri());
	}
}
