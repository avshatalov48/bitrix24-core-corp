/**
 * @module imconnector/consents/notification-service
 */
jn.define('imconnector/consents/notification-service', (require, exports, module) => {
	const { NotificationServiceConsentView } = require('imconnector/consents/notification-service/view');
	const { NotifyManager } = require('notify-manager');

	/**
	 * @class NotificationServiceConsent
	 */
	class NotificationServiceConsent
	{
		constructor()
		{
			this.title = null;
			this.content = null;
			this.isConsentApproved = null;
			this.isLoad = false;
		}

		/**
		 * @return {Promise<boolean>}
		 */
		isApproved()
		{
			return new Promise((resolve, reject) => {
				this.load()
					.then(() => resolve(this.isConsentApproved))
					.catch((errors) => reject(errors))
				;
			});
		}

		/**
		 * @param parentWidget
		 * @return {Promise<boolean>}
		 */
		open(parentWidget = null)
		{
			return new Promise((resolve, reject) => {
				this.load()
					.then(() => this.isApproved())
					.then((isApproved) => {
						if (isApproved)
						{
							resolve(isApproved);
							return;
						}

						parentWidget = parentWidget || PageManager;

						parentWidget.openWidget(
							'layout',
							{
								backdrop: {
									horizontalSwipeAllowed: false,
									mediumPositionPercent: this.getMediumPositionPercent(),
									onlyMediumPosition: true,
									hideNavigationBar: true,
									swipeAllowed: false,
								},
								onReady: (layoutWidget) => {
									layoutWidget.showComponent(new NotificationServiceConsentView({
										title: this.title,
										content: this.content,
										callback: (result) => {
											this.isConsentApproved = result;
											if (result)
											{
												NotifyManager.showLoadingIndicator();
												BX.rest.callMethod(
													'notifications.consent.Agreement.approve',
													{},
													() => {
														NotifyManager.hideLoadingIndicatorWithoutFallback();
														layoutWidget.close();
													},
												);

												return;
											}
											layoutWidget.close();
										},
										layoutWidget,
									}));
								},
							},
						)
							.then((layoutWidget) => {
								layoutWidget.on('onViewRemoved', () => {
									resolve(this.isConsentApproved);
								});
							})
						;
					})
					.catch((errors) => reject(errors))
				;
			});
		}

		/**
		 * @private
		 * @return {Promise<void>}
		 */
		load()
		{
			return new Promise((resolve, reject) => {
				if (this.isLoad)
				{
					resolve();

					return;
				}

				BX.rest.callMethod('notifications.consent.Agreement.get', {}, (result) => {
					if (result.error())
					{
						reject(result.error());
					}

					this.isLoad = true;

					const data = result.data();

					if (data === null)
					{
						this.isConsentApproved = true;
						resolve();

						return;
					}

					this.title = data.title;
					this.content = data.html;
					this.isConsentApproved = false;

					resolve();
				});
			});
		}

		getMediumPositionPercent()
		{
			const height = device.screen.height;

			return (height > 800 ? 88 : 90);
		}
	}

	module.exports = { NotificationServiceConsent };
});
