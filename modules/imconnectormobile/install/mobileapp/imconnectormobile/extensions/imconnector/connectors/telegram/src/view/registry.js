/**
 * @module imconnector/connectors/telegram/view/registry
 */
jn.define('imconnector/connectors/telegram/view/registry', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Banner } = require('imconnector/lib/ui/banner');
	const { CompleteButton } = require('imconnector/lib/ui/buttons/complete');
	const { LinkButton } = require('imconnector/lib/ui/buttons/link');
	const { ButtonSwitcher } = require('imconnector/lib/ui/button-switcher');
	const { SettingStep } = require('imconnector/lib/ui/setting-step');
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');
	const { TokenInput, TokenInputColor } = require('imconnector/connector/telegram/layout-components/token-input');
	const { Loader } = require('imconnector/connector/telegram/layout-components/loader');
	const { Complete } = require('imconnector/connectors/telegram/layout-components/registry-complete');

	class RegistryView extends LayoutComponent {
		constructor(props)
		{
			super(props);
			this.buttonSwitcher = null;
			this.isGoingToTelegram = false;

			this.scrollViewRef = null;

			this.isFirstonLayout = true;
			this.hiddenContentRef = null;
			this.hiddenContentHeight = 0;

			this.state.isInputFocused = false;

			this.state.stage = stages.registry;

			this.tokenInput = null;
			this.state.token = '';

			this.onAppActiveHandler = this.onAppActive.bind(this);
			this.onTokenInputBlurHandler = this.onTokenInputBlur.bind(this);
			this.onTokenInputFocusedHandler = this.onTokenInputFocused.bind(this);
		}

		static getWidgetHeight()
		{
			return 520;
		}

		/**
		 * @param {string}token
		 * @return {RegExpExecArray}
		 */
		static checkToken(token)
		{
			return /\d{8,10}:[\w-]{35}$/gm.exec(token);
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.registerEventHandler();
		}

		render()
		{
			switch (this.state.stage)
			{
				case stages.registry:
					return this.getRegistryStage();

				case stages.loader:
					return this.getLoaderStage();

				case stages.complete:
					return this.getCompleteStage();
			}
		}

		getRegistryStage()
		{
			const iconUri = this.props.bannerIcon === 'toSend'
				? `${currentDomain}/bitrix/mobileapp/imconnectormobile/extensions/imconnector/assets/send.png`
				: `${currentDomain}/bitrix/mobileapp/imconnectormobile/extensions/imconnector/assets/open-lines.png`
			;
			const content = View(
				{
					style: {
						flexDirection: 'column',
					},
					resizableByKeyboard: false,
				},
				View(
					{
						onLayout: (props) => {
							if (this.isFirstonLayout)
							{
								this.hiddenContentHeight = props.height;
								this.isFirstonLayout = false;
							}
						},
						ref: (ref) => this.hiddenContentRef = ref,
					},
					Banner({
						title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_BANNER_TITLE'),
						description: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_BANNER_DESCRIPTION'),
						iconUri,
					}),
					this.getFirstStep(),
				),
				this.getSecondStep(),
			);

			return device.screen.width > 410
				? content
				: ScrollView(
					{
						bounces: false,
						ref: (ref) => this.scrollViewRef = ref,
					},
					content,
				)
			;
		}

		onTokenInputFocused()
		{
			this.hiddenContentRef
				.animate(
					{
						duration: 220,
						height: 0,
						option: 'easeInOut',
					},
					() => {
						if (this.scrollViewRef)
						{
							this.scrollViewRef.scrollToBegin(true);
						}
					},
				)
				.start()
			;
		}

		onTokenInputBlur()
		{
			this.hiddenContentRef
				.animate(
					{
						duration: 170,
						height: this.hiddenContentHeight,
						option: 'easeInOut',
					},
					() => {
						if (this.scrollViewRef)
						{
							this.scrollViewRef.scrollToEnd(true);
						}
					},
				)
				.start()
			;
		}

		onTokenInputChangeText(text)
		{
			this.state.token = text;
			this.props.onTokenSubmit(text);
		}

		onTokenSubmitEditing(text)
		{
			this.state.token = text;
		}

		getSwitcher()
		{
			return View(
				{
					style: {
						alignSelf: 'flex-start',
					},
				},
				this.buttonSwitcher = new ButtonSwitcher({
					states: {
						link: LinkButton({
							text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_GO_TO_TELEGRAM'),
							link: linkToBot,
							onClick: () => {
								this.isGoingToTelegram = true;
							},
						}),
						complete: CompleteButton({
							text: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_GO_TO_TELEGRAM'),
							onClick: () => {
								this.isGoingToTelegram = true;
								inAppUrl.open(linkToBot);
							},
						}),
					},
					startingState: 'link',
				}),
			);
		}

		getFirstStep()
		{
			return new SettingStep({
				withStep: true,
				number: 1,
				title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_STEP_1_TITLE'),
				description: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_STEP_1_DESCRIPTION'),
				icon: telegramIcon,
				additionalComponents: [
					this.getSwitcher(),
				],
				onLinkClick: () => this.openHelpdesk(),
				linksUnderline: false,
			});
		}

		getSecondStep()
		{
			return new SettingStep({
				withStep: true,
				number: 2,
				title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_STEP_2_TITLE'),
				description: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_STEP_2_DESCRIPTION'),
				icon: tokenIcon,
				additionalComponents: [
					this.tokenInput = TokenInput({
						onSubmitEditing: (text) => this.onTokenSubmitEditing(text),
						onChangeText: (text) => this.onTokenInputChangeText(text),
						token: this.state.token,
					}),
				],
			});
		}

		getLoaderStage()
		{
			return Loader({});
		}

		getCompleteStage()
		{
			return Complete({});
		}

		invalidTokenAlert()
		{
			this.tokenInput.setState({ borderColor: TokenInputColor.error });
			Alert.alert(
				Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_ALERT_INVALID_TOKEN_TITLE'),
				Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_ALERT_INVALID_TOKEN_DESCRIPTION'),
				null,
				Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_ALERT_BUTTON_NAME'),
			);
		}

		unknownErrorAlert()
		{
			Alert.alert(
				Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_ALERT_UNKNOWN_ERROR_TITLE'),
				Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_ALERT_UNKNOWN_ERROR_DESCRIPTION'),
				null,
				Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_REGISTRY_ALERT_BUTTON_NAME'),
			);
		}

		/**
		 * @private
		 */
		onAppActive()
		{
			if (this.isGoingToTelegram)
			{
				this.buttonSwitcher.switchTo('complete');

				this.isGoingToTelegram = false;
				if (Application.getApiVersion() < 49)
				{
					return;
				}

				const token = Application.copyFromClipboard();
				const regexToken = RegistryView.checkToken(token);
				if (regexToken !== null)
				{
					this.setState({ token: regexToken[0] }, () => this.props.onTokenSubmitFromClipboard(regexToken[0]));
				}
			}
		}

		registerEventHandler()
		{
			BX.addCustomEvent('onAppActive', this.onAppActiveHandler);
			Keyboard.on(Keyboard.Event.WillHide, this.onTokenInputBlurHandler);
			Keyboard.on(Keyboard.Event.WillShow, this.onTokenInputFocusedHandler);
		}

		unregisterEventHandler()
		{
			BX.removeCustomEvent('onAppActive', this.onAppActiveHandler);
			Keyboard.off(Keyboard.Event.WillHide, this.onTokenInputBlurHandler);
			Keyboard.off(Keyboard.Event.WillShow, this.onTokenInputFocusedHandler);
		}

		openHelpdesk()
		{
			helpdesk.openHelpArticle('17538378', 'helpdesk');
		}
	}

	const stages = {
		registry: 'registry',
		loader: 'loader',
		complete: 'complete',
	};

	const linkToBot = 'https://t.me/botfather';

	const telegramIcon = `<svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M33.625 17C33.625 26.1817 26.1817 33.625 17 33.625C7.81827 33.625 0.375 26.1817 0.375 17C0.375 7.81827 7.81827 0.375 17 0.375C26.1817 0.375 33.625 7.81827 33.625 17ZM21.4529 12.7413L14.4492 19.2218C14.203 19.4497 14.0444 19.7553 13.9994 20.0865L13.7607 21.8465C13.7292 22.0815 13.3977 22.1047 13.3322 21.8773L12.4147 18.6681C12.3098 18.302 12.4629 17.9109 12.7883 17.7112L21.2747 12.5084C21.4269 12.4153 21.5841 12.6206 21.4529 12.7413ZM24.851 9.16005L7.03049 16.0033C6.59056 16.172 6.59437 16.7919 7.03543 16.9562L11.3777 18.5694L13.0585 23.9499C13.166 24.2942 13.5893 24.4218 13.8703 24.193L16.2907 22.2291C16.5442 22.0232 16.9059 22.0131 17.1708 22.2044L21.5364 25.3596C21.837 25.5767 22.2629 25.413 22.3383 25.0516L25.5363 9.73895C25.6187 9.34406 25.2287 9.0146 24.851 9.16005Z" fill="#3FBBE8"/>
</svg>
`;

	const tokenIcon = `<svg width="34" height="23" viewBox="0 0 34 23" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M28.7694 11.3102C28.7827 11.1385 28.7912 10.9658 28.7912 10.791C28.7912 7.0923 25.7931 4.094 22.0944 4.094C21.2901 4.094 20.5192 4.23607 19.8049 4.49576C18.7391 2.20998 16.4219 0.625 13.7334 0.625C10.2525 0.625 7.39266 3.28114 7.06799 6.67683C3.48673 7.48949 0.8125 10.6897 0.8125 14.5167C0.8125 18.9579 4.41264 22.5581 8.85386 22.5581H27.8985C31.0516 22.5581 33.6074 20.3332 33.6074 16.8492C33.6074 12.3371 29.2129 11.1997 28.7694 11.3102ZM8.59193 15.6883L8.47196 17.5846H9.7628L9.63804 15.6883L11.1928 16.7516L11.8358 15.5953L10.1659 14.7623L11.8358 13.9293L11.1928 12.773L9.63804 13.8362L9.7628 11.94H8.47196L8.59193 13.8362L7.03716 12.773L6.39414 13.9293L8.06888 14.7623L6.39414 15.5953L7.03716 16.7516L8.59193 15.6883ZM16.2194 15.6883L16.0994 17.5846H17.3903L17.2655 15.6883L18.8203 16.7516L19.4633 15.5953L17.7934 14.7623L19.4633 13.9293L18.8203 12.773L17.2655 13.8362L17.3903 11.94H16.0994L16.2194 13.8362L14.6646 12.773L14.0216 13.9293L15.6964 14.7623L14.0216 15.5953L14.6646 16.7516L16.2194 15.6883ZM23.7269 17.5846L23.8469 15.6883L22.2921 16.7516L21.6491 15.5953L23.3238 14.7623L21.6491 13.9293L22.2921 12.773L23.8469 13.8362L23.7269 11.94H25.0178L24.893 13.8362L26.4478 12.773L27.0908 13.9293L25.4208 14.7623L27.0908 15.5953L26.4478 16.7516L24.893 15.6883L25.0178 17.5846H23.7269Z" fill="#2FC6F6"/>
</svg>

`;

	module.exports = { RegistryView, stages };
});
