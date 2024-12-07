/**
 * @module im/messenger/lib/element/dialog/message/status
 */
jn.define('im/messenger/lib/element/dialog/message/status', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Moment } = require('utils/date');
	const { FriendlyDate } = require('layout/ui/friendly-date');

	/**
	 * @class StatusField
	 */
	class StatusField
	{
		/**
		 * @constructor
		 * @param {object} options
		 * @param {boolean} options.isGroupDialog
		 * @param {LastMessageViews} options.lastMessageViews
		 */
		constructor(options = {})
		{
			this.statusType = 'viewed';
			this.statusText = '';
			this.isGroupDialog = Type.isUndefined(options.isGroupDialog) ? true : options.isGroupDialog;
			this.buildText(options.lastMessageViews);
			this.setStatusType();
		}

		/**
		 * @desc Build text
		 * @param {LastMessageViews} lastMessageViews
		 */
		buildText(lastMessageViews)
		{
			if (this.isGroupDialog)
			{
				this.buildTextForGroup(lastMessageViews);
			}
			else
			{
				this.buildTextForPrivate(lastMessageViews);
			}
		}

		/**
		 * @desc Build text for status message ( group chat )
		 * @param {LastMessageViews} lastMessageViews
		 */
		buildTextForGroup(lastMessageViews)
		{
			let text;
			const firstUserName = lastMessageViews.firstViewer.userName || '';

			if (lastMessageViews.countOfViewers > 1)
			{
				text = Loc.getMessage(
					'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VIEWED_MORE',
					{
						'#USERNAME#': firstUserName,
						'#USERS_COUNT#': lastMessageViews.countOfViewers - 1,
					},
				);
			}
			else
			{
				text = Loc.getMessage(
					'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VIEWED_ONE',
					{
						'#USERNAME#': firstUserName,
					},
				);
			}

			this.setStatusText(text);
		}

		/**
		 * @desc Build text for status message ( one to one chat )
		 * @param {object} lastMessageViews
		 */
		buildTextForPrivate(lastMessageViews)
		{
			const date = lastMessageViews.firstViewer.date;

			const dataState = new Moment(date);
			const dataFriendly = new FriendlyDate({
				moment: dataState,
				showTime: true,
			});
			const dateText = dataFriendly.makeText(dataState);
			const text = Loc.getMessage(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VIEWED_MSGVER_1',
				{ '#DATE#': dateText },
			);
			this.setStatusText(text);
		}

		/**
		 * @desc Set icons check by type
		 * @param {string} [iconType='doubleCheck'] - check|doubleCheck|line
		 */
		setStatusType(iconType = 'viewed')
		{
			this.statusType = iconType;
		}

		/**
		 * @desc Set text
		 * @param {string} text
		 */
		setStatusText(text)
		{
			this.statusText = text;
		}
	}

	module.exports = {
		StatusField,
	};
});
