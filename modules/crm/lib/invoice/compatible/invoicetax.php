<?

use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Attention!
 * Temporary solution. After refactoring this class will drop
 *
 * @deprecated
 * Class CCrmInvoiceTax
 */
class CCrmInvoiceTax extends CSaleOrderTax
{
	/**
	 * @return string
	 */
	protected static function getTableName()
	{
		return 'b_crm_invoice_tax';
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	static protected function isOrderExists($id)
	{
		$dbRes = \Bitrix\Crm\Invoice\Invoice::getList(array(
			'filter' => array('=ID' => $id)
		));

		return (bool)$dbRes->fetch();
	}
}