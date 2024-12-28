<?php

namespace Bitrix\Sign\Controllers\V1\Document;

use Bitrix\Main;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Sign\Operation\Member\MakeB2eSignedFileName;
use Bitrix\Sign\Service;
use Bitrix\Sign\Operation\GetSignedB2eFileUrl;
use Bitrix\Sign\Type\EntityFileCode;

class B2eSignedFile extends \Bitrix\Sign\Engine\Controller
{
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

		$result = (new MakeB2eSignedFileName($member, $entity))->launch();

		return Main\Engine\Response\BFile::createByFileId($entity->fileId, $result->fileName)
			->showInline(false)
		;
	}
}
