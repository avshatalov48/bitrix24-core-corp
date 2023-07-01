(function() {
	'use strict';

	BX.namespace('BX.Market.Application');

	if (!BX.Market.Application) {
		return;
	}

	BX.Market.Application = {
		inited: false,
		linkInstall: '',
		subscriptionLinkBuy: '',
		subscriptionBuyLandingCode: '',
		iframe: true,
		autoClose: false,

		init: function ()
		{
			if (!this.inited) {
				this.inited = true;

				return new Promise(resolve => {
						BX.ajax.runAction(
							'market.Application.getContext'
						).then(
							response => {
								this.linkInstall = response.data.linkInstall
								this.subscriptionLinkBuy = response.data.subscriptionLinkBuy
								this.subscriptionBuyLandingCode = response.data.subscriptionBuyLandingCode
								resolve();
							}
						)
					}
				);
			} else {
				return new Promise(resolve => resolve());
			}
		},

		openDemo: function ()
		{
			this.init().then(
				() => {
					if (this.subscriptionBuyLandingCode !== '') {
						top.BX.UI.InfoHelper.show(this.subscriptionBuyLandingCode);
					}
				}
			);
		},

		openBuySubscription: function ()
		{
			this.init().then(
				() => {
					if (this.subscriptionLinkBuy !== '') {
						window.open(this.subscriptionLinkBuy, '_blank');
					}
				}
			);
		},

		setRights: function(params)
		{
			this.init().then(
				() => {
					BX.Access.Init(
						{
							other: {
								disabled: false,
								disabled_g2: true,
								disabled_cr: true,
							},
							groups: {
								disabled: true,
							},
							socnetgroups: {
								disabled: true,
							}
						}
					);

					BX.ajax.runAction(
						'market.Application.getRights',
						{
							data: {
								appCode: params.code,
							},
							analyticsLabel: {
								from: params['from'] ?? '',
							},
							method: 'POST'
						}
					).then(
						response => {
							var result = response.data
							BX.Access.SetSelected(result, 'bind');

							BX.Access.ShowForm(
								{
									bind: 'bind',
									showSelected: true,
									callback: function (rights)
									{
										BX.ajax.runAction(
											'market.Application.setRights',
											{
												data: {
													appCode: params.code,
													rights: rights ?? [],
												},
												analyticsLabel: {
													from: params['from'] ?? '',
												},
												method: 'POST'
											}
										);
									}
								}
							);
						}
					);
				}
			);
		},
	};
})();