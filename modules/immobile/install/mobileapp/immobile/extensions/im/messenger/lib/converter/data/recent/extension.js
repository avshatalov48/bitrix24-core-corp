/**
 * @module im/messenger/lib/converter/data/recent
 */
jn.define('im/messenger/lib/converter/data/recent', (require, exports, module) => {
	/* eslint-disable no-param-reassign */
	/* global ChatDataConverter ChatMessengerCommon */
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { Uuid } = require('utils/uuid');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Feature } = require('im/messenger/lib/feature');
	const { MessengerParams } = require('im/messenger/lib/params');
	const {	MessageStatus	} = require('im/messenger/const');

	ChatDataConverter.init({
		userId: MessengerParams.getUserId(),
		listType: 'recent',
		generalChatId: MessengerParams.getGeneralChatId(),
	});

	/**
	 * @class RecentDataConverter
	 */
	class RecentDataConverter
	{
		/**
		 * @param {object} element
		 * @param {string} element.id
		 * @return {RecentModelState}
		 */
		static fromPullToModel(element)
		{
			return RecentDataConverter.fromPushToModel(element);
		}

		// TODO: moved from old im.recent, need to refactor
		/**
		 * @return {object|false}
		 */
		static fromPushToModel(element)
		{
			let newElement = {};
			const recentItem = RecentDataConverter.getRecentItemFromStore(element.id);
			if (recentItem)
			{
				newElement = clone(recentItem);
			}
			else
			{
				newElement = {
					avatar: {},
					user: { id: 0 },
					message: {},
					counter: 0,
					blocked: false,
					writing: false,
					liked: false,
				};
				if (element.id.toString().indexOf('chat') === 0)
				{
					newElement.type = 'chat';
					newElement.id = element.id;
					newElement.chat = {};
					if (Type.isUndefined(element.chat))
					{
						return false;
					}
				}
				else
				{
					newElement.type = 'user';
					newElement.id = parseInt(element.id, 10);
					newElement.user = {};
					if (Type.isUndefined(element.user))
					{
						return false;
					}
				}

				if (Type.isUndefined(element.message))
				{
					return false;
				}
			}

			if (!Type.isUndefined(element.message))
			{
				let id = element.message.id;
				if (!Number.isInteger(element.message.id) && !Uuid.isV4(element.message.id))
				{
					id = parseInt(id, 10);
				}

				newElement.message.id = id;
				newElement.message.text = ChatMessengerCommon.purifyText(element.message.text, element.message.params);
				newElement.message.author_id = element.message.senderId && element.message.system !== 'Y' ? element.message.senderId : 0;
				newElement.message.senderId = element.message.senderId && element.message.system !== 'Y' ? element.message.senderId : 0;
				newElement.message.date = new Date(element.message.date);
				newElement.message.file = element.message.params && element.message.params.FILE_ID
					? element.message.params.FILE_ID.length > 0
					: false
				;
				newElement.message.attach = element.message.params && element.message.params.ATTACH
					? element.message.params.ATTACH
					: false
				;
				newElement.message.status = element.message.status ?? '';
				newElement.message.subTitleIcon = element.message.subTitleIcon ?? '';

				if (!Type.isPlainObject(newElement.message.params))
				{
					newElement.message.params = {};
				}

				newElement.message.params.withFile = (
					element.message.params
					&& Type.isArrayFilled(element.message.params.FILE_ID)
				)
					? element.message.params.FILE_ID
					: newElement.message.params?.withFile ?? false
				;
			}

			if (!Type.isUndefined(element.counter))
			{
				newElement.counter = element.counter;
			}

			if (!Type.isUndefined(element.writing))
			{
				newElement.writing = element.writing;
			}

			if (!Type.isUndefined(element.lastActivityDate))
			{
				newElement.lastActivityDate = element.lastActivityDate;
			}

			if (Type.isBoolean(element.defaultUserRecord) && newElement.options?.defaultUserRecord)
			{
				newElement.options.defaultUserRecord = element.defaultUserRecord;
			}

			if (!Type.isUndefined(element.user))
			{
				element.user.id = parseInt(element.user.id, 10);
				if (element.user.id > 0)
				{
					// eslint-disable-next-line no-multi-assign
					newElement.user = element.user = RecentDataConverter.getUserDataFormat(element.user);

					if (newElement.type === 'user')
					{
						newElement.avatar = element.user.avatar;
						newElement.color = element.user.color;
						newElement.title = element.user.name;
					}
				}
				else
				{
					newElement.user = element.user;
				}
			}

			if (newElement.type === 'chat' && !Type.isUndefined(element.chat))
			{
				element.chat.id = parseInt(element.chat.id, 10);
				element.chat.date_create = new Date(element.chat.date_create);
				newElement.chat = element.chat;

				newElement.avatar = element.chat.avatar;
				newElement.color = element.chat.color;
				newElement.title = element.chat.name;

				if (element.chat.type === 'lines' && !Type.isUndefined(element.lines))
				{
					if (typeof newElement.lines === 'undefined')
					{
						newElement.lines = {};
					}
					newElement.lines.id = parseInt(element.lines.id, 10);
					newElement.lines.status = parseInt(element.lines.status, 10);
				}
			}

			if (!Type.isUndefined(element.liked))
			{
				newElement.liked = element.liked;
			}

			return newElement;
		}

		static getUserDataFormat(user)
		{
			const userData = ChatDataConverter.getUserDataFormat(user);

			if (userData.id > 0)
			{
				if (Type.isStringFilled(user.name))
				{
					userData.name = jnComponent.convertHtmlEntities(user.name);
				}

				if (Type.isStringFilled(user.last_name))
				{
					userData.last_name = jnComponent.convertHtmlEntities(user.last_name);
				}

				if (Type.isStringFilled(user.first_name))
				{
					userData.first_name = jnComponent.convertHtmlEntities(user.first_name);
				}

				if (Type.isStringFilled(user.work_position))
				{
					userData.work_position = jnComponent.convertHtmlEntities(user.work_position);
				}
			}

			return userData;
		}

		/**
		 * @param {Object} params
		 * @param {Object} params.user
		 * @param {Object} params.invited
		 * @return {RecentModelState}
		 */
		static fromPullUserInviteToModel(params)
		{
			return RecentDataConverter.fromPushUserInviteToModel(params);
		}

		/**
		 * @param {Object} params
		 * @param {Object} params.user
		 * @param {number} params.user.id
		 * @return {RecentModelState}
		 */
		static fromPullUserUpdateToModel(params)
		{
			return RecentDataConverter.fromPullToModel({
				id: params.user.id,
				user: params.user,
			});
		}

		/**
		 * @param {Object} params
		 * @param {Object} params.user
		 * @param {Object} params.invited
		 * @return {RecentModelState}
		 */
		static fromPushUserInviteToModel(params)
		{
			const { user, invited } = params;

			return {
				id: Number(user.id),
				message: {
					id: 0,
					senderId: 0,
					date: new Date(),
					status: MessageStatus.received,
					subTitleIcon: '',
					sending: false,
					text: '',
					params: {
						withFile: false,
						withAttach: false,
					},
				},
				dateMessage: null,
				lastActivityDate: user.last_activity_date,
				unread: false,
				pinned: false,
				liked: false,
				avatar: user.avatar,
				color: user.color,
				title: user.name,
				counter: 0,
				invitation: {
					isActive: user.active,
					...invited,
				},
				options: {},
			};
		}

		/**
		 * @param {string} id
		 * @return {?RecentModelState}
		*/
		static getRecentItemFromStore(id)
		{
			const store = serviceLocator.get('core').getStore();

			return store.getters['recentModel/getById'](id);
		}
	}

	module.exports = { RecentDataConverter };
});
