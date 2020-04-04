<?php
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Copy\CopyImplementer;

abstract class Base extends CopyImplementer
{
	/**
	 * Updates entity.
	 *
	 * @param int $entityId Entity id.
	 * @param array $fields List entity fields.
	 * @return bool
	 */
	abstract public function update($entityId, array $fields);

	/**
	 * Updates identifiers who's added to text.
	 *
	 * @param int $id Id of the entity whose text will be updated.
	 * @param array $attachedIds
	 * @param callable $auxiliaryCallback
	 */
	public function updateAttachedIdsInText(int $id, array $attachedIds, callable $auxiliaryCallback): void
	{
		list($field, $text) = $this->getText($id);

		$detailText = call_user_func_array($auxiliaryCallback, [
			$text,
			$this->ufEntityObject,
			$id,
			$this->ufDiskFileField,
			$attachedIds
		]);

		$this->update($id, [$field => $detailText]);
	}
}