<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile;

use Bitrix\Intranet\Dto\User\MiniProfile\Structure\DepartmentDto;
use Bitrix\Intranet\Dto\User\MiniProfile\Structure\HeadDto;
use Bitrix\Intranet\Dto\User\MiniProfile\Structure\TeamDto;

class StructureDto implements \JsonSerializable
{
	public function __construct(
		public string $title,
		/** @type array<int, HeadDto> */
		public array $headDictionary,
		/** @type array<int, DepartmentDto> */
		public array $departmentDictionary,
		/** @type array<int> */
		public array $userDepartmentIds,
		/** @type list<TeamDto> */
		public array $teams = [],
	) {}

	public function jsonSerialize(): array
	{
		return [
			'title' => $this->title,
			'headDictionary' => $this->headDictionary,
			'departmentDictionary' => $this->departmentDictionary,
			'userDepartmentIds' => $this->userDepartmentIds,
			'teams' => $this->teams,
		];
	}
}
