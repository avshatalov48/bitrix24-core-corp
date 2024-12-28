<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Item;

class UrlGeneratorService
{
	private const AJAX_ENDPOINT = "/bitrix/services/main/ajax.php";
	public const B2E_KANBAN_URL = '/sign/b2e/';
	public const B2E_LIST_URL = '/sign/b2e/list/';

	public function makeMemberAvatarLoadUrl(Item\Member $member): string
	{
		return self::AJAX_ENDPOINT . "?action=sign.api_v1.b2e.member.getAvatar&uid=" . $member->uid;
	}

	public function makeSigningUrl(Item\Member $member): string
	{
		return '/sign/link/member/' . $member->id . '/';
	}

	public function makeMySafeUrl(): string
	{
		return '/sign/b2e/mysafe/';
	}

	public function makeProfileSafeUrl(int $userId): string
	{
		// TODO update for new grid
		return '/company/personal/user/'.$userId.'/sign';
	}

	public function getSigningProcessLink(Item\Document $document): string
	{
		$uri = new Uri('/bitrix/components/bitrix/sign.document.list/slider.php');
		$uri->addParams([
			'site_id' => SITE_ID,
			'type' => 'document',
			'entity_id' => $document->entityId,
		]);
		return $uri->getUri();
	}

	public function makeEditTemplateLink(int $templateId): string
	{
		$uri = new Uri('/sign/b2e/doc/0/');
		$uri->addParams([
			'templateId' => $templateId,
			'stepId' => 'changePartner',
			'noRedirect' => 'Y',
			'mode' => 'template',
		]);

		return $uri->getUri();
	}

	public function makeB2eKanbanCategoryUrl(int $categoryId): string
	{
		$url = new Uri(self::B2E_KANBAN_URL);
		if ($categoryId > 0)
		{
			$url->addParams(['categoryId' => $categoryId]);
		}

		return $url->getUri();
	}

	public function makeB2eListCategoryUrl(int $categoryId): string
	{
		$url = new Uri(self::B2E_LIST_URL);
		if ($categoryId > 0)
		{
			$url->addParams(['categoryId' => $categoryId]);
		}

		return $url->getUri();
	}
}
