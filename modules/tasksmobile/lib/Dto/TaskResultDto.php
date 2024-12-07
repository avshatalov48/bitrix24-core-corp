<?php

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Type;

final class TaskResultDto extends Dto
{
	public int $id;
	public int $taskId;
	public int $commentId;
	public int $createdBy;
	public int $createdAt;
	public int $status;
	public string $text;
	/** @var DiskFileDto[] */
	public array $files = [];

	public function getCasts(): array
	{
		return [
			'files' => Type::collection(DiskFileDto::class),
		];
	}

	protected function getDecoders(): array
	{
		return [
			function (array $fields) {
				if (!empty($fields['files']))
				{
					$converter = new Converter(Converter::KEYS | Converter::TO_CAMEL | Converter::LC_FIRST);
					$fields['files'] = $converter->process($fields['files']);
				}

				return $fields;
			},
		];
	}
}