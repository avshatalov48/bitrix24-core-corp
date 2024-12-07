<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Operation\Result\ConfigureProgressResult;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentStatus;

class GetFillAndStartProgress implements Operation
{
	private const TOTAL_PERCENT = 100;
	private const CONFIGURE_PERCENT = 1;
	private const START_PERCENT = 1;

	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;

	public function __construct(
		private readonly string $uid,
		?DocumentRepository $documentRepository = null,
		?MemberRepository $memberRepository = null,
	)
	{
		$this->documentRepository = $documentRepository ?? Container::instance()->getDocumentRepository();
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function launch(): Main\Result
	{
		$document = $this->documentRepository->getByUid($this->uid);
		if ($document === null)
		{
			return (new Main\Result())
				->addError(new Main\Error("Document with id `$this->uid` doesnt exist"))
			;
		}

		if ($this->isStarted($document))
		{
			return new ConfigureProgressResult(
				completed: true,
				progress: self::TOTAL_PERCENT,
			);
		}

		if ($this->isNotConfiguredYet($document))
		{
			return new ConfigureProgressResult(
				completed: false,
				progress: 0,
			);
		}

		return new ConfigureProgressResult(
			completed: false,
			progress: $this->calculateTotalPercent($document->id),
		);
	}

	private function isNotConfiguredYet(Document $document): bool
	{
		return in_array($document->status, [
			DocumentStatus::UPLOADED,
			DocumentStatus::NEW,
		], true);
	}

	private function isStarted(Document $document): bool
	{
		return in_array($document->status, [
			DocumentStatus::SIGNING,
			DocumentStatus::DONE,
			DocumentStatus::STOPPED,
		], true);
	}

	private function calculateTotalPercent(int $documentId): float
	{
		return $this->getFillResult($documentId) / self::TOTAL_PERCENT *
			(self::TOTAL_PERCENT - self::CONFIGURE_PERCENT - self::START_PERCENT) + self::CONFIGURE_PERCENT;
	}

	private function getFillResult(int $documentId): float
	{
		$notConfigured = $this->memberRepository->countNotConfiguredByDocumentId($documentId);
		if ($notConfigured <= 0)
		{
			return 100;
		}

		$total = $this->memberRepository->countByDocumentId($documentId);

		return ($total - $notConfigured) / $total * 100;
	}
}