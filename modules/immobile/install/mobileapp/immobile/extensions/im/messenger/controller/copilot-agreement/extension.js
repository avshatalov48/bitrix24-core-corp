/**
 * @module im/messenger/controller/copilot-agreement
 */
jn.define('im/messenger/controller/copilot-agreement', (require, exports, module) => {
	const { Color, Component, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { Box } = require('ui-system/layout/box');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');

	class CopilotUserAgreementWidget extends LayoutComponent
	{
		static open(props = {}, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				parentWidget.openWidget('layout', {
					modal: true,
					titleParams: {
						type: 'dialog',
						text: Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_TITLE'),
					},
					backdrop: {
						mediumPositionHeight: 550,
						onlyMediumPosition: true,
						forceDismissOnSwipeDown: true,
						horizontalSwipeAllowed: false,
					},
				}).then((layoutWidget) => {
					layoutWidget.showComponent(new CopilotUserAgreementWidget({
						...props,
						layoutWidget,
					}));
					resolve(layoutWidget);
				}).catch((err) => {
					console.error(err);
					reject(err);
				});
			});
		}

		constructor(props)
		{
			super(props);

			this.state = {
				isLoading: true,
				isAccepted: false,
			};
		}

		componentDidMount()
		{
			this.#query('ai.api.agreement.check')
				.then((response) => {
					this.setState({
						isLoading: false,
						isAccepted: Boolean(response?.data?.isAccepted),
					});
				})
				.catch((err) => {
					console.error(err);

					this.setState({
						isLoading: false,
						isAccepted: false,
					});
				});
		}

		#query(action)
		{
			const data = {
				agreementCode: 'AI_BOX_AGREEMENT',
				parameters: {
					bx_module: 'immobile',
					bx_context: 'chat-with-copilot',
				},
			};

			return BX.ajax.runAction(action, { data });
		}

		#accept()
		{
			this.#close();

			const notifyError = () => {
				navigator.notification.alert(Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_APPLY_ERROR'), null, '');
			};

			this.#query('ai.api.agreement.accept')
				.then((response) => {
					if (response?.data?.isAccepted === true)
					{
						this.props.onAccept?.();
					}
					else
					{
						notifyError();
					}
				})
				.catch((err) => {
					console.error(err);
					this.props.onAcceptError?.(err);
					notifyError();
				});
		}

		#decline()
		{
			this.props.onDecline?.();
			this.#close();
		}

		#close()
		{
			this.props.layoutWidget?.close(() => {
				this.props.onClose?.();
			});
		}

		render()
		{
			const isLoading = this.state.isLoading;
			const isReady = !isLoading;

			return Box(
				{
					backgroundColor: Color.bgContentPrimary,
					safeArea: { bottom: true },
				},
				isLoading && new LoadingScreenComponent(),
				isReady && StatusBlock({
					testId: 'CopilotUserAgreementWidget',
					image: this.#getImage(),
					description: this.#getText(),
					descriptionColor: Color.base1,
				}),
				isReady && this.#renderButtons(),
			);
		}

		#getImage()
		{
			return Image({
				style: {
					width: 108,
					height: 108,
				},
				svg: {
					content: '<svg width="85" height="85" viewBox="0 0 85 85" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.8" d="M3.06201 13.0102C3.06201 6.18495 8.04446 0.929485 14.0735 1.248L44.8782 2.84056C50.2019 3.11357 54.4563 8.39179 54.4563 14.6483V48.5699C54.4563 54.8037 50.2019 60.0819 44.8782 60.3549L14.0735 61.8564C8.04446 62.1522 3.06201 56.874 3.06201 50.0487V13.0102Z" fill="#B15EF5"/><path fill-rule="evenodd" clip-rule="evenodd" d="M44.8782 3.54579L14.0735 1.97597C8.40848 1.68021 3.74454 6.63991 3.74454 13.0102V50.0032C3.74454 56.3962 8.40848 61.3331 14.0735 61.0601L44.8782 59.5813C49.8834 59.331 53.8648 54.3941 53.8648 48.5471V14.58C53.8648 8.71024 49.8834 3.7733 44.8782 3.52304V3.54579ZM14.0735 1.24794C8.04446 0.92943 3.06201 6.20764 3.06201 13.0102V50.0487C3.06201 56.8739 8.04446 62.1521 14.0735 61.8564L44.8782 60.3548C50.2019 60.1046 54.4563 54.8264 54.4563 48.5698V14.6482C54.4563 8.41448 50.2019 3.11352 44.8782 2.84051L14.0735 1.24794Z" fill="white" fill-opacity="0.18"/><path fill-rule="evenodd" clip-rule="evenodd" d="M29.9764 43.8831C35.8461 43.7694 40.5328 38.2637 40.5328 31.5976C40.5328 24.9316 35.8461 19.4032 29.9764 19.2666C23.9702 19.1301 19.0332 24.6359 19.0332 31.5521C19.0332 38.4684 23.9929 43.9969 29.9764 43.8831ZM28.2473 22.338C28.1563 22.0423 27.7696 22.0195 27.6786 22.338L26.6093 25.6597C26.2907 26.6152 25.6537 27.3432 24.8119 27.6845L21.8543 28.913C21.5813 29.0268 21.5813 29.4591 21.8543 29.5728L24.8119 30.8241C25.6537 31.1881 26.3135 31.9162 26.6093 32.8717L27.6786 36.1933C27.7696 36.4891 28.1563 36.4891 28.2473 36.1933L29.3166 32.8717C29.6124 31.9389 30.2722 31.1881 31.0912 30.8469L33.9578 29.6411C34.2081 29.5273 34.2081 29.1178 33.9578 29.004L31.0912 27.7755C30.2722 27.4115 29.6124 26.6607 29.3166 25.7279L28.2473 22.4063V22.338ZM34.3673 31.9389C34.3218 31.7569 34.0943 31.7569 34.0488 31.9389L33.4345 33.85C33.2525 34.396 32.8885 34.8283 32.4107 35.033L30.7499 35.7383C30.5907 35.8066 30.5907 36.0568 30.7499 36.1023L32.4107 36.8076C32.8885 37.0124 33.2525 37.4219 33.4345 37.9679L34.0488 39.8562C34.0943 40.0382 34.3218 40.0155 34.3673 39.8562L34.9816 37.9451C35.1636 37.3991 35.5276 36.9669 35.9826 36.7621L37.6207 36.0568C37.7572 35.9886 37.7572 35.7611 37.6207 35.6928L35.9826 35.0103C35.5049 34.8055 35.1409 34.396 34.9816 33.85L34.3673 31.9617V31.9389Z" fill="white" fill-opacity="0.9"/><path d="M45.0601 32.3256C46.0612 32.3711 46.8347 33.3722 46.6982 34.5325C45.4924 43.8376 38.5761 51.2089 29.999 51.4819C21.4219 51.7549 12.0713 42.8821 12.0713 31.5521C12.0713 20.2221 20.2426 11.2319 29.999 11.6678C35.8934 11.9311 38.6216 13.9656 41.602 17.31C42.3528 18.1291 42.2618 19.4941 41.4882 20.2676C40.7147 21.0184 39.5544 20.8819 38.7809 20.0856C36.4603 17.674 33.3889 16.1497 29.999 16.0587C22.4002 15.854 16.121 22.793 16.121 31.5749C16.121 40.3567 22.423 47.3185 29.999 47.1137C37.5751 46.909 41.9205 41.4487 43.0353 34.3732C43.2173 33.2129 44.0591 32.2801 45.0601 32.3256Z" fill="white" fill-opacity="0.9"/><path d="M46.1069 52.3918C46.1069 46.0443 50.4069 40.8571 55.6396 40.7661L72.134 40.5159C76.9572 40.4476 80.8248 45.2026 80.8248 51.1633V70.6836C80.8248 76.6443 76.9799 81.8088 72.134 82.2638L55.6396 83.7881C50.4069 84.2659 46.1069 79.5337 46.1069 73.1862V52.3918Z" fill="#C8C9CD" fill-opacity="0.68"/><path fill-rule="evenodd" clip-rule="evenodd" d="M72.134 41.1984L55.6396 41.4714C50.7481 41.5397 46.6985 46.4311 46.6985 52.3691V73.1407C46.6985 79.0787 50.7254 83.5151 55.6396 83.0828L72.134 81.5813C76.6614 81.1718 80.2788 76.3031 80.2788 70.7291V51.186C80.2788 45.6121 76.6614 41.1301 72.134 41.1984ZM55.6396 40.7661C50.4069 40.8344 46.1069 46.0443 46.1069 52.3918V73.1862C46.1069 79.5337 50.4069 84.2659 55.6396 83.7881L72.134 82.2638C76.9572 81.8088 80.8248 76.6443 80.8248 70.6836V51.1633C80.8248 45.2026 76.9799 40.4476 72.134 40.5159L55.6396 40.7661Z" fill="white" fill-opacity="0.4"/><path d="M64.9671 48.9339C64.9671 48.2286 64.5121 47.6826 63.9206 47.6826C63.329 47.6826 62.874 48.2741 62.874 48.9794V50.2307C62.874 50.936 63.3518 51.482 63.9206 51.482C64.4893 51.482 64.9671 50.8677 64.9671 50.1625V48.9339Z" fill="white" fill-opacity="0.9"/><path d="M70.4728 61.1965C70.4728 63.2668 69.2442 65.2234 68.1977 66.7022C67.8337 67.2255 67.8109 67.7487 67.7882 68.2265C67.7427 69.273 67.7199 70.1148 63.9205 70.3651C60.0756 70.6381 60.0073 69.7963 59.9391 68.727C59.9163 68.2492 59.8708 67.726 59.5068 67.2482C58.4148 65.8604 57.209 64.0176 57.209 61.879C57.209 57.3516 60.2349 53.5749 63.9205 53.4157C67.6062 53.2564 70.4728 56.7373 70.4728 61.1737V61.1965Z" fill="white" fill-opacity="0.9"/><path d="M60.2803 73.4138C60.2803 72.572 60.849 71.8439 61.5543 71.7757L66.4458 71.4344C67.1283 71.3889 67.6743 72.0259 67.6743 72.8677C67.6743 73.7095 67.1283 74.4148 66.4458 74.4831L61.5543 74.8698C60.849 74.9153 60.2803 74.2783 60.2803 73.4365V73.4138Z" fill="white" fill-opacity="0.9"/><path d="M56.6859 50.5037C57.1637 50.0487 57.8235 50.1397 58.1875 50.6858L58.8245 51.6413C59.1885 52.1873 59.0975 53.0063 58.6425 53.4614C58.1875 53.9164 57.5049 53.8481 57.1409 53.3021L56.5039 52.3238C56.1399 51.7778 56.2309 50.9588 56.6859 50.5037Z" fill="white" fill-opacity="0.9"/><path d="M54.0466 56.8738C53.4778 56.7145 52.8636 57.1468 52.7271 57.8521C52.5678 58.5574 52.9091 59.2399 53.5006 59.3992L54.5244 59.6722C55.0932 59.8314 55.6847 59.3992 55.8439 58.6939C56.0032 57.9886 55.6619 57.3061 55.0932 57.1468L54.0694 56.8738H54.0466Z" fill="white" fill-opacity="0.9"/><path d="M74.7048 56.8511C74.5683 56.1913 73.9996 55.8273 73.4763 56.0321L72.5207 56.3961C71.9747 56.6009 71.6562 57.3061 71.8155 57.9659C71.952 58.6257 72.5207 58.9897 73.044 58.785L73.9996 58.4209C74.5456 58.2162 74.8641 57.5109 74.7048 56.8511Z" fill="white" fill-opacity="0.9"/><path d="M69.5631 50.2989C69.9044 49.7301 70.5414 49.6164 70.9964 50.0259C71.4287 50.4354 71.5197 51.2317 71.1784 51.7777L70.5642 52.756C70.2229 53.3248 69.5859 53.4385 69.1309 53.029C68.6758 52.6195 68.6076 51.8232 68.9488 51.2544L69.5631 50.2761V50.2989Z" fill="white" fill-opacity="0.9"/></svg>',
				},
			});
		}

		#getText()
		{
			const replaceLink = {
				'#LINK#': `[URL=${this.#getFullAgreementLink()}]`,
				'#/LINK#': '[/URL]',
			};

			const line1 = Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_PARAGRAPH_1');
			const line2 = this.state.isAccepted
				? Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_PARAGRAPH_2_ACCEPTED', replaceLink)
				: Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_PARAGRAPH_2_DECLINED', replaceLink);

			return `${line1}\n\n${line2}`;
		}

		#getFullAgreementLink()
		{
			const linksByRegion = {
				ru: 'https://www.bitrix24.ru/about/terms-of-use-ai.php',
				kz: 'https://www.bitrix24.kz/about/terms-of-use-ai.php',
				by: 'https://www.bitrix24.by/about/terms-of-use-ai.php',
				en: 'https://www.bitrix24.com/terms/bitrix24copilot-rules.php',
			};

			const region = jnExtensionData?.get('im:messenger/controller/copilot-agreement')?.region || 'en';

			return linksByRegion[region] || linksByRegion.en;
		}

		#renderButtons()
		{
			if (this.state.isAccepted)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingHorizontal: Component.paddingLrMore.toNumber(),
						paddingBottom: Indent.XL4.toNumber(),
					},
				},
				Button({
					testId: 'CopilotUserAgreementWidget_AcceptBtn',
					size: ButtonSize.L,
					text: Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_APPLY_BTN'),
					design: ButtonDesign.FILLED,
					stretched: true,
					onClick: () => this.#accept(),
					style: {
						marginBottom: Indent.L.toNumber(),
					},
				}),
				Button({
					testId: 'CopilotUserAgreementWidget_DeclineBtn',
					size: ButtonSize.L,
					text: Loc.getMessage('IMMOBILE_MESSENGER_COPILOT_AGREEMENT_POPUP_CANCEL_BTN'),
					design: ButtonDesign.PLAIN_NO_ACCENT,
					stretched: true,
					onClick: () => this.#decline(),
				}),
			);
		}
	}

	module.exports = { CopilotUserAgreementWidget };
});
