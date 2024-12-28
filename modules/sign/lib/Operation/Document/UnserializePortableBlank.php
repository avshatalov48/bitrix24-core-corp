<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank\Export\PortableBlank;
use Bitrix\Sign\Item\Blank\Export\PortableBlock;
use Bitrix\Sign\Item\Blank\Export\PortableBlockCollection;
use Bitrix\Sign\Item\Blank\Export\PortableFieldCollection;
use Bitrix\Sign\Item\Blank\Export\PortableFile;
use Bitrix\Sign\Item\Blank\Export\PortableFileCollection;
use Bitrix\Sign\Item\Blank\Export\PortableUserField;
use Bitrix\Sign\Item\Block\Position;
use Bitrix\Sign\Item\Block\Style;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlankResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableBlocksResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableFieldsResult;
use Bitrix\Sign\Result\Operation\Document\UnserializePortableFilesResult;
use Bitrix\Sign\Serializer\ItemPropertyJsonSerializer;
use Bitrix\Sign\Type\BlankScenario;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Field\PortableFieldType;
use Bitrix\Sign\Result\Result;

class UnserializePortableBlank implements Operation
{
	public function __construct(
		public readonly string $serialized,
	) {}

	public function launch(): Main\Result|UnserializePortableBlankResult
	{
		try
		{
			$decoded = Main\Web\Json::decode($this->serialized);
		}
		catch (Main\ArgumentException $e)
		{
			return Result::createByErrorMessage("Json decode error {$e->getMessage()}");
		}

		if (!is_array($decoded))
		{
			return Result::createByErrorMessage('Unexpected unserialized structure');
		}

		$title = $decoded['title'] ?? null;
		if (!is_string($title) || empty($title))
		{
			return Result::createByErrorMessage('Title not found');
		}

		$scenario = $decoded['scenario'] ?? null;
		if (!BlankScenario::isValid($scenario))
		{
			return Result::createByErrorMessage('Incorrect scenario');
		}

		$initiatedByType = InitiatedByType::tryFrom((string)($decoded['initiatedByType'] ?? ''));
		if ($initiatedByType === null)
		{
			return Result::createByErrorMessage('Incorrect initiatedByType');
		}

		$portableFilesResult = $this->extractFiles($decoded);
		if (!$portableFilesResult instanceof UnserializePortableFilesResult)
		{
			return $portableFilesResult;
		}

		$portableBlocksResult = $this->extractBlocks($decoded);
		if (!$portableBlocksResult instanceof UnserializePortableBlocksResult)
		{
			return $portableBlocksResult;
		}

		$portableFieldsResult = $this->extractFields($decoded);
		if (!$portableFieldsResult instanceof UnserializePortableFieldsResult)
		{
			return $portableFieldsResult;
		}

		$portableBlank = new PortableBlank(
			title: $title,
			scenario: $scenario,
			isForTemplate: (bool)($decoded['isForTemplate'] ?? false),
			initiatedByType: $initiatedByType,
			blocks: $portableBlocksResult->blocks,
			files: $portableFilesResult->files,
			fields: $portableFieldsResult->fields,
		);

		return new UnserializePortableBlankResult($portableBlank);
	}

	private function extractFiles(array $decoded): UnserializePortableFilesResult|Main\Result
	{
		$files = $decoded['files'] ?? null;
		if (!is_array($files) || empty($files))
		{
			return Result::createByErrorMessage('Files not found');
		}
		$portableFiles = new PortableFileCollection();
		foreach ($files as $file)
		{
			try
			{
				$portableFile = (new ItemPropertyJsonSerializer())->deserialize($file, PortableFile::class);
			}
			catch (\Exception)
			{
				return Result::createByErrorMessage('File has invalid fields');
			}

			$portableFiles->add($portableFile);
		}

		return new UnserializePortableFilesResult($portableFiles);
	}

	private function extractBlocks(array $decoded): UnserializePortableBlocksResult|Main\Result
	{
		$blocks = $decoded['blocks'] ?? null;
		if (!is_array($blocks))
		{
			return Result::createByErrorMessage('Blocks not found');
		}

		$portableBlocks = new PortableBlockCollection();
		foreach ($blocks as $key => $block)
		{
			$party = (int)($block['party'] ?? 0);
			$role = $block['role'] ?? null;
			if ($role !== null && !is_string($role))
			{
				return Result::createByErrorMessage("Incorrect role in block $key");
			}

			$type = $block['type'] ?? null;
			if (empty($type) || !is_string($type))
			{
				return Result::createByErrorMessage("Incorrect type in block $key");
			}

			$code = $block['code'] ?? null;
			if (empty($code) || !is_string($code))
			{
				return Result::createByErrorMessage("Incorrect code in block $key");
			}

			$style = $block['style'] ?? null;
			if ($style !== null && !is_array($style))
			{
				return Result::createByErrorMessage("Incorrect style in block $key");
			}

			if (is_array($style))
			{
				try
				{
					$style = (new ItemPropertyJsonSerializer())->deserialize($style, Style::class);
				}
				catch (\Exception)
				{
					return Result::createByErrorMessage("Incorrect style in block $key");
				}
			}

			$position = $block['position'] ?? null;
			if ($position !== null && !is_array($position))
			{
				return Result::createByErrorMessage("Incorrect position in block $key");
			}

			if (is_array($position))
			{
				try
				{
					$position = (new ItemPropertyJsonSerializer())->deserialize($position, Position::class);
				}
				catch (\Exception)
				{
					return Result::createByErrorMessage("Incorrect position data in block $key");
				}
			}

			$data = $block['data'] ?? null;
			if (!is_array($data))
			{
				return Result::createByErrorMessage("Incorrect data in block $key");
			}

			$portableBlock = new PortableBlock(
				party: $party,
				type: $type,
				code: $code,
				position: $position,
				style: $style,
				role: $role,
				data: $data,
			);

			$portableBlocks->add($portableBlock);
		}

		return new UnserializePortableBlocksResult($portableBlocks);
	}

	private function extractFields(array $decoded): UnserializePortableFieldsResult|Main\Result
	{
		$fields = $decoded['fields'] ?? null;
		if (!is_array($fields))
		{
			return Result::createByErrorMessage('Fields not found');
		}

		$portableFields = new PortableFieldCollection();
		foreach ($fields as $key => $field)
		{
			$type = PortableFieldType::tryFrom((string)($field['type'] ?? ''));
			if ($type === null)
			{
				return Result::createByErrorMessage("Incorrect type in field $key");
			}

			if ($type === PortableFieldType::USER_FIELD)
			{
				try
				{
					$portableField = (new ItemPropertyJsonSerializer())->deserialize($field, PortableUserField::class);
					$portableFields->add($portableField);
				}
				catch (\Exception)
				{
					return Result::createByErrorMessage("Incorrect data in field $key");
				}
			}
		}

		return new UnserializePortableFieldsResult($portableFields);
	}

}