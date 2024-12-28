<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Main;
use Bitrix\Main\Web\MimeType;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank\Export\PortableBlank;
use Bitrix\Sign\Item\Blank\Export\PortableField;
use Bitrix\Sign\Item\Blank\Export\PortableFile;
use Bitrix\Sign\Item\Blank\Export\PortableUserField;
use Bitrix\Sign\Item\Block;
use Bitrix\Sign\Item\Fs\File;
use Bitrix\Sign\Item\Fs\FileCollection;
use Bitrix\Sign\Item\Fs\FileContent;
use Bitrix\Sign\Repository\BlockRepository;
use Bitrix\Sign\Repository\FileRepository;
use Bitrix\Sign\Result\Operation\Document\ImportBlankResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\BlankService;
use Bitrix\Sign\Type\BlockCode;
use Bitrix\Sign\Type\BlockType;
use Bitrix\Sign\Type\Member\Role;

class ImportBlank implements Operation
{
	private const ALLOWED_BLOCK_CODES = [
		BlockCode::TEXT,
		BlockCode::DATE,
		BlockCode::NUMBER,
		BlockCode::B2E_REFERENCE,
		BlockCode::B2E_MY_REFERENCE,
		BlockCode::EMPLOYEE_DYNAMIC,
		BlockCode::MY_REQUISITES,
	];

	private readonly FileRepository $fileRepository;
	private readonly BlankService $blankService;
	private readonly BlockRepository $blockRepository;

	public function __construct(
		private readonly PortableBlank $blank,
		?FileRepository $fileRepository = null,
		?BlankService $blankService = null,
		?BlockRepository $blockRepository = null,
	)
	{
		$container = Container::instance();
		$this->fileRepository = $fileRepository ?? $container->getFileRepository();
		$this->blankService = $blankService ?? $container->getSignBlankService();
		$this->blockRepository = $blockRepository ?? $container->getBlockRepository();
	}

	public function launch(): Main\Result|ImportBlankResult
	{
		$result = $this->validate();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->importWithFilesDeleteOnFail();
	}

	private function importWithFilesDeleteOnFail(): ImportBlankResult|Main\Result
	{
		$files = new FileCollection();
		$result = $this->importBlankWithFiles($files);
		if (!$result->isSuccess())
		{
			$this->deleteAllCreatedFiles($files);
		}

		return $result;
	}

	private function importBlankWithFiles(FileCollection $files): ImportBlankResult|Main\Result
	{
		$result = $this->importFiles($files);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->blankService->createFromFileIds(
			files: $files->getIds(),
			scenario: $this->blank->scenario,
			forTemplate: $this->blank->isForTemplate
		);

		if (!$result->isSuccess())
		{
			return $result;
		}

		$blankId = $result->getId();

		$result = $this->importBlocks($blankId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->importFields();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new ImportBlankResult($blankId);
	}

	private function importFiles(FileCollection $files): Main\Result
	{
		foreach ($this->blank->files as $portableFile)
		{
			$file = $this->makeFileByPortableFile($portableFile);
			$result = $this->fileRepository->put($file);
			if (!$result->isSuccess())
			{
				return $result;
			}

			$files->addItem($file);
		}

		return new Main\Result();
	}

	private function importFields(): Main\Result
	{
		foreach ($this->blank->fields as $field)
		{
			if ($field instanceof PortableUserField)
			{
				$result = $this->createUserFieldIfNotExists($field);
				if (!$result->isSuccess())
				{
					return $result;
				}
			}
		}

		return new Main\Result();
	}

	private function createUserFieldIfNotExists(PortableUserField $field): Main\Result
	{
		if ($this->isUserFieldExists($field->entityId, $field->getFieldName()))
		{
			return new Main\Result();
		}

		$id = (new \CUserTypeEntity())->Add($field->structure);
		if (!$id)
		{
			return Result::createByErrorMessage('User field addition error');
		}

		if ($field->items)
		{
			$itemsAdded = (new \CUserFieldEnum())->SetEnumValues($id, $field->items);
			if (!$itemsAdded)
			{
				return Result::createByErrorMessage('User field items addition error');
			}
		}

		return new Main\Result();
	}

	private function makeFileByPortableFile(PortableFile $portableFile): File
	{
		return new File(
			name: $portableFile->name,
			type: $portableFile->mimeType,
			content: new FileContent(base64_decode($portableFile->base64Content)),
			isImage: MimeType::isImage($portableFile->mimeType),
		);
	}

	private function deleteAllCreatedFiles(FileCollection $files): void
	{
		foreach ($files as $file)
		{
			if ($file->id)
			{
				$this->fileRepository->deleteById($file->id);
			}
		}
	}

	private function importBlocks(int $blankId): Main\Result
	{
		foreach ($this->blank->blocks as $portableBlock)
		{
			$block = new Block(
				party: $portableBlock->party,
				type: $portableBlock->type,
				code: $portableBlock->code,
				blankId: $blankId,
				position: $portableBlock->position,
				data: $portableBlock->data,
				style: $portableBlock->style,
				role: $portableBlock->role,
			);
			$result = $this->blockRepository->add($block);
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Main\Result();
	}

	private function validate(): Main\Result
	{
		$result = new Main\Result();
		if ($this->blank->files->isEmpty())
		{
			$result->addError(new Main\Error('Files required'));
		}

		foreach ($this->blank->files as $key => $file)
		{
			if (empty(base64_decode($file->base64Content)))
			{
				$result->addError(new Main\Error("Empty content in file $key"));
			}
		}

		foreach ($this->blank->blocks as $key => $block)
		{
			if ($block->role && !Role::isValid($block->role))
			{
				$result->addError(new Main\Error("Incorrect role in block $key"));
			}

			if (!BlockType::isValid($block->type))
			{
				$result->addError(new Main\Error("Incorrect type in block $key"));
			}

			if (!in_array($block->code, self::ALLOWED_BLOCK_CODES, true))
			{
				$result->addError(new Main\Error("Unsupported code in block $key"));
			}
		}

		foreach ($this->blank->fields as $field)
		{
			$fieldResult = $this->validatePortableField($field);
			$result->addErrors($fieldResult->getErrors());
		}

		return new Main\Result();
	}

	private function validatePortableField(PortableField $field): Main\Result
	{
		if ($field instanceof PortableUserField)
		{
			return $this->validatePortableUserField($field);
		}

		return Result::createByErrorMessage('Unexpected portable field type');
	}

	private function validatePortableUserField(PortableUserField $field): Main\Result
	{
		$userType = new \CUserTypeEntity();
		if (!$userType->CheckFields(0, $field->structure))
		{
			return Result::createByErrorMessage('Incorrect user field structure');
		}

		return new Main\Result();
	}

	private function isUserFieldExists(string $entityId, string $fieldName): bool
	{
		return (bool)\CUserTypeEntity::GetList([], ['ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName])->Fetch();
	}
}