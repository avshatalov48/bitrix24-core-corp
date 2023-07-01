/**
 * @module crm/receive-payment/steps/payment-systems/paysystem-settings
 */
jn.define('crm/receive-payment/steps/payment-systems/paysystem-settings', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PaymentMethods } = require('crm/receive-payment/steps/payment-systems/payment-methods');
	const { Oauth } = require('crm/payment-system/creation/actions/oauth');
	const { Before } = require('crm/payment-system/creation/actions/before');
	const { handleErrors } = require('crm/error');
	const { BackdropHeader } = require('layout/ui/banners');

	const imagePath = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/payment-system/creation/actions/oauth/images/payment-banner.png`;

	/**
	 * @class PaySystemSettings
	 */
	class PaySystemSettings extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget;

			this.psCreationOauthAction = (new Oauth({ showMenuCustomSection: false }))
				.setContext('receive-payment')
				.setHelpArticleId('17584326')
				.setLayout(this.layoutWidget);
			this.psCreationBeforeAction = new Before();
			this.uid = props.uid || Random.getString();
		}

		render()
		{
			return View(
				{
					style: styles.backdrop,
				},
				BackdropHeader({
					title: Loc.getMessage('M_RP_PS_SETTINGS_BANNER_TITLE'),
					description: Loc.getMessage('M_RP_PS_SETTINGS_BANNER_DESCRIPTION'),
					image: imagePath,
				}),
				this.renderPaySystems(),
			);
		}

		renderPaySystems()
		{
			const paySystems = this.getPaysystems().map((paySystem) => this.renderPaySystemItem(paySystem));

			return View(
				{
					style: styles.paySystemsContainer,
				},
				Text({
					style: styles.title,
					text: Loc.getMessage('M_RP_PS_SETTINGS_CHOOSE'),
				}),
				ScrollView(
					{
						style: styles.selector,
						horizontal: true,
					},
					View(
						{
							style: {
								flexDirection: 'row',
							},
						},
						...paySystems,
					),
				),
			);
		}

		renderPaySystemItem(paySystem)
		{
			return View(
				{
					style: styles.item.container,
					onClick: () => {
						if (paySystem.id === 'other')
						{
							qrauth.open({
								title: Loc.getMessage('M_RP_PS_PAY_SYSTEM_SETTINGS_QRAUTH_TITLE'),
								redirectUrl: '/saleshub/',
								layout: this.layoutWidget,
							});
						}
						else
						{
							BX.ajax
								.runAction('crmmobile.ReceivePayment.PaySystemMode.initializeOauthParams', { json: {} })
								.then((response) => {
									const oauthData = response.data.oauthData[paySystem.id];
									const beforeData = response.data.beforeData[paySystem.id];

									this.psCreationOauthAction.run(oauthData)
										.then(this.psCreationBeforeAction.run.bind(null, beforeData))
										.then(() => {
											PaymentMethods.open({
												uid: this.uid,
												handler: paySystem.id,
												parentWidget: this.layoutWidget,
											});
										})
										.catch(handleErrors);
								}).catch(handleErrors);
						}
					},
				},
				paySystem.recommended && View(
					{
						style: styles.item.badge,
					},
					Text({
						style: styles.item.badgeText,
						text: Loc.getMessage('M_RP_PS_SETTINGS_RECOMMENDED').toLocaleUpperCase(env.languageId),
					}),
				),
				View(
					{
						style: styles.item.box,
					},
					Image({
						style: styles.item.icon(paySystem.iconWidth, paySystem.iconHeight),
						svg: {
							content: paySystem.icon,
						},
					}),
				),
				Text({
					style: styles.item.title(paySystem.disabled),
					text: paySystem.title,
				}),
			);
		}

		getPaysystems()
		{
			return [
				{
					title: Loc.getMessage('M_RP_PS_SETTINGS_YOOKASSA'),
					id: 'yandexcheckout',
					icon: icons.yookassa,
					iconWidth: 98,
					iconHeight: 22,
					disabled: false,
					recommended: true,
				},
				{
					title: Loc.getMessage('M_RP_PS_SETTINGS_OTHER'),
					id: 'other',
					icon: icons.other,
					iconWidth: 32,
					iconHeight: 24,
					disabled: true,
					recommended: false,
				},
			];
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;
			const widgetParams = {
				backdrop: {
					swipeAllowed: true,
					forceDismissOnSwipeDown: false,
					horizontalSwipeAllowed: false,
					showOnTop: false,
					onlyMediumPosition: true,
				},
			};

			parentWidget.openWidget('layout', widgetParams)
				.then((layoutWidget) => {
					layoutWidget.enableNavigationBarBorder(false);
					layoutWidget.setTitle({
						text: Loc.getMessage('M_RP_PS_SETTINGS_TITLE'),
					});
					layoutWidget.showComponent(new this({
						...props,
						layoutWidget,
					}));
				});
		}
	}

	const styles = {
		backdrop: {
			backgroundColor: '#EEF2F4',
		},
		paySystemsContainer: {
			backgroundColor: '#FFFFFF',
			borderRadius: 12,
			marginTop: 10,
		},
		title: {
			width: '100%',
			marginHorizontal: 20,
			marginVertical: 10,
			color: '#525C69',
			fontSize: 14,
		},
		selector: {
			margin: 20,
			height: 116,
		},
		item: {
			container: {
				padding: 4,
				marginRight: 9,
				alignItems: 'center',
			},
			title: (disabled) => {
				return {
					color: disabled ? '#828B95' : '#151515',
				};
			},
			box: {
				width: 130,
				height: 72,
				borderWidth: 1,
				borderColor: '#DFE0E3',
				borderRadius: 6,
				marginBottom: 8,
				alignItems: 'center',
				justifyContent: 'center',
			},
			icon: (width, height) => {
				return {
					width,
					height,
				};
			},
			badge: {
				position: 'absolute',
				right: 0,
				top: 0,
				borderRadius: 12,
				paddingVertical: 2,
				paddingHorizontal: 6,
				backgroundColor: '#2FC6F6',
				zIndex: 1,
			},
			badgeText: {
				color: '#FFFFFF',
				fontSize: 8,
				fontWeight: '700',
			},
		},
	};

	const icons = {
		yookassa: '<svg width="116" height="26" viewBox="0 0 116 26" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_1_9984)"><path d="M60.4904 7.15494H56.7767L54.1485 11.5638H52.7958L52.7525 2.02222H49.2368V19.2546H52.7525L52.7958 14.525H54.1399L57.6297 19.2546H61.5245L56.9747 12.9868L60.4904 7.15494Z" fill="black"/><path d="M87.0989 12.8306C86.3847 12.3894 85.6104 12.0434 84.7981 11.8024L84.0225 11.5228L83.8158 11.4487C83.3333 11.276 82.8249 11.095 82.8076 10.6262C82.8033 10.4873 82.8353 10.3496 82.9019 10.2259C82.968 10.1022 83.0661 9.99651 83.1868 9.91876C83.4414 9.75129 83.7397 9.65447 84.0484 9.63912C84.7215 9.59475 85.3891 9.78467 85.9269 10.1738L86.0216 10.2313L87.9 8.16673L87.8054 8.09273C87.5715 7.89369 87.3177 7.71734 87.047 7.5663C86.5624 7.30167 86.0358 7.11546 85.4876 7.01518C84.6978 6.8548 83.8815 6.8548 83.0921 7.01518C82.3286 7.11163 81.6044 7.39498 80.9897 7.83773C80.5962 8.13136 80.2677 8.49611 80.0221 8.91103C79.777 9.32596 79.6196 9.78298 79.5591 10.256C79.4514 11.1096 79.6784 11.9711 80.1968 12.6743C80.885 13.402 81.7851 13.9175 82.7817 14.1549L82.9369 14.2043L83.2901 14.3194C84.5655 14.7307 84.9273 14.8952 85.134 15.142C85.2304 15.2661 85.2844 15.4154 85.2892 15.5697C85.2892 16.1537 84.5395 16.3922 84.0311 16.5403C83.6757 16.604 83.3104 16.5975 82.9576 16.5211C82.6048 16.4447 82.2724 16.3001 81.9806 16.0961C81.508 15.7946 81.1038 15.4058 80.7912 14.9528C80.5932 15.1502 78.6456 16.9845 78.6801 17.0174L78.7407 17.0996C79.6788 18.2211 80.9935 18.9996 82.463 19.3041C82.7985 19.366 83.1383 19.4073 83.4799 19.4275H83.8331C84.9939 19.4507 86.1309 19.1129 87.073 18.4651C87.7102 18.0348 88.1988 17.4336 88.4776 16.7377C88.6471 16.2705 88.7068 15.7735 88.6532 15.2818C88.5995 14.7901 88.434 14.3156 88.1672 13.8917C87.8948 13.469 87.5304 13.1071 87.0989 12.8306Z" fill="black"/><path d="M98.4558 12.8305C97.7446 12.3893 96.9737 12.0434 96.164 11.8023L95.3797 11.5227L95.1817 11.4486C94.6906 11.2759 94.1908 11.0949 94.1649 10.6261C94.1671 10.4875 94.2034 10.3513 94.2708 10.2284C94.3383 10.1056 94.4347 9.99952 94.5527 9.91867C94.8073 9.75116 95.1056 9.65438 95.4143 9.63903C96.0875 9.59615 96.7541 9.78591 97.2928 10.1737L97.3788 10.2312L99.2573 8.16664L99.1713 8.0926C98.9357 7.89166 98.6789 7.71519 98.4043 7.56621C97.9227 7.30191 97.3987 7.11566 96.8531 7.01509C96.0607 6.85451 95.2418 6.85451 94.4494 7.01509C93.6867 7.11438 92.963 7.39745 92.3465 7.83764C91.9509 8.12851 91.6193 8.49119 91.3707 8.90479C91.1226 9.3184 90.9622 9.77476 90.8991 10.2477C90.7862 11.1016 91.0136 11.9648 91.5368 12.666C92.225 13.3937 93.1251 13.9091 94.1217 14.1466L94.2682 14.196L94.6214 14.3111C95.9055 14.7224 96.2673 14.8869 96.474 15.1337C96.5734 15.2557 96.6253 15.4069 96.6205 15.5614C96.6205 16.1454 95.8795 16.3839 95.3711 16.532C95.0144 16.5959 94.6474 16.5894 94.2933 16.5131C93.9392 16.4367 93.605 16.292 93.3119 16.0878C92.8433 15.7819 92.4399 15.3939 92.1226 14.9445C91.9332 15.1419 89.9855 16.9761 90.0115 17.0091L90.0802 17.0913C91.0188 18.2128 92.3335 18.9913 93.803 19.2958C94.1385 19.3582 94.4783 19.3995 94.8199 19.4191H95.1731C96.3339 19.4423 97.4709 19.1046 98.413 18.4567C99.0502 18.0265 99.5388 17.4253 99.8176 16.7294C99.9867 16.2622 100.047 15.7652 99.9932 15.2735C99.9395 14.7818 99.774 14.3073 99.5072 13.8834C99.2383 13.4658 98.8799 13.107 98.4558 12.8305Z" fill="black"/><path d="M72.0363 7.15508V8.33952H71.8815C70.9191 7.41974 69.6152 6.89953 68.2538 6.89186C67.419 6.87609 66.5898 7.02838 65.8211 7.33873C65.052 7.64912 64.3598 8.11064 63.79 8.69324C62.6409 9.92164 62.0248 11.5215 62.0667 13.1679C62.0222 14.8419 62.637 16.471 63.79 17.733C64.3464 18.316 65.0273 18.7779 65.7874 19.0873C66.547 19.3967 67.3675 19.5464 68.1933 19.5262C69.5569 19.5018 70.8681 19.0215 71.8984 18.169H72.0363V19.2301H75.6899V7.15508H72.0363ZM72.2174 13.2337C72.2542 14.2068 71.9135 15.1586 71.2607 15.907C70.9481 16.2414 70.562 16.506 70.1301 16.6823C69.6987 16.8585 69.2313 16.9421 68.7622 16.9269C68.307 16.9343 67.8556 16.8434 67.4423 16.6611C67.029 16.4788 66.6641 16.2098 66.3753 15.8741C65.7303 15.1102 65.3996 14.148 65.4445 13.1679C65.4151 12.2173 65.7523 11.2894 66.3922 10.5604C66.687 10.2298 67.0545 9.96547 67.4687 9.78632C67.8829 9.60712 68.3334 9.51757 68.7877 9.52401C69.2542 9.51014 69.7177 9.5952 70.1453 9.77299C70.5728 9.95078 70.9541 10.2169 71.2607 10.5522C71.9135 11.3039 72.2537 12.2582 72.2174 13.2337Z" fill="black"/><path d="M111.777 7.15495V8.33944H111.622C110.662 7.42164 109.362 6.90155 108.003 6.89173C107.167 6.87692 106.337 7.02953 105.567 7.3398C104.797 7.65011 104.103 8.11113 103.531 8.69312C102.382 9.92151 101.766 11.5214 101.807 13.1678C101.763 14.8418 102.378 16.4709 103.531 17.7329C104.087 18.3159 104.768 18.7778 105.528 19.0872C106.288 19.3966 107.108 19.5463 107.934 19.5261C109.298 19.5016 110.608 19.0214 111.639 18.1689H111.777V19.23H115.431V7.15495H111.777ZM111.958 13.2336C111.999 14.2073 111.658 15.1605 111.001 15.9069C110.689 16.2412 110.303 16.506 109.871 16.6822C109.439 16.8584 108.972 16.942 108.503 16.9269C108.047 16.9342 107.596 16.8433 107.183 16.661C106.77 16.4787 106.405 16.2096 106.116 15.874C105.471 15.1101 105.14 14.1479 105.185 13.1678C105.156 12.2171 105.493 11.2893 106.133 10.5603C106.428 10.2297 106.795 9.96538 107.209 9.78619C107.624 9.60704 108.074 9.51744 108.528 9.52392C108.995 9.51005 109.458 9.59507 109.886 9.77286C110.314 9.95065 110.694 10.2168 111.001 10.5521C111.658 11.3018 111.998 12.2575 111.958 13.2336Z" fill="black"/><path d="M25.2376 0C17.6526 0 11.6191 5.84179 11.6191 13C11.6191 20.2405 17.7388 26 25.2376 26C32.7363 26 38.856 20.1582 38.856 13C38.856 5.84179 32.7363 0 25.2376 0ZM25.2376 17.7722C22.4794 17.7722 20.1522 15.5506 20.1522 12.9177C20.1522 10.2848 22.4794 8.0633 25.2376 8.0633C27.9957 8.0633 30.3229 10.2848 30.3229 12.9177C30.2367 15.6329 27.9957 17.7722 25.2376 17.7722Z" fill="url(#paint0_linear_1_9984)"/><path d="M11.5326 3.70251V22.6266H6.70585L0.5 3.70251H11.5326Z" fill="url(#paint1_linear_1_9984)"/></g><defs><linearGradient id="paint0_linear_1_9984" x1="25.2376" y1="0" x2="25.2376" y2="26" gradientUnits="userSpaceOnUse"><stop stop-color="#0160D1"/><stop offset="1" stop-color="#00479C"/></linearGradient><linearGradient id="paint1_linear_1_9984" x1="6.01632" y1="3.70251" x2="6.01632" y2="22.6266" gradientUnits="userSpaceOnUse"><stop stop-color="#0160D1"/><stop offset="1" stop-color="#00479C"/></linearGradient><clipPath id="clip0_1_9984"><rect width="115" height="26" fill="white" transform="translate(0.5)"/></clipPath></defs></svg>',
		other: '<svg width="32" height="24" viewBox="0 0 32 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.45752 13.2247H10.484V18.2512H5.45752V13.2247Z" fill="#66bdee"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.75879 0.522217C1.82579 0.522217 0.258789 2.08922 0.258789 4.02222V19.6091C0.258789 21.542 1.82579 23.1091 3.75879 23.1091H28.219C30.152 23.1091 31.719 21.542 31.719 19.6091V4.02222C31.719 2.08922 30.152 0.522217 28.219 0.522217H3.75879ZM4.49078 3.13708C3.52428 3.13708 2.74078 3.92057 2.74078 4.88707V6.9756H29.237V4.88708C29.237 3.92058 28.4535 3.13708 27.487 3.13708H4.49078ZM29.237 11.009H2.74078V18.7442C2.74078 19.7107 3.52428 20.4942 4.49078 20.4942H27.487C28.4535 20.4942 29.237 19.7107 29.237 18.7442V11.009Z" fill="#66bdee"/></svg>',
	};

	module.exports = { PaySystemSettings };
});
