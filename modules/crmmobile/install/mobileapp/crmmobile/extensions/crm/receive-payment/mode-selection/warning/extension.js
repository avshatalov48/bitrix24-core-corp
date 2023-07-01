/**
 * @module crm/receive-payment/mode-selection/warning
 */
jn.define('crm/receive-payment/mode-selection/warning', (require, exports, module) => {
	const { inAppUrl } = require('in-app-url');

	/**
	 * @class ModeSelectionMenuWarning
	 */
	class ModeSelectionMenuWarning extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.menu = null;
		}

		setMenu(menu)
		{
			this.menu = menu;
		}

		render()
		{
			return View(
				{
					style: styles.container,
				},
				this.renderIcon(),
				this.renderText(),
			);
		}

		renderIcon()
		{
			return View(
				{
					style: styles.warningIconContainer,
				},
				Image(
					{
						style: styles.warningIcon,
						svg: {
							content: Icons.warningIcon,
						},
					},
				),
			);
		}

		renderText()
		{
			return View(
				{
					style: styles.rightContainer,
				},
				Text(
					{
						style: styles.title,
						text: this.props.title,
					},
				),
				BBCodeText(
					{
						style: styles.text,
						value: this.props.text,
						onLinkClick: ({ url }) => {
							if (this.menu)
							{
								this.menu.close(this.onLinkClick.bind(this, url));
							}
						},
					},
				),
			);
		}

		onLinkClick(url)
		{
			if (url.startsWith('/'))
			{
				const code = url.slice(1);
				if (code === 'noProviders')
				{
					qrauth.open({
						title: BX.message('MOBILE_RECEIVE_PAYMENT_NO_SMS_PROVIDERS_CONFIGURE_LINK'),
						redirectUrl: '/saleshub/',
					});
					return;
				}
			}

			inAppUrl.open(url);
		}
	}

	const Icons = {
		warningIcon: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M23.3333 14.0003C23.3333 19.155 19.1546 23.3337 14 23.3337C8.8453 23.3337 4.66663 19.155 4.66663 14.0003C4.66663 8.84567 8.8453 4.66699 14 4.66699C19.1546 4.66699 23.3333 8.84567 23.3333 14.0003ZM12.8262 9.85476C12.8262 9.26271 13.2998 8.78906 13.8919 8.78906H14.0366C14.6287 8.78906 15.1023 9.26271 15.1023 9.85476V13.8413C15.1023 14.4333 14.6287 14.907 14.0366 14.907H13.8919C13.2998 14.907 12.8262 14.4333 12.8262 13.8413V9.85476ZM13.9708 18.9322C14.7076 18.9322 15.3128 18.327 15.3128 17.5902C15.3128 16.8534 14.7076 16.2482 13.9708 16.2482C13.234 16.2482 12.6288 16.8534 12.6288 17.5902C12.6288 18.327 13.234 18.9322 13.9708 18.9322Z" fill="#C48300"/></svg>',
	};

	const styles = {
		container: {
			borderRadius: 12,
			backgroundColor: '#FEF3B8',
			paddingTop: 13,
			paddingBottom: 14,
			paddingLeft: 18,
			paddingRight: 60,
			flexDirection: 'row',
			alignItems: 'center',
		},
		warningIconContainer: {
			marginRight: 18,
		},
		warningIcon: {
			width: 28,
			height: 28,
		},
		title: {
			fontSize: 16,
			fontWeight: '500',
		},
		rightContainer: {
			flexDirection: 'column',
		},
		text: {
			fontSize: 14,
			color: '#525C69',
		},
	};

	module.exports = { ModeSelectionMenuWarning };
});
