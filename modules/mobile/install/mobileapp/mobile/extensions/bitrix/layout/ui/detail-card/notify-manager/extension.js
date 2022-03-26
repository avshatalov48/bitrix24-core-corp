(() => {

	let loadingIndicatorIsShown = false;
	let loadingTimeout = 0;

	/**
	 * @class NotifyManager
	 */
	class NotifyManager
	{
		static showError(errorMessage)
		{
			this.showErrors([{message: errorMessage}]);
		}

		static showErrors(errors, message = '')
		{
			const text = (
				(message ? message + '\n' : '')
				+ errors.map(error => error.message).join('\n')
			);

			if (loadingIndicatorIsShown)
			{
				this.hideLoadingIndicator(false);
				if (text)
				{
					setTimeout(() => Notify.alert(text), 300);
				}
			}
			else
			{
				if (text)
				{
					Notify.alert(text);
				}
			}
		}

		static showLoadingIndicator(dismissKeyboard = false)
		{
			const showIndicator = () => {
				if (loadingTimeout)
				{
					clearTimeout(loadingTimeout);
					loadingTimeout = 0;
				}

				Notify.showIndicatorLoading();
			}

			if (dismissKeyboard)
			{
				Keyboard.dismiss();
				loadingTimeout = setTimeout(() => showIndicator(), 50);
			}
			else
			{
				showIndicator();
			}
		}

		static hideLoadingIndicatorWithoutFallback()
		{
			if (loadingTimeout)
			{
				clearTimeout(loadingTimeout);
				loadingTimeout = 0;
			}

			Notify.hideCurrentIndicator();
			loadingIndicatorIsShown = false;
		}

		static hideLoadingIndicator(success = true, text = '')
		{
			if (loadingTimeout)
			{
				clearTimeout(loadingTimeout);
				loadingTimeout = 0;
			}

			if (loadingIndicatorIsShown)
			{
				if (success)
				{
					Notify.showIndicatorSuccess({text, hideAfter: 300});
				}
				else
				{
					Notify.showIndicatorError({text, hideAfter: 300});
				}
			}
			else
			{
				Notify.hideCurrentIndicator();
			}

			loadingIndicatorIsShown = false;
		}
	}

	this.NotifyManager = NotifyManager;
})();
