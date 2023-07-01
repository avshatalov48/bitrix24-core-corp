/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/controller/dialog/web
 */
jn.define('im/messenger/controller/dialog/web', (require, exports, module) => {

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { core } = require('im/messenger/core');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { PushHandler } = require('im/messenger/provider/push');
	const { OpenLinesRest } = require('im/messenger/provider/rest');

	/**
	 * @class WebDialog
	 */
	class WebDialog
	{
		static open(options)
		{
			const {
				dialogId,
				dialogTitleParams,
				userCode,
			} = options;

			const page = PageManager.getNavigator().getVisible();
			if (page.type === 'Web' && page.pageId === 'im-' + dialogId)
			{
				if (!PageManager.getNavigator().isActiveTab())
				{
					PageManager.getNavigator().makeTabActive();
				}

				return false;
			}

			const pageParams = this.getOpenDialogParams(dialogId, dialogTitleParams, userCode);
			if (this.isOpenlineDialog(dialogId, dialogTitleParams, userCode))
			{
				PageManager.openWebComponent(pageParams);

				BX.postComponentEvent('onTabChange', ['openlines'], 'im.navigation');

				return true;
			}

			PageManager.openWebComponent(pageParams);

			BX.postComponentEvent('onTabChange', ['chats'], 'im.navigation');

			return true;
		}

		static getOpenDialogParams(dialogId, dialogTitleParams = null, userCode = null)
		{
			const chatSettings = Application.storage.getObject('settings.chat', {
				quoteEnable: ChatPerformance.isGestureQuoteSupported(),
				quoteFromRight: false,
				backgroundType: 'LIGHT_GRAY',
			});

			if (!ChatDialogBackground[chatSettings.backgroundType])
			{
				chatSettings.backgroundType = 'LIGHT_GRAY';
			}

			const backgroundConfig = { ...ChatDialogBackground[chatSettings.backgroundType] };
			backgroundConfig.url = currentDomain + backgroundConfig.url;

			let titleParams = {};
			const imagePath = component.path + 'images';
			let dialogEntity = false;

			const recentItem = clone(core.getStore().getters['recentModel/getById'](dialogId));
			if (recentItem)
			{
				titleParams = {
					text: recentItem.title,
					imageUrl: encodeURI(recentItem.avatar),
					useLetterImage: true,
					callback: -1,
				};

				if (recentItem.avatar === '')
				{
					titleParams.imageColor = recentItem.color;
				}

				if (recentItem.type === 'user')
				{
					dialogEntity = JSON.stringify(recentItem.user);
					titleParams.detailText = ChatMessengerCommon.getUserPosition(recentItem.user);
				}
				else if (recentItem.type === 'chat')
				{
					dialogEntity = JSON.stringify(recentItem.chat);

					if (recentItem.chat.entity_type === 'GENERAL')
					{
						titleParams.imageUrl = imagePath + '/avatar_general_x3.png';
					}

					if (recentItem.chat.entity_type === 'SUPPORT24_QUESTION')
					{
						titleParams.imageUrl = imagePath + '/avatar_24_question_x3.png';
						titleParams.detailText = '';
					}

					titleParams.detailText = ChatMessengerCommon.getChatDescription(recentItem.chat);
				}
			}
			else if (dialogTitleParams)
			{
				titleParams = {
					text: dialogTitleParams.name,
					imageUrl: encodeURI(dialogTitleParams.avatar),
					useLetterImage: true,
					detailText: dialogTitleParams.description,
					imageColor: dialogTitleParams.color,
				};
			}
			else
			{
				titleParams = {
					text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_UNNAMED'),
					callback: -1,
				};
			}

			const openDialogParams = {
				PAGE_ID: 'im-' + dialogId,
				DIALOG_ID: dialogId,
				DIALOG_ENTITY: dialogEntity,
				USER_ID: MessengerParams.getUserId(),
				SITE_ID: MessengerParams.get('SITE_ID', 's1'),
				SITE_DIR: env.siteDir,
				LANGUAGE_ID: MessengerParams.get('LANGUAGE_ID', 'en'),
				STORED_EVENTS: PushHandler.getStoredPullEvents(),
				SEARCH_MIN_TOKEN_SIZE : MessengerParams.get('SEARCH_MIN_SIZE', 3),
				WIDGET_CHAT_USERS_VERSION: MessengerParams.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
				WIDGET_CHAT_RECIPIENTS_VERSION: MessengerParams.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
				WIDGET_CHAT_TRANSFER_VERSION: MessengerParams.get('WIDGET_CHAT_TRANSFER_VERSION', '1.0.0'),
				WIDGET_BACKDROP_MENU_VERSION: MessengerParams.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0'),
			};

			if (this.isOpenlineDialog(dialogId, dialogTitleParams, userCode))
			{
				openDialogParams.DIALOG_TYPE = 'chat';

				return {
					page_id: 'im-' + dialogId,
					data: openDialogParams,
					url: '/mobile/web_mobile_component/im.dialog/?version=' + MessengerParams.get('COMPONENT_CHAT_DIALOG_VERSION', '1.0.0'),
					animated: true,
					titleParams,
					textPanelParams: {
						smileButton: {},
						attachButton: {},
						useImageButton: true,
						placeholder: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT'),
						mentionDataSource: {
							outsection: 'NO',
							url: env.siteDir + '/mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots',
						},
					},
				};
			}

			openDialogParams.DIALOG_ID = dialogId;
			openDialogParams.DIALOG_TYPE = DialogHelper.isDialogId(dialogId) ? 'chat' : 'user';

			return {
				page_id: 'im-' + dialogId,
				data: openDialogParams,
				url: '/mobile/web_mobile_component/im.dialog.vue/?version=' + MessengerParams.get('COMPONENT_CHAT_DIALOG_VUE_VERSION', '1.0.0'),
				customInsets: true,
				titleParams,
				animated: true,
				useSystemSwipeBehavior: chatSettings.quoteEnable && !chatSettings.quoteFromRight,
				textPanelParams: {
					smileButton: {},
					attachButton: {},
					useImageButton: true,
					useAudioMessages: true,
					placeholder: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT'),
					mentionDataSource: {
						outsection: 'NO',
						url: env.siteDir + '/mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots'
					},
				},
				background: backgroundConfig
			};
		}

		static getOpenLineParams(userCode, dialogTitleParams = null)
		{
			return new Promise(resolve => {
				this.getOpenlineDialogByUserCode(userCode).then((dialog) => {
					let titleParams;
					if (dialogTitleParams)
					{
						titleParams = {
							text: dialogTitleParams.name,
							imageUrl: encodeURI(dialogTitleParams.avatar),
							useLetterImage: true,
							detailText: dialogTitleParams.description,
							imageColor: dialogTitleParams.color,
						};

						if (
							Type.isStringFilled(dialogTitleParams.name)
							&& !Type.isStringFilled(dialogTitleParams.description)
						)
						{
							titleParams.detailText = Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_OPEN');
						}
					}
					else
					{
						titleParams = {
							text: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_UNNAMED'),
							callback: -1,
						};
					}

					const params = {
						page_id: 'im-' + dialog.dialog_id,
						data: {
							PAGE_ID: 'im-' + dialog.dialog_id,
							DIALOG_ENTITY: false,
							USER_ID: MessengerParams.getUserId(),
							DIALOG_ID: dialog.dialog_id,
							DIALOG_TYPE: 'chat',
							SITE_ID: MessengerParams.get('SITE_ID', 's1'),
							SITE_DIR: env.siteDir,
							LANGUAGE_ID: MessengerParams.get('LANGUAGE_ID', 'en'),
							STORED_EVENTS: [],
							SEARCH_MIN_TOKEN_SIZE: MessengerParams.get('SEARCH_MIN_SIZE', 3),
							WIDGET_CHAT_USERS_VERSION: MessengerParams.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
							WIDGET_CHAT_RECIPIENTS_VERSION: MessengerParams.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
							WIDGET_CHAT_TRANSFER_VERSION: MessengerParams.get('WIDGET_CHAT_TRANSFER_VERSION', '1.0.0'),
							WIDGET_BACKDROP_MENU_VERSION: MessengerParams.get('WIDGET_BACKDROP_MENU_VERSION', '1.0.0'),
						},
						url: '/mobile/web_mobile_component/im.dialog/?version='
							+ MessengerParams.get('COMPONENT_CHAT_DIALOG_VERSION', '1.0.0')
						,
						animated: true,
						titleParams,
						textPanelParams: {
							smileButton: {},
							attachButton: {},
							useImageButton: true,
							placeholder: Loc.getMessage('IMMOBILE_MESSENGER_DIALOG_INPUT_PLACEHOLDER_TEXT'),
							mentionDataSource: {
								outsection: 'NO',
								url: env.siteDir + '/mobile/index.php?mobile_action=get_user_list&use_name_format=Y&with_bots',
							},
						},
					};

					resolve(params);
				});
			});
		}

		static getOpenlineDialogByUserCode(userCode)
		{
			return new Promise((resolve) => {
				OpenLinesRest.getByUserCode(userCode)
					.then(response => {
						resolve(response.data());
					})
					.catch(() => {
						resolve({ dialog_id: 0 });
					});
			});
		}

		static isOpenlineDialog(dialogId, dialogTitleParams = null, userCode = null)
		{
			const recentItem = clone(core.getStore().getters['recentModel/getById'](dialogId));

			return (
				recentItem
				&& (
					recentItem.chat
					&& recentItem.chat.type === 'lines'
					|| !Type.isUndefined(recentItem.lines)
				)
				|| (
					dialogTitleParams
					&& dialogTitleParams.chatType === 'lines'
				)
				|| userCode
			);
		}
	}

	module.exports = {
		WebDialog,
	};
});
