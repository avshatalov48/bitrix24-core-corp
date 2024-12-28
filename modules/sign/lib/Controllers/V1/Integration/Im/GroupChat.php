<?php

namespace Bitrix\Sign\Controllers\V1\Integration\Im;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Attribute;
use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Operation\DocumentChat\CreateGroupChat;
use Bitrix\Sign\Result\Service\Integration\Im\CreateGroupChatResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\Integration\Im\DocumentChatType;

class GroupChat extends \Bitrix\Sign\Engine\Controller
{
	private const FORM_CONTENT_TYPE = 'application/x-www-form-urlencoded';

	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		return
			[
				new Main\Engine\ActionFilter\ContentType([Main\Engine\ActionFilter\ContentType::JSON, self::FORM_CONTENT_TYPE]),
				new Main\Engine\ActionFilter\Authentication(),
				new Main\Engine\ActionFilter\HttpMethod(
					[Main\Engine\ActionFilter\HttpMethod::METHOD_GET, Main\Engine\ActionFilter\HttpMethod::METHOD_POST]
				),
				new Intranet\ActionFilter\IntranetUser()
			];
	}

	#[Attribute\Access\LogicAnd(
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT),
		new Attribute\ActionAccess(ActionDictionary::ACTION_B2E_DOCUMENT_ADD),
	)]
	public function createDocumentChatAction(int $chatType, int $documentId, bool $isEntityId = false): array
	{
		$documentRepository = Container::instance()->getDocumentRepository();
		if ($isEntityId)
		{
			$document = $documentRepository->getByEntityIdAndType(
				$documentId,
				EntityType::SMART_B2E
			);
		}
		else
		{
			$document = $documentRepository->getById($documentId);
		}

		$chatType = DocumentChatType::tryFrom($chatType);
		if ($chatType === null || $document === null)
		{
			$this->addError(new Error(
				Loc::getMessage('SIGN_INTEGRATION_ERROR_WRONG_INPUT')
			));

			return [];
		}

		$createGroupChatResultOperation = new CreateGroupChat(
			$document,
			$chatType,
			CurrentUser::get()->getId()
		);
		$createGroupChatResult = $createGroupChatResultOperation->launch();
		if (!$createGroupChatResult instanceOf CreateGroupChatResult)
		{
			$this->addErrorsFromResult($createGroupChatResult);

			return [];
		}

		return ['chatId' => $createGroupChatResult->chatId];
	}
}