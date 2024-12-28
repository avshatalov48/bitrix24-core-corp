(() => {
	const require = jn.require;

	const { OpenInviteNotification } = require('background/notifications/open-invite');
	const { OpenDesktopNotification } = require('background/notifications/open-desktop');
	const { OpenCopilotChatTabNotification } = require('background/notifications/open-copilot-chat-tab');
	const { OpenHelpdeskNotification } = require('background/notifications/open-helpdesk');
	const { OpenPromotionNotification } = require('background/notifications/promotion');

	new OpenInviteNotification();
	new OpenDesktopNotification();
	new OpenCopilotChatTabNotification();
	new OpenHelpdeskNotification();
	new OpenPromotionNotification();
})();
