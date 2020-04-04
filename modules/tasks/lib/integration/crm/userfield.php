<?
/**
 * Class implements all further interactions with "disk" module considering "task" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\CRM;

class UserField extends \Bitrix\Tasks\Integration\CRM
{
	/**
	 * Returns a code of the main system field for this entity
	 * @return string
	 */
	public static function getMainSysUFCode()
	{
		return 'UF_CRM_TASK';
	}

	public static function getSysUFScheme()
	{
		if(!static::isInstalled())
		{
			return array();
		}

		static $scheme;

		if(!$scheme)
		{
			$langs = static::getLangs();
			$names = array(
			);
			foreach($langs as $lang)
			{
				$MESS = array();
				@include($_SERVER['DOCUMENT_ROOT'].'/'.BX_ROOT.'/modules/crm/lang/'.$lang.'/install/index.php');
				$names[$lang] = $MESS['CRM_UF_NAME'];
			}

			$scheme = array(
				static::getMainSysUFCode() => array(
					'FIELD_NAME'    => static::getMainSysUFCode(),
					'USER_TYPE_ID'  => 'crm',
					'XML_ID'        => '',
					'MULTIPLE'      => 'Y',
					'MANDATORY'     => 'N',
					'SHOW_FILTER'   => 'N',
					'SHOW_IN_LIST'  => 'N',
					'EDIT_IN_LIST'  => 'N',
					'IS_SEARCHABLE' => 'N',
					'SETTINGS'      => array(
						'LEAD' => 'Y',
						'CONTACT' => 'Y',
						'COMPANY' => 'Y',
						'DEAL' => 'Y',
					),
					'EDIT_FORM_LABEL' => $names,
					'LIST_COLUMN_LABEL' => $names,
					'LIST_FILTER_LABEL' => $names,
				)
			);
		}

		return $scheme;
	}

	private static function getLangs()
	{
		$result = array();

		$by = 'LID';
		$order = 'ASC';
		$rsLangs = \CLanguage::GetList($by, $order, array("ACTIVE" => "Y"));
		while ($arLang = $rsLangs->Fetch())
		{
			$result[] = $arLang['LID'];
		}

		return $result;
	}
}