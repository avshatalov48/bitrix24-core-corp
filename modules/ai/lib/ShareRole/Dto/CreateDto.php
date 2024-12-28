<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

use Bitrix\AI\ShareRole\Events\Enums\ShareType;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

class CreateDto
{
	public int $userCreatorId;
	public array $accessCodes = [];
	public array $accessCodesData = [];
	public array $usersIdsInAccessCodes = [];
	public ?DateTime $dateCreate;
	public string $roleTitle;
	public string $roleCode;
	public string $roleDescription;
	public array $roleAvatarFile;
	public string $roleText;
	public int $roleId;
	public string $industryCode = 'custom';
	public array $roleAvatarPaths = [];
	public ShareType $shareType;

	public function getHash(): string
	{
		if (empty($this->hash))
		{
			$this->hash = md5(
				$this->roleTitle .
				$this->roleDescription .
				$this->roleText .
				implode(',', $this->accessCodesData) .
				Json::encode($this->roleAvatarPaths)
			);
		}

		return $this->hash;
	}
}
