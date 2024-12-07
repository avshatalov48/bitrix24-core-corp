<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Item;

class UrlGeneratorService
{
	private const AJAX_ENDPOINT = "/bitrix/services/main/ajax.php";

	public function makeMemberAvatarLoadUrl(Item\Member $member): string
	{
		return self::AJAX_ENDPOINT . "?action=sign.api_v1.b2e.member.getAvatar&uid=" . $member->uid;
	}

	public function makeSigningUrl(Item\Member $member): string
	{
		return '/sign/link/member/' . $member->id . '/';
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
}
