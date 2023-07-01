<?php

namespace Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto;

final class ContentBlockDto extends \Bitrix\Crm\Dto\Dto
{
	public const TYPE_TEXT = 'text';
	public const TYPE_LARGE_TEXT = 'largeText';
	public const TYPE_LINK = 'link';
	public const TYPE_DEADLINE = 'deadline';
	public const TYPE_WITH_TITLE = 'withTitle';
	public const TYPE_LINE_OF_BLOCKS = 'lineOfBlocks';

	public ?string $type = null;
	public ?Dto\ContentBlock\BaseContentBlockDto $properties = null;

	public function getCastByPropertyName(string $propertyName): ?\Bitrix\Crm\Dto\Caster
	{
		if ($propertyName !== 'properties')
		{
			return null;
		}

		$typeToClassnameMap = [
			self::TYPE_TEXT => Dto\ContentBlock\TextDto::class,
			self::TYPE_LARGE_TEXT => Dto\ContentBlock\LargeTextDto::class,
			self::TYPE_LINK => Dto\ContentBlock\LinkDto::class,
			self::TYPE_DEADLINE => Dto\ContentBlock\DeadlineDto::class,
			self::TYPE_WITH_TITLE => Dto\ContentBlock\WithTitleDto::class,
			self::TYPE_LINE_OF_BLOCKS => Dto\ContentBlock\LineOfBlocksDto::class,
		];

		return $typeToClassnameMap[$this->type]
			? new \Bitrix\Crm\Dto\Caster\ObjectCaster($typeToClassnameMap[$this->type])
			: new \Bitrix\Crm\Dto\Caster\InvalidValueCaster()
		;
	}

	protected function getValidators(array $fields): array
	{
		return [
			new \Bitrix\Crm\Dto\Validator\EnumField($this, 'type', [
				self::TYPE_TEXT,
				self::TYPE_LARGE_TEXT,
				self::TYPE_LINK,
				self::TYPE_DEADLINE,
				self::TYPE_WITH_TITLE,
				self::TYPE_LINE_OF_BLOCKS,
			])
		];
	}
}
