/**
 * @module im/messenger/provider/rest/recent
 */
jn.define('im/messenger/provider/rest/recent', (require, exports, module) => {
	const { Type } = require('type');

	const { RestMethod } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { runAction } = require('im/messenger/lib/rest');
	const { callMethod } = require('im/messenger/lib/rest');
	const { AnalyticsService } = require('im/messenger/provider/services/analytics');

	/**
	 * @class RecentRest
	 */
	class RecentRest
	{
		/**
		 * @param options
		 * @returns {Promise<RestResult>}
		 */
		getList(options = {})
		{
			const methodParams = {};

			if (Type.isBoolean(options.skipOpenlines))
			{
				methodParams.SKIP_OPENLINES = options.skipOpenlines ? 'Y' : 'N';
			}

			if (Type.isBoolean(options.onlyCopilot))
			{
				methodParams.ONLY_COPILOT = options.onlyCopilot ? 'Y' : 'N';
			}

			if (options.lastActivityDate)
			{
				methodParams.DATE_LAST_ACTIVITY = options.lastActivityDate;
			}

			return callMethod(RestMethod.imRecentList, methodParams);
		}

		getChannelList(options = {})
		{
			const methodParams = {};

			if (Type.isNumber(options.limit))
			{
				methodParams.limit = options.limit;
			}

			if (Type.isPlainObject(options.filter))
			{
				methodParams.filter = {};

				if (Type.isNumber(options.filter.lastMessageId))
				{
					methodParams.filter.lastMessageId = options.filter.lastMessageId;
				}
			}

			return BX.rest.callMethod(RestMethod.imV2RecentChannelTail, methodParams);
		}

		getCollabList(options = {})
		{
			const methodParams = {};

			if (Type.isNumber(options.limit))
			{
				methodParams.limit = options.limit;
			}

			if (Type.isPlainObject(options.filter))
			{
				methodParams.filter = {};

				if (Type.isNumber(options.filter.lastMessageDate) || Type.isStringFilled(options.filter.lastMessageDate))
				{
					methodParams.filter.lastMessageDate = options.filter.lastMessageDate;
				}
			}

			return BX.rest.callMethod(RestMethod.imV2RecentCollabTail, methodParams);
		}

		getExternalChatList(options = {})
		{
			const methodParams = {};

			if (Type.isStringFilled(options.type))
			{
				methodParams.type = options.type;
			}

			if (Type.isNumber(options.limit))
			{
				methodParams.limit = options.limit;
			}

			if (Type.isPlainObject(options.filter))
			{
				methodParams.filter = {};

				if (Type.isNumber(options.filter.lastMessageDate) || Type.isStringFilled(options.filter.lastMessageDate))
				{
					methodParams.filter.lastMessageDate = options.filter.lastMessageDate;
				}
			}

			return BX.rest.callMethod(RestMethod.imV2RecentExternalChatTail, methodParams);
		}

		/**
		 * @param {number|string} dialogId
		 */
		pinChat(dialogId)
		{
			if (!dialogId)
			{
				throw new Error(`${this.constructor.name}: dialogId is required.`);
			}

			if (!DialogHelper.isDialogId(dialogId) && !DialogHelper.isChatId(dialogId))
			{
				throw new Error(`${this.constructor.name}: dialogId is invalid.`);
			}

			const isNotes = DialogHelper.createByDialogId(dialogId)?.isNotes;
			if (isNotes)
			{
				AnalyticsService.getInstance().sendPinChatNotes();
			}

			const data = { dialogId };

			return runAction(RestMethod.imV2ChatPin, { data });
		}

		/**
		 * @param {DialogId|string} dialogId
		 */
		unpinChat(dialogId)
		{
			if (!dialogId)
			{
				throw new Error(`${this.constructor.name}: dialogId is required.`);
			}

			if (!DialogHelper.isDialogId(dialogId) && !DialogHelper.isChatId(dialogId))
			{
				throw new Error(`${this.constructor.name}: dialogId is invalid.`);
			}

			const data = { dialogId };

			return runAction(RestMethod.imV2ChatUnpin, { data });
		}

		hideChat(options = {})
		{
			const methodParams = {};

			if (!options.dialogId)
			{
				throw new Error('RecentRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('RecentRest: options.dialogId is invalid.');
			}

			methodParams.DIALOG_ID = options.dialogId;

			return BX.rest.callMethod(RestMethod.imRecentHide, methodParams);
		}

		read(options = {})
		{
			if (!options.dialogId)
			{
				throw new Error('RecentRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('RecentRest: options.dialogId is invalid.');
			}

			const dialogId = options.dialogId;

			return BX.rest.callMethod(RestMethod.imRecentUnread, { DIALOG_ID: dialogId, ACTION: 'N' });
		}

		readChat(options = {})
		{
			if (!options.dialogId)
			{
				throw new Error('RecentRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('RecentRest: options.dialogId is invalid.');
			}

			const dialogId = options.dialogId;

			const requestMethods = {
				recentUnread: [RestMethod.imRecentUnread, { DIALOG_ID: dialogId, ACTION: 'N' }],
				dialogRead: [RestMethod.imDialogRead, { DIALOG_ID: dialogId }],
			};

			return new Promise((resolve, reject) => {
				BX.rest.callBatch(requestMethods, (result) => {
					const unreadError = result.recentUnread.error();
					const dialogReadError = result.dialogRead.error();

					if (unreadError || dialogReadError)
					{
						reject(result);

						return;
					}

					resolve(result);
				});
			});
		}

		unreadChat(options = {})
		{
			const methodParams = {
				ACTION: 'Y',
			};

			if (!options.dialogId)
			{
				throw new Error('RecentRest: options.dialogId is required.');
			}

			if (!DialogHelper.isDialogId(options.dialogId) && !DialogHelper.isChatId(options.dialogId))
			{
				throw new Error('RecentRest: options.dialogId is invalid.');
			}

			methodParams.DIALOG_ID = options.dialogId;

			return BX.rest.callMethod(RestMethod.imRecentUnread, methodParams);
		}
	}

	module.exports = {
		RecentRest,
	};
});
