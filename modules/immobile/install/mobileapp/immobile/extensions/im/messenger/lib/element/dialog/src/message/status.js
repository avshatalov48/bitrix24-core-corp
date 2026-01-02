/**
 * @module im/messenger/lib/element/dialog/message/status
 */
jn.define('im/messenger/lib/element/dialog/message/status', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
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
			this.type = 'viewed';
			this.text = '';
			this.additionalText = '';
			this.isGroupDialog = Type.isUndefined(options.isGroupDialog) ? true : options.isGroupDialog;
			this.buildText(options.lastMessageViews);
			this.setType();
		}

		/**
		 * @desc Build text
		 * @param {LastMessageViews} lastMessageViews
		 */
		buildText(lastMessageViews)
		{
			const text = this.isGroupDialog
				? this.buildTextForGroup(lastMessageViews)
				: this.buildTextForPrivate(lastMessageViews)
			;

			this.setText(text);
			this.setAdditionalText(this.buildAdditionalText(lastMessageViews));
		}

		/**
		 * @param {LastMessageViews} lastMessageViews
		 * @return {number}
		 */
		getUsersCount(lastMessageViews)
		{
			return lastMessageViews.countOfViewers - 1;
		}

		/**
		 * @desc Build text for status message ( group chat )
		 * @param {LastMessageViews} lastMessageViews
		 * @return {string}
		 */
		buildTextForGroup(lastMessageViews)
		{
			const firstUserName = lastMessageViews.firstViewer.userName || '';

			return Loc.getMessage(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VIEWED_ONE',
				{
					'#USERNAME#': firstUserName,
				},
			);
		}

		/**
		 * @desc Build text for status message ( one to one chat )
		 * @param {object} lastMessageViews
		 * @return {string}
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

			return Loc.getMessage(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VIEWED_MSGVER_1',
				{ '#DATE#': dateText },
			);
		}

		/**
		 * @desc Build additionalText for status message
		 * @param {object} lastMessageViews
		 * @return {string}
		 */
		buildAdditionalText(lastMessageViews)
		{
			if (this.getUsersCount(lastMessageViews) === 0)
			{
				return '';
			}

			return Loc.getMessage(
				'IMMOBILE_ELEMENT_DIALOG_MESSAGE_VIEWED_MORE_V2',
				{
					'#USERS_COUNT#': this.getUsersCount(lastMessageViews),
				},
			);
		}

		/**
		 * @desc Set icons check by type
		 * @param {string} [iconType='doubleCheck'] - check|doubleCheck|line
		 */
		setType(iconType = 'viewed')
		{
			this.type = iconType;
		}

		/**
		 * @desc Set text
		 * @param {string} text
		 */
		setText(text)
		{
			this.text = text;
		}

		/**
		 * @desc Set additionalText
		 * @param {string} text
		 */
		setAdditionalText(text)
		{
			this.additionalText = text;
		}
	}

	module.exports = {
		StatusField,
	};
});
