<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

final class IntranetConfigsComponent extends CBitrixComponent
{

	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public static function processOldAccessCodes($rightsList)
	{
		static $rootDepartmentId = null;

		if (!is_array($rightsList))
		{
			return [];
		}

		if ($rootDepartmentId === null)
		{
			$rootDepartmentId = COption::GetOptionString("main", "wizard_departament", false, SITE_DIR, true);
			if (
				empty($rootDepartmentId)
				&& \Bitrix\Main\Loader::includeModule('iblock')
			)
			{
				$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', false);
				if ($iblockId > 0)
				{
					$res = \CIBlockSection::getList(
						array(
							'LEFT_MARGIN' => 'ASC'
						),
						array(
							'IBLOCK_ID' => $iblockId,
							'ACTIVE' => 'Y'
						),
						false,
						array('ID')
					);
					if (
						!empty($res)
						&& ($rootSection = $res->fetch())
					)
					{
						$rootDepartmentId = $rootSection['ID'];
					}
				}
			}
		}

		foreach($rightsList as $key => $value)
		{
			if ($value == 'AU')
			{
				unset($rightsList[$key]);
				$rightsList[] = 'UA';
			}
			elseif (preg_match('/^IU(\d+)$/i', $value, $matches))
			{
				unset($rightsList[$key]);
				$rightsList[] = 'U'.$matches[1];
			}
			elseif (
				!empty($rootDepartmentId)
				&& ($value == 'DR'.$rootDepartmentId)
			)
			{
				unset($rightsList[$key]);
				$rightsList[] = 'UA';
			}
		}

		return array_unique($rightsList);
	}


	public function executeComponent()
	{
		return $this->__includeComponent();
	}
}