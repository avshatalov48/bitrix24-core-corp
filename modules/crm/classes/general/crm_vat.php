<?
use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Catalog;

class CCrmVat
{
	private static $VATS = null;

	public static function GetAll()
	{
		if (!Loader::includeModule('catalog'))
			return false;

		$VATS = isset(self::$VATS) ? self::$VATS : null;

		if (!$VATS)
		{
			$VATS = [];
			$iterator = Catalog\VatTable::getList([
				'select' => ['ID', 'ACTIVE', 'SORT', 'NAME', 'RATE'],
				'order' => ['SORT' => 'ASC']
			]);
			while ($row = $iterator->fetch())
			{
				$id = (int)$row['ID'];
				$row['C_SORT'] = $row['SORT']; // compatibility - legacy code
				$VATS[$id] = $row;
			}
			unset($row, $iterator);

			self::$VATS = $VATS;
		}

		return $VATS;
	}

	public static function GetByID($vatID)
	{
		$vatID = (int)$vatID;
		if ($vatID <= 0)
			return false;

		$arVats = self::GetAll();

		return isset($arVats[$vatID]) ? $arVats[$vatID] : false;
	}

	public static function GetVatRatesListItems()
	{
		$listItems = array('' => Loc::getMessage('CRM_VAT_NOT_SELECTED'));
		foreach (self::GetAll() as $vatRate)
		{
			if ($vatRate['ACTIVE'] !== 'Y')
			{
				continue;
			}
			$listItems[$vatRate['ID']] = $vatRate['NAME'];
		}
		unset($vatRate);
		return $listItems;
	}

	public static function GetFieldCaption($fieldName)
	{
		$result = Loc::getMessage("CRM_VAT_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
}