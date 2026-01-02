<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item;

class UrlGeneratorService
{
	private const AJAX_ENDPOINT = "/bitrix/services/main/ajax.php";
	private const B2E_WIZARD_URL = '/sign/b2e/doc/0/';
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

	public function makeSignersListsUrl(): string
	{
		return '/sign/b2e/signers/';
	}

	public function makeEditSignersListUrl(int $listId): string
	{
		$uri = new Uri('/sign/b2e/signers/'.$listId.'/');
		$uri->addParams([
			'noRedirect' => 'Y',
		]);

		return $uri->getUri();
	}

	public function makeAddSignerUrl(int $listId): string
	{
		$uri = new Uri('/sign/b2e/signers/'.$listId.'/signer/0/');
		return $uri->getUri();
	}

	public function makeSignersListRejectedUrl(): ?string
	{
		$rejectedListId = Storage::instance()->getSignersListRejectedId();
		if ($rejectedListId)
		{
			return '/sign/b2e/signers/'.$rejectedListId.'/';
		}

		return null;
	}

	public function makeProfileUrl(int $userId): string
	{
		return '/company/personal/user/'.$userId.'/';
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

	public function makeCreateTemplateLink(bool $isFolderIdProvided, int $folderId = 0): string
	{
		$uri = new Uri(self::B2E_WIZARD_URL);
		$uri->addParams([
			'folderId' => $folderId,
			'isOpenedAsFolder' => $isFolderIdProvided ? 'Y' : 'N',
			'mode' => 'template',
		]);

		return $uri->getUri();
	}

	public function makeEditTemplateLink(int $templateId,  int $folderId = 0): string
	{
		$uri = new Uri(self::B2E_WIZARD_URL);
		$uri->addParams([
			'templateId' => $templateId,
			'folderId' => $folderId,
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
