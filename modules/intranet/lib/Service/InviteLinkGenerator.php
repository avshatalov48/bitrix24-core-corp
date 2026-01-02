<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\CurrentUser;
use Bitrix\Intranet\Infrastructure\LinkCodeGenerator;
use Bitrix\Main\Config\Option;
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
		$inviteToken = self::createInviteToken($payload);

		return new self(AttachJwtTokenToUrlCommand::createDefaultInstance($inviteToken));
	}

	public static function createByPayloadWithUserLang(array $payload, string $userLang = LANGUAGE_ID): ?self
	{
		$inviteToken = self::createInviteToken($payload);

		return new self(AttachJwtTokenToUrlCommand::createInstanceWithUserLang($inviteToken, $userLang));
	}

	public static function createByCollabId(int $collabId, string $userLang = LANGUAGE_ID): ?self
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

		return self::createByPayloadWithUserLang($payload, $userLang);
	}

	public static function createByDepartmentsIds(array $departmentsIds): ?self
	{
		if (empty($departmentsIds))
		{
			return null;
		}

		$linkCodeGenerator = LinkCodeGenerator::createByUserId(CurrentUser::get()?->getId());

		$payload = [
			'departments_ids' => $departmentsIds,
			'inviting_user_id' => CurrentUser::get()?->getId() ?? null,
			'link_code' => $linkCodeGenerator->getOrGenerate()->getCode(),
		];

		return self::createByPayload($payload);
	}

	private static function createInviteToken($payload): string
	{
		return ServiceContainer::getInstance()->inviteTokenService()->create($payload);
	}

	private function create(): Uri
	{
		return $this->jwtTokenUrlService->attach();
	}

	public function getCollabLink(): string
	{
		return $this->getLink();
	}

	public function getShortCollabLink(): string
	{
		return $this->getShortLink();
	}

	public function getLink(): string
	{
		$uri = $this->create();

		return $uri->getUri();
	}

	public function getShortLink(): string
	{
		$uri = $this->create();

		return $uri->getScheme() . '://' . $uri->getHost() . \CBXShortUri::GetShortUri($uri->getUri());
	}
}
