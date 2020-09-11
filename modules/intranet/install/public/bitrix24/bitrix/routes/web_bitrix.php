<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Bitrix\Main\Routing\Controllers\PublicPageController;

return function (RoutingConfigurator $routes) {

	$routes->any('/pub/site/{any}', new PublicPageController('/pub/site/index.php'))
		->where('any', '.*');

	$routes->any('/docs/pub/{hash}/{action}/{any}', new PublicPageController('/docs/pub/index.php'))
		->where('any', '.*')
		->where('hash', '[0-9a-f]{32}')
		->where('action', '[0-9a-zA-Z]+');

	$routes->any('/disk/{action}/{fileId}/{any}', new PublicPageController('/bitrix/services/disk/index.php'))
		->where('any', '.*')
		->where('action', '[0-9a-zA-Z]+')
		->where('fileId', '[0-9]+');

	$routes->any('/pub/pay/{account_number}/{hash}/([^/]*){any}', new PublicPageController('/pub/payment.php'))
		->where('any', '.*')
		->where('account_number', '[0-9a-zA-Z_-]+')
		->where('hash', '[0-9a-zA-Z]+');

	$routes->any('/pub/document/{id}/{hash}/([^/]*){any}', new PublicPageController('/pub/document.php'))
		->where('any', '.*')
		->where('id', '[0-9a-zA-Z_-]+')
		->where('hash', '[0-9a-zA-Z]+');

	$routes->any('/docs/pub/{id}/{hash}', new PublicPageController('/docs/pub/index.php'))
		->where('id', '[0-9a-f]{32}')
		->where('hash', '.*');

	$routes->any('/pub/{id}/{hash}', new PublicPageController('/pub/index.php'))
		->where('id', '[0-9a-f]{32}')
		->where('hash', '.*');

	$routes->any('/pub/pay/{account_number}/{hash}/([^/]*){any}', new PublicPageController('/pub/payment.php'))
		->where('any', '.*')
		->where('account_number', '[\w\W]+')
		->where('hash', '[0-9a-zA-Z]+');

	$routes->any('/pub/pay/{account_number}/{hash}/([^/]*){any}', new PublicPageController('/pub/payment.php'))
		->where('any', '.*')
		->where('account_number', '[0-9]+')
		->where('hash', '[0-9a-zA-Z]+');

	$routes->any('/pub/pay/{account_number}/{hash}/([^/]+){any}', new PublicPageController('/pub/payment.php'))
		->where('any', '.*')
		->where('account_number', '[0-9]+')
		->where('hash', '[0-9a-zA-Z]+');

	$routes->any('/pub/form/{form_code}/{sec}/{any}', new PublicPageController('/pub/form.php'))
		->where('any', '.*')
		->where('form_code', '[0-9a-z_]+?')
		->where('sec', '[0-9a-z]+?');

	$routes->any('/mobile/disk/{objectId}/download{any}', new PublicPageController('/mobile/disk/index.php'))
		->where('any', '.*')
		->where('objectId', '[0-9]+')
		->default('download', '1');

	$routes->any('/video/{alias}(/?)([^/]*){any}', new PublicPageController('/desktop_app/router.php'))
		->where('any', '.*')
		->where('alias', '[\.\-0-9a-zA-Z]+')
		->default('videoconf', 1);

	$routes->any('/tasks/getfile/{taskid}/{fileid}/{filename}{any}', new PublicPageController('/tasks/getfile.php'))
		->where('any', '.*')
		->where('taskid', '\d+')
		->where('fileid', '\d+')
		->where('any', '[^/]+');

	$routes->any('/pub/pay/{account_number}/{hash}/{any}', new PublicPageController('/pub/payment.php'))
		->where('any', '.*')
		->where('account_number', '[0-9]+')
		->where('hash', '[0-9a-zA-Z]+');

	$routes->any('/extranet/contacts/personal/{any}', new PublicPageController('/extranet/contacts/personal.php'))
		->where('any', '.*');

	$routes->any('/extranet/workgroups/create/{any}', new PublicPageController('/extranet/workgroups/create/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/crm/configs/perms/{any}', new PublicPageController('/extranet/crm/configs/perms/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/deal_category/{any}', new PublicPageController('/crm/configs/deal_category/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/productprops/{any}', new PublicPageController('/crm/configs/productprops/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/mailtemplate/{any}', new PublicPageController('/crm/configs/mailtemplate/index.php'))
		->where('any', '.*');

	$routes->any('/bitrix/services/ymarket/{any}', new PublicPageController('/bitrix/services/ymarket/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/crm/configs/bp/{any}', new PublicPageController('/extranet/crm/configs/bp/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/locations/{any}', new PublicPageController('/crm/configs/locations/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/mobile/webdav{any}', new PublicPageController('/extranet/mobile/webdav/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/mycompany/{any}', new PublicPageController('/crm/configs/mycompany/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/currency/{any}', new PublicPageController('/crm/configs/currency/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/measure/{any}', new PublicPageController('/crm/configs/measure/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/volume/{any}', new PublicPageController('/crm/configs/volume/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/docs/shared{any}', new PublicPageController('/extranet/docs/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/workgroups/{any}', new PublicPageController('/extranet/workgroups/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/exch1c/{any}', new PublicPageController('/crm/configs/exch1c/index.php'))
		->where('any', '.*');

	$routes->any('/crm/reports/report/{any}', new PublicPageController('/crm/reports/report/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/fields/{any}', new PublicPageController('/crm/configs/fields/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/preset/{any}', new PublicPageController('/crm/configs/preset/index.php'))
		->where('any', '.*');

	$routes->any('/marketplace/local/{any}', new PublicPageController('/marketplace/local/index.php'))
		->where('any', '.*');

	$routes->any('/marketplace/configuration/{any}', new PublicPageController('/marketplace/configuration/index.php'))
		->where('any', '.*');

	$routes->any('/marketplace/hook/{any}', new PublicPageController('/marketplace/hook/index.php'))
		->where('any', '.*');

	$routes->any('/online/(/?)([^/]*){any}', new PublicPageController('/desktop_app/router.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/perms/{any}', new PublicPageController('/crm/configs/perms/index.php'))
		->where('any', '.*');

	$routes->any('/bizproc/processes/{any}', new PublicPageController('/bizproc/processes/index.php'))
		->where('any', '.*');

	$routes->any('/company/personal/mail/{any}', new PublicPageController('/mail/index.php'))
		->where('any', '.*');

	$routes->any('/company/personal/{any}', new PublicPageController('/company/personal.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/tax/{any}', new PublicPageController('/crm/configs/tax/index.php'))
		->where('any', '.*');

	$routes->any('/marketplace/app/{any}', new PublicPageController('/marketplace/app/index.php'))
		->where('any', '.*');

	$routes->any('/marketplace/view/{APP}/{any}', new PublicPageController('/marketplace/app/index.php'))
		->where('APP', '[a-zA-Z0-9\\.\\_]+')
		->where('any', '.*');

	$routes->any('/timeman/meeting/{any}', new PublicPageController('/timeman/meeting/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/ps/{any}', new PublicPageController('/crm/configs/ps/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/automation/{any}', new PublicPageController('/crm/configs/automation/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/bp/{any}', new PublicPageController('/crm/configs/bp/index.php'))
		->where('any', '.*');

	$routes->any('/company/lists/{any}', new PublicPageController('/company/lists/index.php'))
		->where('any', '.*');

	$routes->any('/crm/activity/{any}', new PublicPageController('/crm/activity/index.php'))
		->where('any', '.*');

	$routes->any('/mobile/webdav{any}', new PublicPageController('/mobile/webdav/index.php'))
		->where('any', '.*');

	$routes->any('/crm/company/{any}', new PublicPageController('/crm/company/index.php'))
		->where('any', '.*');

	$routes->any('/crm/webform/{any}', new PublicPageController('/crm/webform/index.php'))
		->where('any', '.*');

	$routes->any('/\.well-known{any}', new PublicPageController('/bitrix/groupdav.php'))
		->where('any', '.*');

	$routes->any('/marketplace/{any}', new PublicPageController('/marketplace/index.php'))
		->where('any', '.*');

	$routes->any('/crm/invoice/{any}', new PublicPageController('/crm/invoice/index.php'))
		->where('any', '.*');

	$routes->any('/crm/product/{any}', new PublicPageController('/crm/product/index.php'))
		->where('any', '.*');

	$routes->any('/crm/contact/{any}', new PublicPageController('/crm/contact/index.php'))
		->where('any', '.*');

	$routes->any('/workgroups/{any}', new PublicPageController('/workgroups/index.php'))
		->where('any', '.*');

	$routes->any('/crm/button/{any}', new PublicPageController('/crm/button/index.php'))
		->where('any', '.*');

	$routes->any('/crm/quote/{any}', new PublicPageController('/crm/quote/index.php'))
		->where('any', '.*');

	$routes->any('/crm/lead/{any}', new PublicPageController('/crm/lead/index.php'))
		->where('any', '.*');

	$routes->any('/crm/deal/{any}', new PublicPageController('/crm/deal/index.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/exclusion/{any}', new PublicPageController('/crm/configs/exclusion/index.php'))
		->where('any', '.*');

	$routes->any('/shop/orderform/{any}', new PublicPageController('/shop/orderform/index.php'))
		->where('any', '.*');

	$routes->any('/shop/buyer_group/{any}', new PublicPageController('/shop/buyer_group/index.php'))
		->where('any', '.*');

	$routes->any('/shop/buyer/{any}', new PublicPageController('/shop/buyer/index.php'))
		->where('any', '.*');

	$routes->any('/shop/import/instagram/{any}', new PublicPageController('/shop/import/instagram/index.php'))
		->where('any', '.*');

	$routes->any('/docs/pub/{any}', new PublicPageController('/docs/pub/extlinks.php'))
		->where('any', '.*');

	$routes->any('/m/docs/{any}', new PublicPageController('/m/docs/index.php'))
		->where('any', '.*');

	$routes->any('/rest/{any}', new PublicPageController('/bitrix/services/rest/index.php'))
		->where('any', '.*');

	$routes->any('/docs/{any}', new PublicPageController('/docs/index.php'))
		->where('any', '.*');

	$routes->any('/pub/{any}', new PublicPageController('/pub/payment.php'))
		->where('any', '.*');

	$routes->any('/stssync/contacts/{any}', new PublicPageController('/bitrix/services/stssync/contacts/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/calendar/{any}', new PublicPageController('/bitrix/services/stssync/calendar/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/tasks/{any}', new PublicPageController('/bitrix/services/stssync/tasks/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/contacts_crm/{any}', new PublicPageController('/bitrix/services/stssync/contacts_crm/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/contacts_extranet/{any}', new PublicPageController('/bitrix/services/stssync/contacts_extranet/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/contacts_extranet_emp/{any}', new PublicPageController('/bitrix/services/stssync/contacts_extranet_emp/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/tasks_extranet/{any}', new PublicPageController('/bitrix/services/stssync/tasks_extranet/index.php'))
		->where('any', '.*');

	$routes->any('/stssync/calendar_extranet/{any}', new PublicPageController('/bitrix/services/stssync/calendar_extranet/index.php'))
		->where('any', '.*');

	$routes->any('/onec/{any}', new PublicPageController('/onec/index.php'))
		->where('any', '.*');

	$routes->any('/settings/configs/userconsent/{any}', new PublicPageController('/settings/configs/userconsent.php'))
		->where('any', '.*');

	$routes->any('/sites/{any}', new PublicPageController('/sites/index.php'))
		->where('any', '.*');

	$routes->any('/stores/{any}', new PublicPageController('/stores/index.php'))
		->where('any', '.*');

	$routes->any('\/?\/mobile/mobile_component\/{componentName}\/{any}', new PublicPageController('/bitrix/services/mobile/jscomponent.php'))
		->where('any', '.*')
		->where('componentName', '.*');

	$routes->any('\/?\/mobile/web_mobile_component\/{componentName}\/{any}', new PublicPageController('/bitrix/services/mobile/webcomponent.php'))
		->where('any', '.*')
		->where('componentName', '.*');

	$routes->any('\/?\/mobile/jn\/{componentName}\/{any}', new PublicPageController('/bitrix/services/mobile/jscomponent.php'))
		->where('any', '.*')
		->where('componentName', '.*');

	$routes->any('\/?\/mobile/jn\/{componentName}\/{any}', new PublicPageController('/bitrix/services/mobile/jscomponent.php'))
		->where('any', '.*')
		->where('componentName', '.*');

	$routes->any('\/?\/mobileapp/jn\/{componentName}\/{any}', new PublicPageController('/bitrix/services/mobileapp/jn.php'))
		->where('any', '.*')
		->where('componentName', '.*');

	$routes->any('/marketing/letter/{any}', new PublicPageController('/marketing/letter.php'))
		->where('any', '.*');

	$routes->any('/marketing/ads/{any}', new PublicPageController('/marketing/ads.php'))
		->where('any', '.*');

	$routes->any('/marketing/segment/{any}', new PublicPageController('/marketing/segment.php'))
		->where('any', '.*');

	$routes->any('/marketing/template/{any}', new PublicPageController('/marketing/template.php'))
		->where('any', '.*');

	$routes->any('/marketing/blacklist/{any}', new PublicPageController('/marketing/blacklist.php'))
		->where('any', '.*');

	$routes->any('/marketing/contact/{any}', new PublicPageController('/marketing/contact.php'))
		->where('any', '.*');

	$routes->any('/shop/settings/{any}', new PublicPageController('/shop/settings/index.php'))
		->where('any', '.*');

	$routes->any('/shop/stores/{any}', new PublicPageController('/shop/stores/index.php'))
		->where('any', '.*');

	$routes->any('/shop/orders/{any}', new PublicPageController('/shop/orders/index.php'))
		->where('any', '.*');

	$routes->any('/marketing/rc/{any}', new PublicPageController('/marketing/rc.php'))
		->where('any', '.*');

	$routes->any('/marketing/config/role/{any}', new PublicPageController('/marketing/config/role.php'))
		->where('any', '.*');

	$routes->any('/crm/configs/document_numerators/{any}', new PublicPageController('/crm/configs/document_numerators/index.php'))
		->where('any', '.*');

	$routes->any('/mail/{any}', new PublicPageController('/mail/index.php'))
		->where('any', '.*');

	$routes->any('/crm/tracking/{any}', new PublicPageController('/crm/tracking/index.php'))
		->where('any', '.*');

	$routes->any('/timeman/schedules/{any}', new PublicPageController('/timeman/schedules.php'))
		->where('any', '.*');

	$routes->any('/timeman/settings/{any}', new PublicPageController('/timeman/settings.php'))
		->where('any', '.*');

	$routes->any('/timeman/worktime/{any}', new PublicPageController('/timeman/worktime.php'))
		->where('any', '.*');

	$routes->any('/crm/ml/{any}', new PublicPageController('/crm/ml/index.php'))
		->where('any', '.*');

	$routes->any('/knowledge/group/{any}', new PublicPageController('/knowledge/group/index.php'))
		->where('any', '.*');

	$routes->any('/knowledge/{any}', new PublicPageController('/knowledge/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/knowledge/group/{any}', new PublicPageController('/extranet/knowledge/group/index.php'))
		->where('any', '.*');

	$routes->any('/mobile/knowledge/group/{any}', new PublicPageController('/mobile/knowledge/group/index.php'))
		->where('any', '.*');

	$routes->any('/mobile/knowledge/{any}', new PublicPageController('/mobile/knowledge/index.php'))
		->where('any', '.*');

	$routes->any('/kb/group/{any}', new PublicPageController('/kb/group/index.php'))
		->where('any', '.*');

	$routes->any('/kb/{any}', new PublicPageController('/kb/index.php'))
		->where('any', '.*');

	$routes->any('/rpa/{any}', new PublicPageController('/rpa/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/marketplace/app/{any}', new PublicPageController('/extranet/marketplace/app/index.php'))
		->where('any', '.*');

	$routes->any('/extranet/marketplace/{any}', new PublicPageController('/extranet/marketplace/index.php'))
		->where('any', '.*');

	$routes->any('/shop/catalog/{any}', new PublicPageController('/shop/catalog/index.php'))
		->where('any', '.*');

	$routes->any('/crm/catalog/{any}', new PublicPageController('/crm/catalog/index.php'))
		->where('any', '.*');

	$routes->any('/devops/{any}', new PublicPageController('/devops/index.php'))
		->where('any', '.*');
};
