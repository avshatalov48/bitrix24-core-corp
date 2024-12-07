<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Blank\Block;
use Bitrix\Sign\Blank\Form;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Member;
use Bitrix\Sign\Error;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\Role;

class Reference extends Dummy
{
	/**
	 * Is true when self::finalAction() will call on epilog.
	 * @var bool
	 */
	private static $finalActionSet = false;

	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'reference',
			'thirdParty' => true,
			'section' => Section::PARTNER,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_REFERENCE_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_REFERENCE_BLOCK_HINT'),
		];
	}

	/**
	 * Returns block form's manifest.
	 * @return array
	 */
	public static function getDefaultData(): array
	{
		return [
			//'field' =>
		];
	}

	/**
	 * Optionally transforms data before giving out.
	 * @param array $data Data to set.
	 * @param Document|null $document Document instance, if we're within document context.
	 * @return array
	 */
	public static function getData(array $data, ?Document $document): array
	{
		if ($data['data']['field'] ?? null)
		{
			$member = $document ? $document->getMemberByPart($data['part']) : null;

			if ($member)
			{
				$fieldCode = $data['data']['field'];
				$userData = $member->getUserData();

				if (isset($userData[$fieldCode]) && $userData[$fieldCode])
				{
					$data['data'] = $userData[$fieldCode];
				}
				else
				{
					$fieldValue = static::getEntityFieldValue($fieldCode, $document, $member);
					if ($fieldValue)
					{
						$data['data'] = array_merge(
							$data['data'],
							$fieldValue
						);
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Calls when block success added or updated on blank.
	 * @param Block $block Block instance.
	 * @return void
	 */
	public static function wasUpdatedOnBlank(Block $block): void
	{
		$blockData = $block->getData();
		$blankId = $block->getBlank()->getId();
		$memberPart = $block->getPart();
		$presetId = CRM::getOtherSidePresetId($block->getDocument()->getEntityId());

		if ($blockData['data']['field'] ?? null)
		{
			Form::setField($blankId, $memberPart, $blockData['data']['field']);
		}

		if (self::$finalActionSet)
		{
			return;
		}

		self::$finalActionSet = true;

		$block->getBlank()->setCallbackAfterSetBlock('form', function() use ($presetId)
		{
			Form::buildForm($presetId ?: 0);
			Form::clearFields();
		});
	}

	/**
	 * Must return false, if block data is not correct for saving.
	 *
	 * @param Block $block Block instance.
	 * @return Result
	 */
	public static function checkBeforeSave(Block $block): Result
	{
		$data = $block->getData();

		$result = new Result();
		if (!($data['data']['field'] ?? null))
		{
			return $result->addError(
				new \Bitrix\Main\Error(Loc::getMessage('SIGN_CORE_BLOCK_REQUISITES_ERROR_EMPTY'),
					'REQUISITES_ERROR_EMPTY'
				));
		}

		return $result;
	}

	private static function getEntityFieldValue(string $fieldCode, Document $document, Member $member): ?array
	{
		$entityId = static::getEntityIdByFieldCode($fieldCode, $document, $member);
		if ($entityId === null)
		{
			return null;
		}

		$documentId = $document?->getId() ?? $member?->getDocument()?->getId();
		try
		{
			$party = (int)Container::instance()->getMemberRepository()->convertRoleToInt(Role::ASSIGNEE);
			$presetId = $party === $member->getPart()
				? \Bitrix\Sign\Integration\CRM::getMyDefaultPresetId($member->getPart())
				: \Bitrix\Sign\Integration\CRM::getOtherSidePresetId($member->getPart())
			;
		}
		catch (ArgumentException|LoaderException $e)
		{
			$presetId = null;
		}

		return CRM::getEntityFieldValue($entityId, $fieldCode, $documentId, $presetId);
	}

	private static function getEntityIdByFieldCode(
		string $fieldCode,
		Document $document,
		Member $member
	): ?int
	{
		$entityTypeId = (new CRM\FieldCode($fieldCode))->getEntityTypeId();

		if ($entityTypeId === null)
		{
			return null;
		}

		switch (true)
		{
			case $entityTypeId === \Bitrix\Sign\Document\Entity\Smart::getEntityTypeId():
			{
				return $document->getEntityId();
			}
			case $entityTypeId === CRM::getOwnerTypeContact():
			{
				return $member->getContactId();
			}
			case $entityTypeId === CRM::getOwnerTypeCompany():
			{
				return $document->getCompanyId();
			}
		}

		return null;
	}
}
