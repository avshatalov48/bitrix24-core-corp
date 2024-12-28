<?php

namespace Bitrix\ImOpenLines\V2\Controller;

use Bitrix\Im\V2\Entity\User\UserError;
use Bitrix\ImOpenLines\V2\Recent\Cursor;
use Bitrix\ImOpenLines\V2\Helper\DateTimeHelper;
use Bitrix\ImOpenLines\V2\Status\StatusGroup;
use Bitrix\Main\Engine\AutoWire\ExactParameter;

class Recent extends BaseController
{
	use DateTimeHelper;

	public function getAutoWiredParameters()
	{
		return array_merge([
			new ExactParameter(
				Cursor::class,
				'cursor',
				function ($className, array $cursor) {
					$sortPointer = null;

					if (isset($cursor['sortPointer']))
					{
						$sortPointer = is_numeric($cursor['sortPointer']) ? (int)$cursor['sortPointer'] : $this->createFromText($cursor['sortPointer']);
					}

					//$lastMessageDate = isset($cursor['lastMessageDate']) ? $this->createFromText($cursor['lastMessageDate']) : null;
					$statusGroup = isset($cursor['statusGroup']) ? StatusGroup::tryFrom($cursor['statusGroup']) : null;

					return new Cursor($sortPointer, $statusGroup);
				}
			),
		], parent::getAutoWiredParameters());
	}

	/**
	 * @restMethod imopenlines.v2.Recent.list
	 */
	public function listAction(Cursor $cursor = new Cursor(), int $limit = 50): ?array
	{
		$currentUser = $this->getCurrentUser();
		if ($currentUser === null)
		{
			$this->addError(new UserError(UserError::NOT_FOUND));

			return null;
		}

		$limit = $this->getLimit($limit);
		$recent = \Bitrix\ImOpenLines\V2\Recent\Recent::getOpenLines($currentUser, $cursor, $limit);

		return $this->toRestFormatWithPaginationData([$recent], $limit, $recent->count());
	}
}