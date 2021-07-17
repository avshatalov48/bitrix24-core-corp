<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Configuration;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Security\ParameterSigner;
use Bitrix\Disk\TypeFile;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\Localization\Loc;

class TrackedObject extends BaseObject
{
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$configureActions['download'] = [
			'-prefilters' => [
				Main\Engine\ActionFilter\Csrf::class,
				Authentication::class,
			],
			'+prefilters' => [
				new Authentication(true),
				new Main\Engine\ActionFilter\CloseSession(),
			]
		];

		return $configureActions;
	}

	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\Document\TrackedObject::class, 'object', function($className, $id) {
			return Disk\Document\TrackedObject::loadById($id);
		});
	}

	public function renameAction(Disk\Document\TrackedObject $object, $newName, $autoCorrect = false)
	{
		return $this->rename($object->getFile(), $newName, $autoCorrect);
	}

	public function generateExternalLinkAction(Disk\Document\TrackedObject $object)
	{
		return $this->generateExternalLink($object->getFile());
	}

	public function disableExternalLinkAction(Disk\Document\TrackedObject $object)
	{
		return $this->disableExternalLink($object->getFile());
	}

	public function getExternalLinkAction(Disk\Document\TrackedObject $object)
	{
		return $this->getExternalLink($object->getFile());
	}

	public function downloadAction(Disk\Document\TrackedObject $object)
	{
		$response = Response\BFile::createByFileId($object->getFile()->getFileId(), $object->getFile()->getName());
		$response->setCacheTime(Disk\Configuration::DEFAULT_CACHE_TIME);

		return $response;
	}
}