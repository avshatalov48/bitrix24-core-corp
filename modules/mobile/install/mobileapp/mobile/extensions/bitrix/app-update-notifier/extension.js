/**
 * @module app-update-notifier
 */
jn.define('app-update-notifier', (require, exports, module) => {

	const APP_STORE_URL = 'https://apps.apple.com/ru/app/bitrix24/id561683423';
	const GOOGLE_PLAY_URL = 'https://play.google.com/store/apps/details?id=com.bitrix24.android';

	const { Loc } = require('loc');

	/**
	 * @class AppUpdateNotifier
	 */
	class AppUpdateNotifier extends LayoutComponent
	{
		constructor(props)
		{
			props = (props || {})
			props.style = (props.style || {});
			super(props);

			this.layout = props.layout;
		}

		static open(props = {}, parentWidget = PageManager)
		{
			parentWidget.openWidget('layout', {
				modal: true,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 70,
					hideNavigationBar: true
				}}
			).then(widget => {
				widget.showComponent(new this({
					layout: widget,
					...props,
				}));
			});
		}

		render()
		{
			return View(
				{
					style: this.getContainerStyle(),
				},

				this.renderIcon(),
				this.renderText(),
				this.renderUpdateButton(),
			);
		}

		getContainerStyle()
		{
			const style = {
				flexDirection: 'column',
				flexGrow: 1,
				justifyContent: 'center',
				alignItems: 'center',
				paddingTop: 35,
				paddingBottom: 35,
			};

			if (this.props.style.backgroundColor)
			{
				style.backgroundColor = this.props.style.backgroundColor;
			}

			return style;
		}

		renderIcon()
		{
			return Image({
				style: {
					width: 110,
					height: 110,
					marginBottom: 20,
				},
				svg: (this.props.svg || styles.defaultSvg),
				resizeMode: 'contain'
			});
		}

		renderText()
		{
			return Text({
				style: {
					color: (this.props.style.textColor || '#828B95'),
					fontSize: 17,
					textAlign: 'center',
					paddingHorizontal: 10,
				},
				text: (this.props.text || Loc.getMessage('APP_UPDATE_NOTIFIER_NEED_UPDATE2')),
			});
		}

		renderUpdateButton()
		{
			if (Application.getPlatform() === 'android')
			{
				return this.renderAndroidButton();
			}

			return this.renderIphoneButton();
		}

		renderIphoneButton()
		{
			return this.renderButton(
				APP_STORE_URL,
				Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN_APP_STORE')
			);
		}

		renderAndroidButton()
		{
			return this.renderButton(
				GOOGLE_PLAY_URL,
				Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN_PLAY_MARKET')
			)
		}

		renderButton(link, text)
		{
			return View(
				{},
				Button({
					style: {
						color: '#ffffff',
						borderWidth: 1,
						borderRadius: 24,
						height: 48,
						borderColor: '#00a2e8',
						backgroundColor: '#00A2E8',
						marginTop: 20,
						paddingLeft: 20,
						paddingRight: 20,
						fontSize: 20,
					},
					text: text,
					onClick: () => {
						Application.openUrl(link);
					}
				}),
				Button({
					style: {
						marginTop: 25,
						color: '#0B66C3',
						fontSize: 16,
					},
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_CLOSE'),
					onClick: () => this.close()
				})
			)
		}

		close()
		{
			if (this.layout)
			{
				this.layout.close();
			}
		}
	}

	const styles = {
		defaultSvg: {
			content: '<svg xmlns="http://www.w3.org/2000/svg" width="110" height="110" fill="none" viewBox="0 0 110 110"><g filter="url(#a)" opacity=".866"><path fill="#FF5752" d="M54.99 90.55c-19.882 0-36-16.118-36-36.001 0-19.882 16.118-36 36-36 19.883 0 36 16.118 36 36 0 19.883-16.117 36-36 36Z"/></g><path fill="#fff" fill-rule="evenodd" d="M73.15 64.4 57.603 38.506c-1.198-1.988-4.053-1.988-5.225 0L36.83 64.401c-1.224 2.039.254 4.613 2.625 4.613H70.55c2.344 0 3.823-2.574 2.6-4.613ZM52.76 47.529c0-1.147.918-2.064 2.065-2.064h.28c1.147 0 2.065.917 2.065 2.064v7.723a2.056 2.056 0 0 1-2.065 2.064h-.28a2.056 2.056 0 0 1-2.065-2.064v-7.723Zm4.817 14.987a2.61 2.61 0 0 1-2.6 2.6 2.61 2.61 0 0 1-2.6-2.6 2.61 2.61 0 0 1 2.6-2.6 2.61 2.61 0 0 1 2.6 2.6Z" clip-rule="evenodd"/><path fill="#FF6E69" fill-rule="evenodd" d="M110 54.549c0 30.127-24.624 54.549-55 54.549S0 84.676 0 54.549 24.624 0 55 0s55 24.422 55 54.549Zm-4.508 0c0 27.637-22.606 50.041-50.492 50.041-27.886 0-50.492-22.404-50.492-50.041 0-27.637 22.606-50.04 50.492-50.04 27.886 0 50.492 22.403 50.492 50.04Z" clip-rule="evenodd" opacity=".1"/><defs><filter id="a" width="84" height="84" x="12.991" y="14.549" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="2"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.0741641 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_9171:164335"/><feBlend in="SourceGraphic" in2="effect1_dropShadow_9171:164335" result="shape"/></filter></defs></svg>'
		},
	}

	module.exports = { AppUpdateNotifier };

});