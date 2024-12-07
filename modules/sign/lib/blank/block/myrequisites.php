<?php
namespace Bitrix\Sign\Blank\Block;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sign\Blank\Block;
use Bitrix\Sign\Blank\Section;
use Bitrix\Sign\Document;
use Bitrix\Sign\Error;
use Bitrix\Sign\Integration\CRM;

class MyRequisites extends Dummy
{
	/**
	 * Returns block's manifest.
	 * @return array
	 */
	public static function getManifest(): array
	{
		return [
			'code' => 'myrequisites',
			'section' => Section::INITIATOR,
			'title' => Loc::getMessage('SIGN_CORE_BLOCK_MYREQUISITES_TITLE'),
			'hint' => Loc::getMessage('SIGN_CORE_BLOCK_MYREQUISITES_HINT'),
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
		$hasFields = false;
		if ($document)
		{
			$requisites = $document->actualizeCompanyRequisites();

			if ($requisites)
			{
				$textLines = [];

				foreach ($requisites as $requisite)
				{
					$textLines[] = $requisite['label'] . ': ' . $requisite['value'];
				}

				$data['data'] = [
					'text' => implode('[br]', $textLines)
				];
			}
			
			if ($requisites)
			{
				$hasFields = true;
			}
			else
			{
				$fieldSet = \Bitrix\Crm\Integration\Sign\Form::getFieldSet(
					\CCrmOwnerType::Company, CRM::getMyDefaultPresetId(
						$document->getEntityId(),
						$document->getCompanyId()
					)
				);
				
				if ($fieldSet && !empty($fieldSet->getFields()))
				{
					$hasFields = true;
				}
			}
		}

		return $data + ['hasFields' => $hasFields,];
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
						'presetId' => CRM::getMyDefaultPresetId(
							$block->getDocument()->getEntityId(),
							$block->getDocument()->getCompanyId()
						),
					]
				));
		}

		return $result;
	}
}
