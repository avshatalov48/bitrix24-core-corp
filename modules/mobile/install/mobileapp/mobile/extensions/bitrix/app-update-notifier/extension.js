/**
 * @module app-update-notifier
 */
jn.define('app-update-notifier', (require, exports, module) => {
	const AppTheme = require('apptheme');
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
			props = (props || {});
			props.style = (props.style || {});
			super(props);

			this.layout = props.layout;
		}

		static open(props = {}, parentWidget = PageManager)
		{
			props.isOldBuild = BX.type.isBoolean(props.isOldBuild) ? props.isOldBuild : true;

			parentWidget.openWidget('layout', {
				modal: true,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: props.isOldBuild ? 70 : 100,
					hideNavigationBar: true,
					swipeContentAllowed: false,
					shouldResizeContent: false,
				},
			}).then((widget) => {
				widget.showComponent(new this({
					layout: widget,
					...props,
				}));
			}).catch(console.error);
		}

		render()
		{
			const elements = this.props.isOldBuild
				? [this.renderIcon(), this.renderText()]
				: [this.renderIcon(), this.renderHeaderText(), this.renderFeatureList()];

			return View(
				{
					style: this.getContainerStyle(),
				},
				...elements,
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
			const svg = this.props.svg
				? this.props.svg
				: (this.props.isOldBuild ? styles.defaultSvg : styles.headerIcon);

			return Image({
				style: {
					width: 110,
					height: 110,
					marginBottom: this.props.isOldBuild ? 20 : '2%',
				},
				svg,
				resizeMode: 'contain',
			});
		}

		renderText()
		{
			return Text({
				style: {
					color: (this.props.style.textColor || AppTheme.colors.base3),
					fontSize: 17,
					textAlign: 'center',
					paddingHorizontal: 10,
				},
				text: (this.props.text || Loc.getMessage('APP_UPDATE_NOTIFIER_NEED_UPDATE2')),
			});
		}

		renderHeaderText()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Text({
					style: {
						color: AppTheme.colors.base1,
						fontSize: 28,
						textAlign: 'center',
						paddingHorizontal: 10,
						fontWeight: 600,
					},
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_TITLE_MAIN'),
				}),
				Text({
					style: {
						color: AppTheme.colors.base3,
						fontSize: 16,
						textAlign: 'center',
						paddingHorizontal: 10,
						fontWeight: 400,
					},
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_TITLE_PARAGRAPH'),
				}),
			);
		}

		renderFeatureList()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						justifyContent: 'center',
						alignItems: 'flex-start',
						marginTop: '5%',
					},
				},
				this.featureItem(styles.elements.rocket(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_1')),
				this.featureItem(styles.elements.lightning(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_2')),
				this.featureItem(styles.elements.mobile(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_3')),
				this.featureItem(styles.elements.calendar(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_4')),
				this.featureItem(styles.elements.list(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_5')),
				this.featureItem(styles.elements.refresh(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_6')),
				this.featureItem(styles.elements.smart(), Loc.getMessage('APP_UPDATE_NOTIFIER_FEATHER_LIST_7')),
			);
		}

		featureItem(icon, text = 'test')
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
						marginBottom: '3%',
					},
				},
				Image({
					style: {
						width: 36,
						height: 36,
					},
					svg: { content: icon },
					resizeMode: 'contain',
				}),
				Text({
					style: {
						color: AppTheme.colors.base1,
						fontSize: 17,
						textAlign: 'center',
						marginLeft: 14,
						fontWeight: 500,
					},
					text,
				}),
			);
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
			const loc = this.props.isOldBuild
				? Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN_APP_STORE')
				: Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN');

			return this.renderButton(
				APP_STORE_URL,
				loc,
			);
		}

		renderAndroidButton()
		{
			const loc = this.props.isOldBuild
				? Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN_PLAY_MARKET')
				: Loc.getMessage('APP_UPDATE_NOTIFIER_OPEN');

			return this.renderButton(
				GOOGLE_PLAY_URL,
				loc,
			);
		}

		renderButton(link, text)
		{
			return View(
				{},
				Button({
					style: {
						color: AppTheme.colors.baseWhiteFixed,
						borderWidth: 1,
						borderRadius: this.props.isOldBuild ? 24 : 6,
						height: 48,
						borderColor: AppTheme.colors.accentMainPrimary,
						backgroundColor: AppTheme.colors.accentMainPrimary,
						marginTop: '2%',
						paddingLeft: this.props.isOldBuild ? 30 : '16%',
						paddingRight: this.props.isOldBuild ? 30 : '16%',
						fontSize: 20,
					},
					text,
					onClick: () => {
						Application.openUrl(link);
					},
				}),
				Button({
					style: {
						marginTop: '1%',
						color: AppTheme.colors.accentMainLinks,
						fontSize: 16,
					},
					text: Loc.getMessage('APP_UPDATE_NOTIFIER_CLOSE'),
					onClick: () => this.close(),
				}),
			);
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
			content: '<svg xmlns="http://www.w3.org/2000/svg" width="110" height="110" fill="none" viewBox="0 0 110 110"><g filter="url(#a)" opacity=".866"><path fill="#FF5752" d="M54.99 90.55c-19.882 0-36-16.118-36-36.001 0-19.882 16.118-36 36-36 19.883 0 36 16.118 36 36 0 19.883-16.117 36-36 36Z"/></g><path fill="#ffffff" fill-rule="evenodd" d="M73.15 64.4 57.603 38.506c-1.198-1.988-4.053-1.988-5.225 0L36.83 64.401c-1.224 2.039.254 4.613 2.625 4.613H70.55c2.344 0 3.823-2.574 2.6-4.613ZM52.76 47.529c0-1.147.918-2.064 2.065-2.064h.28c1.147 0 2.065.917 2.065 2.064v7.723a2.056 2.056 0 0 1-2.065 2.064h-.28a2.056 2.056 0 0 1-2.065-2.064v-7.723Zm4.817 14.987a2.61 2.61 0 0 1-2.6 2.6 2.61 2.61 0 0 1-2.6-2.6 2.61 2.61 0 0 1 2.6-2.6 2.61 2.61 0 0 1 2.6 2.6Z" clip-rule="evenodd"/><path fill="#FF6E69" fill-rule="evenodd" d="M110 54.549c0 30.127-24.624 54.549-55 54.549S0 84.676 0 54.549 24.624 0 55 0s55 24.422 55 54.549Zm-4.508 0c0 27.637-22.606 50.041-50.492 50.041-27.886 0-50.492-22.404-50.492-50.041 0-27.637 22.606-50.04 50.492-50.04 27.886 0 50.492 22.403 50.492 50.04Z" clip-rule="evenodd" opacity=".1"/><defs><filter id="a" width="84" height="84" x="12.991" y="14.549" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="2"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.0741641 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_9171:164335"/><feBlend in="SourceGraphic" in2="effect1_dropShadow_9171:164335" result="shape"/></filter></defs></svg>',
		},
		headerIcon: { content: '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="none" height="79" viewBox="0 0 98 79" width="98"><filter id="a" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse" height="37.7659" width="47.1504" x="29.0054" y="24.7284"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feOffset dy="5.00001"/><feGaussianBlur stdDeviation="4.00001"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0.0335938 0 0 0 0 0.382893 0 0 0 0 0.5375 0 0 0 0.2 0"/><feBlend in2="BackgroundImageFix" mode="normal" result="effect1_dropShadow_10642_260550"/><feBlend in="SourceGraphic" in2="effect1_dropShadow_10642_260550" mode="normal" result="shape"/></filter><linearGradient id="b" gradientUnits="userSpaceOnUse" x1="29" x2="29" y1="16.8121" y2="63.3885"><stop offset="0" stop-color="#7bd4fb"/><stop offset="1" stop-color="#2bb7f5"/></linearGradient><linearGradient id="c" gradientUnits="userSpaceOnUse" x1="58.9854" x2="58.9854" y1="46.0767" y2="34.0831"><stop offset="0" stop-color="#61df00"/><stop offset="1" stop-color="#4cca00"/></linearGradient><linearGradient id="d"><stop offset="0" stop-color="#bbed21"/><stop offset="1" stop-color="#bbed21" stop-opacity="0"/></linearGradient><linearGradient id="e" gradientUnits="userSpaceOnUse" x1="90.5" x2="72" xlink:href="#d" y1="28" y2="3"/><linearGradient id="f" gradientUnits="userSpaceOnUse" x1="14" x2="40.5" xlink:href="#d" y1="34.5" y2="78.5"/><linearGradient id="g" gradientUnits="userSpaceOnUse" x1="90.5" x2="68" y1="43.5" y2="76"><stop offset="0" stop-color="#29a8df" stop-opacity="0"/><stop offset=".510417" stop-color="#29a8df"/><stop offset="1" stop-color="#29a8df" stop-opacity="0"/></linearGradient><linearGradient id="h" gradientUnits="userSpaceOnUse" x1="12" x2="41.1973" y1="24.9412" y2="2.56202"><stop offset="0" stop-color="#29a8df" stop-opacity="0"/><stop offset=".427209" stop-color="#29a8df"/><stop offset="1" stop-color="#29a8df" stop-opacity="0"/></linearGradient><path clip-rule="evenodd" d="m38.2789 16.8121h28.0186c5.1246 0 9.2789 4.1543 9.2789 9.2789v28.0186c0 5.1246-4.1543 9.2789-9.2789 9.2789h-28.0186c-5.1246 0-9.2789-4.1543-9.2789-9.2789v-28.0186c0-5.1246 4.1543-9.2789 9.2789-9.2789z" fill="url(#b)" fill-rule="evenodd"/><g filter="url(#a)"><path clip-rule="evenodd" d="m61.0804 49.483h-16.7997c-.1437 0-.2862-.0054-.4273-.0159-3.7968-.0875-6.8478-3.229-6.848-7.0915.0011-1.8825.7412-3.6876 2.0576-5.0179.6748-.682 1.4726-1.2106 2.3406-1.5637-.0145-.1916-.0219-.3852-.0219-.5805.0012-1.9864.7822-3.8909 2.1712-5.2947 1.3889-1.4037 3.2721-2.1916 5.2353-2.1904 2.5147.0031 4.7349 1.2755 6.0692 3.2176.6336-.2271 1.3157-.3506 2.0263-.3502 3.139.0038 5.7173 2.4251 6.0161 5.5214 3.0078.6635 5.2586 3.3748 5.256 6.6165-.0031 3.7357-2.9977 6.7621-6.6897 6.7605-.1295 0-.2581-.0038-.3857-.0112z" fill="#fff" fill-rule="evenodd"/></g><path clip-rule="evenodd" d="m52.9885 34.0831c3.3118 0 5.9969 2.685 5.9969 5.9968s-2.6851 5.9968-5.9969 5.9968c-3.3117 0-5.9968-2.685-5.9968-5.9968s2.6851-5.9968 5.9968-5.9968z" fill="url(#c)" fill-rule="evenodd"/><path clip-rule="evenodd" d="m53.438 39.7596h2.0385c.4269-.0011.7741.316.7757.7087-.0007.1885-.0829.369-.2284.5018-.1455.1327-.3424.2069-.5473.2062h-2.7204c-.0307.0033-.0618.0051-.0935.0051-.4282 0-.7754-.3193-.7754-.7131v-3.4736c.0006-.1885.0825-.3691.2279-.502.1453-.1329.3422-.2073.5471-.2067.427-.0011.7742.3161.7758.7087z" fill="#fff" fill-rule="evenodd"/><path d="m90.6333 29 7.3667-13.0035-3.3944.9139c-.4045-17.435783-22.6056-13.53842-22.6056-13.53842 10.0455 0 15.6481 8.35922 14.6611 13.53842l-3.5389-.9139z" fill="url(#e)"/><path d="m11.9306 37-11.9306 20.6303 5.49745-1.45c.65501 27.6622 35.50255 21.3197 35.50255 21.3197-21.5-.5-24.2347-13.1028-22.6362-21.3197l5.7314 1.45z" fill="url(#f)"/><g clip-rule="evenodd" fill-rule="evenodd"><path d="m90.5 43c.5523 0 1 .4477 1 1v21.5c0 7.1797-5.8203 13-13 13h-7.5c-.5523 0-1-.4477-1-1s.4477-1 1-1h7.5c6.0751 0 11-4.9249 11-11v-21.5c0-.5523.4477-1 1-1z" fill="url(#g)" opacity=".2"/><path d="m12 24c-.5523 0-1-.4477-1-1v-10c0-7.1797 5.8203-13 13-13h16c.5523 0 1 .447714 1 1 0 .55228-.4477 1-1 1h-16c-6.0751 0-11 4.92487-11 11v10c0 .5523-.4477 1-1 1z" fill="url(#h)" opacity=".2"/></g></svg>' },
		elements: {
			rocket: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.2767 9.68868C16.2767 10.8181 15.3611 11.7337 14.2317 11.7337C13.1023 11.7337 12.1867 10.8181 12.1867 9.68868C12.1867 8.55927 13.1023 7.64371 14.2317 7.64371C15.3611 7.64371 16.2767 8.55927 16.2767 9.68868ZM15.0767 9.68868C15.0767 10.1553 14.6984 10.5337 14.2317 10.5337C13.765 10.5337 13.3867 10.1553 13.3867 9.68868C13.3867 9.22202 13.765 8.84371 14.2317 8.84371C14.6984 8.84371 15.0767 9.22202 15.0767 9.68868Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M18.2244 4.55504C18.8471 4.53937 19.3396 5.04672 19.3355 5.65146C19.3246 7.25982 19.2373 8.8836 18.5102 10.5086C18.0489 11.5396 17.3425 12.5456 16.2786 13.5472C16.286 13.5624 16.2936 13.5782 16.3014 13.5946C16.3875 13.7756 16.4953 14.0319 16.5788 14.3301C16.7389 14.9012 16.8466 15.7628 16.3517 16.5113C15.8987 17.1941 14.7157 18.5139 14.0187 19.2757C13.4361 19.9125 12.3978 19.653 12.1611 18.8395L11.5666 16.7958C11.4162 16.8811 11.2596 16.9684 11.0943 17.059C10.666 17.2937 10.1379 17.2145 9.79528 16.8745L6.78505 13.8868C6.47894 13.583 6.36164 13.1044 6.55748 12.677C6.63291 12.5124 6.71202 12.3586 6.79388 12.2133L5.01828 11.6967C4.20475 11.4601 3.94526 10.4218 4.58209 9.83917C5.34391 9.14223 6.66288 7.95967 7.34566 7.50669C8.09417 7.01182 8.95656 7.11903 9.5277 7.27905C9.82587 7.36259 10.0822 7.47039 10.2632 7.55647C10.2876 7.56808 10.3108 7.57935 10.3326 7.59019C12.4403 5.59583 15.1322 4.63289 18.2244 4.55504ZM9.45798 8.51755C9.3789 8.48813 9.29354 8.45965 9.20397 8.43456C8.74858 8.30697 8.31892 8.30201 8.00863 8.50693C7.45534 8.87413 6.32192 9.8776 5.53235 10.5965L7.50287 11.1698C7.57393 11.0775 7.64425 10.9877 7.71411 10.8985C7.96444 10.5786 8.20896 10.2662 8.4609 9.87929C8.7722 9.38976 9.10493 8.93614 9.45798 8.51755ZM12.6335 16.167L13.2613 18.3254C13.9804 17.5357 14.9841 16.4021 15.3511 15.8489C15.5559 15.5386 15.5509 15.1091 15.4233 14.6538C15.3945 14.551 15.3613 14.4537 15.3272 14.3652C14.9305 14.6785 14.4957 14.992 14.0194 15.3063L14.0059 15.3149C13.8585 15.4067 13.7177 15.4945 13.5818 15.5793C13.2458 15.7891 12.9391 15.9805 12.6335 16.167ZM18.1346 5.75817C14.4374 5.88122 11.4744 7.37499 9.47188 10.5258L9.46845 10.5312C9.18445 10.9677 8.89752 11.3339 8.64164 11.6607C8.57051 11.7515 8.50177 11.8392 8.43616 11.9246C8.1477 12.2999 7.8995 12.6525 7.68807 13.0924L10.5864 15.9689C11.4756 15.4793 12.1039 15.0872 12.9441 14.5628C13.0784 14.479 13.2181 14.3918 13.3651 14.3003C15.638 12.7994 16.8013 11.3898 17.4149 10.0185C18.0184 8.6696 18.12 7.29601 18.1346 5.75817Z" fill="${color}"/>
				<path d="M6.80684 15.0066C6.52872 14.7302 6.06111 14.8134 5.93027 15.1831C5.75318 15.6834 5.62664 16.2026 5.56355 16.7275C5.5322 16.9883 5.52414 17.198 5.52437 17.3467C5.52448 17.4211 5.52666 17.4803 5.52905 17.523C5.53025 17.5444 5.53149 17.5617 5.53255 17.5747L5.53398 17.5911L5.53454 17.5968L5.53477 17.5991C5.56145 17.8498 5.7556 18.0519 6.0053 18.0866L6.00716 18.0869L6.00946 18.0872L6.01539 18.088L6.03244 18.09C6.0461 18.0916 6.0643 18.0935 6.08684 18.0954C6.1319 18.0994 6.19437 18.1036 6.27259 18.1061C6.42894 18.1109 6.64871 18.1085 6.91837 18.0816C7.45548 18.0279 7.9865 17.9007 8.49381 17.7121C8.85646 17.5772 8.92948 17.116 8.65503 16.8433C8.49038 16.6797 8.24362 16.6351 8.02474 16.7124C7.54694 16.8811 7.12886 16.957 6.80914 16.989C6.74865 16.995 6.69172 16.9995 6.6386 17.0027C6.64264 16.9575 6.64763 16.9094 6.65374 16.8585C6.69167 16.543 6.77296 16.1213 6.94276 15.6226C7.01606 15.4074 6.96814 15.1669 6.80684 15.0066Z" fill="${color}"/>
				</svg>
			`,
			lightning: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M13.1836 5.76156L6.55442 13.3777H11.4656C11.6564 13.3777 11.8359 13.4685 11.949 13.6223C12.062 13.776 12.0952 13.9744 12.0383 14.1566L10.8471 17.9697L17.1858 10.4656H12.5505C12.3601 10.4656 12.1811 10.3752 12.068 10.2221C11.9548 10.069 11.9211 9.87135 11.977 9.68938L13.1836 5.76156ZM13.5161 3.55181C14.0834 2.90004 15.1379 3.48598 14.8842 4.31196L13.3625 9.26557H18.4781C18.7116 9.26557 18.9239 9.40108 19.0223 9.61289C19.1206 9.82471 19.0871 10.0743 18.9364 10.2528L10.486 20.2569C9.92279 20.9236 8.85097 20.3352 9.11121 19.5021L10.6495 14.5777H5.23673C5.00159 14.5777 4.78812 14.4403 4.69067 14.2263C4.59323 14.0123 4.62977 13.7611 4.78415 13.5838L13.5161 3.55181Z" fill="${color}"/>
				</svg>
			`,
			mobile: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="36" height="37" viewBox="0 0 36 37" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M9.0249 8.5869C9.0249 6.74908 10.5264 5.27356 12.3609 5.27356H23.639C25.4734 5.27356 26.9748 6.74912 26.9748 8.5869V27.7602C26.9748 29.5979 25.4734 31.0736 23.639 31.0736H12.3609C10.5264 31.0736 9.0249 29.598 9.0249 27.7602V8.5869ZM12.3609 7.07356C11.5046 7.07356 10.8249 7.759 10.8249 8.5869V27.7602C10.8249 28.5881 11.5047 29.2736 12.3609 29.2736H23.639C24.4951 29.2736 25.1748 28.5881 25.1748 27.7602V8.5869C25.1748 7.75896 24.4951 7.07356 23.639 7.07356H12.3609Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M14.0999 9.47354C14.0999 8.97648 14.5028 8.57354 14.9999 8.57354H20.9999C21.4969 8.57354 21.8999 8.97648 21.8999 9.47354C21.8999 9.9706 21.4969 10.3735 20.9999 10.3735H14.9999C14.5028 10.3735 14.0999 9.9706 14.0999 9.47354Z" fill="${color}"/>
				<path d="M17.9999 27.9243C19.0361 27.9243 19.8761 27.0842 19.8761 26.0479C19.8761 25.0119 19.0361 24.1718 17.9999 24.1718C16.9636 24.1718 16.1236 25.0119 16.1236 26.0479C16.1236 27.0842 16.9636 27.9243 17.9999 27.9243Z" fill="${color}"/>
				</svg>
			`,
			calendar: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="36" height="37" viewBox="0 0 36 37" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M25.9568 9.96956H10.0432C8.80053 9.96956 7.79316 10.9769 7.79316 12.2196V26.0913C7.79316 27.3339 8.80052 28.3413 10.0432 28.3413H25.9568C27.1994 28.3413 28.2068 27.3339 28.2068 26.0913V12.2196C28.2068 10.9769 27.1994 9.96956 25.9568 9.96956ZM10.0432 8.16956C7.80641 8.16956 5.99316 9.98281 5.99316 12.2196V26.0913C5.99316 28.328 7.80641 30.1413 10.0432 30.1413H25.9568C28.1935 30.1413 30.0068 28.328 30.0068 26.0913V12.2196C30.0068 9.9828 28.1935 8.16956 25.9568 8.16956H10.0432Z" fill="#29A8DF"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M12.8419 6.02502C12.1681 6.02502 11.6218 6.57125 11.6218 7.24506V11.7116C11.6218 12.3854 12.1681 12.9316 12.8419 12.9316C13.5157 12.9316 14.0619 12.3854 14.0619 11.7116V7.24506C14.0619 6.57125 13.5157 6.02502 12.8419 6.02502Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M23.1749 6.02502C22.5011 6.02502 21.9549 6.57125 21.9549 7.24506V11.7116C21.9549 12.3854 22.5011 12.9316 23.1749 12.9316C23.8487 12.9316 24.3949 12.3854 24.3949 11.7116V7.24506C24.3949 6.57125 23.8487 6.02502 23.1749 6.02502Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M22.7557 15.0737C22.0929 15.0737 21.5557 15.6109 21.5557 16.2737V17.5428C21.5557 18.2056 22.0929 18.7428 22.7557 18.7428H23.9678C24.6306 18.7428 25.1678 18.2056 25.1678 17.5428V16.2737C25.1678 15.6109 24.6306 15.0737 23.9678 15.0737H22.7557Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M17.4808 15.0737C16.818 15.0737 16.2808 15.6109 16.2808 16.2737V17.5428C16.2808 18.2056 16.818 18.7428 17.4808 18.7428H18.6929C19.3557 18.7428 19.8929 18.2056 19.8929 17.5428V16.2737C19.8929 15.6109 19.3557 15.0737 18.6929 15.0737H17.4808Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M11.9605 15.0737C11.2978 15.0737 10.7605 15.6109 10.7605 16.2737V17.5428C10.7605 18.2056 11.2978 18.7428 11.9605 18.7428H13.1727C13.8354 18.7428 14.3727 18.2056 14.3727 17.5428V16.2737C14.3727 15.6109 13.8354 15.0737 13.1727 15.0737H11.9605Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M11.9605 20.7749C11.2978 20.7749 10.7605 21.3121 10.7605 21.9749V23.244C10.7605 23.9068 11.2978 24.444 11.9605 24.444H13.1727C13.8354 24.444 14.3727 23.9068 14.3727 23.244V21.9749C14.3727 21.3121 13.8354 20.7749 13.1727 20.7749H11.9605Z" fill="${color}"/>
				</svg>
			`,
			list: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="36" height="37" viewBox="0 0 36 37" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M26.2003 9.56625C26.1228 9.60317 26.0333 9.59932 25.9538 9.56703C25.6927 9.46104 25.4071 9.40266 25.108 9.40266H10.7385C9.49584 9.40266 8.48848 10.41 8.48848 11.6527V24.6206C8.48848 25.8633 9.49584 26.8706 10.7385 26.8706H25.108C26.3506 26.8706 27.358 25.8633 27.358 24.6206V19.8865C27.358 19.8122 27.3856 19.7405 27.4354 19.6854L28.6354 18.3578C28.8196 18.1541 29.158 18.2844 29.158 18.559V24.6206C29.158 26.8574 27.3447 28.6706 25.108 28.6706H10.7385C8.50172 28.6706 6.68848 26.8574 6.68848 24.6206V11.6527C6.68848 9.41591 8.50172 7.60266 10.7385 7.60266H25.108C25.9024 7.60266 26.6434 7.83138 27.2687 8.22655C27.4154 8.31927 27.4369 8.52009 27.3238 8.65179L27.1863 8.812C26.9106 9.13316 26.5737 9.38835 26.2003 9.56625Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M11.9211 21.5505C11.9211 21.0534 12.3241 20.6505 12.8211 20.6505H20.9518C21.4488 20.6505 21.8518 21.0534 21.8518 21.5505C21.8518 22.0475 21.4488 22.4505 20.9518 22.4505H12.8211C12.3241 22.4505 11.9211 22.0475 11.9211 21.5505Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M11.9211 16.3036C11.9211 15.8065 12.3241 15.4036 12.8211 15.4036H18.9191C19.4162 15.4036 19.8191 15.8065 19.8191 16.3036C19.8191 16.8006 19.4162 17.2036 18.9191 17.2036H12.8211C12.3241 17.2036 11.9211 16.8006 11.9211 16.3036Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M32.5201 8.87556C32.8909 9.20649 32.9233 9.77542 32.5924 10.1463L26.7617 16.6806C26.4323 17.0498 25.8667 17.0838 25.4954 16.7569L21.7849 13.4897C21.4119 13.1612 21.3758 12.5925 21.7042 12.2195C22.0327 11.8464 22.6014 11.8103 22.9745 12.1388L26.0139 14.8151L31.2493 8.94787C31.5803 8.577 32.1492 8.54462 32.5201 8.87556Z" fill="${color}"/>
				</svg>
			`,
			refresh: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="36" height="37" viewBox="0 0 36 37" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M21.3062 12.674C21.3062 12.1769 21.7092 11.774 22.2062 11.774H27.7537C28.2508 11.774 28.6537 12.1769 28.6537 12.674C28.6537 13.171 28.2508 13.574 27.7537 13.574H22.2062C21.7092 13.574 21.3062 13.171 21.3062 12.674Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M27.7538 6.22278C28.2508 6.22278 28.6538 6.62572 28.6538 7.12278L28.6537 12.674C28.6537 13.171 28.2508 13.574 27.7537 13.574C27.2567 13.574 26.8538 13.171 26.8538 12.6739V7.12278C26.8538 6.62572 27.2567 6.22278 27.7538 6.22278Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M7.56848 23.6242C7.56848 23.1272 7.97143 22.7242 8.46848 22.7242H14.0185C14.5155 22.7242 14.9185 23.1272 14.9185 23.6242C14.9185 24.1213 14.5155 24.5242 14.0185 24.5242H8.46848C7.97143 24.5242 7.56848 24.1213 7.56848 23.6242Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M8.46848 22.7242C8.96554 22.7242 9.36848 23.1272 9.36848 23.6242L9.36848 29.1742C9.36848 29.6713 8.96554 30.0742 8.46848 30.0742C7.97143 30.0742 7.56848 29.6713 7.56848 29.1742L7.56848 23.6242C7.56848 23.1272 7.97143 22.7242 8.46848 22.7242Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M26.0523 12.3365C23.6555 8.97443 19.2154 7.51543 15.0739 8.69003C11.0592 9.82868 8.84106 13.4021 8.33162 17.77C8.27404 18.2637 7.82713 18.6173 7.33342 18.5597C6.83971 18.5021 6.48616 18.0552 6.54374 17.5615C7.10538 12.746 9.63422 8.36182 14.5827 6.95833C19.4045 5.5908 24.6492 7.26749 27.518 11.2916C27.8065 11.6964 27.7123 12.2584 27.3076 12.5469C26.9028 12.8355 26.3408 12.7413 26.0523 12.3365Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M28.8824 17.7212C29.3761 17.7788 29.7295 18.2258 29.6718 18.7195C29.1095 23.5309 26.5778 27.9099 21.6258 29.3115C16.801 30.6771 11.5523 29.0031 8.68079 24.9833C8.39187 24.5788 8.48553 24.0167 8.88999 23.7278C9.29445 23.4389 9.85655 23.5325 10.1455 23.937C12.5443 27.2951 16.9891 28.7532 21.1355 27.5795C25.1548 26.4419 27.3743 22.8723 27.884 18.5106C27.9417 18.0169 28.3887 17.6635 28.8824 17.7212Z" fill="${color}"/>
				</svg>
			`,
			smart: (color = AppTheme.colors.accentMainPrimaryalt) => `
				<svg width="36" height="37" viewBox="0 0 36 37" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M23.8444 14.26C24.2108 14.5958 24.2355 15.1651 23.8997 15.5315L17.9099 22.0659C17.5819 22.4237 17.0293 22.4569 16.6607 22.141L12.849 18.8739C12.4717 18.5504 12.4279 17.9822 12.7514 17.6048C13.0749 17.2274 13.6431 17.1837 14.0205 17.5072L17.1712 20.2079L22.5728 14.3152C22.9087 13.9488 23.478 13.9241 23.8444 14.26Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M25.8525 11.3818C25.2379 10.6977 24.5344 10.1012 23.764 9.60753C23.3864 9.36556 23.2024 8.90081 23.3512 8.47775C23.5339 7.95834 24.1341 7.71662 24.6015 8.00758C25.5599 8.60413 26.4331 9.33472 27.1915 10.1788C27.5237 10.5486 27.4933 11.1176 27.1235 11.4498C26.7538 11.782 26.1847 11.7516 25.8525 11.3818ZM7.32848 23.0214C6.95543 22.1772 6.67503 21.2769 6.50173 20.3312C6.32843 19.3854 6.27136 18.4442 6.32081 17.5225C6.34744 17.0262 6.77139 16.6454 7.26773 16.672C7.76408 16.6987 8.14485 17.1226 8.11822 17.619C8.07627 18.4008 8.12457 19.2008 8.27225 20.0067C8.41994 20.8126 8.65845 21.5778 8.97491 22.2939C9.1758 22.7486 8.9701 23.28 8.51545 23.4809C8.0608 23.6818 7.52938 23.4761 7.32848 23.0214ZM7.62255 15.0233C7.15904 14.8438 6.9288 14.3226 7.1083 13.8591C7.78976 12.0994 8.87956 10.5205 10.2833 9.25939C10.653 8.92719 11.2221 8.95763 11.5543 9.32738C11.8864 9.69713 11.856 10.2662 11.4863 10.5984C10.293 11.6704 9.36612 13.0132 8.78683 14.5091C8.60733 14.9726 8.08606 15.2028 7.62255 15.0233ZM10.8284 25.9309C10.2544 26.2033 10.1189 26.9586 10.6069 27.3654C11.6082 28.2001 12.7423 28.8697 13.9635 29.3426C14.427 29.5221 14.9483 29.2919 15.1278 28.8284C15.3073 28.3648 15.077 27.8436 14.6135 27.6641C13.6189 27.2789 12.6919 26.74 11.8673 26.0714C11.5735 25.8332 11.1701 25.7688 10.8284 25.9309ZM12.97 8.41101C12.7691 7.95636 12.9748 7.42494 13.4295 7.22404C14.2737 6.85099 15.174 6.57059 16.1197 6.39729C17.0655 6.22399 18.0067 6.16692 18.9284 6.21637C19.4247 6.243 19.8055 6.66695 19.7789 7.16329C19.7522 7.65964 19.3283 8.04041 18.8319 8.01378C18.0501 7.97183 17.2501 8.02013 16.4442 8.16781C15.6383 8.3155 14.8731 8.554 14.157 8.87047C13.7023 9.07136 13.1709 8.86566 12.97 8.41101ZM16.7765 29.1832C16.8031 28.6868 17.2271 28.306 17.7234 28.3327C18.5052 28.3746 19.3052 28.3263 20.1111 28.1786C20.9171 28.031 21.6822 27.7925 22.3984 27.476C22.853 27.2751 23.3844 27.4808 23.5853 27.9354C23.7862 28.3901 23.5805 28.9215 23.1259 29.1224C22.2816 29.4955 21.3813 29.7759 20.4356 29.9492C19.4899 30.1225 18.5486 30.1795 17.627 30.1301C17.1306 30.1035 16.7498 29.6795 16.7765 29.1832ZM25.0011 27.0191C24.6689 26.6493 24.6993 26.0803 25.0691 25.7481C26.2623 24.676 27.1892 23.3332 27.7685 21.8374C27.948 21.3739 28.4693 21.1436 28.9328 21.3231C29.3963 21.5026 29.6265 22.0239 29.447 22.4874C28.7656 24.2471 27.6758 25.8259 26.2721 27.0871C25.9023 27.4193 25.3333 27.3888 25.0011 27.0191ZM29.2876 19.6744C28.7913 19.6478 28.4105 19.2238 28.4371 18.7275C28.4791 17.9456 28.4308 17.1457 28.2831 16.3398C28.1354 15.5338 27.8969 14.7687 27.5804 14.0525C27.3795 13.5979 27.5852 13.0665 28.0399 12.8656C28.4945 12.6647 29.026 12.8704 29.2269 13.325C29.5999 14.1693 29.8803 15.0696 30.0536 16.0153C30.2269 16.961 30.284 17.9023 30.2345 18.8239C30.2079 19.3203 29.7839 19.7011 29.2876 19.6744Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M16.9575 4.28251C17.3411 3.9664 17.9083 4.02109 18.2244 4.40467L20.123 6.70845C20.4147 7.06234 20.3936 7.57893 20.0741 7.90788L17.9332 10.1121C17.5869 10.4687 17.0171 10.477 16.6605 10.1307C16.304 9.78437 16.2957 9.21458 16.642 8.85802L18.2217 7.2316L16.8354 5.54943C16.5193 5.16585 16.5739 4.59863 16.9575 4.28251Z" fill="${color}"/>
				<path fill-rule="evenodd" clip-rule="evenodd" d="M10.1898 25.7886C10.5229 25.4197 11.092 25.3906 11.461 25.7237C12.5286 26.6876 13.7884 27.4141 15.1574 27.8554C16.5264 28.2966 17.9734 28.4424 19.403 28.2833C20.8325 28.1242 22.212 27.6637 23.4505 26.9323C24.689 26.2008 25.7582 25.2151 26.5877 24.04C27.4173 22.8649 27.9881 21.5273 28.2627 20.1154C28.5372 18.7035 28.5093 17.2495 28.1805 15.8492C27.8519 14.4489 27.2299 13.1342 26.3558 11.992C25.4817 10.8497 24.3753 9.9058 23.1095 9.22257C22.6721 8.98646 22.509 8.44048 22.7451 8.00308C22.9812 7.56568 23.5272 7.4025 23.9646 7.63861C25.4543 8.44274 26.7565 9.55367 27.7853 10.8981C28.8141 12.2425 29.5461 13.7897 29.9329 15.4378C30.3198 17.0859 30.3527 18.7973 30.0296 20.459C29.7064 22.1208 29.0346 23.6951 28.0583 25.0781C27.082 26.4611 25.8235 27.6213 24.3658 28.4822C22.9082 29.343 21.2846 29.885 19.6021 30.0723C17.9196 30.2596 16.2165 30.0879 14.6053 29.5686C12.994 29.0493 11.5112 28.1942 10.2547 27.0597C9.88578 26.7266 9.85673 26.1575 10.1898 25.7886Z" fill="${color}"/>
				</svg>
			`,

		},
	};

	module.exports = { AppUpdateNotifier };
});
