<?php

namespace Bitrix\Sign\Blanks\Block\Configuration\B2e;

use Bitrix\Sign\Item;
use Bitrix\Sign\Service\Providers\ProfileProvider;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;
use Bitrix\Main;
use Bitrix\Sign\Blanks\Block\Configuration;

class B2eReference extends Configuration
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
					Main\Localization\Loc::getMessage('SIGN_BLANKS_BLOCK_CONFIGURATION_B2E_REFERENCE_ERROR_FIELD_NOT_SELECTED'),
					'REFERENCE_ERROR_FIELD_NOT_SELECTED',
				)
			);
		}

		return $result;
	}

	function loadData(Item\Block $block, Item\Document $document, ?Item\Member $member = null): array
	{
		$data = $block->data;
		if (($data['field'] ?? null) === null)
		{
			return $data;
		}

		if (
			$member === null
			|| $member->entityType !== Type\Member\EntityType::USER
			|| $member->entityId === null
		)
		{
			return $data;
		}

		$data['text'] = $this->loadDataFromProfileProvider($member, $data['field']);

		return $data;
	}

	public function loadDataFromProfileProvider(Item\Member $member, string $fieldName): ?string
	{
		if (!$this->profileProvider->isProfileField($fieldName))
		{
			return null;
		}

		return $this->profileProvider->loadFieldData($member->entityId, $fieldName)->value;
	}
}
