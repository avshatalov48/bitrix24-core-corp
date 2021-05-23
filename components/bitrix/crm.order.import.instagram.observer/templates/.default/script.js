(function()
{
	'use strict';

	/**
	 * @namespace BX.Crm.Order.Import.Instagram.Observer
	 */
	BX.namespace('BX.Crm.Order.Import.Instagram.Observer');

	BX.Crm.Order.Import.Instagram.Observer = function(options)
	{
		this.hasNewMedia = options.hasNewMedia;
		this.pathToImport = options.pathToImport;

		if (this.hasNewMedia)
		{
			this.showBalloon();
		}
		else
		{
			this.checkNewMedia();
		}
	};

	BX.Crm.Order.Import.Instagram.Observer.prototype =
		{
			checkNewMedia: function()
			{
				BX.ajax.runComponentAction(
					'bitrix:crm.order.import.instagram.observer',
					'checkMedia'
				).then(function(response)
				{
					if (response.data)
					{
						this.showBalloon();
					}
				}.bind(this));
			},

			showBalloon: function()
			{
				BX.UI.Notification.Center.notify({
					id: 'new-media-notification',
					content: BX.message('CRM_OIIO_NEW_MEDIA'),
					autoHide: false,
					closeButton: false,
					width: 'auto',
					actions: [
						{
							title: BX.message('CRM_OIIO_GO_TO_IMPORT'),
							href: this.pathToImport,
							events: {
								click: function(event, balloon)
								{
									balloon.analyticsAction = 'goToImport';
									balloon.close();
								}
							}
						},
						{
							title: BX.message('CRM_OIIO_LATER'),
							events: {
								click: function(event, balloon)
								{
									balloon.analyticsAction = 'seeLater';
									balloon.close();
								}
							}
						}
					],
					events: {
						onClose: function(event)
						{
							var action = event.balloon && event.balloon.analyticsAction || 'closedByApi';

							BX.ajax.runComponentAction(
								'bitrix:crm.order.import.instagram.observer',
								'markNotificationRead',
								{
									analyticsLabel: {
										source: 'InstagramStore',
										entity: 'CatalogNotification',
										action: action
									}
								}
							);
						}
					}
				});
			}
		};
})();
