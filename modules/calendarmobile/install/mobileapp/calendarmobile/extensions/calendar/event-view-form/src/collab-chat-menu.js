/**
 * @module calendar/event-view-form/collab-chat-menu
 */
jn.define('calendar/event-view-form/collab-chat-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Icon } = require('ui-system/blocks/icon');

	const { BaseMenu, baseSectionType } = require('calendar/base-menu');

	/**
	 * @class CollabChatMenu
	 */
	class CollabChatMenu extends BaseMenu
	{
		getItems()
		{
			return [
				this.getCollabChatItem(),
				this.getNormalChatItem(),
			];
		}

		getCollabChatItem()
		{
			return {
				id: collabChatItemTypes.collabChat,
				testId: 'calendar-event-view-form-collab-chat-menu-item',
				sectionCode: baseSectionType,
				title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_OPEN_COLLAB_CHAT'),
				iconName: Icon.COLLAB.getIconName(),
				styles: itemStyles,
			};
		}

		getNormalChatItem()
		{
			return {
				id: collabChatItemTypes.normalChat,
				testId: 'calendar-event-view-form-normal-chat-menu-item',
				sectionCode: baseSectionType,
				title: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_OPEN_CHAT'),
				iconName: Icon.ADD_CHAT.getIconName(),
				styles: itemStyles,
			};
		}
	}

	const itemStyles = {
		title: {
			font: {
				color: Color.base1.toHex(),
			},
		},
		icon: {
			color: Color.base3.toHex(),
		},
	};

	const collabChatItemTypes = {
		collabChat: 'collab-chat',
		normalChat: 'normal-chat',
	};

	module.exports = { CollabChatMenu, collabChatItemTypes };
});
