<?php

use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Engine;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\ActionFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class DiskFileEditorOnlyOfficeController extends Engine\Controller
{
	protected function shouldDecodePostData(Action $action): bool
	{
		return false;
	}

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

	public function getSliderContentAction(OnlyOffice\Models\DocumentSession $documentSession): HttpResponse
	{
		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'DOCUMENT_SESSION' => $documentSession,
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}
}