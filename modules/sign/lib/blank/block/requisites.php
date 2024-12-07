<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Blank\Block;
use Bitrix\Sign\Blank\Form;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;
use Bitrix\Sign\Integration\CRM;

class Requisites extends Dummy
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
			'code' => 'requisites',
			'thirdParty' => true,
			'section' => Section::PARTNER,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_REQUISITES_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_REQUISITES_HINT'),
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
		$member = $document ? $document->getMemberByPart($data['part']) : null;
		$hasFields = false;
		if ($member)
		{
			$userData = $member->getUserData();

			if ($userData[CRM\Form::REQUISITES_CODE] ?? null)
			{
				$requisites = $userData[CRM\Form::REQUISITES_CODE];
			}
			else
			{
				$requisites = CRM::getRequisitesContactFieldSetValues($member->getContactId(), $document);
			}

			$textLines = [];

			foreach ($requisites as $requisite)
			{
				$textLines[] = $requisite['label'] . ': ' . $requisite['value'];
			}

			$data['data'] = [
				'text' => implode('[br]', $textLines)
			];
			
			if ($requisites)
			{
				$hasFields = true;
			}
			else
			{
				$fieldSet = \Bitrix\Crm\Integration\Sign\Form::getFieldSet(
					\CCrmOwnerType::Contact, CRM::getOtherSidePresetId($document->getEntityId())
				);
				
				if ($fieldSet && !empty($fieldSet->getFields()))
				{
					$hasFields = true;
				}
			}
		}

		return $data + ['hasFields' => $hasFields];
	}

	/**
	 * Calls when block success added or updated on blank.
	 * @param Block $block Block instance.
	 * @return void
	 */
	public static function wasUpdatedOnBlank(Block $block): void
	{
		$blankId = $block->getBlank()->getId();
		$memberPart = $block->getPart();
		$presetId = CRM::getOtherSidePresetId($block->getDocument()->getEntityId());

		Form::setField($blankId, $memberPart, CRM\Form::REQUISITES_CODE);

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
	 * @param Block $block
	 * @return Result
	 */
	public static function checkBeforeSave(Block $block): Result
	{
		$data = $block->getData();
		$result = new Result();
		if (!$data['hasFields'])
		{
			return $result->addError(
				new \Bitrix\Main\Error(Loc::getMessage('SIGN_CORE_BLOCK_REQUISITES_ERROR_EMPTY'),
					'REQUISITES_ERROR_EMPTY',
					[
						'field' => 'requisites',
						'code' => $block->getCode(),
						'presetId' => CRM::getOtherSidePresetId($block->getDocument()->getEntityId())
					]
				));
		}

		return $result;
	}
}
