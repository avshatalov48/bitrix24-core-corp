/**
 * @module im/in-app-url/routes
 */
jn.define('im/in-app-url/routes', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { NotifyManager } = require('notify-manager');
	const { Haptics } = require('haptics');

	const {
		EventType,
		ComponentCode,
		FileType,
		OpenDialogContextType,
	} = require('im/messenger/const');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { DialogOpener } = require('im/messenger/api/dialog-opener');

	const openlinesPrefix = 'imol|';

	const checkIsMessengerFamilyComponent = () => {
		const componentCode = BX.componentParameters.get('COMPONENT_CODE', '');
		const messengerComponentCodes = [
			ComponentCode.imMessenger,
			ComponentCode.imCopilotMessenger,
			ComponentCode.imChannelMessenger,
		];

		return messengerComponentCodes.includes(componentCode);
	};

	/**
	 * @param componentCode
	 * @param {string} dialogId
	 * @param {string|null} messageId
	 */
	const openDialog = (componentCode, dialogId, messageId = null) => {
		const openDialogEvent = {
			dialogId,
			context: OpenDialogContextType.link,
		};

		if (Type.isStringFilled(messageId) && Type.isNumber(parseInt(messageId, 10)))
		{
			openDialogEvent.messageId = parseInt(messageId, 10);
			openDialogEvent.withMessageHighlight = true;
		}

		MessengerEmitter.emit(EventType.messenger.openDialog, openDialogEvent, componentCode);
	};

	/**
	 * @param {string} dialogId
	 */
	const checkIsOpenLineSessionId = (dialogId) => {
		if (!Type.isStringFilled(dialogId))
		{
			return false;
		}

		if (!dialogId.startsWith(openlinesPrefix))
		{
			return false;
		}

		const sessionIdParts = dialogId.split(openlinesPrefix);
		if (sessionIdParts.length !== 2)
		{
			return false;
		}

		return Type.isNumber(Number(sessionIdParts[1]));
	};

	/**
	 * @param {string} dialogId
	 */
	const checkIsOpenLineUserCode = (dialogId) => {
		return !checkIsOpenLineSessionId(dialogId) && dialogId.startsWith(openlinesPrefix);
	};

	const openLineByDialogId = (dialogId, messageId = null) => {
		const openDialogEvent = {
			dialogId,
			dialogTitleParams: {
				chatType: 'lines',
			},
		};

		if (Type.isStringFilled(messageId) && Type.isNumber(parseInt(messageId, 10)))
		{
			openDialogEvent.messageId = parseInt(messageId, 10);
			openDialogEvent.withMessageHighlight = true;
		}

		MessengerEmitter.emit(EventType.messenger.openLine, openDialogEvent, ComponentCode.imMessenger);
	};

	const openLineBySessionId = (sessionId, fallbackUrl) => {
		return openLineWithUnknownDialogId({
			sessionId,
			fallbackUrl,
		});
	};

	const openLineByUserCode = (userCode, fallbackUrl) => {
		return openLineWithUnknownDialogId({
			userCode,
			fallbackUrl,
		});
	};

	/**
	 * @param {object} options
	 */
	const openLineWithUnknownDialogId = async (options = {}) => {
		await NotifyManager.showLoadingIndicator();

		let openLinePromise = Promise.reject();
		if (options.userCode)
		{
			openLinePromise = DialogOpener.openLine({
				userCode: options.userCode,
			});
		}
		else if (options.sessionId)
		{
			openLinePromise = DialogOpener.openLine({
				sessionId: Number(options.sessionId),
			});
		}

		openLinePromise
			.catch((error) => {
				// eslint-disable-next-line no-console
				console.error(error);

				Haptics.notifyFailure();

				if (Type.isStringFilled(options.fallbackUrl))
				{
					Application.openUrl(options.fallbackUrl);
				}
			})
			.finally(() => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
			})
		;
	};

	const openMessageAttach = (messageId) => {
		const realUrl = `${currentDomain}/mobile/im/attach.php?messageId=${messageId}`;

		PageManager.openPage({
			url: realUrl,
			titleParams: {
				text: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_ATTACH_TITLE'),
			},
			backdrop: {
				horizontalSwipeAllowed: false,
			},
		});
	};

	const openMessageGallery = async (messageId) => {
		if (!checkIsMessengerFamilyComponent())
		{
			return;
		}

		const { serviceLocator } = await requireLazy('im:messenger/lib/di/service-locator');
		/**
		 * @type MessengerCoreStore
		 */
		const store = serviceLocator.get('core').getStore();
		const fileList = store.getters['filesModel/getListByMessageId'](messageId);
		if (!Type.isArrayFilled(fileList))
		{
			return;
		}

		const isImageGallery = fileList.every((file) => file.type === FileType.image);
		if (isImageGallery)
		{
			const imageList = fileList.map((file, index) => {
				return {
					url: file.urlShow,
					previewUrl: file.urlPreview,
					name: file.name,
					default: index === 1,
				};
			});

			viewer.openImageCollection(imageList);
		}
	};

	const openFile = async (fileId) => {
		if (!checkIsMessengerFamilyComponent())
		{
			return;
		}

		const { serviceLocator } = await requireLazy('im:messenger/lib/di/service-locator');
		/**
		 * @type MessengerCoreStore
		 */
		const store = serviceLocator.get('core').getStore();
		const file = store.getters['filesModel/getById'](fileId);
		if (file)
		{
			viewer.openDocument(file.urlDownload, file.name);
		}
	};

	const openCopilotAgreement = async () => {
		try
		{
			const { CopilotUserAgreementWidget } = await requireLazy('im:messenger/controller/copilot-agreement');

			CopilotUserAgreementWidget.open({});
		}
		catch (error)
		{
			console.error(error);
		}
	};

	const openGoToWebWidget = async ({ title = null, hintText = null, redirectUrl = null }) => {
		try
		{
			const { qrauth } = await requireLazy('qrauth/utils');

			qrauth.open({
				title,
				hintText,
				redirectUrl,
				analyticsSection: 'chat',
			});
		}
		catch (error)
		{
			console.error(error);
		}
	};

	/**
	 * @param {InAppUrl} inAppUrl
	 */
	module.exports = (inAppUrl) => {
		// chat and channel
		inAppUrl.register(
			'/online/\\?IM_DIALOG=:dialogId$',
			({ dialogId }) => openDialog(ComponentCode.imMessenger, dialogId),
		).name('im:dialog:openDialog');

		inAppUrl.register(
			'/online/\\?IM_DIALOG=:dialogId&IM_MESSAGE=:messageId$',
			({ dialogId, messageId }) => openDialog(ComponentCode.imMessenger, dialogId, messageId),
		).name('im:dialog:goToMessageContext');

		// CoPilot
		inAppUrl.register(
			'/online/\\?IM_COPILOT=:dialogId$',
			({ dialogId }) => openDialog(ComponentCode.imCopilotMessenger, dialogId),
		).name('im:copilot:openDialog');

		inAppUrl.register(
			'/online/\\?IM_COPILOT=:dialogId&IM_MESSAGE=:messageId$',
			({ dialogId, messageId }) => openDialog(ComponentCode.imCopilotMessenger, dialogId, messageId),
		).name('im:copilot:goToMessageContext');

		inAppUrl.register(
			'/online/\\?AI_UX_TRIGGER=box_agreement$',
			() => openCopilotAgreement(),
		).name('im:copilot:agreement');

		inAppUrl.register(
			'/online/\\?FEATURE_PROMOTER=limit_boost_copilot',
			() => openGoToWebWidget({
				title: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_BAAS_LIMIT_TITLE'),
				hintText: Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_COPILOT_BAAS_LIMIT_HINT'),
				redirectUrl: '/?feature_promoter=limit_boost_copilot',
			}),
		).name('im:copilot:openGoToWeb');

		// lines
		inAppUrl.register(
			'/online/\\?IM_LINES=:dialogId$',
			({ dialogId }) => openLineByDialogId(dialogId),
		).name('im:openline:openLine');

		inAppUrl.register(
			'/online/\\?IM_LINES=:dialogId&IM_MESSAGE=:messageId$',
			({ dialogId, messageId }) => openLineByDialogId(dialogId, messageId),
		).name('im:openline:goToMessageContext');

		inAppUrl.register(
			'/online/\\?IM_DIALOG=',
			(params, { queryParams = {}, url = '' }) => {
				const dialogId = queryParams.IM_DIALOG;
				if (checkIsOpenLineUserCode(dialogId))
				{
					void openLineByUserCode(dialogId, url);
				}
				else if (checkIsOpenLineSessionId(dialogId))
				{
					const sessionId = dialogId.replace(openlinesPrefix, '');
					void openLineBySessionId(sessionId, url);
				}
			},
		).name('im:openline:openDialog');

		inAppUrl.register(
			'/online/\\?IM_HISTORY=',
			(params, { queryParams = {}, url = '' }) => {
				const historyId = queryParams.IM_HISTORY;
				if (checkIsOpenLineSessionId(historyId))
				{
					const sessionId = historyId.replace(openlinesPrefix, '');
					void openLineBySessionId(sessionId, url);
				}
			},
		).name('im:openline:openHistory');

		// internal handlers
		inAppUrl.register(
			'/immobile/in-app/message-attach/:messageId',
			({ messageId }) => openMessageAttach(messageId),
		).name('im:message:attach:open');

		inAppUrl.register(
			'/immobile/in-app/message-gallery/:messageId',
			({ messageId }) => openMessageGallery(messageId),
		).name('im:message:gallery:open');

		inAppUrl.register(
			'/immobile/in-app/file-open/:fileId',
			({ fileId }) => openFile(fileId),
		).name('im:message:gallery:open');

		inAppUrl.register(
			'/immobile/in-app/helpdesk=:articleCode',
			({ articleCode }) => helpdesk.openHelpArticle(articleCode, 'helpdesk'),
		).name('im:helpdesk');
	};
});
