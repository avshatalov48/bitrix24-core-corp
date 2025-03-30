<?php

use Bitrix\Bitrix24\Sso\Scim;
use Bitrix\Main\Routing\Controllers\PublicPageController;
use Bitrix\Main\Routing\RoutingConfigurator;

return function (RoutingConfigurator $routes) {

	$routes->any('/pub/site/', new PublicPageController('/pub/site/index.php'));

	// docs
	$routes->prefix('docs/pub')->group(function (RoutingConfigurator $routes) {

		$routes->any('{hash}/{action}/', new PublicPageController('/docs/pub/index.php'))
			->where('hash', '[0-9a-f]{32}')
			->where('action', '[0-9a-zA-Z]+');

		$routes->any('{id}/{hash}', new PublicPageController('/docs/pub/index.php'))
			->where('id', '[0-9a-f]{32}')
			->where('hash', '.*');
	});

	$routes->any('/docs/pub/', new PublicPageController('/docs/pub/extlinks.php'));

	$routes->any('/docs/{any}', new PublicPageController('/docs/index.php'))
		->where('any', '.*');


	// disk
	$routes->prefix('disk/boards')
		->where('fileId', '[0-9]+')
		->group(function (RoutingConfigurator $routes) {
			$routes->any('{fileId}/openDocument', [\Bitrix\Disk\Controller\Integration\Flipchart::class, 'openDocument']);
		});

	$routes->any('/disk/{action}/{fileId}/{any}', new PublicPageController('/bitrix/services/disk/index.php'))
		->where('any', '.*')
		->where('action', '[0-9a-zA-Z]+')
		->where('fileId', '[0-9]+');

	$routes->any('/mobile/disk/{objectId}/download', new PublicPageController('/mobile/disk/index.php'))
		->where('objectId', '[0-9]+')
		->default('download', '1');

	// pub
	$routes->prefix('pub')->group(function (RoutingConfigurator $routes) {
		$routes->any('pay/{account_number}/{hash}/([^/]*)', new PublicPageController('/pub/payment.php'))
			->where('account_number', '[0-9a-zA-Z_-]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('document/{id}/{hash}/([^/]*)', new PublicPageController('/pub/document.php'))
			->where('id', '[0-9a-zA-Z_-]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('{id}/{hash}', new PublicPageController('/pub/index.php'))
			->where('id', '[0-9a-f]{32}')
			->where('hash', '.*');

		$routes->any('pay/{account_number}/{hash}/([^/]*)', new PublicPageController('/pub/payment.php'))
			->where('account_number', '[\w\W]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('pay/{account_number}/{hash}/([^/]*)', new PublicPageController('/pub/payment.php'))
			->where('account_number', '[0-9]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('pay/{account_number}/{hash}/([^/]+)', new PublicPageController('/pub/payment.php'))
			->where('account_number', '[0-9]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('form/{form_code}/{sec}/', new PublicPageController('/pub/form.php'))
			->where('form_code', '[0-9a-z_]+?')
			->where('sec', '[0-9a-z]+?');

		$routes->any('pay/{account_number}/{hash}/', new PublicPageController('/pub/payment.php'))
			->where('account_number', '[0-9]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('calendar-event/{event_id}/{hash}/', new PublicPageController('/pub/calendar_event.php'))
			->where('event_id', '[0-9]+')
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('calendar-sharing/{hash}/', new PublicPageController('/pub/calendar_sharing.php'))
			->where('hash', '[0-9a-zA-Z]+');

		$routes->any('payment-slip/{signed_payment_id}/', new PublicPageController('/pub/payment_slip.php'))
			->where('signed_payment_id', '[\w\W]+');

		$routes->any('booking/confirmation/{hash}/', new PublicPageController('/pub/booking/confirmation.php'))
			->where('hash', '[0-9a-z\.]+');
	});

	$routes->any('/pub/', new PublicPageController('/pub/payment.php'));

	// extranet
	$routes
		->prefix('extranet')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {
			$routes->any('/extranet/contacts/personal/{any}', new PublicPageController('/extranet/contacts/personal.php'));
			$routes->any('/extranet/workgroups/create/', new PublicPageController('/extranet/workgroups/create/index.php'));
			$routes->any('/extranet/crm/configs/perms/{any}', new PublicPageController('/extranet/crm/configs/perms/index.php'));
			$routes->any('/extranet/crm/configs/bp/{any}', new PublicPageController('/extranet/crm/configs/bp/index.php'));
			$routes->any('/extranet/mobile/webdav{any}', new PublicPageController('/extranet/mobile/webdav/index.php'));
			$routes->any('/extranet/docs/shared{any}', new PublicPageController('/extranet/docs/index.php'));
			$routes->any('/extranet/workgroups/{any}', new PublicPageController('/extranet/workgroups/index.php'));
			$routes->any('/extranet/knowledge/group/{any}', new PublicPageController('/extranet/knowledge/group/index.php'));
			$routes->any('/extranet/marketplace/app/{any}', new PublicPageController('/extranet/marketplace/app/index.php'));
			$routes->any('/extranet/marketplace/{any}', new PublicPageController('/extranet/marketplace/index.php'));
		}
	);

	// crm
	$routes
		->prefix('crm')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {

			$routes->any('reports/report/{any}', new PublicPageController('/crm/reports/report/index.php'));
			$routes->any('activity/{any}', new PublicPageController('/crm/activity/index.php'));
			$routes->any('company/{any}', new PublicPageController('/crm/company/index.php'));
			$routes->any('webform/{any}', new PublicPageController('/crm/webform/index.php'));
			$routes->any('invoice/{any}', new PublicPageController('/crm/invoice/index.php'));
			$routes->any('product/{any}', new PublicPageController('/crm/product/index.php'));
			$routes->any('contact/{any}', new PublicPageController('/crm/contact/index.php'));
			$routes->any('button/{any}', new PublicPageController('/crm/button/index.php'));
			$routes->any('quote/{any}', new PublicPageController('/crm/quote/index.php'));
			$routes->any('lead/{any}', new PublicPageController('/crm/lead/index.php'));
			$routes->any('deal/{any}', new PublicPageController('/crm/deal/index.php'));
			$routes->any('tracking/{any}', new PublicPageController('/crm/tracking/index.php'));
			$routes->any('ml/{any}', new PublicPageController('/crm/ml/index.php'));
			$routes->any('catalog/{any}', new PublicPageController('/crm/catalog/index.php'));

			// configs
			$routes->prefix('configs')->group(function (RoutingConfigurator $routes) {
				$routes->any('deal_category/{any}', new PublicPageController('/crm/configs/deal_category/index.php'));
				$routes->any('productprops/{any}', new PublicPageController('/crm/configs/productprops/index.php'));
				$routes->any('mailtemplate/{any}', new PublicPageController('/crm/configs/mailtemplate/index.php'));
				$routes->any('locations/{any}', new PublicPageController('/crm/configs/locations/index.php'));
				$routes->any('mycompany/{any}', new PublicPageController('/crm/configs/mycompany/index.php'));
				$routes->any('currency/{any}', new PublicPageController('/crm/configs/currency/index.php'));
				$routes->any('measure/{any}', new PublicPageController('/crm/configs/measure/index.php'));
				$routes->any('volume/', new PublicPageController('/crm/configs/volume/index.php'));
				$routes->any('exch1c/{any}', new PublicPageController('/crm/configs/exch1c/index.php'));
				$routes->any('fields/{any}', new PublicPageController('/crm/configs/fields/index.php'));
				$routes->any('preset/{any}', new PublicPageController('/crm/configs/preset/index.php'));
				$routes->any('perms/{any}', new PublicPageController('/crm/configs/perms/index.php'));
				$routes->any('tax/{any}', new PublicPageController('/crm/configs/tax/index.php'));
				$routes->any('ps/{any}', new PublicPageController('/crm/configs/ps/index.php'));
				$routes->any('automation/{any}', new PublicPageController('/crm/configs/automation/index.php'));
				$routes->any('bp/{any}', new PublicPageController('/crm/configs/bp/index.php'));
				$routes->any('exclusion/{any}', new PublicPageController('/crm/configs/exclusion/index.php'));
				$routes->any('document_numerators/', new PublicPageController('/crm/configs/document_numerators/index.php'));
				$routes->any('communication_channel_routes/{any}', new PublicPageController('/crm/configs/communication_channel_routes/index.php'));
			});
		}
	);

	// automation
	$routes
		->any('/automation/type/{any}', new PublicPageController('/automation/type/index.php'))
		->where('any', '.*');

	// marketplace
	$routes
		->prefix('marketplace')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {


			$routes->any('configuration/{any}', new PublicPageController('/marketplace/configuration/index.php'));
			$routes->any('hook/{any}', new PublicPageController('/marketplace/hook/index.php'));
			$routes->any('app/{any}', new PublicPageController('/marketplace/app/index.php'));

			$routes->any('view/quick/{any}', new PublicPageController('/marketplace/view/quick/index.php'));
			$routes->any('view/{APP}/{any}', new PublicPageController('/marketplace/view/index.php'))
				->where('APP', '[a-zA-Z0-9\\.\\_]+');

			$routes->any('{any}', new PublicPageController('/marketplace/index.php'));
		}

	);

	// timeman
	$routes
		->prefix('timeman')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {
			$routes->any('meeting/{any}', new PublicPageController('/timeman/meeting/index.php'));
			$routes->any('schedules/{any}', new PublicPageController('/timeman/schedules.php'));
			$routes->any('settings/{any}', new PublicPageController('/timeman/settings.php'));
			$routes->any('worktime/{any}', new PublicPageController('/timeman/worktime.php'));
			$routes->any('login-history/{user}/{any}', new PublicPageController('/timeman/login-history/index.php'))
				->where('user', '[0-9]+');
		}
	);

	// shop
	$routes
		->prefix('shop')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {
			$routes->any('orderform/{any}', new PublicPageController('/shop/orderform/index.php'));
			$routes->any('buyer_group/{any}', new PublicPageController('/shop/buyer_group/index.php'));
			$routes->any('buyer/{any}', new PublicPageController('/shop/buyer/index.php'));
			$routes->any('import/instagram/{any}', new PublicPageController('/shop/import/instagram/index.php'));
			$routes->any('settings/', new PublicPageController('/shop/settings/index.php'));
			$routes->any('stores/{any}', new PublicPageController('/shop/stores/index.php'));
			$routes->any('orders/{any}', new PublicPageController('/shop/orders/index.php'));
			$routes->any('catalog/{any}', new PublicPageController('/shop/catalog/index.php'));
		}
	);

	// stssync
	$routes
		->prefix('stssync')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {
			$routes->any('contacts/{any}', new PublicPageController('/bitrix/services/stssync/contacts/index.php'));
			$routes->any('calendar/{any}', new PublicPageController('/bitrix/services/stssync/calendar/index.php'));
			$routes->any('tasks/{any}', new PublicPageController('/bitrix/services/stssync/tasks/index.php'));
			$routes->any('contacts_crm/{any}', new PublicPageController('/bitrix/services/stssync/contacts_crm/index.php'));
			$routes->any('contacts_extranet/{any}', new PublicPageController('/bitrix/services/stssync/contacts_extranet/index.php'));
			$routes->any('contacts_extranet_emp/{any}', new PublicPageController('/bitrix/services/stssync/contacts_extranet_emp/index.php'));
			$routes->any('tasks_extranet/{any}', new PublicPageController('/bitrix/services/stssync/tasks_extranet/index.php'));
			$routes->any('calendar_extranet/{any}', new PublicPageController('/bitrix/services/stssync/calendar_extranet/index.php'));
		}
	);

	// marketing
	$routes
		->prefix('marketing')
		->where('any', '.*')
		->group(function (RoutingConfigurator $routes) {
			$routes->any('letter/{any}', new PublicPageController('/marketing/letter.php'));
			$routes->any('ads/{any}', new PublicPageController('/marketing/ads.php'));
			$routes->any('segment/{any}', new PublicPageController('/marketing/segment.php'));
			$routes->any('template/{any}', new PublicPageController('/marketing/template.php'));
			$routes->any('blacklist/{any}', new PublicPageController('/marketing/blacklist.php'));
			$routes->any('contact/{any}', new PublicPageController('/marketing/contact.php'));
			$routes->any('rc/{any}', new PublicPageController('/marketing/rc.php'));
			$routes->any('config/role/{any}', new PublicPageController('/marketing/config/role.php'));
		}
	);

	$routes->any('/video/{alias}(/?)([^/]*)', new PublicPageController('/desktop_app/router.php'))
		->where('alias', '[\.\-0-9a-zA-Z]+')
		->default('videoconf', 1);

	$routes->any('/tasks/getfile/{taskid}/{fileid}/{filename}', new PublicPageController('/tasks/getfile.php'))
		->where('taskid', '\d+')
		->where('fileid', '\d+');

	$routes->any('/bitrix/services/ymarket/{any}', new PublicPageController('/bitrix/services/ymarket/index.php'))
		->where('any', '.*');

	$routes->any('/online/(/?){alias}([^/]*)', new PublicPageController('/desktop_app/router.php'))
		->where('alias', '[\.\-0-9a-zA-Z]+');

	$routes->any('/bizproc/processes/{any}', new PublicPageController('/bizproc/processes/index.php'))
		->where('any', '.*');

	$routes->any('/company/personal/mail/{any}', new PublicPageController('/mail/index.php'))
		->where('any', '.*');

	$routes->any('/company/personal/{any}', new PublicPageController('/company/personal.php'))
		->where('any', '.*');

	$routes->any('/company/lists/{any}', new PublicPageController('/company/lists/index.php'))
		->where('any', '.*');

	$routes->any('/\.well-known', new PublicPageController('/bitrix/groupdav.php'));

	$routes->any('/workgroups/{any}', new PublicPageController('/workgroups/index.php'))
		->where('any', '.*');

	$routes->any('/rest/{any}', new PublicPageController('/bitrix/services/rest/index.php'))
		->where('any', '.*');

	$routes->any('/onec/{any}', new PublicPageController('/onec/index.php'))
		->where('any', '.*');

	$routes->any('/settings/configs/userconsent/', new PublicPageController('/settings/configs/userconsent.php'));

	$routes->any('/sites/{any}', new PublicPageController('/sites/index.php'))
		->where('any', '.*');

	$routes->any('/stores/{any}', new PublicPageController('/stores/index.php'))
		->where('any', '.*');

	// mobile
	$routes->any('/m/docs/{any}', new PublicPageController('/m/docs/index.php'))
		->where('any', '.*');

	$routes->any('/mobile/webdav{any}', new PublicPageController('/mobile/webdav/index.php'))
		->where('any', '.*');

	$routes->any('\/?\/mobile/mobile_component\/{componentName}\/', new PublicPageController('/bitrix/services/mobile/jscomponent.php'))
		->where('componentName', '.*');

	$routes->any('\/?\/mobile/web_mobile_component\/{componentName}\/', new PublicPageController('/bitrix/services/mobile/webcomponent.php'))
		->where('componentName', '.*');

	$routes->any('\/?\/mobile/jn\/{componentName}\/', new PublicPageController('/bitrix/services/mobile/jscomponent.php'))
		->where('componentName', '.*');

	$routes->any('\/?\/mobile/jn\/{componentName}\/', new PublicPageController('/bitrix/services/mobile/jscomponent.php'))
		->where('componentName', '.*');

	$routes->any('\/?\/mobileapp/jn\/{componentName}\/', new PublicPageController('/bitrix/services/mobileapp/jn.php'))
		->where('componentName', '.*');

	$routes->any('/mobile/knowledge/group/{any}', new PublicPageController('/mobile/knowledge/group/index.php'))
		->where('any', '.*');

	$routes->any('/mobile/knowledge/{any}', new PublicPageController('/mobile/knowledge/index.php'))
		->where('any', '.*');

	$routes->any('/mail/{any}', new PublicPageController('/mail/index.php'))
		->where('any', '.*');

	$routes->any('/knowledge/group/{any}', new PublicPageController('/knowledge/group/index.php'))
		->where('any', '.*');

	$routes->any('/knowledge/{any}', new PublicPageController('/knowledge/index.php'))
		->where('any', '.*');

	$routes->any('/kb/group/{any}', new PublicPageController('/kb/group/index.php'))
		->where('any', '.*');

	$routes->any('/kb/{any}', new PublicPageController('/kb/index.php'))
		->where('any', '.*');

	$routes->any('/rpa/', new PublicPageController('/rpa/index.php'));

	$routes->any('/devops/{any}', new PublicPageController('/devops/index.php'))
		->where('any', '.*');

	$routes->any('/conference/{any}', new PublicPageController('/conference/index.php'))
		->where('any', '.*');

	$routes
		->any('/_analytics/', fn() => '42')
	;

//scim azure
	$routes
		->prefix('integration/scim/v2.0')
		->group(function (RoutingConfigurator $routes) {
			$routes->get('Users', [Scim\Controller\UserController::class, 'list'])->name('AzureProvisioning.Users');
			$routes->get('Users/{id}', [Scim\Controller\UserController::class, 'get'])->name('AzureProvisioning.User');
			$routes->post('Users', [Scim\Controller\UserController::class, 'create'])->name('AzureProvisioning.User.Create');
			$routes->patch('Users/{id}', [Scim\Controller\UserController::class, 'update'])->name('AzureProvisioning.User.Update');
			$routes->put('Users/{id}', [Scim\Controller\UserController::class, 'replace'])->name('AzureProvisioning.User.Replace');
			$routes->delete('Users/{id}', [Scim\Controller\UserController::class, 'delete'])->name('AzureProvisioning.User.Delete');

			$routes->get('ServiceProviderConfig', [Scim\Controller\ServiceProviderConfigController::class, 'index']);
			$routes->get('Schemas', [Scim\Controller\SchemaController::class, 'index']);
			$routes->get('Schemas/{id}', [Scim\Controller\SchemaController::class, 'get'])->name('AzureProvisioning.Schemas');
			$routes->get('ResourceTypes', [Scim\Controller\ResourceTypesController::class, 'index']);
			$routes->get('ResourceTypes/{id}', [Scim\Controller\ResourceTypesController::class, 'get'])->name('AzureProvisioning.ResourceType');
		}
	);
};
