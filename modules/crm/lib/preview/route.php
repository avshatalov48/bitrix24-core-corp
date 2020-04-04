<?

namespace Bitrix\Crm\Preview;

use Bitrix\Main\UrlPreview\Router;

class Route
{
	/**
	 * Registers routes for building previews of the crm entities.
	 * @return void
	 */
	public static function setCrmRoutes()
	{
		$pathToUserProfile = "/company/personal/user/#user_id#/";
		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_company_show'),
			'crm',
			'\Bitrix\Crm\Preview\Company',
			array(
				'companyId' => '$company_id',
				'PATH_TO_USER_PROFILE' => $pathToUserProfile,
			)
		);

		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_contact_show'),
			'crm',
			'\Bitrix\Crm\Preview\Contact',
			array(
				'contactId' => '$contact_id',
				'PATH_TO_USER_PROFILE' => $pathToUserProfile,
			)
		);

		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_deal_show'),
			'crm',
			'\Bitrix\Crm\Preview\Deal',
			array(
				'dealId' => '$deal_id',
				'PATH_TO_USER_PROFILE' => $pathToUserProfile,
			)
		);

		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_invoice_show'),
			'crm',
			'\Bitrix\Crm\Preview\Invoice',
			array(
				'invoiceId' => '$invoice_id',
				'PATH_TO_USER_PROFILE' => $pathToUserProfile,
				'PATH_TO_DEAL_SHOW' => \COption::GetOptionString('crm', 'path_to_deal_show'),
				'PATH_TO_QUOTE_SHOW' => \COption::GetOptionString('crm', 'path_to_quote_show'),
			)
		);

		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_lead_show'),
			'crm',
			'\Bitrix\Crm\Preview\Lead',
			array(
				'leadId' => '$lead_id',
				'PATH_TO_USER_PROFILE' => $pathToUserProfile,
			)
		);

		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_product_show'),
			'crm',
			'\Bitrix\Crm\Preview\Product',
			array(
				'productId' => '$product_id',
			)
		);

		Router::setRouteHandler(
			\COption::GetOptionString('crm', 'path_to_quote_show'),
			'crm',
			'\Bitrix\Crm\Preview\Quote',
			array(
				'quoteId' => '$quote_id',
				'PATH_TO_USER_PROFILE' => $pathToUserProfile,
				'PATH_TO_LEAD_SHOW' => \COption::GetOptionString('crm', 'path_to_lead_show'),
				'PATH_TO_DEAL_SHOW' => \COption::GetOptionString('crm', 'path_to_deal_show'),
			)
		);
	}
}