<?php
namespace Bitrix\Translate\Controller\Index;

use Bitrix\Translate;


class Collector extends Translate\Controller\Controller
{
	const SETTING_ID = 'TRANSLATE_INDEX';

	const ACTION_COLLECT_LANG_PATH = 'collectLangPath';
	const ACTION_COLLECT_PATH = 'collectPath';
	const ACTION_COLLECT_FILE = 'collectFile';
	const ACTION_COLLECT_PHRASE = 'collectPhrase';
	const ACTION_PURGE = 'purge';
	const ACTION_CANCEL = 'cancel';

	/**
	 * Configures actions.
	 *
	 * @return array
	 */
	public function configureActions()
	{
		$configureActions = parent::configureActions();
		$permission = new Translate\Controller\CheckPermission(Translate\Permission::READ);

		$configureActions[self::ACTION_COLLECT_LANG_PATH] = array(
			'class' => Translate\Controller\Index\CollectLangPath::class,
			'+prefilters' => array(
				$permission
			),
		);

		$configureActions[self::ACTION_COLLECT_PATH] = array(
			'class' => Translate\Controller\Index\CollectPathIndex::class,
			'+prefilters' => array(
				$permission
			),
		);

		$configureActions[self::ACTION_COLLECT_FILE] = array(
			'class' => Translate\Controller\Index\CollectFileIndex::class,
			'+prefilters' => array(
				$permission
			),
		);

		$configureActions[self::ACTION_COLLECT_PHRASE] = array(
			'class' => Translate\Controller\Index\CollectPhraseIndex::class,
			'+prefilters' => array(
				$permission
			),
		);

		$configureActions[self::ACTION_PURGE] = array(
			'class' => Translate\Controller\Index\Purge::class,
			'+prefilters' => array(
				$permission
			),
		);

		$configureActions[self::ACTION_CANCEL] = array(
			'+prefilters' => array(
				$permission
			),
		);

		return $configureActions;
	}

	/**
	 * @return array
	 */
	public function cancelAction()
	{
		$settingId = static::SETTING_ID;

		unset($_SESSION[$settingId]);

		return array(
			'STATUS' => Translate\Controller\STATUS_COMPLETED
		);
	}
}
