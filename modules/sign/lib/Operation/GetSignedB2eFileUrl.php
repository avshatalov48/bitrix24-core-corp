<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Sign\Service;
use Bitrix\Sign\Repository;
use Bitrix\Sign\Contract;
use Bitrix\Main\Web\Uri;

use Bitrix\Main;

class GetSignedB2eFileUrl implements Contract\Operation
{
	public bool $ready = false;

	private const AJAX_PATH = "/bitrix/services/main/ajax.php";
	public const B2eFileSalt = 'b2eFileEntitySalt777';
	protected Repository\FileRepository $fileRepository;
	protected Repository\DocumentRepository $documentRepository;
	protected Repository\MemberRepository $memberRepository;

	public function __construct(
		private int $entityTypeId,
		private int $entityId,
		private int $code,
		private ?Repository\EntityFileRepository $entityFileRepository = null,
	)
	{
		$this->entityFileRepository ??= Service\Container::instance()->getEntityFileRepository();
		$this->fileRepository = Service\Container::instance()->getFileRepository();
		$this->documentRepository = Service\Container::instance()->getDocumentRepository();
		$this->memberRepository = Service\Container::instance()->getMemberRepository();
	}

	public function launch(): Main\Result
	{
		$data = [];
		$result = new Main\Result();

		$entity = $this->entityFileRepository->getOne(
			$this->entityTypeId,
			$this->entityId,
			$this->code
		);

		if (!$entity)
		{
			return $result->addError(new Main\Error('Entity not found'));
		}

		if ($entity->fileId > 0)
		{
			$file = Service\Container::instance()->getFileRepository()->getById($entity->fileId);
			$data['ext'] = $file->type === 'application/zip' ? 'zip' : 'pdf';
			$signer = new Main\Security\Sign\Signer();
			$sign= $signer->sign("$this->entityTypeId$this->entityId", self::B2eFileSalt);

			$uri = new Uri(self::AJAX_PATH);
			$uri->addParams([
				'action' => 'sign.api_v1.Document.B2eSignedFile.download',
				'entityTypeId' => $this->entityTypeId,
				'entityId' => $this->entityId,
				'sign' => $sign,
				'fileCode' => $this->code
			]);

			$data['url'] = $uri->getUri();
			$this->ready = true;
			$result->setData($data);
		}

		return $result;
	}
}
