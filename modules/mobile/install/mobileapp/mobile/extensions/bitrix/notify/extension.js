(() =>
{
	include("InAppNotifier");

	class Notify
	{
		static showMessage(message = "", title = "")
		{
			if (typeof InAppNotifier != "undefined")
			{
				InAppNotifier.showNotification({
					title: title,
					backgroundColor: "#075776",
					time: 2,
					blur: true,
					message: message
				})
			}
			else
			{
				navigator.notification.alert(message, () =>
				{
				}, title, 'OK');
			}
		}

		static showIndicatorSuccess(options = {}, delay = 0)
		{
			options.type = "success";
			Notify.showIndicatorWithFallback(options, delay);
		}

		static showIndicatorLoading(options = {}, delay = 0)
		{
			options.type = "loading";
			Notify.showIndicator(options, delay);
		}

		static showIndicatorError(options, delay = 0)
		{
			options.type = "error";
			Notify.showIndicatorWithFallback(options, delay);
		}

		static showIndicatorWithFallback(options = {}, delay = 0)
		{
			ifApi(29,
				() => Notify.showIndicator(options, delay))
				.elseIf(options["fallbackText"],
					() =>
					{
						this.hideCurrentIndicator();
						Notify.showMessage(options["fallbackText"], options.title);
					});
		}

		static showIndicator(options = {type: "loading"}, delay = 0)
		{
			if (delay > 0)
			{
				setTimeout(() => dialogs.showLoadingIndicator(options), delay);
			}
			else
			{
				dialogs.showLoadingIndicator(options);
			}
		}

		static hideCurrentIndicator()
		{
			dialogs.hideLoadingIndicator();
		}

		static alert(message, title = "", buttonLabel = "OK")
		{
			navigator.notification.alert(message,()=>{}, title, buttonLabel);
		}
	}

	this.notify = Notify;
	this.Notify = Notify;
})();