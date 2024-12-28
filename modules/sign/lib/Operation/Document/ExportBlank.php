<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank;
use Bitrix\Sign\Item\Blank\Export\PortableBlank;
use Bitrix\Sign\Item\Blank\Export\PortableFileCollection;
use Bitrix\Sign\Item\Blank\Export\PortableFile;
use Bitrix\Sign\Item\Blank\Export\PortableBlockCollection;
use Bitrix\Sign\Item\Blank\Export\PortableBlock;
use Bitrix\Sign\Item\Blank\Export\PortableFieldCollection;
use Bitrix\Sign\Item\Blank\Export\PortableUserField;
use Bitrix\Sign\Item\Block;
use Bitrix\Sign\Item\BlockCollection;
use Bitrix\Sign\Item\Fs\FileCollection;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Result\Operation\Document\ExportBlankResult;
use Bitrix\Sign\Result\Operation\Document\ExportFieldResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Providers\LegalInfoProvider;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\FieldType;
use Bitrix\Sign\Result\Result;

class ExportBlank implements Operation
{
	private const MAX_TOTAL_FILESIZE = 200 * 1024;

	private readonly BlankRepository $blankRepository;
	private readonly BlockRepository $blockRepository;
	public function __construct(
		private readonly int $blankId,
		private readonly InitiatedByType $initiatedByType = InitiatedByType::EMPLOYEE,
		?BlankRepository $blankRepository = null,
		?BlockRepository $blockRepository = null,
	)
	{
		$container = Container::instance();
		$this->blankRepository = $blankRepository ?? $container->getBlankRepository();
		$this->blockRepository = $blockRepository ?? $container->getBlockRepository();
	}

	public function launch(): Main\Result|ExportBlankResult
	{
		$blank = $this->blankRepository->getById($this->blankId);
		if (!$blank)
		{
			return Result::createByErrorMessage('Blank not found');
		}

		if (!$blank->fileCollection->count())
		{
			return Result::createByErrorMessage('Blank doesnt contain files');
		}

		$validateFilesResult = $this->isTotalFileSizeExceeded($blank->fileCollection);
		if (!$validateFilesResult->isSuccess())
		{
			return $validateFilesResult;
		}

		$blank->blockCollection = $this->blockRepository->loadBlocks($blank);
		if ($blank->blockCollection === null)
		{
			return Result::createByErrorMessage('Can not load blocks');
		}

		return $this->serialize($blank);
	}

	private function serialize(Blank $blank): Main\Result|ExportBlankResult
	{
		$fields = new PortableFieldCollection();
		foreach ($blank->blockCollection as $block)
		{
			$result = $this->makeField($block);
			if ($result instanceof ExportFieldResult)
			{
				$fields->addIfNoSameName($result->field);
			}
			elseif (!$result->isSuccess())
			{
				return $result;
			}
		}

		$portableBlank = new PortableBlank(
			title: $blank->title,
			scenario: $blank->scenario,
			isForTemplate: $blank->forTemplate,
			initiatedByType: $this->initiatedByType,
			blocks: $this->makeBlocks($blank->blockCollection),
			files: $this->makeFiles($blank->fileCollection),
			fields: $fields,
		);

		return new ExportBlankResult($portableBlank);
	}

	private function makeFiles(FileCollection $files): PortableFileCollection
	{
		$portableFiles = new PortableFileCollection();
		foreach ($files as $file)
		{
			$portableFiles->add(new PortableFile(
				base64Content: base64_encode($file->content->data),
				mimeType: $file->type,
				name: $file->name,
			));
		}

		return $portableFiles;
	}

	private function makeBlocks(BlockCollection $blocks): PortableBlockCollection
	{
		$portableBlocks = new PortableBlockCollection();
		foreach ($blocks as $block)
		{
			$allowedDataKeys = $this->getAllowedBlockDataKeys($block->code);
			$portableBlocks->add(
				new PortableBlock(
					party: $block->party,
					type: $block->type,
					code: $block->code,
					position: $block->position,
					style: $block->style,
					role: $block->role,
					data: array_intersect_key($block->data, array_flip($allowedDataKeys)),
				)
			);
		}

		return $portableBlocks;
	}

	private function isTotalFileSizeExceeded(FileCollection $files): Main\Result
	{
		$totalSize = 0;
		foreach ($files as $file)
		{
			$totalSize += $file->size;
		}

		$allowedSize = $this->getAllowedFileSize();

		if ($totalSize > $allowedSize)
		{
			return Result::createByErrorMessage("Total file size should be less than $allowedSize");
		}

		return new Main\Result();
	}

	private function getAllowedFileSize(): int
	{
		return Main\Config\Option::get('sign', 'TEMPLATE_EXPORT_MAX_TOTAL_FILESIZE', self::MAX_TOTAL_FILESIZE);
	}

	private function makeField(Block $block):  ExportFieldResult|Main\Result
	{
		return match ($block->code)
		{
			BlockCode::B2E_REFERENCE, BlockCode::B2E_MY_REFERENCE => $this->createB2eReferenceFields($block),
			BlockCode::EMPLOYEE_DYNAMIC => $this->createDynamicMemberField($block),
			BlockCode::DATE, BlockCode::TEXT, BlockCode::NUMBER, BlockCode::MY_REQUISITES => new Main\Result(),
			default => $this->createUnsupportedBlockTypeResult($block->code),
		};
	}

	private function createDynamicMemberField(Block $block): ExportFieldResult|Main\Result
	{
		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return Result::createByErrorMessage("User field data field is not string for block $block->id");
		}

		return $this->exportUserField(
			entityId: MemberDynamicFieldInfoProvider::USER_FIELD_ENTITY_ID,
			fieldName: $fieldCode,
		);
	}

	private function exportUserField(string $entityId, string $fieldName): ExportFieldResult|Main\Result
	{
		$arFilter = [
			'ENTITY_ID' => $entityId,
			'FIELD_NAME' => $fieldName,
		];

		$field = \CUserTypeEntity::GetList([], $arFilter)->Fetch();
		if (empty($field))
		{
			return Result::createByErrorMessage("User field with entityId $entityId and name $fieldName not found");
		}

		$id = $field['ID'] ?? null;
		$field = \CUserTypeEntity::GetByID($id); // append lang array
		if (empty($field))
		{
			return Result::createByErrorMessage("User field with ID $id not found");
		}

		$enumItems = $field['USER_TYPE_ID'] === FieldType::ENUMERATION ? $this->getUserFieldEnumItems($id) : [];

		$portableField = new PortableUserField(
			entityId: $entityId,
			id: $id,
			structure: $field,
			items: $enumItems,
		);

		return new ExportFieldResult($portableField);
	}

	private function getUserFieldEnumItems(int $userFieldId): array
	{
		$enumItems = [];
		$dbRes = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => $userFieldId]);
		while ($item = $dbRes->Fetch())
		{
			$enumItems[] = $item;
		}

		return $enumItems;
	}

	private function createUnsupportedBlockTypeResult(string $blockCode): Main\Result
	{
		return Result::createByErrorMessage("Export is not supported for blocks with code $blockCode");
	}

	private function createB2eReferenceFields(Block $block): ExportFieldResult|Main\Result
	{
		$fieldCode = $block->data['field'] ?? '';
		if (!is_string($fieldCode))
		{
			return Result::createByErrorMessage("User field data field is not string for block $block->id");
		}

		$profileProvider = Container::instance()->getServiceProfileProvider();
		if ($profileProvider->isProfileField($fieldCode))
		{
			return $this->exportUserField(
				entityId: LegalInfoProvider::USER_FIELD_ENTITY_ID,
				fieldName: $fieldCode,
			);
		}

		return new Main\Result();
	}

	private function getAllowedBlockDataKeys(string $blockCode): array
	{
		return match ($blockCode)
		{
			BlockCode::TEXT => ['text'],
			default => ['field'],
		};
	}
}