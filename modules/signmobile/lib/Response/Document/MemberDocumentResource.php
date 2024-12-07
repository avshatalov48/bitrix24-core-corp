<?php
namespace Bitrix\SignMobile\Response\Document;

use Bitrix\Main\Type\DateTime;
use Bitrix\SignMobile\Contract\Response\ResourceContract;

final class MemberDocumentResource implements ResourceContract
{
	public function __construct(
		public readonly ?int $memberId = null,
		public readonly ?string $memberRole = null,
		public readonly ?DateTime $dateSigned = null,
		public readonly ?int $documentId = null,
		public readonly ?string $documentTitle = null,
		public readonly ?string $documentExternalId = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'document' => [
				'id' => $this->documentId,
				'title' => $this->documentTitle,
				'externalId' => $this->documentExternalId
			],
			'member' => [
				'id' => $this->memberId,
				'role' => $this->memberRole,
				'signedAt' => $this->dateSigned
			]
		];
	}
}