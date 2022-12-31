<?php

namespace Bitrix\Disk\Document\OnlyOffice\Enum;

final class Status
{
	/**
	 * Document is being edited.
	 * @see \Bitrix\Disk\Controller\OnlyOffice::STATUS_IS_BEING_EDITED
	 * @var int
	 */
	public const IS_BEING_EDITED = 1;
	/**
	 * Document is ready for saving.
	 * @see \Bitrix\Disk\Controller\OnlyOffice::STATUS_IS_READY_FOR_SAVE
	 * @var int
	 */
	public const IS_READY_FOR_SAVE = 2;
	/**
	 * Document saving error has occurred.
	 * @see \Bitrix\Disk\Controller\OnlyOffice::STATUS_ERROR_WHILE_SAVING
	 * @var int
	 */
	public const ERROR_WHILE_SAVING = 3;
	/**
	 * Document is closed with no changes.
	 * @see \Bitrix\Disk\Controller\OnlyOffice::STATUS_CLOSE_WITHOUT_CHANGES
	 * @var int
	 */
	public const CLOSE_WITHOUT_CHANGES = 4;
	/**
	 * Document is being edited, but the current document state is saved.
	 * @see \Bitrix\Disk\Controller\OnlyOffice::STATUS_FORCE_SAVE
	 * @var int
	 */
	public const FORCE_SAVE = 6;
	/**
	 * Error has occurred while force saving the document.
	 * @see \Bitrix\Disk\Controller\OnlyOffice::STATUS_ERROR_WHILE_FORCE_SAVING
	 * @var int
	 */
	public const ERROR_WHILE_FORCE_SAVING = 7;
}