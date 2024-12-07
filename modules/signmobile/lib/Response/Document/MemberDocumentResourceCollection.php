<?php
namespace Bitrix\SignMobile\Response\Document;

use Bitrix\Sign\Item\Integration\SignMobile\MemberDocument;
use Bitrix\SignMobile\Response\ResourceCollection;

final class MemberDocumentResourceCollection extends ResourceCollection
{
	public static function fromItemCollection(\Bitrix\Sign\Contract\ItemCollection $collection): self
	{
		$result = array_map(
			fn (MemberDocument $item): MemberDocumentResource => new MemberDocumentResource(
				$item->memberId,
				$item->memberRole,
				$item->dateSigned,
				$item->documentId,
				$item->documentTitle,
				$item->documentExternalId
			),
			$collection->toArray()
		);

		return new self(...$result);
	}
}