<?php

namespace Bitrix\Sign\Blanks\Block\Configuration\B2e;

use Bitrix\Sign\Item;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;
use Bitrix\Main;
use Bitrix\Sign\Blanks\Block\Configuration;

class EmployeeDynamic extends Configuration
{
	private readonly MemberDynamicFieldInfoProvider $memberDynamicFieldInfoProvider;

	public function __construct(
		?MemberDynamicFieldInfoProvider $memberDynamicFieldInfoProvider = null,
	)
	{
		$this->memberDynamicFieldInfoProvider = $memberDynamicFieldInfoProvider
			?? Container::instance()->getMemberDynamicFieldProvider()
		;
	}

	public function validate(Item\Block $block): Main\Result
	{
		$result = parent::validate($block);

		if (($block->data['field'] ?? null) === null)
		{
			return $result->addError(
				new Main\Error(
					Main\Localization\Loc::getMessage('SIGN_BLANKS_BLOCK_CONFIGURATION_EMPLOYEE_DYNAMIC_ERROR_FIELD_NOT_SELECTED'),
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

		if (!$member?->id)
		{
			return $data;
		}

		$data['text'] = $this->memberDynamicFieldInfoProvider->loadFieldData($member->id, $data['field']);

		return $data;
	}
}