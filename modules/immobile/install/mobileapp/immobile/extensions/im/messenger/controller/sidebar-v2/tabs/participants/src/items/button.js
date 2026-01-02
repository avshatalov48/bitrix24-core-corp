/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/items/button
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/items/button', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Color } = require('tokens');
	const { Icon } = require('assets/icons');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { IconView } = require('ui-system/blocks/icon');
	const { resolveSidebarType, SidebarType } = require('im/messenger/controller/sidebar-v2/factory');
	const { onAddParticipants } = require('im/messenger/controller/sidebar-v2/user-actions/participants');
	const { ParticipantBaseItem } = require('im/messenger/controller/sidebar-v2/tabs/participants/src/items/base');

	/**
	 * @class ParticipantButtonItem
	 */
	class ParticipantButtonItem extends ParticipantBaseItem
	{
		createMenu()
		{
			return null;
		}

		createAvatar()
		{
			return () => Avatar({
				testId: 'participant-add-button',
				size: 40,
				icon: IconView({
					size: 28,
					color: Color.base3,
					icon: Icon.PLUS,
				}),
				backgroundColor: Color.bgContentTertiary,
			});
		}

		createSubtitle()
		{
			return null;
		}

		createTitle()
		{
			return {
				text: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PARTICIPANTS_BUTTON_ADD_NEW_ITEM'),
			};
		}

		getTestId()
		{
			return 'sidebar-tab-participant-add-new-item-button';
		}

		handleOnClick()
		{
			const sidebarType = resolveSidebarType(this.getDialogId());
			const widgetTitle = sidebarType === SidebarType.channel
				? Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_ADD_SUBSCRIBERS')
				: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_ACTION_ADD_PARTICIPANTS');

			void onAddParticipants({
				store: this.store,
				dialogId: this.getDialogId(),
				widgetTitle,
			});
		}
	}

	module.exports = {
		ParticipantButtonItem,
	};
});
