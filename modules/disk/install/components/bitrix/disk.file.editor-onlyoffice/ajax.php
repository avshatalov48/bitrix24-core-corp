<?php

use Bitrix\Disk;
use Bitrix\Disk\Document\Online\UserInfoToken;
use Bitrix\Disk\Document\OnlyOffice;
use Bitrix\Disk\Document\OnlyOffice\Editor\ConfigBuilder;
use Bitrix\Disk\Document\OnlyOffice\Filters\DocumentSessionCheck;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\User;
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

Loader::requireModule('disk');

class DiskFileEditorOnlyOfficeController extends Engine\Controller
{
	protected function processBeforeAction(Action $action): bool
	{
		if (!OnlyOfficeHandler::isEnabled())
		{
			$this->addError(new Error('OnlyOffice handler is not configured.'));

			return false;
		}

		return parent::processBeforeAction($action);
	}

	protected function shouldDecodePostData(Action $action): bool
	{
		return false;
	}

	public function configureActions()
	{
		return [
			'showNotFound' => [
				'-prefilters' => [
					ActionFilter\Csrf::class,
				],
			],
			'getSliderContent' => [
				'+prefilters' => [
					(new DocumentSessionCheck())
						->enableHashCheck(function(){
							return Bitrix\Main\Context::getCurrent()->getRequest()->get('documentSessionHash');
						})
						->enableOwnerCheck()
						->enableStrictCheckRight()
					,
				],
				'-prefilters' => [
					ActionFilter\Csrf::class,
				],
			],
			'getUserInfo' => [
				'+prefilters' => [
					new Bitrix\Main\Engine\ActionFilter\CloseSession(),
					new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
					(new DocumentSessionCheck())
						->enableOwnerCheck()
						->enableHashCheck(function(){
							return (new Engine\JsonPayload())->getData()['documentSessionHash'];
						})
					,
				],
				'-prefilters' => [
					ActionFilter\Authentication::class,
				],
			],
			'markAsStillWorkingSession' => [
				'+prefilters' => [
					new Bitrix\Main\Engine\ActionFilter\CloseSession(),
					new ActionFilter\ContentType([ActionFilter\ContentType::JSON]),
					(new DocumentSessionCheck())
						->enableOwnerCheck()
						->enableHashCheck(function(){
							return (new Engine\JsonPayload())->getData()['documentSessionHash'];
						})
					,
				],
			],
		];
	}

	public function markAsStillWorkingSessionAction(OnlyOffice\Models\DocumentSession $documentSession): ?array
	{
		if ($documentSession->isView())
		{
			$this->addError(new Error("Could not update info by view session: {$documentSession->getId()}."));

			return null;
		}
		$documentInfo = $documentSession->getInfo();
		if (!$documentInfo)
		{
			$this->addError(new Error("Could not get info by session: {$documentSession->getId()}."));

			return null;
		}

		$documentInfo->actualizeUpdateTime();

		return [
			'documentInfo' => [
				'updateTime' => $documentInfo->getUpdateTime(),
			],
		];
	}

	public function getUserInfoAction(int $userId, string $infoToken, OnlyOffice\Models\DocumentSession $documentSession): ?array
	{
		$validToken = UserInfoToken::checkTimeLimitedToken($infoToken, $userId, $documentSession->getObject()->getRealObjectId());
		if (!$validToken)
		{
			$this->addError(new Error("Invalid infoToken to get information about user {$userId}."));

			return null;
		}

		$userModel = User::getById($userId);
		if (!$userModel)
		{
			$this->addError(new Error("Could find user by id: {$userId}."));

			return null;
		}

		return [
			'user' => [
				'id' => $userId,
				'name' => $userModel->getFormattedName(),
				'avatar' => $userModel->getAvatarSrc(),
			],
		];
	}

	public function getSliderContentAction(OnlyOffice\Models\DocumentSession $documentSession, int $editorMode = ConfigBuilder::VISUAL_MODE_USUAL): HttpResponse
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
					'EDITOR_MODE' => $editorMode,
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}

	public function showNotFoundAction(): HttpResponse
	{
		$content = $GLOBALS['APPLICATION']->includeComponent(
			'bitrix:ui.sidepanel.wrapper',
			'',
			[
				'RETURN_CONTENT' => true,
				'POPUP_COMPONENT_NAME' => 'bitrix:disk.file.editor-onlyoffice',
				'POPUP_COMPONENT_TEMPLATE_NAME' => '',
				'POPUP_COMPONENT_PARAMS' => [
					'TEMPLATE' => 'not-found',
				],
				'PLAIN_VIEW' => true,
				'IFRAME_MODE' => true,
				'PREVENT_LOADING_WITHOUT_IFRAME' => false,
				'USE_PADDING' => false,
			]
		);

		$response = new HttpResponse();
		$response->setContent($content);

		return $response;
	}
}