<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Engine;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\Engine\ActionFilter\ClosureWrapper;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

final class ExternalLink extends Engine\Controller
{
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(Disk\ExternalLink::class, 'externalLink', function($className, $id){
			return Disk\ExternalLink::loadById($id);
		});
	}

	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		$defaultPreFilters = parent::getDefaultPreFilters();

		$defaultPreFilters[] = function(Event $event) {
			/** @var ClosureWrapper $this */
			$currentUser = CurrentUser::get();
			foreach ($this->getAction()->getArguments() as $argument)
			{
				if (!($argument instanceof Disk\ExternalLink))
				{
					continue;
				}

				if ($argument->getCreatedBy() != $currentUser->getId())
				{
					$this->errorCollection[] = new Error(
						Loc::getMessage('Could not operate with external link of stranger')
					);

					return new EventResult(EventResult::ERROR, null, null, $this);
				}
			}
		};

		return $defaultPreFilters;
	}

	public function allowEditDocumentAction(Disk\ExternalLink $externalLink)
	{
		if ($externalLink->availableEdit())
		{
			$storage = $externalLink->getObject()->getStorage();
			$securityContext = $storage->getSecurityContext($this->getCurrentUser());

			if ($externalLink->getObject()->canUpdate($securityContext))
			{
				$externalLink->changeAccessRight(Disk\ExternalLink::ACCESS_RIGHT_EDIT);
			}
		}
	}

	public function disallowEditDocumentAction(Disk\ExternalLink $externalLink)
	{
		if ($externalLink->availableEdit())
		{
			$externalLink->changeAccessRight(Disk\ExternalLink::ACCESS_RIGHT_VIEW);
		}
	}

	public function setPasswordAction(Disk\ExternalLink $externalLink, $newPassword)
	{
		$externalLink->changePassword($newPassword);
	}

	public function setDeathTimeAction(Disk\ExternalLink $externalLink, $deathTime)
	{
		$deathTime = (int)$deathTime;
		$deathTime = DateTime::createFromTimestamp($deathTime);

		$externalLink->changeDeathTime($deathTime);

		return [
			'externalLink' => [
				'id' => $externalLink->getId(),
				'hasDeathTime' => $externalLink->hasDeathTime(),
				'deathTime' => $externalLink->getDeathTime(),
				'deathTimeTimestamp' => $externalLink->hasDeathTime()? $externalLink->getDeathTime()->getTimestamp() : null,
			],
		];
	}

	public function revokeDeathTimeAction(Disk\ExternalLink $externalLink)
	{
		$externalLink->revokeDeathTime();
	}

	public function revokePasswordAction(Disk\ExternalLink $externalLink)
	{
		$externalLink->revokePassword();
	}
}