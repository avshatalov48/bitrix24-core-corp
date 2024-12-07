<?php

namespace Bitrix\Tasks\CheckList\Node;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Internals\Attribute\Min;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Dto\AbstractBaseDto;

/**
 * @method self setNodeId(string $nodeId)
 */
class Node extends AbstractBaseDto
{
	public ?int $id;

	#[Required]
	public string $nodeId;

	#[Required]
	public string $title;

	public array $members = [];

	public array $attachments = [];
	public bool $isComplete = false;
	public bool $isImportant = false;

	#[Min(0)]
	public int $parentId = 0;
	public string $parentNodeId = '0';
	public int $sortIndex = 0;

	protected static function modifyKeyFromArray(string $key): string
	{
		return (new Converter(Converter::OUTPUT_JSON_FORMAT))->process($key);
	}

	protected static function modifyKeyToArray(string $key): string
	{
		return (new Converter(Converter::TO_SNAKE | Converter::TO_UPPER))->process($key);

	}

	public function getAuditors(): array
	{
		return $this->getByRole(RoleDictionary::ROLE_AUDITOR);
	}

	public function getAccomplices(): array
	{
		return $this->getByRole(RoleDictionary::ROLE_ACCOMPLICE);
	}

	protected function getByRole(string $role): array
	{
		$members = array_filter(
			$this->members,
			static fn (array $member): bool => $member['TYPE'] === $role
		);

		return array_unique(array_keys($members));
	}
}