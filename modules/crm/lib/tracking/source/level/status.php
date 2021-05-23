<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Source\Level;

use Bitrix\Crm\Tracking;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class Status
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
class Status
{
	const Active = 1;
	const Pause = 0;

	/**
	 * Change status of source child.
	 *
	 * @param int $id ID.
	 * @param int|bool $status Status.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function change($id, $status)
	{
		$result = new Result();
		$child = Tracking\Internals\SourceChildTable::getByPrimary($id, [
			'select' => ['*', 'SOURCE', 'PARENT']
		])->fetchObject();
		if (!$child)
		{
			$result->addError(new Error('Wrong child ID.'));
			return $result;
		}

		$source = $child->getSource();
		if (!$source)
		{
			$result->addError(new Error('Unknown source of child.'));
			return $result;
		}

		$ad = new Tracking\Analytics\Ad([
			'CODE' => $source->getCode(),
			'AD_CLIENT_ID' => $source->getAdClientId(),
			'AD_ACCOUNT_ID' => $source->getAdAccountId(),
		]);

		switch ($child->getLevel())
		{
			case Type::Campaign:
				$result = $ad->manageCampaign($child->getCode(), $status);
				break;

			case Type::Adgroup:
				$result = $ad->manageGroup($child->getCode(), $status);
				break;

			case Type::Keyword:
				$group = $child->getParent();
				if (!$group)
				{
					$result->addError(new Error('Unknown parent of child.'));
					return $result;
				}
				$result = $ad->manageKeyword($group->getCode(), $child->getCode(), $status);
				break;

			default:
				return (new Result())->addError(new Error('Wrong level of child.'));
		}

		if ($result->isSuccess())
		{
			Tracking\Internals\SourceChildTable::update($id, ['IS_ENABLED' => $status ? 1 : 0]);
		}

		return $result;
	}

	public static function actualize()
	{

	}
}