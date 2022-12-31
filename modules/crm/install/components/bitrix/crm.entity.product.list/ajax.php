<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Engine\Response;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;

class ProductListController extends \Bitrix\Main\Engine\Controller
{
	/**
	 * @return \Bitrix\Main\Engine\Response\HtmlContent
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getProductGridAction(): Response\HtmlContent
	{
		if (!Loader::includeModule('crm'))
		{
			return $this->sendErrorResponse('Could not load "crm" module.');
		}

		$componentParams = $this->getUnsignedParameters();

		$template = $this->request->get('template') ?: '.default';

		return new Response\Component(
			'bitrix:crm.entity.product.list',
			$template,
			$componentParams,
			[
				'HIDE_ICONS' => 'Y',
				'ACTIVE_COMPONENT' => 'Y'
			]
		);
	}

	private function sendErrorResponse(string $message)
	{
		$errorCollection = new ErrorCollection();
		$errorCollection->setError(new Error($message));

		return Response\AjaxJson::createError($errorCollection);
	}
}
