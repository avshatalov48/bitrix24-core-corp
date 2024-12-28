(() => {
	const require = (ext) => jn.require(ext);
	const { AvaMenu } = require('ava-menu');
	AvaMenu.init();

	try
	{
		const { IntranetBackground } = require('intranet/background');
		IntranetBackground?.init();
	}
	catch (e)
	{
		console.warn(e);
	}

	const { OpenDesktopNotification } = require('background/notifications/open-desktop');
	OpenDesktopNotification.bindOpenDesktopEvent();

	const { OpenHelpdeskNotification } = require('background/notifications/open-helpdesk');
	OpenHelpdeskNotification.bindOpenHelpdeskEvent();

	const { OpenPromotionNotification } = require('background/notifications/promotion');
	OpenPromotionNotification.bindPromotionEvent();
})();
