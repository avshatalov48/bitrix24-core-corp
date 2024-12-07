<?php

namespace Bitrix\Sign\Repository;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Item\B2e\DocumentRequiredField;
use Bitrix\Sign\Item\B2e\DocumentRequiredFieldCollection;
use Bitrix\Sign\Item\B2e\RequiredFieldsCollection;
use Bitrix\Sign\Model\DocumentRequiredFieldTable;
use Bitrix\Sign\Model\EO_DocumentRequiredField;
use Bitrix\Sign\Model\EO_DocumentRequiredField_Collection;
use Bitrix\Sign\Type\Member\Role;

class RequiredFieldRepository
{
	public function add(DocumentRequiredField $item): Result
	{
		$filledMemberEntity = $this->extractModelFromItem($item);
		$saveResult = $filledMemberEntity->save();
		if (!$saveResult->isSuccess())
		{
			return (new Result())->addErrors($saveResult->getErrors());
		}

		$item->id = $saveResult->getId();

		return new Result();
	}

	public function replaceRequiredItemsCollectionForDocumentId(
		RequiredFieldsCollection $requiredFieldsCollection,
		int $documentId,
	): Result
	{
		$this->deleteAllByDocumentId($documentId);

		foreach ($requiredFieldsCollection as $item)
		{
			$result = $this->add(new DocumentRequiredField(
									 type: $item->type,
									 role: $item->role,
									 documentId: $documentId,
								 ));

			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Result();
	}

	public function listByDocumentId(int $documentId): DocumentRequiredFieldCollection
	{
		$models = DocumentRequiredFieldTable::query()
											->where('DOCUMENT_ID', $documentId)
											->fetchCollection()
		;

		return $this->extractItemCollectionFromModelCollection($models);
	}

	public function deleteAllByDocumentId(int $documentId): Result
	{
		try
		{
			DocumentRequiredFieldTable::deleteByFilter([
													 '=DOCUMENT_ID' => $documentId,
												 ]);
		}
		catch (ArgumentException $e)
		{
			return (new Result())->addError(new Error($e->getMessage()));
		}

		return new Result();
	}

	private function extractItemFromModel(EO_DocumentRequiredField $model): DocumentRequiredField
	{
		return new DocumentRequiredField(
			type: $model->getType(),
			role: Role::convertIntToRole($model->getRole()),
			documentId: $model->getDocumentId(),
			id: $model->getId(),
		);
	}

	private function extractModelFromItem(DocumentRequiredField $item): EO_DocumentRequiredField
	{
		$model = DocumentRequiredFieldTable::createObject(true);

		return $model
			->setType($item->type)
			->setRole(Role::convertRoleToInt($item->role))
			->setDocumentId($item->documentId)
		;
	}

	private function extractItemCollectionFromModelCollection(
		EO_DocumentRequiredField_Collection $modelCollection,
	): DocumentRequiredFieldCollection
	{
		$items = array_map(
			fn(EO_DocumentRequiredField $model) => $this->extractItemFromModel($model),
			$modelCollection->getAll(),
		);

		return new DocumentRequiredFieldCollection(...$items);
	}
}