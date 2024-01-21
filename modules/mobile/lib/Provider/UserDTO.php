<?php

namespace Bitrix\Mobile\Provider;

use Bitrix\Mobile\Dto\Dto;

final class UserDTO extends Dto
{
	public int $id;
	public ?string $login = null;
	public ?string $name = null;
	public ?string $lastName = null;
	public ?string $secondName = null;
	public ?string $fullName = null;
	public ?string $workPosition = null;
	public ?string $link = null;
	public ?string $avatarSizeOriginal = null;
	public ?string $avatarSize100 = null;
}
