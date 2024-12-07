<?php declare(strict_types=1);

namespace Bitrix\AI\Synchronization;

use Bitrix\AI\Model\ImageStylePromptTable;

class ImageStylePromptSync extends BaseSync
{
	/**
	 * @inheritDoc
	 */
	protected function getDataManager(): ImageStylePromptTable
	{
		return $this->dataManager ?? ($this->dataManager = new ImageStylePromptTable());
	}
}