<?php
namespace Bitrix\Landing\Hook\Page;

use \Bitrix\Landing\File;
use \Bitrix\Landing\Manager;
use \Bitrix\Landing\Field;
use \Bitrix\Landing\PublicAction;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class MetaOg extends \Bitrix\Landing\Hook\Page
{
	/**
	 * Map of the field.
	 * @return array
	 */
	protected function getMap()
	{
		return array(
			'TITLE' => new Field\Text('TITLE', array(
				'title' => Loc::getMessage('LANDING_HOOK_METAOG_TITLE'),
				'placeholder' => Loc::getMessage('LANDING_HOOK_METAOG_TITLE_PLACEHOLDER'),
				'maxlength' => 140
			)),
			'DESCRIPTION' => new Field\Textarea('DESCRIPTION', array(
				'title' => Loc::getMessage('LANDING_HOOK_METAOG_DESCRIPTION'),
				'placeholder' => Loc::getMessage('LANDING_HOOK_METAOG_DESCRIPTION_PLACEHOLDER'),
				'maxlength' => 300
			)),
			'IMAGE' => new Field\Hidden('IMAGE', array(
				'title' => Loc::getMessage('LANDING_HOOK_METAOG_PICTURE'),
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
								return $path;
							}
						}
					}
					return $value;
				}
			))
		);
	}

	/**
	 * Specific method gor get all landing's images.
	 * @return array
	 */
	public static function getAllImages()
	{
		$images = array();
		$res = \Bitrix\Landing\Internals\HookDataTable::getList(array(
			'select' => array(
				'VALUE', 'ENTITY_ID'
			),
			'filter' => array(
				'=HOOK' => 'METAOG',
				'=CODE' => 'IMAGE',
				'=ENTITY_TYPE' => \Bitrix\Landing\Hook::ENTITY_TYPE_LANDING
			)
		));
		while ($row = $res->fetch())
		{
			$images[$row['ENTITY_ID']] = $row['VALUE'];
		}

		return $images;
	}

	/**
	 * Title of Hook, if you want.
	 * @return string
	 */
	public function getTitle()
	{
		return Loc::getMessage('LANDING_HOOK_METAOG_NAME');
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

		return
				trim($this->fields['TITLE']) != '' ||
				trim($this->fields['DESCRIPTION']) != '' ||
				trim($this->fields['IMAGE']) != '';
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

		$output = '';
		$og = array(
			'title' => \htmlspecialcharsbx(trim($this->fields['TITLE'])),
			'description' => \htmlspecialcharsbx(trim($this->fields['DESCRIPTION'])),
			'image' => trim($this->fields['IMAGE']),
			'type' => 'website'
		);
		foreach ($og as $key => $val)
		{
			if ($key == 'image' && intval($val) > 0)
			{
				$val = \Bitrix\Landing\File::getFileArray(
					$val
				);
			}
			if ($val)
			{
				if ($key == 'image')
				{
					if (is_array($val))
					{
						$val['SRC'] = Manager::getUrlFromFile($val['SRC']);
						$output .=
							'<meta property="og:image" content="' . str_replace(' ', '%20', \htmlspecialcharsbx($val['SRC'])) . '" />' .
							'<meta property="og:image:width" content="' . $val['WIDTH'] . '" />' .
							'<meta property="og:image:height" content="' . $val['HEIGHT'] . '" />';
					}
					else
					{
						$output .= '<meta property="og:image" content="' . str_replace(' ', '%20', \htmlspecialcharsbx($val)) . '" />';
					}
				}
				else
				{
					$output .= '<meta property="og:' . $key . '" content="' . $val . '" />';
				}
			}
		}
		if ($output)
		{
			Manager::setPageView('MetaOG', $output);
		}
	}
}
