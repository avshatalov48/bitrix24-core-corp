/**
 * @module im/messenger/api/dialog-opener
 */
jn.define('im/messenger/api/dialog-opener', (require, exports, module) => {
	const { Type } = require('type');
	const { EntityReady } = require('entity-ready');
	const {
		EventType,
		FeatureFlag,
		ComponentCode,
		OpenRequest,
		OpenDialogContextType,
	} = require('im/messenger/const');

	/**
	 * @class DialogOpener
	 *
	 * This API is designed to be used in the context of other modules.
	 * Do not use code in it that depends on the im.messenger component like BX.componentParameters etc.
	 */
	class DialogOpener
	{
		static getVersion()
		{
			return 4;
		}

		static get context()
		{
			return OpenDialogContextType;
		}

		/**
		 * Opens a dialog on top of the parent widget.
		 *
		 * @param {object} options
		 *
		 * @param {string|number} options.dialogId
		 *
		 * @param {object} [options.dialogTitleParams]
		 * @param {string} [options.dialogTitleParams.name]
		 * @param {string} [options.dialogTitleParams.description]
		 * @param {string} [options.dialogTitleParams.avatar]
		 * @param {string} [options.dialogTitleParams.color]
		 *
		 * @see DialogOpener.context
		 * @param {string} [options.context] from opened. Need for analytics
		 *
		 * @param {object} [options.parentWidget]
		 *
		 * @return {Promise<DialoguesModelState>}
		 */
		static open(options)
		{
			return new Promise((resolve, reject) => {
				if (!Type.isObject(options))
				{
					reject({
						text: `options must be an object, ${options} given.`,
						code: 'INVALID_ARGUMENT',
					});

					return;
				}

				if (!Type.isStringFilled(options.dialogId) && !Type.isNumber(options.dialogId))
				{
					reject({
						text: `options.userCode must be a filled string or number, ${options.dialogId} given.`,
						code: 'INVALID_ARGUMENT',
					});

					return;
				}

				const openDialogCompleteHandler = ({ chatData, error }) => {
					if (String(chatData?.dialogId) !== String(options.dialogId))
					{
						return;
					}

					BX.removeCustomEvent(EventType.messenger.openDialogComplete, openDialogCompleteHandler);

					if (error)
					{
						reject(new Error(error));
					}
					else
					{
						resolve(chatData);
					}
				};
				BX.addCustomEvent(EventType.messenger.openDialogComplete, openDialogCompleteHandler);

				if (BX.componentParameters.get('COMPONENT_CODE') === ComponentCode.imMessenger)
				{
					EntityReady.wait('chat').then(() => {
						BX.postComponentEvent(EventType.messenger.openDialog, [options], ComponentCode.imMessenger);
					});

					return;
				}

				EntityReady.wait('chat').then(() => {
					if (Type.isFunction(jnComponent.sendOpenRequest))
					{
						jnComponent.sendOpenRequest(ComponentCode.imMessenger, {
							[OpenRequest.dialog]: {
								options,
							},
						});

						return;
					}

					BX.postComponentEvent(EventType.messenger.openDialog, [options], ComponentCode.imMessenger);
				});
			});
		}

		/**
		 * Open an openline chat on top of the parent widget.
		 *
		 * @param {object} options
		 *
		 * one of them must be passed on:
		 * @param {string} [options.userCode]
		 * @param {number} [options.sessionId]
		 *
		 * @param {object} [options.dialogTitleParams]
		 * @param {string} [options.dialogTitleParams.name]
		 * @param {string} [options.dialogTitleParams.description]
		 * @param {string} [options.dialogTitleParams.avatar]
		 * @param {string} [options.dialogTitleParams.color]
		 *
		 * @param {object} [options.parentWidget]
		 *
		 * @return {Promise}
		 */
		static openLine(options)
		{
			return new Promise((resolve, reject) => {
				if (!FeatureFlag.native.openWebComponentParentWidgetSupported)
				{
					reject({
						text: 'This method is not supported in applications with the API version less than 45.',
						code: 'UNSUPPORTED_APP_VERSION',
					});

					return;
				}

				if (!Type.isObject(options))
				{
					reject({
						text: `options must be an object, ${options} given.`,
						code: 'INVALID_ARGUMENT',
					});

					return;
				}

				let shouldOpenByUserCode = false;
				if (Type.isStringFilled(options.userCode))
				{
					shouldOpenByUserCode = true;
				}

				let shouldOpenBySessionId = false;
				if (Type.isNumber(options.sessionId))
				{
					shouldOpenBySessionId = true;
				}

				if (!shouldOpenByUserCode && !shouldOpenBySessionId)
				{
					reject({
						text: 'one of the required options.userCode or options.sessionId is not specified or invalid',
						code: 'INVALID_ARGUMENT',
					});

					return;
				}

				EntityReady.wait('chat').then(() => {
					// eslint-disable-next-line init-declarations
					let requestId;
					if (shouldOpenByUserCode)
					{
						requestId = options.userCode;
					}
					else if (shouldOpenBySessionId)
					{
						requestId = options.sessionId;
					}

					const openLineParamsEvent = `${EventType.messenger.openLineParams}::${requestId}`;

					const onOpenLineParams = (params) => {
						BX.removeCustomEvent(openLineParamsEvent, onOpenLineParams);

						if (!params.data.DIALOG_ID)
						{
							reject({
								text: 'Failed to load the chat.',
								code: 'LOADING_ERROR',
							});

							return;
						}

						if (options.parentWidget)
						{
							PageManager.openWebComponent(params, options.parentWidget);
						}
						else
						{
							PageManager.openWebComponent(params);
						}

						resolve();
					};

					BX.addCustomEvent(openLineParamsEvent, onOpenLineParams);

					const getOpenLineParams = {
						dialogTitleParams: options.dialogTitleParams,
					};

					if (shouldOpenByUserCode)
					{
						getOpenLineParams.userCode = options.userCode;
					}
					else if (shouldOpenBySessionId)
					{
						getOpenLineParams.sessionId = options.sessionId;
					}

					BX.postComponentEvent(EventType.messenger.getOpenLineParams, [getOpenLineParams], ComponentCode.imMessenger);
				});
			});
		}
	}

	module.exports = {
		DialogOpener,
	};
});
