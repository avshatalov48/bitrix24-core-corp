<?

namespace Bitrix\Crm\Preview;
class Invoice
{
	/**
	 * Returns HTML code for preview of the invoice entity.
	 *
	 * @param array $parameters Parameters that will be passed to the component.
	 * @return string HTML code of the preview.
	 */
	public function buildPreview($parameters)
	{
		global $APPLICATION;
		if(empty($parameters['NAME_TEMPLATE']))
			$parameters['NAME_TEMPLATE'] =  \CSite::GetNameFormat(false);
		else
			$parameters['NAME_TEMPLATE'] = str_replace(array("#NOBR#","#/NOBR#"), array("",""), $parameters["NAME_TEMPLATE"]);

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.invoice.preview',
			'',
			$parameters
		);
		return ob_get_clean();
	}

	/**
	 * Checks for current user's read access to the invoice.
	 *
	 * @param array $parameters Allowed key: invoiceId.
	 * @return bool True if current user has access to the invoice.
	 */
	public function checkUserReadAccess($parameters)
	{
		return \CCrmInvoice::CheckReadPermission($parameters['invoiceId'], \CCrmPerms::GetCurrentUserPermissions());
	}
}