<?php

namespace Bitrix\Sign\Blanks\Block\Configuration;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Blanks\Block\Configuration;
use Bitrix\Sign\Connector\MemberConnectorFactory;
use Bitrix\Sign\Contract\RequisiteConnector;
use Bitrix\Sign\Integration\CRM;
use Bitrix\Sign\Integration\CRM\FieldCode;
use Bitrix\Sign\Item;
use Bitrix\Sign\Service\Container;

class Requisites extends Configuration
{
	private MemberConnectorFactory $memberConnectorFactory;

	public function __construct(
		?MemberConnectorFactory $memberConnectorFactory = null,
	)
	{
		$this->memberConnectorFactory = $memberConnectorFactory ?? Container::instance()
			->getMemberConnectorFactory();
	}

	public function validate(Item\Block $block): Main\Result
	{
		$result = parent::validate($block);

		if (!$block->data['hasFields'])
		{
			return $result->addError(
				new Main\Error(
					Loc::getMessage('SIGN_BLANKS_BLOCK_CONFIGURATION_REQUISITES_ERROR_EMPTY'),
					'REQUISITES_ERROR_EMPTY',
					[
						'field' => 'requisites',
						'code' => $block->code,
						'presetId' => $block->data['presetId'],
					]
				)
			);
		}

		return $result;
	}

	function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		$result = [];
		$result['hasFields'] = false;
		if (!$member)
		{
			return $result;
		}

		$result['presetId'] = $member->presetId;
		if (!Main\Loader::includeModule('crm'))
		{
			return $result;
		}

		$memberConnector = $this->memberConnectorFactory->create($member);
		if (!$memberConnector instanceof RequisiteConnector)
		{
			return $result;
		}

		$requisites = $memberConnector->fetchRequisite(new Item\Connector\FetchRequisiteModifier($member->presetId));

		$textLines = [];
		foreach ($requisites as $requisite)
		{
			$fieldDescription = (new FieldCode($requisite->name))->getDescription();
			if ($fieldDescription === null || $fieldDescription['TYPE'] !== 'list')
			{
				$textLines[] = "{$requisite->label}: {$requisite->value}";
				continue;
			}

			$itemValue = $requisite->value;
			foreach ($fieldDescription['ITEMS'] ?? [] as $item)
			{
				if (($item['ID'] ?? null) === $requisite->value)
				{
					$itemValue = $item['VALUE'] ?? '';
					break;
				}
			}

			$textLines[] = "{$requisite->label}: {$itemValue}";
		}
		$result['text'] = implode('[br]', $textLines);

		if (!$requisites->isEmpty())
		{
			$result['hasFields'] = true;

			return $result;
		}
		$fieldSet = \Bitrix\Crm\Integration\Sign\Form::getFieldSet(
			\CCrmOwnerType::Contact,
			$member->presetId,
		);

		if ($fieldSet && !empty($fieldSet->getFields()))
		{
			$result['hasFields'] = true;
		}

		return $result;
	}
}