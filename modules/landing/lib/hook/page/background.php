<?php
namespace Bitrix\Landing\Hook\Page;

use \Bitrix\Landing\Field;
use \Bitrix\Landing\File;
use \Bitrix\Landing\Manager;
use \Bitrix\Landing\PublicAction;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Background extends \Bitrix\Landing\Hook\Page
{
	/**
	 * Map of the field.
	 * @return array
	 */
	protected function getMap()
	{
		return array(
			'USE' => new Field\Checkbox('USE', array(
				'title' => Loc::getMessage('LANDING_HOOK_BG_USE')
			)),
			'PICTURE' => new Field\Hidden('PICTURE', array(
				'title' => Loc::getMessage('LANDING_HOOK_BG_PICTURE'),
				'fetch_data_modification' => function($value)
				{
					if (PublicAction::restApplication())
					{
						if ($value > 0)
						{
							$path = File::getFilePath($value);
							if ($path)
							{
								$path = Manager::getUrlFromFile($path);
								return $path.'!';
							}
						}
					}
					return $value;
				}
			)),
			'POSITION' => new Field\Select('POSITION', array(
				'title' => Loc::getMessage('LANDING_HOOK_BG_POSITION'),
				'help' => Loc::getMessage('LANDING_HOOK_BG_POSITION_HELP'),
				'options' => array(
					'center' => Loc::getMessage('LANDING_HOOK_BG_POSITION_CENTER'),
					'repeat' => Loc::getMessage('LANDING_HOOK_BG_POSITION_REPEAT')
				)
			)),
			'COLOR' => new Field\Text('COLOR', array(
				'title' => Loc::getMessage('LANDING_HOOK_BG_COLOR')
			))
		);
	}

	/**
	 * Title of Hook, if you want.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('LANDING_HOOK_BG_NAME');
	}

	/**
	 * Description of Hook, if you want.
	 * @return string
	 */
	public function getDescription()
	{
		return Loc::getMessage('LANDING_HOOK_BG_DESCRIPTION');
	}

	/**
	 * Enable or not the hook.
	 * @return boolean
	 */
	public function enabled()
	{
		if ($this->issetCustomExec())
		{
			return true;
		}

		return $this->fields['USE']->getValue() == 'Y';
	}

	/**
	 * Exec hook.
	 * @return void
	 */
	public function exec()
	{
		if ($this->execCustom())
		{
			return;
		}

		$picture = \htmlspecialcharsbx(trim($this->fields['PICTURE']->getValue()));
		$color = \htmlspecialcharsbx(trim($this->fields['COLOR']->getValue()));
		$position = trim($this->fields['POSITION']->getValue());

		if ($picture)
		{
			if ($picture > 0)
			{
				$picture = \htmlspecialcharsbx(
					\Bitrix\Landing\File::getFilePath($picture)
				);
			}
		}

		if ($picture)
		{
			if ($position == 'center')
			{
				\Bitrix\Main\Page\Asset::getInstance()->addString(
					'<style type="text/css">
						body {
							background-image: url("' . $picture . '");
							background-attachment: fixed;
							background-size: cover;
							background-position: center;
							background-repeat: no-repeat;
						}
					</style>'
				);
			}
			else
			{
				\Bitrix\Main\Page\Asset::getInstance()->addString(
					'<style type="text/css">
						body {
							background-image: url("' . $picture . '");
							background-attachment: fixed;
							background-position: center;
							background-repeat: repeat;
						}
					</style>'
				);
			}
		}

		if ($color)
		{
			\Bitrix\Main\Page\Asset::getInstance()->addString(
				'<style type="text/css">
					body {
						background-color: ' . $color . '!important;
					}
				</style>'
			);
		}
	}
}