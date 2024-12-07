<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;

class Member implements Contract\Item
{
	public int $party;
	public Member\Channel $channel;
	public ?string $key = null;
	public ?string $avatarUrl = null;

	public function __construct(
		int $party,
		Member\Channel $channel,
		public string $uid,
		public ?string $name,
		public ?string $role = null,
		public ?string $sesSigningLogin = null,
	)
	{
		$this->party = $party;
		$this->channel = $channel;
	}
}