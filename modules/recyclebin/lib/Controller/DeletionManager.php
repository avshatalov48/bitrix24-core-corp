<?php

namespace Bitrix\Recyclebin\Controller;

use Bitrix\Main;
use Bitrix\Recyclebin\Controller\Action\CancelDeletionAction;
use Bitrix\Recyclebin\Controller\Action\CancelRestoreAction;
use Bitrix\Recyclebin\Controller\Action\PrepareDeletionAction;
use Bitrix\Recyclebin\Controller\Action\PrepareRestoreAction;
use Bitrix\Recyclebin\Controller\Action\ProcessDeletionAction;
use Bitrix\Recyclebin\Controller\Action\ProcessRestoreAction;

class DeletionManager extends Main\Engine\Controller
{
	public function configureActions(): array
	{
		return [
			'prepareDeletion' => ['class' => PrepareDeletionAction::class],
			'cancelDeletion' => ['class' => CancelDeletionAction::class],
			'processDeletion' => ['class' => ProcessDeletionAction::class],
			'prepareRestore' => ['class' => PrepareRestoreAction::class],
			'cancelRestore' => ['class' => CancelRestoreAction::class],
			'processRestore' => ['class' => ProcessRestoreAction::class],
		];
	}
}
