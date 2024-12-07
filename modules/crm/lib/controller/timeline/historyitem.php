<?php

namespace Bitrix\Crm\Controller\Timeline;

use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline\Rest\HistoryItem\ItemList;
use Bitrix\Crm\Timeline\Rest\HistoryItem\ListParams;
use Bitrix\Crm\Timeline\Rest\HistoryItem\ListParamsValidator;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Rest\NonLoggedExceptionDecorator;
use Bitrix\Rest\RestException;
use CRestServer;

class HistoryItem extends Base
{

	public function listAction(
		?array $select = ['*'],
		?array $order = null,
		?array $filter = null,
		PageNavigation $pageNavigation = null
	): ?Page
	{
		$currentUserPermissions = Container::getInstance()->getUserPermissions($this->getCurrentUser()->getId());

		$listParamsValidator = ListParamsValidator::make($currentUserPermissions);

		$params = ListParams\Builder::build($select, $order, $filter, $pageNavigation);

		$errors = $listParamsValidator->validate($params);
		if (!$errors->isEmpty())
		{
			/** @var Error $error */
			$error = current($errors->getValues());
			$message = ($error instanceof Error) ? $error->getMessage() : 'Invalid filter';
			throw new NonLoggedExceptionDecorator(
				new RestException($message, 'ARGUMENTS_VALIDATION_ERROR', CRestServer::STATUS_WRONG_REQUEST)
			);
		}

		return ItemList::getInstance()->execute($params, $currentUserPermissions);
	}
}