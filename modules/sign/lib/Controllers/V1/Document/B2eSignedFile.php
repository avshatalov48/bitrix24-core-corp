<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Sign\Service;
use Bitrix\Sign\Operation\GetSignedB2eFileUrl;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Util\Request\File;

class B2eSignedFile extends \Bitrix\Sign\Engine\Controller
{
	const FILE_NAME_SEPARATOR = '. ';
	const FILE_NAME_DATE_FORMAT = 'Y-m-d';

	public function configureActions(): array
	{
		$actionsConfiguration = parent::configureActions();
		$actionsConfiguration['download']['-prefilters'] = [
			Main\Engine\ActionFilter\ContentType::class
		];

		return $actionsConfiguration;
	}

	/**
	 * @throws ArgumentTypeException
	 * @throws ObjectNotFoundException
	 * @throws BadSignatureException
	 */
	public function downloadAction(int $entityTypeId, int $entityId, string $sign, int $fileCode): Main\Engine\Response\BFile | array
	{
		if (!in_array($fileCode, EntityFileCode::getAll(), true))
		{
			return [];
		}

		$signer = new Signer();

		if ($signer->unsign($sign, GetSignedB2eFileUrl::B2eFileSalt) !== "$entityTypeId$entityId")
		{
			$this->addError(new Main\Error(
				'Entity not found',
				'SIGN_DOCUMENT_NOT_FOUND',
			));

			return [];
		}

		if ($entityTypeId === \Bitrix\Sign\Type\EntityType::MEMBER)
		{
			$member = Service\Container::instance()->getMemberRepository()->getById($entityId);
		}
		else
		{
			$this->addError(new Main\Error(
				'Wrong entity type',
				'SIGN_WRONG_ENTITY_TYPE',
			));

			return [];
		}

		$entity = Service\Container::instance()->getEntityFileRepository()->getOne(
			$entityTypeId,
			$entityId,
			$fileCode,
		);

		if (!$entity)
		{
			$this->addError(new Main\Error(
				'Entity not found',
				'SIGN_DOCUMENT_NOT_FOUND',
			));

			return [];
		}

		if ($entity->fileId <= 0)
		{
			$this->addError(new Main\Error(
				'Entity has no result file',
				'SIGN_ENTITY_NO_RESULT_FILE',
			));

			return [];
		}

		$title = $this->getDocumentName($member);
		$dateSigned = $this->getDateSigned($member);
		$memberName = $this->getMemberName($member);
		$ext = $this->getFileExtension($entity);
		$separator = self::FILE_NAME_SEPARATOR;

		$name = "{$title}{$separator}{$dateSigned}{$separator}{$memberName}.{$ext}";

		return Main\Engine\Response\BFile::createByFileId($entity->fileId, $name)
			->showInline(false)
			;
	}

	private function getFileExtension(\Bitrix\Sign\Item\EntityFile $entity): string
	{
		$file = \CFile::GetFileArray($entity->fileId);

		return \Bitrix\Main\IO\Path::getExtension($file['ORIGINAL_NAME']);
	}

	private function getMemberName(?\Bitrix\Sign\Item\Member $member): string
	{
		if ($member === null)
		{
			return '';
		}

		$memberService = Service\Container::instance()->getMemberService();
		$memberRepresentedName = $memberService->getMemberRepresentedName($member);

		return File::sanitizeFilename($memberRepresentedName) ?? '';
	}

	private function getDateSigned(?\Bitrix\Sign\Item\Member $member): string
	{
		$member->dateSigned->setDefaultTimeZone();

		return $member->dateSigned->format(self::FILE_NAME_DATE_FORMAT);
	}

	private function getDocumentName(?\Bitrix\Sign\Item\Member $member): ?string
	{
		$document = Service\Container::instance()->getDocumentRepository()->getById($member->documentId);
		$title = File::sanitizeFilename($document?->title ?? '');
		if ($title === null)
		{
			$title = Main\Localization\Loc::getMessage('SIGN_DEFAULT_FILE_NAME') . '_' . File::getRandomName(5);
		}

		return $title;
	}
}
