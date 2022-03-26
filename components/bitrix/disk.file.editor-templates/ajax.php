<?php

use Bitrix\Disk;
use Bitrix\Disk\Document;
use Bitrix\Im\Call\Call;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class DiskFileEditorTemplatesController extends Engine\Controller
{
	public function configureActions()
	{
		return [
			'getSliderContent' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
				],
			],
		];
	}

	public function getAutoWiredParameters()
	{
		$autoWiredParameters = parent::getAutoWiredParameters();
		$autoWiredParameters[] = new ExactParameter(
			Call::class,
			'call',
			function ($className, int $callId) {
				$call = Call::loadWithId($callId);

				if (!$call)
				{
					return null;
				}

				$associatedEntity = $call->getAssociatedEntity();
				if (!$associatedEntity->checkAccess($this->getCurrentUser()->getId()))
				{
					return null;
				}

				return $call;
			}
		);

		return $autoWiredParameters;
	}

	protected function processBeforeAction(Action $action)
	{
		if (!Document\OnlyOffice\OnlyOfficeHandler::isEnabled())
		{
			$this->addError(new Error('OnlyOffice handler is not configured.'));

			return false;
		}

		if (!Loader::includeModule('im'))
		{
			$this->addError(new Error("Required module `im` was not found"));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	public function getSliderContentAction(Call $call): HttpResponse
	{
		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-templates',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'MESSENGER' => [
						'CALL' => $call,
					]
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => true,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}
}