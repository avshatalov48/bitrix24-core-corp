/**
 * @module im/messenger/api/dialog-opener
 */
jn.define('im/messenger/api/dialog-opener', (require, exports, module) => {

	const { Type } = require('type');
	const { EntityReady } = require('entity-ready');
	const {
		EventType,
		FeatureFlag,
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
			return 2;
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
		 * @param {object} [options.parentWidget]
		 *
		 * @return {Promise}
		 */
		static open(options)
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

				if (!Type.isStringFilled(options.dialogId) && !Type.isNumber(options.dialogId))
				{
					reject({
						text: `options.userCode must be a filled string or number, ${options.dialogId} given.`,
						code: 'INVALID_ARGUMENT',
					});

					return;
				}

				EntityReady.wait('chat').then(() => {
					const openDialogParamsEvent = EventType.messenger.openDialogParams + '::' + options.dialogId;

					const onOpenDialogParams = (params) => {
						BX.removeCustomEvent(openDialogParamsEvent, onOpenDialogParams);

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

					BX.addCustomEvent(openDialogParamsEvent, onOpenDialogParams);

					BX.postComponentEvent(EventType.messenger.getOpenDialogParams, [{
						dialogId: options.dialogId,
						dialogTitleParams: options.dialogTitleParams,
					}], 'im.messenger');
				});
			});
		}

		/**
		 * Open an openline chat on top of the parent widget.
		 *
		 * @param {object} options
		 *
		 * @param {string} options.userCode
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

				if (!Type.isStringFilled(options.userCode))
				{
					reject({
						text: `options.userCode must be a filled string, ${options.userCode} given.`,
						code: 'INVALID_ARGUMENT',
					});

					return;
				}

				EntityReady.wait('chat').then(() => {
					const openLineParamsEvent = EventType.messenger.openLineParams + '::' + options.userCode;

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

					BX.postComponentEvent(EventType.messenger.getOpenLineParams, [{
						userCode: options.userCode,
						dialogTitleParams: options.dialogTitleParams,
					}], 'im.messenger');
				});
			});
		}
	}

	module.exports = {
		DialogOpener,
	};
});
