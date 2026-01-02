<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile;

class UserMiniProfileDto implements \JsonSerializable
{
	public function __construct(
		public BaseInfoDto $baseInfo,
		public AccessDto $access,
		public ?DetailInfoDto $detailInfo = null,
		public ?StructureDto $structure = null,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'baseInfo' => $this->baseInfo,
			'detailInfo' => $this->detailInfo,
			'structure' => $this->structure,
			'access' => $this->access,
		];
	}
}
