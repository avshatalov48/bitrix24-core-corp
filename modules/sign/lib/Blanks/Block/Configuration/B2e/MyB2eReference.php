<?php

namespace Bitrix\Sign\Blanks\Block\Configuration\B2e;

use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Item;
use Bitrix\Sign\Type;
use Bitrix\Main;
use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Service\Providers\ProfileProvider;
use Bitrix\Sign\Service;

class MyB2eReference extends Configuration
{
	private ProfileProvider $profileProvider;
	public function __construct(bool $skipSecurity = false)
	{
		$this->profileProvider = Service\Container::instance()->getServiceProfileProvider();
		$userId = Main\Engine\CurrentUser::get()->getId();
		if ($userId && !$skipSecurity)
		{
			$this->profileProvider->setAccessController(new \Bitrix\Sign\Access\AccessController($userId));
		}
	}

	public function validate(Item\Block $block): Main\Result
	{
		$result = parent::validate($block);

		if (($block->data['field'] ?? null) === null)
		{
			return $result->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage('SIGN_BLANKS_BLOCK_CONFIGURATION_MY_B2E_REFERENCE_ERROR_FIELD_NOT_SELECTED'),
					'REFERENCE_ERROR_FIELD_NOT_SELECTED',
				)
			);
		}

		return $result;
	}

	public function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		$data = $block->data;
		if (($data['field'] ?? null) === null)
		{
			return $data;
		}

		if ($member === null)
		{
			return $data;
		}

		/* @todo Add profile fields support */
		$fieldCode = $data['field'];
		$fieldValue = static::getEntityFieldValue($fieldCode, $document, $member);
		if ($fieldValue !== null)
		{
			$data = array_merge(
				$data,
				$fieldValue
			);
		}

		return $data;
	}

	private function getRepresentativeMemberByDocument(Item\Document $document): ?Item\Member
	{
		if ($document->representativeId === null)
		{
			return null;
		}

		return new Item\Member(
			documentId: $document->id,
			entityType: Type\Member\EntityType::USER,
			entityId: $document->representativeId,
		);
	}

	private function getEntityFieldValue(string $fieldCode, Item\Document $document, Item\Member $member): ?array
	{
		if ($this->profileProvider->isProfileField($fieldCode))
		{
			return ['text' => $this->profileProvider->loadFieldData($document->representativeId, $fieldCode)->value];
		}

		$entityId = static::getEntityIdByFieldCode($fieldCode, $document, $member);
		if ($entityId === null)
		{
			return null;
		}

		$documentId = $document?->id ?? $member?->documentId;

		return CRM::getEntityFieldValue($entityId, $fieldCode, $documentId, $member->presetId);
	}

	private function getEntityIdByFieldCode(
		string $fieldCode,
		Item\Document $document,
		Item\Member $member
	): ?int
	{
		$entityTypeId = (new CRM\FieldCode($fieldCode))->getEntityTypeId();
		if ($entityTypeId === null)
		{
			return null;
		}

		switch (true)
		{
			case $entityTypeId === \Bitrix\Sign\Document\Entity\SmartB2e::getEntityTypeId():
			{
				return $document->entityId;
			}
			case $entityTypeId === CRM::getOwnerTypeCompany():
			{
				return $member->entityId;
			}
		}

		return null;
	}
}
