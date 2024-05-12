/**
 * @module notify-manager
 */
jn.define('notify-manager', (require, exports, module) => {
	const { Haptics } = require('haptics');

	let loadingIndicatorIsShown = false;
	let loadingTimeout = null;

	/**
	 * @class NotifyManager
	 */
	class NotifyManager
	{
		static showDefaultError()
		{
			this.showErrors([{ message: BX.message('DETAIL_CARD_DEFAULT_ERROR2') }]);
		}

		/**
		 * @param {string} errorMessage
		 */
		static showError(errorMessage)
		{
			this.showErrors([{ message: errorMessage }]);
		}

		/**
		 * @param {{message: string}[]} errors
		 * @param {?string} titleMessage
		 */
		static showErrors(errors, titleMessage = BX.message('DETAIL_CARD_DEFAULT_ERROR_TITLE'))
		{
			const errorsSet = new Set();

			errors = Array.isArray(errors) ? errors : [];
			errors.forEach((error) => errorsSet.add(error.message));

			const text = errorsSet.size > 0
				? [...errorsSet].join('\n')
				: BX.message('DETAIL_CARD_DEFAULT_ERROR2')
			;

			if (loadingIndicatorIsShown)
			{
				this.hideLoadingIndicator(false);
				if (text !== '')
				{
					setTimeout(() => Notify.alert(text, titleMessage), 300);
				}
			}
			else
			{
				Haptics.notifyFailure();
				if (text !== '')
				{
					Notify.alert(text, titleMessage);
				}
			}
		}

		static showLoadingIndicator(dismissKeyboard = true)
		{
			return new Promise((resolve) => {
				if (loadingIndicatorIsShown)
				{
					resolve(true);

					return;
				}
				loadingIndicatorIsShown = true;
				const showIndicator = () => {
					if (loadingTimeout !== null)
					{
						clearTimeout(loadingTimeout);
						loadingTimeout = null;
					}

					Notify.showIndicatorLoading().then(() => {
						resolve(true);
					});
				};

				if (dismissKeyboard)
				{
					Keyboard.dismiss();
					loadingTimeout = setTimeout(() => showIndicator(), 50);
				}
				else
				{
					showIndicator();
				}
			});
		}

		static hideLoadingIndicatorWithoutFallback()
		{
			if (loadingTimeout !== null)
			{
				clearTimeout(loadingTimeout);
				loadingTimeout = null;
			}

			Notify.hideCurrentIndicator();
			loadingIndicatorIsShown = false;
		}

		/**
		 * @param {Boolean} success
		 * @param {String|null} text
		 * @param {Number|null} hideAfter
		 */
		static hideLoadingIndicator(success, text = '', hideAfter = 300)
		{
			if (loadingTimeout !== null)
			{
				clearTimeout(loadingTimeout);
				loadingTimeout = null;
			}

			if (success)
			{
				Haptics.notifySuccess();
				Notify.showIndicatorSuccess({ text, hideAfter });
			}
			else
			{
				Haptics.notifyFailure();
				Notify.showIndicatorError({ text, hideAfter });
			}

			loadingIndicatorIsShown = false;
		}
	}

	module.exports = { NotifyManager };
});
