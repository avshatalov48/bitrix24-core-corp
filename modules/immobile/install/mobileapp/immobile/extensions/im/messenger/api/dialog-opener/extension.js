/**
 * @module im/messenger/api/dialog-opener
 */
jn.define('im/messenger/api/dialog-opener', (require, exports, module) => {

	const { Type } = jn.require('type');
	const { EntityReady } = jn.require('entity-ready');
	const {
		EventType,
		FeatureFlag,
	} = jn.require('im/messenger/const');

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
			return 1;
		}

		/**
		 * Open an openline chat on top of the parent widget.
		 *
		 * @param {object} options
		 *
		 * @param {string} options.userCode
		 *
		 * @param {object} [options.titleParams]
		 * @param {string} [options.titleParams.name]
		 * @param {string} [options.titleParams.description]
		 * @param {string} [options.titleParams.avatar]
		 * @param {string} [options.titleParams.color]
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

				if (!Type.isStringFilled(options.userCode))
				{
					reject({
						text: 'options.userCode must be a filled string.',
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
						titleParams: options.titleParams,
					}], 'im.messenger');
				});
			});
		}
	}

	module.exports = {
		DialogOpener,
	};
});
