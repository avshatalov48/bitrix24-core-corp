/**
 * @module im/messenger/controller/dialog/header/icon
 */
jn.define('im/messenger/controller/dialog/header/icon', (require, exports, module) => {

	const headerIconPath = '/bitrix/mobileapp/mobile/extensions/bitrix/menu/header/images/';

	const headerIcon = {
		chat: 'chat_v1.png',
		checked: 'checked_v1.png',
		copy: 'copy_v1.png',
		cross: 'cross_v1.png',
		edit: 'edit_v1.png',
		lifeFeed: 'lifefeed_v1.png',
		notifyOff: 'notify_off_v1.png',
		notify: 'notify_v1.png',
		phone: 'phone_v1.png',
		video: 'video_v1.png',
		quote: 'quote_v1.png',
		reload: 'reload_v1.png',
		reply: 'reply_v1.png',
		task: 'task_v1.png',
		trash: 'trash_v1.png',
		unread: 'unread_v1.png',
		user: 'user_v1.png',
		userPlus: 'user_plus_v1.png',
		users: 'users_v1.png',
	};

	const headerIconType = {
		chat: 'chat',
		checked: 'checked',
		copy: 'copy',
		cross: 'cross',
		edit: 'edit',
		lifeFeed: 'lifeFeed',
		notifyOff: 'notifyOff',
		notify: 'notify',
		phone: 'phone',
		video: 'video',
		quote: 'quote',
		reload: 'reload',
		reply: 'reply',
		task: 'task',
		trash: 'trash',
		unread: 'unread',
		user: 'user',
		userPlus: 'userPlus',
		users: 'users',
	}


	const getHeaderIcon = (iconType) => {

		if(!headerIcon[iconType])
		{
			console.error(`immobile: headerIcon: incorrect icon type - ${iconType}`);

			return '';
		}

		return headerIconPath + headerIcon[iconType];
	}

	module.exports = { getHeaderIcon, headerIconType };
});