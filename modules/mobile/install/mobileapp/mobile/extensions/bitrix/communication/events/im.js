/**
 * @module communication/events/im
 */
jn.define('communication/events/im', (require, exports, module) => {

	const { BaseEvent } = require('communication/events/base');
	const { isOpenLine } = require('communication/connection');
	const { NotifyManager } = require('notify-manager');
	const { isEmpty } = require('utils/object');

	const DialogOpener = () => {
		try
		{
			const { DialogOpener } = require('im/messenger/api/dialog-opener');

			return DialogOpener;
		}
		catch (e)
		{
			console.log(e, 'DialogOpener not found');

			return null;
		}
	};

	class ImEvent extends BaseEvent
	{
		prepareValue(value)
		{
			if (isEmpty(value))
			{
				return null;
			}

			const { params: { titleParams = {}, ...restParams } = {} } = value;

			return {
				...value,
				params: {
					...restParams,
					dialogTitleParams: titleParams,
				},
			};
		}

		open()
		{
			const { event, params, callback } = this.getValue();

			if (!event || isEmpty(params))
			{
				return;
			}

			const { value, userCode } = params;

			if (isOpenLine(userCode))
			{
				this.showOpenLine(params, callback);
				return;
			}

			Application.openUrl(value);
		}

		showOpenLine(params, callback)
		{
			const imOpener = DialogOpener();

			if (this.isApiVersionGreaterThan45 && imOpener)
			{
				NotifyManager.showLoadingIndicator();

				imOpener
					.openLine(params)
					.then(() => {
						NotifyManager.hideLoadingIndicatorWithoutFallback();

						if (callback)
						{
							callback();
						}
					})
					.catch((error) => {
						NotifyManager.hideLoadingIndicator(false);
						console.error(error);
					});
			}
			else
			{
				BX.postComponentEvent(
					'ImMobile.Messenger.Openlines:open',
					[{ userCode: params.userCode }],
					'im.messenger',
				);
			}
		}
	}

	module.exports = { ImEvent };

})
;