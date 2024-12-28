<?php

namespace Bitrix\Sign\Item\Integration\Im\Messages\GroupChat\B2b;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Contract\Chat\GroupChatMessage;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;

abstract class BaseGroupChatMessage implements GroupChatMessage
{
	private const IM_COMPONENT_ID = 'SignMessage';
	protected const SIGNING_COMPLETED_BANNER_ID = 'doneB2bDocumentSigning';
	protected const SIGNING_STARTED_BANNER_ID = 'inviteB2bDocumentSigning';
	private DocumentService $documentService;

	public function __construct(
		private readonly Document $document,
		private readonly ?string $language = null,
	) {
		$this->documentService = Container::instance()->getDocumentService();
	}

	abstract public function getBannerId(): string;

	public function getParams(): array
	{
		return [
			'COMPONENT_ID' => self::IM_COMPONENT_ID,
			'COMPONENT_PARAMS' => [
				'STAGE_ID' => $this->getBannerId(),
				'DOCUMENT' => [
					'ID' => $this->document->id,
					'NAME' => $this->getDocumentName(),
				],
			],
		];
	}

	protected function getLanguage(): ?string
	{
		return $this->language;
	}

	protected function getDocumentName(): string
	{
		return $this->documentService->getComposedTitleByDocument($this->document);
	}

	protected function getTranslatedTextByCode(string $code, array $params = []): string
	{
		return (string)Loc::getMessage($code, $params, $this->getLanguage());
	}
}