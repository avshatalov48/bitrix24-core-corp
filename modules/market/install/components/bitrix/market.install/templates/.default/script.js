'use strict';

BX.namespace('BX.Market.Install');

BX.Market.Install =
{
	init: function (params)
	{
		params = typeof params === 'object' ? params : {};
		this.code = params.CODE || false;
		this.version = params.VERSION || false;
		this.checkHash = params.CHECK_HASH || false;
		this.installHash = params.INSTALL_HASH || false;
		this.from = params.FROM || '';
		this.iframe = params.IFRAME || false;
		this.redirectPriority = params.REDIRECT_PRIORITY || false;
		this.autoClose = true;

		this.formNode = BX('marketAppInstallForm');
		this.buttonInstallNode = BX.findChildByClassName(this.formNode, 'market-btn-start-install');
		this.buttonCloseNode = BX.findChildByClassName(this.formNode, 'market-btn-close-install');
		BX.bind(this.formNode, 'submit', this.onSubmitForm.bind(this));
		BX.bind(this.buttonCloseNode, 'click', this.onClickClose.bind(this));

	},

	onClickClose: function (event)
	{
		event.preventDefault();
		if (!!this.iframe)
		{
			BX.SidePanel.Instance.close();
		}
	},

	showError: function (message)
	{
		BX('market_install_error').innerHTML = message;
		BX.show(BX('market_install_error'));
		BX.scrollToNode(
			BX('market_install_error')
		);
	},

	onSubmitForm: function (event)
	{
		event.preventDefault();

		if (BX('mp_tos_license') && !BX('mp_tos_license').checked)
		{
			this.showError(BX.message('MARKET_INSTALL_TOS_ERROR'));
			return;
		}

		if (
			BX('mp_detail_license') && !BX('mp_detail_license').checked
			|| BX('mp_detail_confidentiality') && !BX('mp_detail_confidentiality').checked
		)
		{
			this.showError(BX.message('MARKET_INSTALL_LICENSE_ERROR'));
			return;
		}

		if (BX.hasClass(this.buttonInstallNode, 'ui-btn-wait'))
		{
			return;
		}

		BX.addClass(this.buttonInstallNode, 'ui-btn-wait');

		var queryParam = {
			code: this.code
		};

		if (!!this.version)
		{
			queryParam.version = this.version;
		}

		if (!!this.checkHash)
		{
			queryParam.check_hash = this.checkHash;
			queryParam.install_hash = this.installHash;
		}

		if (!!this.from)
		{
			queryParam.from = this.from;
		}
		BX.ajax.runAction(
			'market.Application.install',
			{
				data: queryParam,
				analyticsLabel: {
					from: this.from,
				},
			}
		).then(
			BX.Market.Action.installFinish.bind(this)
		);
	},

	initHelper: function (params)
	{
		if (!window.BX.UI.InfoHelper.isInited())
		{
			window.BX.UI.InfoHelper.init(
				{
					frameUrlTemplate: params.frameUrlTemplate,
				}
			);
			window.BX.UI.InfoHelper.frameNode = BX(params.iframeId);
		}

		BX.bind(
			window,
			'message',
			BX.proxy(
				function (event)
				{
					if (!!event.origin && event.origin.indexOf('bitrix') === -1)
					{
						return;
					}

					if (!event.data || typeof(event.data) !== 'object')
					{
						return;
					}

					if (event.data.action === 'reloadParent')
					{
						var slider = BX.SidePanel.Instance.getTopSlider();
						if (!!slider)
						{
							slider.reload();
						}
						else
						{
							window.location.reload();
						}
					}
				}
			)
		);
	},
};
