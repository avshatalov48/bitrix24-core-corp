/**
 * @module crm/timeline/item/ui/icon/logo
 */
jn.define('crm/timeline/item/ui/icon/logo', (require, exports, module) => {
	const { TimelineItemCalendar } = require('crm/timeline/item/ui/icon/calendar');
	const AppTheme = require('apptheme');
	const isIOS = Application.getPlatform() === 'ios';
	const iconSize = isIOS ? 47 : 24;
	const logoWrapperSize = 84;

	const IconType = {
		failure: AppTheme.colors.accentMainAlert,
		success: AppTheme.colors.accentMainSuccess,

		get(type)
		{
			return IconType[type] || '';
		},
	};

	class TimelineItemIconLogo extends LayoutComponent
	{
		get icon()
		{
			return BX.prop.getString(this.props, 'icon', null);
		}

		get iconType()
		{
			return BX.prop.getString(this.props, 'iconType', null);
		}

		get hasPlayer()
		{
			return BX.prop.getBoolean(this.props, 'hasPlayer', false);
		}

		get play()
		{
			return BX.prop.getBoolean(this.props, 'play', false);
		}

		get isLoading()
		{
			return BX.prop.getBoolean(this.props, 'isLoading', false);
		}

		get isLoaded()
		{
			return BX.prop.getBoolean(this.props, 'isLoaded', false);
		}

		get inCircle()
		{
			return BX.prop.getBoolean(this.props, 'inCircle', false);
		}

		get isCalendar()
		{
			const { icon } = this.props;

			return icon && icon === 'calendar';
		}

		get timestamp()
		{
			return BX.prop.getInteger(this.props, 'timestamp', null);
		}

		get addIcon()
		{
			return BX.prop.getString(this.props, 'addIcon', null);
		}

		get addIconType()
		{
			return BX.prop.getString(this.props, 'addIconType', null);
		}

		get backgroundUrl()
		{
			return BX.prop.getString(this.props, 'backgroundUrl', null);
		}

		render()
		{
			if (this.isCalendar)
			{
				return this.renderCalendarIcon();
			}

			return View(
				{
					style: {
						width: logoWrapperSize,
						height: logoWrapperSize,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				this.renderCircle(),
				this.renderLogo(),
				this.renderAddIcon(),
			);
		}

		renderCalendarIcon()
		{
			return View(
				{
					style: {
						width: logoWrapperSize,
						height: logoWrapperSize,
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				TimelineItemCalendar({
					timestamp: this.timestamp,
				}),
				this.renderAddIcon(),
			);
		}

		renderCircle()
		{
			if (!this.inCircle)
			{
				return null;
			}

			return Shadow(
				{
					offset: { x: 0, y: 3 },
					radius: 6,
					color: this.iconType === 'failure' ? AppTheme.colors.accentSoftRed2 : AppTheme.colors.accentSoftBlue2,
					style: {
						borderRadius: 30,
						position: 'absolute',
						top: 11,
						left: 8,
					},
				},
				View(
					{
						style: {
							width: 56,
							height: 56,
							borderRadius: 28,
							backgroundColor: AppTheme.colors.base8,
						},
					},
				),
			);
		}

		renderLogo()
		{
			if (!this.icon)
			{
				return null;
			}

			return (this.hasPlayer ? this.getPlayer() : this.getLogoImage());
		}

		getPlayer()
		{
			return View(
				{
					style: {
						width: logoWrapperSize,
						height: logoWrapperSize,
						position: 'relative',
					},
				},
				Image({
					style: {
						width: logoWrapperSize,
						height: logoWrapperSize,
					},
					resizeMode: 'contain',
					svg: {
						content: this.getPlayerContent(),
					},
				}),
				this.isLoading && !this.isLoaded && Loader({
					style: {
						width: 20,
						height: 20,
						position: 'absolute',
						top: 32,
						left: 32,
					},
					tintColor: AppTheme.colors.base6,
					animating: true,
				}),
			);
		}

		getPlayerContent()
		{
			if (this.isLoading && !this.isLoaded)
			{
				return LogoIcons.playLoading();
			}

			return this.play ? LogoIcons.pausePlayer() : LogoIcons.playPlayer();
		}

		getLogoImage()
		{
			const inCircle = this.inCircle;

			const imageProps = {
				style: {
					width: inCircle ? 47 : 64,
					height: inCircle ? 47 : 64,
				},
				resizeMode: 'contain',
			};

			if (this.backgroundUrl)
			{
				imageProps.uri = currentDomain + encodeURI(this.backgroundUrl);
			}
			else
			{
				imageProps.svg = {
					content: this.getIconContent(),
				};
			}

			return Image(imageProps);
		}

		getIconContent()
		{
			const content = LogoIcons[this.icon] || LogoIcons.empty;

			return content(IconType.get(this.iconType));
		}

		renderAddIcon()
		{
			if (!this.addIcon || this.hasPlayer)
			{
				return null;
			}

			const addIconContent = this.getAdditionalIcon();
			if (addIconContent.length === 0)
			{
				return null;
			}

			return View(
				{
					style: {
						width: 34,
						height: 34,
						position: 'absolute',
						bottom: 3,
						right: 3,
					},
				},
				Image(
					{
						style: {
							width: 34,
							height: 34,
						},
						resizeMode: 'center',
						svg: {
							content: addIcons.background,
						},
					},
				),
				View(
					{
						style: {
							width: 34,
							height: 34,
							marginTop: -34,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					Image({
						style: {
							width: 34,
							height: 34,
						},
						resizeMode: 'contain',
						svg: {
							content: addIconContent,
						},
					}),
				),
			);
		}

		getAdditionalIcon()
		{
			const empty = () => '';
			const content = addIcons[this.addIcon] || empty;

			return content(IconType.get(this.addIconType));
		}
	}

	const LogoIcons = {
		email: (color = '') => `<svg width="71" height="54" viewBox="0 0 71 54" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_6853_2946)"><g clip-path="url(#clip0_6853_2946)"><rect x="9" y="7" width="53" height="36" rx="2" fill="${AppTheme.colors.bgContentPrimary}"/><path d="M34.4971 20.3672L7.80979 44.3621H63.1902L36.5029 20.3672C35.9326 19.8544 35.0674 19.8544 34.4971 20.3672Z" fill="${AppTheme.colors.bgContentPrimary}" stroke="${AppTheme.colors.accentSoftBlue1}"/><path d="M34.506 29.6597L5.95491 4.39655H65.0451L36.494 29.6597C35.9265 30.1619 35.0735 30.1619 34.506 29.6597Z" fill="${AppTheme.colors.bgContentPrimary}" stroke="${AppTheme.colors.accentSoftBlue1}"/><path d="M9 11.3448L10.8706 10.1035V12.5862L9 13.8276V11.3448Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M9 16.931L10.8706 15.6897V18.1724L9 19.4138V16.931Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M9 22.5172L10.8706 21.2758V23.7586L9 25V22.5172Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M9 28.1034L10.8706 26.8621V29.3448L9 30.5862V28.1034Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M9 33.6897L10.8706 32.4483V34.931L9 36.1724V33.6897Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M9 39.2759L10.8706 38.0345V40.5172L9 41.7586V39.2759Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M60.1294 11.3448L62 10.1035V12.5862L60.1294 13.8276V11.3448Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M60.1294 16.931L62 15.6897V18.1724L60.1294 19.4138V16.931Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M60.1294 22.5172L62 21.2758V23.7586L60.1294 25V22.5172Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M60.1294 28.1034L62 26.8621V29.3448L60.1294 30.5862V28.1034Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M60.1294 33.6897L62 32.4483V34.931L60.1294 36.1724V33.6897Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M60.1294 39.2759L62 38.0345V40.5172L60.1294 41.7586V39.2759Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M13.3647 41.1379L12.1177 43L14.6118 43L15.8589 41.1379L13.3647 41.1379Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M18.6647 41.1379L17.4177 43L19.9118 43L21.1588 41.1379L18.6647 41.1379Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M23.9647 41.1379L22.7177 43L25.2118 43L26.4588 41.1379L23.9647 41.1379Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M28.9529 41.1379L27.7059 43L30.2 43L31.447 41.1379L28.9529 41.1379Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M33.9412 41.1379L32.6941 43L35.1882 43L36.4353 41.1379L33.9412 41.1379Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M39.2412 41.1379L37.9941 43L40.4883 43L41.7353 41.1379L39.2412 41.1379Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M44.5412 41.1379L43.2941 43L45.7882 43L47.0353 41.1379L44.5412 41.1379Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M49.5294 41.1379L48.2823 43L50.7765 43L52.0235 41.1379L49.5294 41.1379Z" fill="${AppTheme.colors.accentMainPrimary}"/><path d="M55.1412 41.1379L53.8941 43L56.3882 43L57.6353 41.1379L55.1412 41.1379Z" fill="${AppTheme.colors.accentBrandBlue}"/></g></g><defs><filter id="filter0_d_6853_2946" x="0" y="0" width="71" height="54" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="4.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6853_2946"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6853_2946" result="shape"/></filter><clipPath id="clip0_6853_2946"><rect x="9" y="7" width="53" height="36" rx="4" fill="white"/></clipPath></defs></svg>`,
		sms: () => `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0096 12.0624C9.80049 12.0624 8.00962 13.8532 8.00964 16.0624L8.00977 31.8977C8.00979 34.1068 9.80065 35.8976 12.0098 35.8976H15.3777V40.1099C15.3777 40.8353 16.2658 41.1863 16.7616 40.6568L21.219 35.8976H36.2158C38.425 35.8976 40.2158 34.1067 40.2158 31.8976L40.2157 16.0623C40.2156 13.8532 38.4248 12.0624 36.2157 12.0624H12.0096ZM17.3133 27.0724C17.5919 26.6463 17.7313 26.1587 17.7313 25.6094C17.7313 25.0116 17.5829 24.5158 17.286 24.122C16.9891 23.7283 16.459 23.3597 15.6957 23.0164C14.9 22.6529 14.4123 22.4025 14.2326 22.2652C14.0529 22.1279 13.963 21.9724 13.963 21.7987C13.963 21.6371 14.0337 21.5018 14.1751 21.3928C14.3164 21.2837 14.5426 21.2292 14.8536 21.2292C15.4554 21.2292 16.1703 21.419 16.9982 21.7987L17.7313 19.9509C16.7781 19.5268 15.8431 19.3148 14.9263 19.3148C13.8883 19.3148 13.0725 19.543 12.4787 19.9994C11.885 20.4558 11.5882 21.0919 11.5882 21.9077C11.5882 22.3439 11.6578 22.7216 11.7972 23.0406C11.9365 23.3597 12.1506 23.6424 12.4394 23.8888C12.7281 24.1352 13.1613 24.3896 13.7389 24.6522C14.377 24.9389 14.7698 25.1287 14.9172 25.2216C15.0646 25.3145 15.1717 25.4064 15.2383 25.4973C15.3049 25.5882 15.3383 25.6942 15.3383 25.8153C15.3383 26.0092 15.2555 26.1677 15.0899 26.2909C14.9243 26.4141 14.6638 26.4757 14.3083 26.4757C13.8964 26.4757 13.444 26.4101 12.9513 26.2788C12.4585 26.1475 11.984 25.9648 11.5276 25.7305V27.863C11.9597 28.069 12.3757 28.2134 12.7756 28.2962C13.1754 28.379 13.6702 28.4204 14.2599 28.4204C14.9667 28.4204 15.5826 28.3023 16.1077 28.066C16.6327 27.8297 17.0346 27.4985 17.3133 27.0724ZM21.1484 21.9192L22.9599 28.2986H25.2378L27.025 21.9313H27.0795C27.031 22.8925 27.0038 23.4963 26.9977 23.7427C26.9917 23.9891 26.9886 24.2132 26.9886 24.4152V28.2986H29.1878V19.4413H25.9951L24.1836 25.7298H24.1352L22.2874 19.4413H19.1007V28.2986H21.2211V24.4515C21.2211 23.8497 21.1787 23.0056 21.0939 21.9192H21.1484ZM36.8891 25.6094C36.8891 26.1587 36.7497 26.6463 36.471 27.0724C36.1923 27.4985 35.7905 27.8297 35.2654 28.066C34.7404 28.3023 34.1245 28.4204 33.4176 28.4204C32.828 28.4204 32.3332 28.379 31.9334 28.2962C31.5335 28.2134 31.1175 28.069 30.6854 27.863V25.7305C31.1418 25.9648 31.6163 26.1475 32.1091 26.2788C32.6018 26.4101 33.0541 26.4757 33.4661 26.4757C33.8215 26.4757 34.082 26.4141 34.2476 26.2909C34.4132 26.1677 34.496 26.0092 34.496 25.8153C34.496 25.6942 34.4627 25.5882 34.3961 25.4973C34.3294 25.4064 34.2224 25.3145 34.075 25.2216C33.9276 25.1287 33.5348 24.9389 32.8966 24.6522C32.3191 24.3896 31.8859 24.1352 31.5971 23.8888C31.3083 23.6424 31.0943 23.3597 30.955 23.0406C30.8156 22.7216 30.7459 22.3439 30.7459 21.9077C30.7459 21.0919 31.0428 20.4558 31.6365 19.9994C32.2302 19.543 33.0461 19.3148 34.0841 19.3148C35.0009 19.3148 35.9359 19.5268 36.8891 19.9509L36.156 21.7987C35.328 21.419 34.6132 21.2292 34.0114 21.2292C33.7004 21.2292 33.4742 21.2837 33.3328 21.3928C33.1915 21.5018 33.1208 21.6371 33.1208 21.7987C33.1208 21.9724 33.2107 22.1279 33.3904 22.2652C33.5701 22.4025 34.0578 22.6529 34.8535 23.0164C35.6168 23.3597 36.1469 23.7283 36.4438 24.122C36.7406 24.5158 36.8891 25.0116 36.8891 25.6094Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		notification: () => `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.91455 23.9681C7.91455 32.7869 15.0636 39.9359 23.8823 39.9359C32.701 39.9359 39.8501 32.7869 39.8501 23.9681C39.8501 15.1494 32.701 8.00037 23.8823 8.00037C15.0636 8.00037 7.91455 15.1494 7.91455 23.9681ZM15.1228 20.0072C15.1228 18.3504 16.4659 17.0072 18.1228 17.0072H29.6418C31.2987 17.0072 32.6418 18.3504 32.6418 20.0072V27.1465C32.6418 28.8034 31.2987 30.1465 29.6418 30.1465H24.6036L20.8945 33.8556C20.1385 34.6116 18.8459 34.0762 18.8459 33.0071V30.1465H18.1228C16.4659 30.1465 15.1228 28.8034 15.1228 27.1465V20.0072Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		empty: (color = '') => {
			return `<svg width="48" height="48" viewBox="0 0 66 66" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_10_31)"><rect x="13" y="9" width="40" height="40" fill="${AppTheme.colors.accentBrandBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M14.6471 5H51.3529C54.4717 5 57 7.52827 57 10.6471V47.3529C57 50.4717 54.4717 53 51.3529 53H14.6471C11.5283 53 9 50.4717 9 47.3529V10.6471C9 7.52827 11.5283 5 14.6471 5ZM14.7931 47.2069H51.3724V44.3933L41.6187 33.1373L36.7404 38.7661L24.5469 24.6965L14.7931 35.9509V47.2069ZM43.0691 23.6281C45.6756 23.6281 47.7886 21.5151 47.7886 18.9086C47.7886 16.3021 45.6756 14.1891 43.0691 14.1891C40.4626 14.1891 38.3496 16.3021 38.3496 18.9086C38.3496 21.5151 40.4626 23.6281 43.0691 23.6281Z" fill="${AppTheme.colors.bgContentPrimary}"/></g><defs><filter id="filter0_d_10_31" x="0" y="0" width="66" height="66" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_10_31"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_10_31" result="shape"/></filter></defs></svg>`;
		},
		call: (color = '') => {
			return `<svg width="46" height="49" viewBox="0 0 46 49" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M37.7522 32.0686L44.3927 37.2088C46.3991 38.7834 46.5548 41.8954 44.7034 43.6704C37.7608 50.5245 26.193 53.1853 10.9643 36.5522C-4.26452 19.9192 -1.23585 8.0124 5.70671 1.15814C7.55596 -0.616152 10.5019 -0.322596 11.9093 1.83848L16.5536 8.8554C18.1802 11.4312 17.1528 14.9405 14.6573 16.6612L11.8695 18.5835C11.2289 19.0051 11.0504 19.8884 11.3941 20.5653C14.9994 27.1027 20.2082 32.8317 26.3 36.9371C26.9076 37.2995 27.7801 37.1121 28.2036 36.515L30.1722 33.6322C31.8936 31.1197 35.394 30.2381 37.7522 32.0686Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		pausePlayer: (color = '') => {
			return `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="12" fill="${AppTheme.colors.accentSoftBlue2}"/><rect x="10.6602" y="34" width="2.57143" height="32" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="16" y="26" width="2" height="40" rx="1" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="20.9453" y="34" width="2.57143" height="32" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="26.0879" y="42" width="2.57143" height="24" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="31.2314" y="42" width="2.57143" height="24" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="36.375" y="50" width="2.57143" height="16" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="41.5176" y="42" width="2.57143" height="24" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="46.6602" y="50" width="2.57143" height="16" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="51.8027" y="50" width="2.57143" height="16" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="56.9453" y="34" width="2.57143" height="32" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="62.0879" y="36.9844" width="2.57143" height="29.0161" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="67.2314" y="42" width="2.57143" height="24" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><g opacity="0.9" filter="url(#filter0_d_4682_512117)"><circle cx="40" cy="40" r="20" fill="${AppTheme.colors.bgContentPrimary}"/></g><rect x="33.9238" y="32.2168" width="3.78674" height="16.3459" rx="1" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="41.498" y="32.2168" width="3.78674" height="16.3459" rx="1" fill="${AppTheme.colors.accentBrandBlue}"/><defs><filter id="filter0_d_4682_512117" x="11" y="15" width="58" height="58" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4682_512117"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4682_512117" result="shape"/></filter></defs></svg>`;
		},
		playPlayer: (color = '') => {
			return `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="80" height="80" rx="12" fill="${AppTheme.colors.accentSoftBlue2}"/><rect x="10.6602" y="34" width="2.57143" height="32" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="16" y="26" width="2" height="40" rx="1" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="20.9453" y="34" width="2.57143" height="32" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="26.0879" y="42" width="2.57143" height="24" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="31.2314" y="42" width="2.57143" height="24" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="36.375" y="50" width="2.57143" height="16" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="41.5176" y="42" width="2.57143" height="24" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="46.6602" y="50" width="2.57143" height="16" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="51.8027" y="50" width="2.57143" height="16" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="56.9453" y="34" width="2.57143" height="32" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="62.0879" y="36.9844" width="2.57143" height="29.0161" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="67.2314" y="42" width="2.57143" height="24" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><g opacity="0.9" filter="url(#filter0_d_4682_512092)"><circle cx="40" cy="40" r="20" fill="${AppTheme.colors.bgContentPrimary}"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M46.6778 38.872L37.1494 32.1349C36.9248 31.9739 36.6321 31.9556 36.3903 32.0876C36.1486 32.2197 35.9981 32.4799 36 32.7626V46.2362C35.9972 46.5193 36.1476 46.7803 36.3896 46.9125C36.6317 47.0447 36.925 47.026 37.1494 46.8639L46.6778 40.1268C46.8793 39.986 47 39.7509 47 39.4994C47 39.2479 46.8793 39.0128 46.6778 38.872Z" fill="${AppTheme.colors.accentBrandBlue}"/><defs><filter id="filter0_d_4682_512092" x="11" y="15" width="58" height="58" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4682_512092"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4682_512092" result="shape"/></filter></defs></svg>`;
		},
		playLoading: (color = '') => {
			return `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 12C0 5.37258 5.37258 0 12 0H68C74.6274 0 80 5.37258 80 12V68C80 74.6274 74.6274 80 68 80H12C5.37258 80 0 74.6274 0 68V12Z" fill="${AppTheme.colors.accentSoftBlue2}"/><rect x="10.6597" y="34" width="2.57143" height="32" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="16" y="26" width="2" height="40" rx="1" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="20.9453" y="34" width="2.57143" height="32" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="26.0879" y="42" width="2.57143" height="24" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="31.231" y="42" width="2.57143" height="24" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="36.374" y="50" width="2.57143" height="16" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="41.5166" y="42" width="2.57143" height="24" rx="1.28572" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="46.6597" y="50" width="2.57143" height="16" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="51.8022" y="50" width="2.57143" height="16" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="56.9453" y="34" width="2.57143" height="32" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="62.0879" y="36.9839" width="2.57143" height="29.0161" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><rect x="67.231" y="42" width="2.57143" height="24" rx="1.28571" fill="${AppTheme.colors.accentBrandBlue}"/><g opacity="0.9" filter="url(#filter0_d_140_89832)"><circle cx="40" cy="40" r="20" fill="${AppTheme.colors.bgContentPrimary}"/></g><defs><filter id="filter0_d_140_89832" x="11" y="15" width="58" height="58" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="4.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_140_89832"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_140_89832" result="shape"/></filter></defs></svg>`;
		},
		'call-default': (color = '') => {
			return `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M34.2584 28.3019L38.1736 31.2354C39.3565 32.1341 39.4483 33.9102 38.3568 34.9231C34.2635 38.8349 27.4431 40.3534 18.4645 30.8608C9.48568 21.3681 11.2714 14.5728 15.3646 10.661C16.4549 9.64836 18.1918 9.81589 19.0216 11.0492L21.7599 15.0539C22.7189 16.5239 22.1131 18.5267 20.6418 19.5087L18.9981 20.6058C18.6204 20.8464 18.5152 21.3505 18.7179 21.7368C20.8435 25.4678 23.9146 28.7374 27.5063 31.0804C27.8645 31.2872 28.3789 31.1803 28.6286 30.8395L29.7893 29.1942C30.8042 27.7603 32.868 27.2572 34.2584 28.3019Z" fill="${color}"/></svg>`;
		},
		'call-missed': (color = '') => {
			return `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_276_233833)"><rect width="80" height="80" rx="12" fill="${AppTheme.colors.accentSoftRed3}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M54.7522 47.0686L61.3927 52.2088C63.3991 53.7834 63.5548 56.8954 61.7034 58.6704C54.7608 65.5245 43.193 68.1853 27.9643 51.5522C12.7355 34.9192 15.7641 23.0124 22.7067 16.1581C24.556 14.3838 27.5019 14.6774 28.9093 16.8385L33.5536 23.8554C35.1802 26.4312 34.1528 29.9405 31.6573 31.6612L28.8695 33.5835C28.2289 34.0051 28.0504 34.8884 28.3941 35.5653C31.9994 42.1027 37.2082 47.8317 43.3 51.9371C43.9076 52.2995 44.7801 52.1121 45.2036 51.515L47.1722 48.6322C48.8936 46.1197 52.394 45.2381 54.7522 47.0686Z" fill="${AppTheme.colors.accentMainAlert}" fill-opacity="0.4"/><g filter="url(#filter0_d_276_233833)"><circle cx="54.5" cy="54.5" r="17.5" fill="${AppTheme.colors.bgContentPrimary}" fill-opacity="0.7" shape-rendering="crispEdges"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M63.6332 54.1786C63.4613 53.9624 63.1467 53.9266 62.9306 54.0985L57.0094 58.8077L53.2605 54.094L55.8955 51.9984C56.5503 51.4776 56.2486 50.4253 55.4175 50.3305L48.0481 49.4904C47.533 49.4315 47.0676 49.8017 47.009 50.3169L46.1686 57.6862C46.0739 58.5173 47.0312 59.0482 47.6861 58.5274L50.7474 56.0927L56.2136 62.954C56.227 62.9708 56.2408 62.987 56.255 63.0026L56.5302 63.3486C56.7021 63.5647 57.0166 63.6006 57.2327 63.4287L65.2684 57.0379C65.4845 56.866 65.5204 56.5515 65.3485 56.3354L63.6332 54.1786Z" fill="${AppTheme.colors.accentMainAlert}"/></g><defs><filter id="filter0_d_276_233833" x="25" y="31" width="59" height="59" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="6"/><feGaussianBlur stdDeviation="6"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_276_233833"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_276_233833" result="shape"/></filter><clipPath id="clip0_276_233833"><rect width="80" height="80" fill="${AppTheme.colors.bgContentPrimary}"/></clipPath></defs></svg>`;
		},
		'call-incoming': (color = '') => `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M33.6986 21.9883C34.3287 22.6184 33.8825 23.6955 32.9916 23.6956L25.0921 23.6954C24.5398 23.6956 24.092 23.2478 24.0922 22.6955L24.092 14.796C24.0921 13.9051 25.1692 13.4588 25.7993 14.0889L28.3347 16.6244L34.9413 10.0178C35.3318 9.62731 35.9651 9.62731 36.3556 10.0178L37.7698 11.432C38.1603 11.8225 38.1603 12.4557 37.7698 12.8462L31.1632 19.4528L33.6986 21.9883ZM35.0863 32.8907L31.1726 29.9582C29.7827 28.9139 27.7197 29.4169 26.7052 30.8502L25.5449 32.4949C25.2953 32.8355 24.7811 32.9424 24.423 32.7357C20.8326 30.3936 17.7627 27.1252 15.6378 23.3956C15.4352 23.0094 15.5404 22.5055 15.918 22.265L17.561 21.1683C19.0318 20.1867 19.6374 18.1846 18.6787 16.7151L15.9415 12.7119C15.112 11.479 13.3757 11.3116 12.2858 12.3238C8.19404 16.2341 6.40903 23.027 15.3845 32.5161C24.3599 42.0053 31.1777 40.4873 35.2695 36.577C36.3606 35.5644 36.2689 33.789 35.0863 32.8907Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		'call-outgoing': (color = '') => {
			return `<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M28.516 11.8106C27.8859 11.1805 28.3321 10.1035 29.223 10.1034L37.1225 10.1036C37.6748 10.1034 38.1226 10.5512 38.1224 11.1034L38.1226 19.003C38.1225 19.8939 37.0454 20.3401 36.4153 19.71L33.8799 17.1745L27.2733 23.7811C26.8828 24.1716 26.2495 24.1716 25.859 23.7811L24.4448 22.3669C24.0543 21.9764 24.0543 21.3432 24.4448 20.9527L31.0514 14.3461L28.516 11.8106ZM35.8533 32.5621L31.9396 29.6296C30.5497 28.5853 28.4867 29.0883 27.4721 30.5217L26.3119 32.1663C26.0623 32.5069 25.548 32.6138 25.1899 32.4071C21.5996 30.065 18.5297 26.7966 16.4048 23.067C16.2022 22.6808 16.3074 22.1769 16.6849 21.9364L18.328 20.8397C19.7988 19.8581 20.4043 17.856 19.4457 16.3865L16.7084 12.3833C15.8789 11.1504 14.1427 10.983 13.0528 11.9952C8.96101 15.9056 7.17599 22.6984 16.1515 32.1875C25.1269 41.6767 31.9447 40.1587 36.0364 36.2484C37.1276 35.2358 37.0358 33.4604 35.8533 32.5621Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-chat': (color = '') => `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M33.9392 30.7054C38.3105 24.6301 37.3368 16.2202 31.6694 11.1258C26.0351 6.07227 17.4778 6.06897 11.9978 11.1185C6.48661 16.2083 5.76909 24.6176 10.3264 30.6955C14.8334 36.751 23.2412 38.4593 29.6638 34.6249C29.7221 34.6678 29.7836 34.7041 29.8498 34.7332L33.8458 35.7933C34.1907 35.8837 34.5547 35.786 34.8003 35.5358C35.0472 35.283 35.1386 34.9193 35.0426 34.5774L33.9392 30.7054ZM26.4134 24.8006C26.1724 24.5333 25.7955 24.4245 25.4478 24.5215C25.0941 24.6332 24.8299 24.9211 24.7568 25.2774C24.6695 25.6417 24.7732 26.0273 25.0344 26.3087L25.5448 26.8463H16.3149C15.853 26.872 15.503 27.254 15.5291 27.7043L15.5358 28.1215C15.5232 28.5718 15.8858 28.9545 16.3492 28.9787H25.5859L25.0926 29.5179C24.8411 29.7985 24.7494 30.1834 24.8479 30.5484C24.9322 30.9047 25.2045 31.1926 25.5635 31.3043C25.9134 31.402 26.2866 31.2925 26.5201 31.0252L28.6812 28.6687C29.061 28.2361 29.0505 27.5934 28.6573 27.1607L26.4134 24.8006ZM17.8858 19.5304C18.1537 19.5304 18.4082 19.4172 18.5813 19.2204C18.9604 18.7885 18.9499 18.1457 18.5574 17.7131L18.0477 17.1747H27.2851C27.7478 17.1504 28.0985 16.7685 28.0709 16.3175L28.0642 15.9017C28.0776 15.4507 27.7142 15.0673 27.2508 15.0445H18.0142L18.5081 14.5053C18.8857 14.0742 18.8745 13.4322 18.4805 13.0025C18.3022 12.8049 18.0455 12.691 17.7769 12.6903C17.5082 12.6888 17.2545 12.8012 17.0799 12.9981L14.9189 15.3596C14.539 15.7908 14.5495 16.4343 14.9427 16.8654L17.1791 19.2204C17.359 19.4172 17.6164 19.5304 17.8858 19.5304ZM16.5349 21.0082L16.5327 21.0096C16.07 21.0331 15.7193 21.4158 15.7462 21.8669L15.7529 22.29C15.7402 22.741 16.1036 23.1229 16.5663 23.1472H27.7319C28.1945 23.1229 28.5453 22.741 28.5184 22.29L28.5109 21.8713C28.5259 21.4181 28.1632 21.0331 27.6983 21.0082H16.5349Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		'channel-viber': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.2008 8.23049L19.2053 8.2487C19.6552 10.3269 19.6552 12.4345 19.2053 14.5128L19.2008 14.531C18.8369 16.0192 17.1961 17.616 15.6893 17.9483L15.6723 17.9518C14.454 18.1869 13.2267 18.3042 11.9999 18.3042C11.6388 18.3042 11.2777 18.2929 10.9168 18.2724L9.25281 20.0219C8.84087 20.4558 8.11648 20.1605 8.11648 19.5593V17.8981C6.66595 17.4791 5.14738 15.9556 4.79848 14.531L4.79448 14.5128C4.34455 12.4345 4.34455 10.3269 4.79448 8.2487L4.79848 8.23049C5.16292 6.74235 6.80368 5.14548 8.30995 4.81327L8.32745 4.80973C10.7641 4.33947 13.2352 4.33947 15.6723 4.80973L15.6893 4.81327C17.1961 5.14548 18.8369 6.74235 19.2008 8.23049ZM15.3746 16.3629C16.3542 16.1465 17.5871 14.975 17.8276 14.0327C18.2288 12.2095 18.2288 10.3612 17.8276 8.53792C17.5871 7.59563 16.3547 6.42415 15.3746 6.20772C13.1354 5.78516 10.8645 5.78516 8.62482 6.20772C7.64522 6.42415 6.41282 7.59563 6.17187 8.53792C5.77113 10.3611 5.77113 12.2094 6.17187 14.0326C6.41282 14.9749 7.64522 16.1464 8.62482 16.3628L8.62556 16.363C8.66925 16.3715 8.70101 16.409 8.70101 16.4531V19.124C8.70101 19.2575 8.86527 19.3232 8.95877 19.2266L11.4401 16.7002C11.458 16.6819 11.4827 16.672 11.5083 16.6727C12.7996 16.7082 14.0922 16.6048 15.3746 16.3629ZM13.7879 15.4071C13.6865 15.3791 13.589 15.352 13.4963 15.3134C12.0782 14.7226 10.7732 13.9605 9.73945 12.7922C9.15159 12.1278 8.69148 11.3777 8.30254 10.5839C8.13716 10.2464 7.9951 9.89704 7.8533 9.54827L7.80423 9.42771C7.65977 9.07331 7.87255 8.70718 8.09661 8.44016C8.30686 8.18956 8.5774 7.99777 8.87039 7.85643C9.09907 7.7461 9.32465 7.80971 9.49167 8.00435C9.85273 8.42516 10.1844 8.8675 10.4529 9.35528C10.6181 9.65531 10.5728 10.0221 10.2735 10.2262C10.215 10.2661 10.1607 10.3115 10.1065 10.3569L10.0667 10.3902C10.0072 10.4393 9.95132 10.4889 9.91058 10.5554C9.83611 10.6771 9.83256 10.8207 9.88051 10.953C10.2496 11.9715 10.8717 12.7635 11.8926 13.1901L11.9363 13.2085C12.0854 13.2717 12.2368 13.3358 12.4082 13.3158C12.6122 13.2918 12.7269 13.138 12.8423 12.9833C12.9052 12.8989 12.9684 12.8143 13.0463 12.7502C13.2622 12.573 13.5381 12.5706 13.7706 12.7184C14.0032 12.8662 14.2287 13.0249 14.4528 13.1853L14.4981 13.2177C14.7028 13.364 14.9058 13.509 15.0947 13.6761C15.2899 13.8487 15.3571 14.075 15.2472 14.3092C15.046 14.7381 14.7534 15.0949 14.3311 15.3226C14.2496 15.3666 14.1572 15.3902 14.0639 15.414C14.0209 15.425 13.9775 15.4361 13.9351 15.4492C13.8853 15.4341 13.8361 15.4204 13.7879 15.4071ZM15.6792 10.4225C15.3818 8.60023 13.9941 7.37697 12.3045 7.32533C12.295 7.32529 12.2855 7.32512 12.276 7.32495C12.2528 7.32454 12.2296 7.32414 12.2065 7.32553C12.0867 7.33255 11.9805 7.44184 12.0029 7.57909C12.0282 7.73373 12.1707 7.74941 12.2881 7.76233L12.3085 7.7646C12.3679 7.7714 12.4275 7.77701 12.487 7.78263C12.619 7.79507 12.751 7.80751 12.8809 7.8328C14.1337 8.07656 15.1373 9.21254 15.3122 10.5856C15.3387 10.7931 15.3517 11.0027 15.3647 11.2122C15.368 11.2653 15.3713 11.3184 15.3748 11.3715C15.3836 11.504 15.4446 11.6276 15.58 11.6258C15.7111 11.6241 15.7799 11.4982 15.7705 11.3656L15.7623 11.2491C15.743 10.9724 15.7236 10.6948 15.6792 10.4225ZM14.6971 10.8443C14.6964 10.85 14.6959 10.8577 14.6952 10.8668C14.6932 10.8935 14.6903 10.9326 14.6818 10.9699C14.6406 11.1513 14.3483 11.2286 14.2938 11.0455C14.2776 10.9912 14.2752 10.9294 14.2751 10.8709C14.2746 10.4883 14.203 10.106 14.037 9.7731C13.8663 9.43093 13.6056 9.14328 13.2997 8.96922C13.1148 8.86399 12.9148 8.79858 12.7121 8.75959C12.657 8.749 12.6014 8.74099 12.5459 8.73298C12.5122 8.72813 12.4786 8.72327 12.445 8.71783C12.3371 8.70042 12.2795 8.62051 12.2846 8.49697C12.2894 8.3812 12.3622 8.29793 12.4707 8.30506C12.8275 8.32861 13.1721 8.41821 13.4892 8.61339C14.1342 9.01033 14.5583 9.58232 14.6658 10.419C14.6675 10.4325 14.6697 10.446 14.6718 10.4596C14.6756 10.4838 14.6794 10.508 14.681 10.5325C14.6852 10.5985 14.6884 10.6646 14.6921 10.7411L14.6971 10.8443ZM13.3652 10.8784C13.3846 11.0481 13.4856 11.1505 13.6743 11.1472C13.7602 11.1415 13.8483 11.083 13.8759 10.9664C13.8915 10.9004 13.885 10.8277 13.8789 10.7585L13.8783 10.7517C13.7891 9.74219 13.1642 9.29885 12.2898 9.23695C12.1499 9.22701 12.0253 9.32502 12.0039 9.45059C11.9803 9.58857 12.0632 9.70766 12.2132 9.7437C12.2779 9.75925 12.3438 9.76515 12.4099 9.77106C12.4848 9.77776 12.5599 9.78448 12.6334 9.8053C12.7424 9.83613 12.8464 9.88393 12.9382 9.94953C13.1326 10.0884 13.2555 10.2985 13.3122 10.5252C13.3369 10.6242 13.3483 10.7268 13.3596 10.8289C13.3614 10.8454 13.3633 10.8619 13.3652 10.8784Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-avito': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.88365 10.9714C9.7772 10.9714 11.3122 9.43638 11.3122 7.54283C11.3122 5.64928 9.7772 4.11426 7.88365 4.11426C5.9901 4.11426 4.45508 5.64928 4.45508 7.54283C4.45508 9.43638 5.9901 10.9714 7.88365 10.9714ZM15.4272 19.8859C17.6994 19.8859 19.5415 18.0439 19.5415 15.7716C19.5415 13.4994 17.6994 11.6573 15.4272 11.6573C13.1549 11.6573 11.3129 13.4994 11.3129 15.7716C11.3129 18.0439 13.1549 19.8859 15.4272 19.8859ZM18.1697 7.54295C18.1697 9.05779 16.9417 10.2858 15.4268 10.2858C13.912 10.2858 12.684 9.05779 12.684 7.54295C12.684 6.02812 13.912 4.8001 15.4268 4.8001C16.9417 4.8001 18.1697 6.02812 18.1697 7.54295ZM7.88566 17.8285C9.02179 17.8285 9.9428 16.9075 9.9428 15.7714C9.9428 14.6353 9.02179 13.7143 7.88566 13.7143C6.74953 13.7143 5.82852 14.6353 5.82852 15.7714C5.82852 16.9075 6.74953 17.8285 7.88566 17.8285Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-fb': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.94951 9.72794H8.38867V12.4027H9.94951V20.3521H13.1556V12.4036H15.3075C15.3075 12.4036 15.5091 11.121 15.6074 9.71845H13.1683V7.88932C13.1683 7.61613 13.5176 7.24894 13.8634 7.24894H15.6106V4.46387H13.2347C9.87063 4.46387 9.94951 7.14125 9.94951 7.54111V9.72794Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-fb-chat': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.48047 9.66967H4.72935V7.9464C4.72935 7.94004 4.72933 7.93295 4.7293 7.92516C4.72793 7.54698 4.72058 5.52148 7.358 5.52148H9.25902V7.71617H7.86103C7.58432 7.71617 7.30486 8.00553 7.30486 8.2208V9.66219H9.25644C9.17785 10.7674 9.01653 11.7781 9.01653 11.7781H7.29471V18.0417H4.72935V11.7774H3.48047V9.66967ZM12.7906 8.372C11.9622 8.372 11.2906 9.04358 11.2906 9.872V13.4364C11.2906 14.2648 11.9622 14.9364 12.7906 14.9364H13.3415V17.4611L15.9051 14.9364H19.0196C19.848 14.9364 20.5196 14.2648 20.5196 13.4364V9.872C20.5196 9.04358 19.848 8.372 19.0196 8.372H12.7906Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-ok': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.0521 2.8252C9.47524 2.8252 7.38631 4.9142 7.38631 7.49103C7.38631 10.0679 9.47524 12.1571 12.0521 12.1571C14.629 12.1571 16.718 10.0679 16.718 7.49103C16.718 4.9142 14.629 2.8252 12.0521 2.8252ZM12.0525 9.41998C10.9873 9.41998 10.1237 8.55636 10.1237 7.49116C10.1237 6.42596 10.9873 5.56241 12.0525 5.56241C13.1177 5.56241 13.9812 6.42596 13.9812 7.49116C13.9812 8.55636 13.1177 9.41998 12.0525 9.41998ZM16.7468 14.5573C16.6876 14.6048 15.5683 15.4894 13.7003 15.8699L16.5199 18.6644C17.0157 19.1594 17.0163 19.9627 16.5213 20.4585C16.0262 20.9543 15.2231 20.9551 14.7271 20.4599L11.9898 17.7943L9.50357 20.4427C9.25464 20.701 8.92259 20.8308 8.5902 20.8308C8.27317 20.8308 7.95588 20.7128 7.70969 20.4755C7.2053 19.9891 7.19068 19.186 7.67704 18.6816L10.3533 15.8847C8.43302 15.5131 7.26747 14.6055 7.20744 14.5573C6.66085 14.119 6.57305 13.3205 7.0114 12.7739C7.44968 12.2273 8.24807 12.1395 8.79479 12.5778C8.80634 12.5871 10.0254 13.503 11.9897 13.5043C13.9541 13.503 15.1479 12.5871 15.1595 12.5778C15.7062 12.1395 16.5046 12.2273 16.9429 12.7739C17.3812 13.3205 17.2934 14.119 16.7468 14.5573Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-telegram': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.1655 8.59552L9.80531 13.5553C9.61685 13.7297 9.49546 13.9637 9.461 14.2171L9.27837 15.5641C9.25422 15.744 9.00051 15.7617 8.95043 15.5877L8.24819 13.1315C8.16795 12.8514 8.2851 12.552 8.53412 12.3992L15.0291 8.41726C15.1456 8.34605 15.2659 8.50317 15.1655 8.59552ZM17.7667 5.85495L4.12792 11.0924C3.79122 11.2215 3.79414 11.6959 4.13169 11.8217L7.45501 13.0563L8.74137 17.1742C8.82367 17.4377 9.14761 17.5354 9.36269 17.3603L11.2151 15.8572C11.4092 15.6996 11.6859 15.6919 11.8887 15.8383L15.2299 18.2531C15.46 18.4193 15.7859 18.294 15.8436 18.0174L18.2911 6.29801C18.3542 5.99578 18.0557 5.74363 17.7667 5.85495Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-instagram': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.55279 4.4082C6.45592 4.4082 4.54492 6.31916 4.54492 8.41607V15.0958C4.54492 17.1927 6.45588 19.1037 8.55279 19.1037H14.4328C14.1706 18.7122 13.8296 18.3734 13.426 18.1115L12.8961 17.7677H8.70986C7.11837 17.7677 5.88078 16.5301 5.88078 14.9386V8.57321C5.88078 6.98172 7.11837 5.74413 8.70986 5.74413H15.0753C16.6668 5.74413 17.9044 6.98172 17.9044 8.57321V10.642L19.2404 10.3279V8.41607C19.2404 6.3192 17.3295 4.4082 15.2326 4.4082H8.55279Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M11.8925 7.74805C13.8824 7.74805 15.5455 9.21747 15.8504 11.1249L14.5454 11.4317C14.3874 10.1016 13.2681 9.084 11.8926 9.084C10.4073 9.084 9.22071 10.2706 9.22071 11.7559C9.22071 12.4772 9.50058 13.1281 9.95861 13.6069C9.80023 14.0273 9.7576 14.4967 9.86049 14.9677C9.88176 15.0651 9.90895 15.1606 9.94172 15.2537C8.71687 14.5659 7.88464 13.2535 7.88464 11.7559C7.88464 9.55144 9.68799 7.74805 11.8925 7.74805Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M15.2325 7.74806C15.2325 7.37885 15.5313 7.08008 15.9005 7.08008C16.2697 7.08008 16.5685 7.37885 16.5685 7.74806C16.5685 8.11726 16.2697 8.41603 15.9005 8.41603C15.5313 8.41603 15.2325 8.11726 15.2325 7.74806Z" fill="${AppTheme.colors.accentBrandBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.7794 13.4277C22.2803 12.6039 21.5525 11.5807 20.61 11.7836L12.8992 13.4434C11.8702 13.665 11.7121 15.0673 12.666 15.5122L15.3106 16.7459L15.7534 19.6076C15.9101 20.6199 17.2448 20.8854 17.777 20.0102L21.7794 13.4277ZM13.8153 14.5942L20.3881 13.1793L16.9637 18.8113L16.5949 16.4285C16.5389 16.0664 16.306 15.7561 15.9739 15.6012L13.8153 14.5942Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-instagram-direct': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.55279 4.4082C6.45592 4.4082 4.54492 6.31916 4.54492 8.41607V15.0958C4.54492 17.1927 6.45588 19.1037 8.55279 19.1037H14.4328C14.1706 18.7122 13.8296 18.3734 13.426 18.1115L12.8961 17.7677H8.70986C7.11837 17.7677 5.88078 16.5301 5.88078 14.9386V8.57321C5.88078 6.98172 7.11837 5.74413 8.70986 5.74413H15.0753C16.6668 5.74413 17.9044 6.98172 17.9044 8.57321V10.642L19.2404 10.3279V8.41607C19.2404 6.3192 17.3295 4.4082 15.2326 4.4082H8.55279Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M11.8925 7.74805C13.8824 7.74805 15.5455 9.21747 15.8504 11.1249L14.5454 11.4317C14.3874 10.1016 13.2681 9.084 11.8926 9.084C10.4073 9.084 9.22071 10.2706 9.22071 11.7559C9.22071 12.4772 9.50058 13.1281 9.95861 13.6069C9.80023 14.0273 9.7576 14.4967 9.86049 14.9677C9.88176 15.0651 9.90895 15.1606 9.94172 15.2537C8.71687 14.5659 7.88464 13.2535 7.88464 11.7559C7.88464 9.55144 9.68799 7.74805 11.8925 7.74805Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M15.2325 7.74806C15.2325 7.37885 15.5313 7.08008 15.9005 7.08008C16.2697 7.08008 16.5685 7.37885 16.5685 7.74806C16.5685 8.11726 16.2697 8.41603 15.9005 8.41603C15.5313 8.41603 15.2325 8.11726 15.2325 7.74806Z" fill="${AppTheme.colors.accentBrandBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.7794 13.4277C22.2803 12.6039 21.5525 11.5807 20.61 11.7836L12.8992 13.4434C11.8702 13.665 11.7121 15.0673 12.666 15.5122L15.3106 16.7459L15.7534 19.6076C15.9101 20.6199 17.2448 20.8854 17.777 20.0102L21.7794 13.4277ZM13.8153 14.5942L20.3881 13.1793L16.9637 18.8113L16.5949 16.4285C16.5389 16.0664 16.306 15.7561 15.9739 15.6012L13.8153 14.5942Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-whatsapp': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">	<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0661 3.72523C14.1928 3.72523 16.2752 4.56021 17.7816 6.09833C19.288 7.59251 20.1298 9.61404 20.1298 11.7674C20.1298 16.206 16.4967 19.8535 12.0218 19.8535C10.6927 19.8535 9.3635 19.5459 8.16725 18.8867L3.86961 19.9854L5.02155 15.8105C4.35697 14.58 3.95822 13.2176 3.95822 11.8114C3.91391 7.37277 7.54697 3.72523 12.0661 3.72523ZM8.65461 17.5683C9.67364 18.1836 10.8699 18.4912 12.0661 18.4912C13.3067 18.4912 14.5473 18.1396 15.5663 17.4804C18.7563 15.5468 19.6867 11.4598 17.7373 8.29565C15.7878 5.13151 11.6674 4.20864 8.47739 6.14228C5.28739 8.07592 4.35697 12.2069 6.30642 15.3271L6.48364 15.5907L5.81905 18.0957L8.38878 17.4365L8.65461 17.5683ZM13.5225 13.2984C13.7904 12.954 13.9817 12.7626 14.0965 12.7626C14.173 12.7626 14.5174 12.9157 15.1297 13.2218C15.7419 13.528 16.0481 13.7193 16.0863 13.7958C16.0863 13.8117 16.0929 13.8275 16.1006 13.8461C16.1115 13.8724 16.1246 13.9041 16.1246 13.9489C16.1246 14.1785 16.0481 14.4463 15.9333 14.7525C15.8185 15.0203 15.5506 15.2499 15.1679 15.4413C14.7853 15.6326 14.4026 15.7091 14.0965 15.7091C13.6756 15.7091 13.0251 15.4795 12.0684 15.0586C11.3796 14.7525 10.7674 14.3315 10.2699 13.7958C9.77247 13.2601 9.23674 12.6096 8.70102 11.806C8.20357 11.0407 7.93571 10.3519 7.93571 9.73965L7.93571 9.66312C7.97397 9.0126 8.20357 8.43861 8.73929 7.97942C8.89235 7.82636 9.08368 7.74983 9.31328 7.74983L9.5046 7.74983L9.69593 7.74983C9.849 7.74983 9.92553 7.78809 9.96379 7.82636C10.0021 7.86463 10.0786 7.97942 10.1169 8.13249C10.1328 8.19614 10.1685 8.28626 10.2159 8.4056C10.2824 8.57324 10.3718 8.79854 10.4613 9.08913C10.6526 9.58659 10.7291 9.85445 10.7291 9.89271C10.7291 10.0458 10.6143 10.2371 10.3465 10.505C10.2961 10.5637 10.2495 10.6169 10.2073 10.665C10.0571 10.8363 9.96379 10.9427 9.96379 11.0024C9.96379 11.0407 9.96379 11.1172 10.0021 11.1555C10.2317 11.6912 10.6143 12.1504 11.0735 12.6096C11.4562 12.9922 11.9919 13.3366 12.6807 13.681C12.7572 13.7193 12.8337 13.7576 12.9103 13.7576C13.0633 13.7958 13.2547 13.6428 13.5225 13.2984Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-edna': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">	<path fill-rule="evenodd" clip-rule="evenodd" d="M2.53909 8.92915C4.1367 8.18671 5.31588 8.11095 6.06142 8.73975C7.37755 9.86099 6.80696 11.4368 6.30486 12.8232C5.90927 13.9293 5.47564 14.6036 5.04961 15.0127C4.3497 15.6794 3.80196 15.7021 3.37593 15.6794C2.47062 15.6339 1.74791 15.1112 0.941499 13.9596C0.256813 12.9672 -0.321365 11.9217 0.20356 10.7322C0.287244 10.5428 0.606763 9.82312 2.53909 8.92915ZM3.40396 14.0131C3.76053 14.0433 4.19294 14.08 4.80616 12.3611C5.36913 10.8004 5.36913 10.3459 4.95831 10.0428C4.76812 9.90646 4.32689 9.82312 3.21618 10.3534C2.23479 10.8231 1.8468 11.0883 1.73269 11.361C1.57293 11.7398 1.52729 12.0277 2.19675 12.9899C2.65321 13.649 3.06402 13.9823 3.39876 14.0126L3.40396 14.0131ZM15.3514 9.08838H14.895C14.8189 9.08838 14.7657 9.14899 14.7657 9.21717V10.6263C14.4233 10.3081 13.9821 10.1414 13.5104 10.1414C12.4605 10.1414 11.6009 11.0278 11.6009 12.1264C11.6009 13.2173 12.4605 14.1113 13.5104 14.1113C13.9821 14.1113 14.4385 13.937 14.7885 13.6037L14.8113 13.8461C14.8189 13.9143 14.8798 13.9597 14.9406 13.9597H15.3514C15.4275 13.9597 15.4808 13.8991 15.4808 13.8309V9.22475C15.4808 9.15656 15.4199 9.08838 15.3514 9.08838ZM14.7048 12.1264C14.7048 12.8082 14.1723 13.3612 13.5104 13.3612C12.8485 13.3612 12.3084 12.8082 12.3084 12.1264C12.3084 11.4445 12.8409 10.8915 13.5028 10.8915C14.1646 10.8915 14.7048 11.4369 14.7048 12.1264ZM19.4128 10.8156C19.3215 10.6793 19.2148 10.5581 19.0932 10.4671C18.9714 10.3687 18.8346 10.2929 18.6825 10.2399C18.4162 10.1414 18.1194 10.1262 17.8152 10.1793C17.3739 10.255 17.0543 10.4823 16.8642 10.649L16.8414 10.399C16.8337 10.3308 16.7729 10.2853 16.7121 10.2853H16.2707C16.1947 10.2853 16.1414 10.3459 16.1414 10.4141V13.8309C16.1414 13.9066 16.2024 13.9597 16.2707 13.9597H16.7577C16.8337 13.9597 16.887 13.8991 16.887 13.8309V11.399C17.2446 11.149 17.5641 10.9747 17.9293 10.9066C18.0814 10.8763 18.2716 10.899 18.439 10.9596C18.5835 11.0126 18.7128 11.1035 18.7889 11.2399C18.9487 11.4823 18.9487 11.7854 18.9487 12.0581V13.8233C18.9487 13.8991 19.0096 13.9521 19.078 13.9521H19.5724C19.6486 13.9521 19.7017 13.8915 19.7017 13.8233V12.4142V12.2324C19.7094 11.9066 19.7094 11.5581 19.6106 11.2323C19.5573 11.0884 19.4964 10.9369 19.4128 10.8156ZM23.4068 10.2777H23.8634C23.9393 10.2777 24.0002 10.3383 24.0002 10.4292V13.8384C24.0002 13.9066 23.947 13.9672 23.8709 13.9672H23.4601C23.3993 13.9672 23.3383 13.9218 23.3308 13.8536L23.308 13.6112C22.9579 13.9445 22.5015 14.1187 22.0298 14.1187C20.98 14.1187 20.1204 13.2248 20.1204 12.1338C20.1204 11.0353 20.98 10.1489 22.0298 10.1489C22.4938 10.1489 22.9352 10.3156 23.2775 10.6338V10.4065C23.2775 10.3383 23.3308 10.2777 23.4068 10.2777ZM22.0298 13.3611C22.6918 13.3611 23.2242 12.8081 23.2242 12.1263C23.2242 11.4368 22.6918 10.8914 22.0298 10.8914C21.3679 10.8914 20.8354 11.4444 20.8354 12.1263C20.8354 12.8081 21.3679 13.3611 22.0298 13.3611ZM11.2955 11.7399C11.1586 10.8383 10.4435 10.1489 9.51537 10.1489H9.49254C8.44268 10.1489 7.58303 11.0353 7.58303 12.1338C7.58303 13.2248 8.43509 14.1112 9.49254 14.1187H9.53058C10.1696 14.1187 10.7174 13.8536 11.1206 13.4066C11.1662 13.3536 11.1662 13.2854 11.1206 13.2323L10.8315 12.9142C10.8011 12.8839 10.7706 12.8687 10.7326 12.8687C10.6945 12.8687 10.6565 12.8914 10.6337 12.9217C10.3826 13.2399 10.0479 13.3763 9.53818 13.3763H9.49254C8.91436 13.3763 8.43509 12.952 8.32096 12.3914H11.0217C11.189 12.3914 11.326 12.2551 11.326 12.0808C11.3227 12.0775 11.3208 12.0461 11.3179 11.9975L11.3179 11.9974C11.3141 11.9332 11.3085 11.839 11.2955 11.7399ZM8.38943 11.649C8.56441 11.202 8.99044 10.8914 9.48492 10.8914H9.52297C10.0175 10.8914 10.4359 11.1944 10.588 11.649H10.5804H8.38943Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-vk': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">	<path d="M12.8549 17.698C7.18147 17.698 3.73835 13.7617 3.60449 7.2207H6.47795C6.56752 12.0256 8.75356 14.0647 10.4293 14.4803V7.2207H13.184V11.3663C14.8004 11.1872 16.4913 9.30106 17.0609 7.2207H19.7693C19.3355 9.78014 17.4947 11.6663 16.1923 12.4443C17.4947 13.0733 19.5902 14.7199 20.3984 17.698H17.4202C16.7912 15.7072 15.2493 14.1653 13.184 13.956V17.698H12.8549Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-vk-order': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">	<path fill-rule="evenodd" clip-rule="evenodd" d="M8.72421 8.81653C8.72421 8.57958 8.83388 8.36825 9.00523 8.23053V8.79289C9.00523 9.06548 9.22621 9.28647 9.49881 9.28647C9.77141 9.28647 9.99239 9.06548 9.99239 8.79289V8.27103C10.1369 8.40797 10.227 8.60172 10.227 8.81653C10.227 9.23152 9.89061 9.56793 9.47562 9.56793C9.06063 9.56793 8.72421 9.23152 8.72421 8.81653ZM14.0745 6.80918C14.0726 6.83359 14.0705 6.85186 14.0691 6.86297H9.98455C9.98316 6.85186 9.98112 6.83359 9.97916 6.80918C9.97509 6.75835 9.97152 6.68235 9.97472 6.58992C9.98123 6.40207 10.0151 6.16368 10.1156 5.93342C10.2134 5.7091 10.3752 5.4892 10.6516 5.32079C10.9312 5.15035 11.3615 5.01255 12.0268 5.01255C12.6922 5.01255 13.1224 5.15035 13.4021 5.32079C13.6785 5.4892 13.8403 5.7091 13.9381 5.93342C14.0386 6.16368 14.0725 6.40207 14.079 6.58992C14.0822 6.68235 14.0786 6.75835 14.0745 6.80918ZM16.0922 6.86297H15.0604C15.066 6.78319 15.0698 6.67826 15.0655 6.55574C15.0562 6.28718 15.0079 5.91702 14.8429 5.53873C14.6753 5.1545 14.3884 4.76584 13.9158 4.47784C13.4466 4.19187 12.8278 4.02539 12.0268 4.02539C11.2259 4.02539 10.6071 4.19187 10.1379 4.47784C9.66527 4.76584 9.37835 5.1545 9.21075 5.53873C9.04573 5.91702 8.99746 6.28718 8.98816 6.55574C8.98391 6.67826 8.98766 6.78319 8.99327 6.86297H7.907C7.39223 6.86297 6.96157 7.25375 6.9117 7.76609L6.10636 16.0405C6.04915 16.6283 6.51109 17.1374 7.10166 17.1374H16.8975C17.4881 17.1374 17.9501 16.6283 17.8928 16.0405L17.0875 7.7661C17.0376 7.25375 16.607 6.86297 16.0922 6.86297ZM14.0613 8.79289V8.22479C13.8858 8.36234 13.773 8.57626 13.773 8.81653C13.773 9.23152 14.1095 9.56793 14.5244 9.56793C14.9394 9.56793 15.2759 9.23152 15.2759 8.81653C15.2759 8.60533 15.1887 8.41449 15.0485 8.27798V8.79289C15.0485 9.06548 14.8275 9.28647 14.5549 9.28647C14.2823 9.28647 14.0613 9.06548 14.0613 8.79289ZM8.20508 10.8339C8.2656 13.7912 9.82227 15.5708 12.3873 15.5708H12.5361V13.879C13.4698 13.9737 14.1669 14.6708 14.4513 15.5708H15.7978C15.4324 14.2244 14.485 13.4799 13.8962 13.1955C14.485 12.8438 15.3173 11.9911 15.5134 10.8339H14.2889C14.0313 11.7745 13.2669 12.6272 12.5361 12.7082V10.8339H11.2907V14.1161C10.533 13.9282 9.5447 13.0063 9.5042 10.8339H8.20508Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-apple': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">	<path d="M4.5332 11.3127C4.5332 13.5464 5.95042 15.4988 8.07135 16.5967C8.04025 17.0263 7.4697 17.9601 6.83825 18.9072C8.41687 18.9072 9.9368 17.2878 9.9368 17.2878C9.9368 17.2878 11.2825 17.5337 11.9983 17.5337C16.1211 17.5337 19.4635 14.7486 19.4635 11.3128C19.4635 7.87763 16.1211 5.0918 11.9983 5.0918C7.8756 5.09175 4.5332 7.87758 4.5332 11.3127Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'channel-whatsapp-bitrix': (color = '') => {
			return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">	<path fill-rule="evenodd" clip-rule="evenodd" d="M10.6532 3.08594C12.6167 3.08594 14.5392 3.86312 15.9299 5.29477C16.0168 5.38171 16.1014 5.47055 16.1835 5.56123C15.8584 5.5173 15.5265 5.49461 15.1894 5.49461C14.8682 5.49461 14.5518 5.5152 14.2414 5.55514C12.2713 4.13325 9.56133 3.97819 7.33999 5.33568C4.39488 7.13547 3.53589 10.9805 5.33568 13.8847L5.4993 14.1301L4.88573 16.4617L7.25818 15.8481L7.50361 15.9708C7.92256 16.2258 8.37395 16.4241 8.84334 16.5655C9.16834 17.1191 9.56372 17.6264 10.0172 18.0752C8.9913 17.9966 7.97938 17.7122 7.05366 17.1979L3.08594 18.2206L4.14945 14.3346C3.53589 13.1893 3.16775 11.9213 3.16775 10.6123C3.12684 6.481 6.481 3.08594 10.6532 3.08594ZM11.2158 8.64346C12.3058 7.52142 13.8125 6.91231 15.3513 6.91231C18.6213 6.91231 21.2501 9.57316 21.218 12.8111C21.218 13.8369 20.9295 14.8308 20.4486 15.7284L21.2822 18.7739L18.1725 17.9725C17.3069 18.4534 16.3451 18.6778 15.3834 18.6778C12.1455 18.6778 9.51669 16.0169 9.51669 12.779C9.51669 11.2081 10.1258 9.73345 11.2158 8.64346ZM14.2887 11.6037C14.2887 11.1825 13.9402 11.0347 13.5695 11.0347C13.0726 11.0347 12.6648 11.1972 12.2867 11.3672L12.027 10.5913C12.4498 10.3992 13.0356 10.1849 13.7326 10.1849C14.8226 10.1849 15.3491 10.7317 15.3491 11.5002C15.3491 12.2615 14.7552 12.7126 14.1832 13.147C13.7446 13.4801 13.319 13.8034 13.1838 14.2492H15.4158V15.1063H11.8639C11.9851 13.653 12.8476 13.0278 13.5098 12.5477C13.9419 12.2344 14.2887 11.983 14.2887 11.6037ZM16.9804 13.1322H17.4254V12.6076C17.4254 12.2307 17.455 11.7947 17.4699 11.6987L16.365 13.1544C16.4539 13.147 16.8099 13.1322 16.9804 13.1322ZM15.2381 13.302L17.6851 10.1984H18.4119V13.1321H19.1385V13.9449H18.4119V15.1051H17.4256V13.9449H15.2381V13.302Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'document-print': () => {
			return `<svg width="53" height="64" viewBox="0 0 53 64" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.7"><g filter="url(#filter0_d_6853_3709)"><path fill-rule="evenodd" clip-rule="evenodd" d="M4 8C4 4.68629 6.68629 2 10 2H33.1043C34.6578 2 36.1508 2.60257 37.2691 3.68093L46.9648 13.0304C48.1375 14.1612 48.8 15.7203 48.8 17.3494V52C48.8 55.3137 46.1137 58 42.8 58H9.99999C6.68628 58 4 55.3137 4 52V8Z" fill="${AppTheme.colors.accentSoftGray2}"/><path d="M4.5 8C4.5 4.96243 6.96243 2.5 10 2.5H33.1043C34.5284 2.5 35.8969 3.05236 36.922 4.04086L46.6177 13.3903C47.6927 14.4269 48.3 15.8561 48.3 17.3494V52C48.3 55.0376 45.8376 57.5 42.8 57.5H9.99999C6.96243 57.5 4.5 55.0376 4.5 52V8Z" stroke="${AppTheme.colors.accentBrandBlue}"/></g><rect x="10.7198" y="19" width="25.8461" height="2.24" rx="1.12" fill="${AppTheme.colors.accentSoftBlue1}"/><rect x="10.7198" y="24.6" width="18.9538" height="2.24" rx="1.12" fill="${AppTheme.colors.accentSoftBlue1}"/><path d="M25.2648 40.0215C25.6513 40.0215 25.966 40.3559 25.966 40.7667V45.9832C25.966 46.3939 25.6513 46.7284 25.2648 46.7284H24.5636V49.2358C24.5636 49.6465 24.2489 49.981 23.8625 49.981H14.0459C13.6594 49.981 13.3447 49.6465 13.3447 49.2358V46.7284H12.6436C12.2571 46.7284 11.9424 46.3939 11.9424 45.9832V40.7667C11.9424 40.3559 12.2571 40.0215 12.6436 40.0215H25.2648ZM23.1613 45.9832H14.7471V48.4906H23.1613V45.9832ZM23.8625 41.5119C23.476 41.5119 23.1613 41.8463 23.1613 42.2571C23.1613 42.6678 23.476 43.0023 23.8625 43.0023C24.2489 43.0023 24.5636 42.6678 24.5636 42.2571C24.5636 41.8463 24.2489 41.5119 23.8625 41.5119ZM23.6842 35.9424C23.8401 35.9424 23.9632 36.0813 23.9632 36.2504V38.7028C23.9632 38.875 23.8374 39.0109 23.6842 39.0109H14.2248C14.0716 39.0109 13.9458 38.875 13.9458 38.7058V36.2504C13.9458 36.0813 14.0716 35.9424 14.2248 35.9424L23.6842 35.9424Z" fill="${AppTheme.colors.accentBrandBlue}"/><path opacity="0.2" d="M37.3601 16C36.2555 16 35.3601 15.1042 35.3601 13.9996V4.34883C35.3601 3.46734 36.4178 3.01703 37.0532 3.62799L42.6401 9L48.2401 14.32L48.8001 16H37.3601Z" fill="${AppTheme.colors.accentSoftBlue1}"/></g><defs><filter id="filter0_d_6853_3709" x="0.64" y="0.88" width="51.52" height="62.72" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2.24"/><feGaussianBlur stdDeviation="1.68"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.24977 0 0 0 0 0.329167 0 0 0 0.2 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_6853_3709"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_6853_3709" result="shape"/></filter></defs></svg>`;
		},
		'bank-card': () => {
			return `<svg width="46" height="46" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.75952 11.525C6.65495 11.525 5.75952 12.4205 5.75952 13.525V34.263C5.75952 35.3676 6.65495 36.263 7.75952 36.263H38.216C39.3205 36.263 40.216 35.3676 40.216 34.263V13.525C40.216 12.4205 39.3205 11.525 38.216 11.525H7.75952ZM9.47789 14.3889C8.92561 14.3889 8.47789 14.8366 8.47789 15.3889V18.593H37.4976V15.3889C37.4976 14.8366 37.0499 14.3889 36.4976 14.3889H9.47789ZM37.4976 23.0105H8.47789V32.3991C8.47789 32.9514 8.92561 33.3991 9.47789 33.3991H36.4976C37.0499 33.3991 37.4976 32.9514 37.4976 32.3991V23.0105ZM11.4534 25.4373H16.9586V30.9424H11.4534V25.4373Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		comment: () => `<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.4191 12.4102C10.2801 12.4102 8.54614 14.1138 8.54614 16.2153V31.3654C8.54614 33.4669 10.2801 35.1706 12.4191 35.1706H27.3722L32.4489 40.1584C32.9369 40.6378 33.7712 40.2983 33.7712 39.6203V35.1706H35.5125C37.6515 35.1706 39.3855 33.4669 39.3855 31.3654V16.2153C39.3855 14.1138 37.6515 12.4102 35.5125 12.4102H12.4191ZM33.0871 21.156H14.9324C14.5736 21.156 14.2866 20.8997 14.2866 20.5792V18.2077C14.2866 17.8872 14.5736 17.6308 14.9324 17.6308H33.0871C33.4459 17.6308 33.7329 17.8872 33.7329 18.2077V20.5792C33.8046 20.8997 33.4459 21.156 33.0871 21.156ZM14.9329 28.2238H33.0875C33.4463 28.2238 33.8051 27.9675 33.7334 27.647V25.2755C33.7334 24.955 33.4463 24.6986 33.0875 24.6986H14.9329C14.5741 24.6986 14.2871 24.955 14.2871 25.2755V27.647C14.2871 27.9675 14.5741 28.2238 14.9329 28.2238Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		'list-check': () => `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.1903 17.2925C15.1662 17.3446 15.1338 17.3938 15.0928 17.4382L14.9429 17.6009L14.6644 17.948C14.6153 18.0084 14.5563 18.056 14.4916 18.0903L14.3952 18.1949C14.1845 18.4234 13.8285 18.4379 13.6001 18.2272L10.0245 14.9304C9.79606 14.7197 9.78161 14.3637 9.99231 14.1353L10.5434 13.5252C10.7541 13.2968 11.11 13.2823 11.3385 13.4929L13.7816 15.5606L19.1012 9.36509C19.2971 9.12396 19.6515 9.08722 19.8928 9.28323L20.4962 9.78551C20.7373 9.98145 20.774 10.3359 20.5781 10.5771L15.1903 17.2925ZM15.0748 8.05769C15.413 8.05769 15.6871 8.33182 15.6871 8.67V8.68917C15.6871 9.02729 15.413 9.30143 15.0748 9.30143H10.5015C10.1634 9.30143 9.88923 9.02729 9.88923 8.68917V8.67C9.88923 8.33182 10.1634 8.05769 10.5015 8.05769H15.0748ZM8.66463 8.05769C9.00281 8.05769 9.27695 8.33182 9.27695 8.67C9.27695 9.00819 9.00281 9.28232 8.66463 9.28232H8.30109C7.96291 9.28232 7.68872 9.00819 7.68872 8.67C7.68872 8.33182 7.96291 8.05769 8.30109 8.05769H8.66463ZM15.0748 10.507C15.413 10.507 15.6871 10.7811 15.6871 11.1193V11.1384C15.6871 11.4766 15.413 11.7507 15.0748 11.7507H10.5015C10.1634 11.7507 9.88923 11.4766 9.88923 11.1384V11.1193C9.88923 10.7811 10.1634 10.507 10.5015 10.507H15.0748ZM8.66463 10.507C9.00281 10.507 9.27695 10.7811 9.27695 11.1193C9.27695 11.4575 9.00281 11.7316 8.66463 11.7316H8.30109C7.96291 11.7316 7.68872 11.4575 7.68872 11.1193C7.68872 10.7811 7.96291 10.507 8.30109 10.507H8.66463ZM17.2371 17.5296L18.4617 15.9966V19.6917C18.4617 20.3681 17.9134 20.9163 17.2371 20.9163H6.21536C5.53899 20.9163 4.99072 20.3681 4.99072 19.6917V6.22073C4.99072 5.54436 5.53899 4.99609 6.21536 4.99609H17.2371C17.9134 4.99609 18.4617 5.54436 18.4617 6.22073V7.20674L17.2371 8.73974V6.22073H6.21536V19.6917H17.2371V17.5296Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		'calendar-share': () => {
			return `<svg width="46" height="47" viewBox="0 0 46 47" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.474 32.7183H17.6564L15.793 36.7113H9.55946C8.50263 36.7113 7.64491 35.8169 7.64491 34.7148V14.7503C7.63725 14.6465 7.63342 14.5447 7.63342 14.4428C7.63725 12.4025 9.22633 10.7534 11.183 10.7574H13.3886V11.7556C13.3886 13.4087 14.6732 14.7503 16.2604 14.7503C17.8475 14.7503 19.1322 13.4087 19.1322 11.7556V10.7574H24.9844V11.7556C24.9844 13.4087 26.271 14.7503 27.8562 14.7503C29.4415 14.7503 30.728 13.4087 30.728 11.7556V10.7574H33.1306C35.1447 10.8852 36.7051 12.644 36.6687 14.7503V21.9458L32.8396 18.7549V16.86H11.474V32.7183ZM17.6676 11.3603V9.16416C17.6714 8.3536 17.0473 7.69278 16.2699 7.68679C15.4926 7.68279 14.857 8.33563 14.8532 9.1442V9.16416V11.3603C14.8532 12.1708 15.4831 12.8277 16.2604 12.8277C17.0377 12.8277 17.6676 12.1708 17.6676 11.3603ZM29.1848 11.2996V9.21733C29.1848 8.45069 28.5894 7.83179 27.8561 7.83179C27.1228 7.83179 26.5274 8.45069 26.5274 9.21733V11.2976C26.5274 12.0623 27.119 12.6832 27.8542 12.6852C28.5894 12.6852 29.1848 12.0643 29.1848 11.2996ZM15.4088 20.3638C15.1327 20.3638 14.9088 20.5877 14.9088 20.8638V23.4858C14.9088 23.762 15.1327 23.9858 15.4088 23.9858H18.0308C18.307 23.9858 18.5308 23.762 18.5308 23.4858V20.8638C18.5308 20.5877 18.307 20.3638 18.0308 20.3638H15.4088ZM20.3418 20.9345C20.3418 20.6584 20.5657 20.4345 20.8418 20.4345H23.4639C23.74 20.4345 23.9639 20.6584 23.9639 20.9345V23.5566C23.9639 23.8327 23.74 24.0566 23.4639 24.0566H20.8418C20.5657 24.0566 20.3418 23.8327 20.3418 23.5566V20.9345ZM31.5845 22.3657C31.5845 22.0216 32.0255 21.8402 32.3022 22.0704L41.977 30.1212C42.2299 30.3316 42.2299 30.6981 41.977 30.9086L32.3022 38.9594C32.0255 39.1896 31.5845 39.0081 31.5845 38.6641V33.4757C31.532 33.4952 31.4747 33.506 31.415 33.506C25.8108 33.5066 20.9513 37.1951 18.9554 38.951C18.6496 39.2201 18.1465 38.9855 18.2387 38.6047C19.0187 35.3828 21.9484 27.0533 31.4257 26.8045C31.4814 26.8031 31.535 26.8118 31.5845 26.8289V22.3657Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'task-activity': () => {
			return `<svg width="46" height="46" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M27.7285 32.4535V27.3194H32.4676V13.4971H13.5112V27.3194H18.2503V32.4535H27.7285ZM11.9316 9.54785H34.0473C35.356 9.54785 36.4169 10.6087 36.4169 11.9174V34.0332C36.4169 35.3418 35.356 36.4027 34.0473 36.4027H11.9316C10.6229 36.4027 9.56201 35.3418 9.56201 34.0332V11.9174C9.56201 10.6087 10.6229 9.54785 11.9316 9.54785ZM19.288 19.7576L21.4996 22.0481L27.0285 16.4402L28.6082 18.8097L21.4996 25.9184L17.5503 21.9691L19.288 19.7576Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'unread-comment': () => {
			return `<svg width="46" height="46" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M34.3811 17.7997C37.7331 17.7997 40.4505 15.0823 40.4505 11.7303C40.4505 8.37817 37.7331 5.66077 34.3811 5.66077C31.029 5.66077 28.3116 8.37817 28.3116 11.7303C28.3116 15.0823 31.029 17.7997 34.3811 17.7997ZM36.4167 34.0327V20.3918C35.763 20.5449 35.0816 20.6258 34.3812 20.6258C33.7242 20.6258 33.0838 20.5546 32.4674 20.4194V27.319H27.7283V32.453H18.2501V27.319H13.511V13.4967H25.6611C25.5461 12.9258 25.4857 12.3351 25.4857 11.7303C25.4857 10.9771 25.5793 10.2458 25.7555 9.54743H11.9314C10.6227 9.54743 9.56181 10.6083 9.56181 11.917V34.0327C9.56181 35.3414 10.6227 36.4023 11.9314 36.4023H34.0471C35.3558 36.4023 36.4167 35.3414 36.4167 34.0327ZM17.1162 18.1754C17.1162 17.4117 17.7353 16.7926 18.499 16.7926H25.6985C26.4622 16.7926 27.0813 17.4117 27.0813 18.1754C27.0813 18.9391 26.4622 19.5582 25.6985 19.5582H18.499C17.7353 19.5582 17.1162 18.9391 17.1162 18.1754ZM18.4633 21.1963C17.7661 21.1963 17.2009 21.7616 17.2009 22.4588C17.2009 23.156 17.7661 23.7212 18.4633 23.7212H25.9036C26.6008 23.7212 27.166 23.156 27.166 22.4588C27.166 21.7616 26.6008 21.1963 25.9036 21.1963H18.4633Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		bizproc: () => {
			const blue = AppTheme.colors.accentBrandBlue;

			return `
				<svg width="46" height="46" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M7.66666 11.4553C7.66666 10.4214 8.50475 9.58334 9.53858 9.58334H24.1123C25.1462 9.58334 25.9843 10.4214 25.9843 11.4553V14.6281C25.9843 15.662 25.1462 16.5001 24.1123 16.5001H9.53858C8.50475 16.5001 7.66666 15.662 7.66666 14.6281V11.4553Z" fill="${blue}"/>
					<path d="M13.5967 21.3719C13.5967 20.3381 14.4348 19.5 15.4686 19.5H30.5314C31.5652 19.5 32.4033 20.3381 32.4033 21.3719V24.628C32.4033 25.6619 31.5652 26.5 30.5314 26.5H15.4686C14.4348 26.5 13.5967 25.6619 13.5967 24.628V21.3719Z" fill="${blue}"/>
					<path d="M20.0061 31.3719C20.0061 30.3381 20.8442 29.5 21.8781 29.5H36.4614C37.4952 29.5 38.3333 30.3381 38.3333 31.3719V34.5448C38.3333 35.5786 37.4952 36.4167 36.4614 36.4167H21.8781C20.8442 36.4167 20.0061 35.5786 20.0061 34.5448V31.3719Z" fill="${blue}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M28.5405 12.7494C28.5405 12.059 29.1001 11.4994 29.7905 11.4994H34.3991C36.5714 11.4994 38.3324 13.2604 38.3324 15.4327V21.5926C38.3324 22.283 37.7728 22.8426 37.0824 22.8426C36.3921 22.8426 35.8324 22.283 35.8324 21.5926V15.4327C35.8324 14.6411 35.1907 13.9994 34.3991 13.9994H29.7905C29.1001 13.9994 28.5405 13.4398 28.5405 12.7494Z" fill="${blue}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M11.6095 31.9927H16.2095C16.8999 31.9927 17.4595 32.5523 17.4595 33.2427C17.4595 33.933 16.8999 34.4927 16.2095 34.4927H11.6095C9.43721 34.4927 7.6762 32.7317 7.6762 30.5593L7.6762 24.4068C7.6762 23.7165 8.23585 23.1568 8.9262 23.1568C9.61656 23.1568 10.1762 23.7165 10.1762 24.4068V30.5593C10.1762 31.3509 10.8179 31.9927 11.6095 31.9927Z" fill="${blue}"/>
				</svg>
			`;
		},
		'bizproc-task': () => {
			const blue = AppTheme.colors.accentBrandBlue;
			const white = AppTheme.colors.graphicsBase1;

			return `
				<svg width="53" height="64" viewBox="0 0 53 64" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g opacity="0.7">
						<g filter="url(#filter0_d_1019_37062)">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8C4 4.68629 6.68629 2 10 2H33.1043C34.6578 2 36.1508 2.60257 37.2691 3.68093L46.9648 13.0304C48.1375 14.1612 48.8 15.7203 48.8 17.3494V52C48.8 55.3137 46.1137 58 42.8 58H9.99999C6.68628 58 4 55.3137 4 52V8Z" fill="${white}"/>
							<path d="M4.5 8C4.5 4.96243 6.96243 2.5 10 2.5H33.1043C34.5284 2.5 35.8969 3.05236 36.922 4.04086L46.6177 13.3903C47.6927 14.4269 48.3 15.8561 48.3 17.3494V52C48.3 55.0376 45.8376 57.5 42.8 57.5H9.99999C6.96243 57.5 4.5 55.0376 4.5 52V8Z" stroke="${blue}"/>
						</g>
						<g opacity="0.5" filter="url(#filter1_d_1019_37062)">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M4 8C4 4.68629 6.68629 2 10 2H33.1043C34.6578 2 36.1508 2.60257 37.2691 3.68093L46.9648 13.0304C48.1375 14.1612 48.8 15.7203 48.8 17.3494V52C48.8 55.3137 46.1137 58 42.8 58H9.99999C6.68628 58 4 55.3137 4 52V8Z" fill="${white}"/>
							<path d="M4.5 8C4.5 4.96243 6.96243 2.5 10 2.5H33.1043C34.5284 2.5 35.8969 3.05236 36.922 4.04086L46.6177 13.3903C47.6927 14.4269 48.3 15.8561 48.3 17.3494V52C48.3 55.0376 45.8376 57.5 42.8 57.5H9.99999C6.96243 57.5 4.5 55.0376 4.5 52V8Z" stroke="${blue}"/>
						</g>
						<path opacity="0.2" d="M37.3601 16C36.2555 16 35.3601 15.1042 35.3601 13.9996V4.3488C35.3601 3.46731 36.4178 3.017 37.0532 3.62796L42.6401 8.99997L48.2401 14.32L48.8001 16H37.3601Z" fill="${blue}"/>
					</g>
					<path d="M10.6666 18.4553C10.6666 17.4215 11.5047 16.5834 12.5386 16.5834H27.1123C28.1461 16.5834 28.9842 17.4215 28.9842 18.4553V21.6282C28.9842 22.662 28.1461 23.5001 27.1123 23.5001H12.5386C11.5047 23.5001 10.6666 22.662 10.6666 21.6282V18.4553Z" fill="${blue}"/>
					<path d="M16.5967 28.3719C16.5967 27.3381 17.4348 26.5 18.4686 26.5H33.5313C34.5652 26.5 35.4033 27.3381 35.4033 28.3719V31.6281C35.4033 32.6619 34.5652 33.5 33.5313 33.5H18.4686C17.4348 33.5 16.5967 32.6619 16.5967 31.6281V28.3719Z" fill="${blue}"/>
					<path d="M23.0061 38.372C23.0061 37.3381 23.8442 36.5 24.878 36.5H39.4614C40.4952 36.5 41.3333 37.3381 41.3333 38.372V41.5448C41.3333 42.5786 40.4952 43.4167 39.4614 43.4167H24.878C23.8442 43.4167 23.0061 42.5786 23.0061 41.5448V38.372Z" fill="${blue}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M31.5404 19.7494C31.5404 19.0591 32.1001 18.4994 32.7904 18.4994H37.3991C39.5714 18.4994 41.3324 20.2604 41.3324 22.4328V28.5927C41.3324 29.283 40.7728 29.8427 40.0824 29.8427C39.392 29.8427 38.8324 29.283 38.8324 28.5927V22.4328C38.8324 21.6412 38.1907 20.9994 37.3991 20.9994H32.7904C32.1001 20.9994 31.5404 20.4398 31.5404 19.7494Z" fill="${blue}"/>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M14.6095 38.9927H19.2095C19.8999 38.9927 20.4595 39.5523 20.4595 40.2427C20.4595 40.9331 19.8999 41.4927 19.2095 41.4927H14.6095C12.4372 41.4927 10.6762 39.7317 10.6762 37.5594L10.6762 31.4069C10.6762 30.7165 11.2358 30.1569 11.9262 30.1569C12.6165 30.1569 13.1762 30.7165 13.1762 31.4069V37.5594C13.1762 38.351 13.8179 38.9927 14.6095 38.9927Z" fill="${blue}"/>
					<defs>
						<filter id="filter0_d_1019_37062" x="0.64" y="0.88" width="51.52" height="62.72" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
							<feFlood flood-opacity="0" result="BackgroundImageFix"/>
							<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
							<feOffset dy="2.24"/>
							<feGaussianBlur stdDeviation="1.68"/>
							<feComposite in2="hardAlpha" operator="out"/>
							<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.24977 0 0 0 0 0.329167 0 0 0 0.2 0"/>
							<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1019_37062"/>
							<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1019_37062" result="shape"/>
						</filter>
						<filter id="filter1_d_1019_37062" x="0.64" y="0.88" width="51.52" height="62.72" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
							<feFlood flood-opacity="0" result="BackgroundImageFix"/>
							<feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
							<feOffset dy="2.24"/>
							<feGaussianBlur stdDeviation="1.68"/>
							<feComposite in2="hardAlpha" operator="out"/>
							<feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.24977 0 0 0 0 0.329167 0 0 0 0.2 0"/>
							<feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_1019_37062"/>
							<feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_1019_37062" result="shape"/>
						</filter>
					</defs>
				</svg>
			`;
		},
	};

	const addIcons = {
		background: `<svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 1H33V23C33 28.5228 28.5228 33 23 33H1V12C1 5.92487 5.92487 1 12 1Z" fill="${AppTheme.colors.bgContentPrimary}" stroke="${AppTheme.colors.bgContentPrimary}" stroke-width="2"/></svg>`,
		arrow: (color) => {
			return `<svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.4443 12.4756C16.8939 12.4756 17.1151 13.0228 16.7917 13.3352L14.5302 15.5196L19.4055 20.2288L24.7361 15.0798C24.9783 14.8458 25.3624 14.8458 25.6046 15.0798L27.8091 17.2092C28.0635 17.4549 28.0635 17.8626 27.8091 18.1083L21.1423 24.5479C20.1735 25.4837 18.6374 25.4837 17.6686 24.5479L11.426 18.518L9.12191 20.7436C8.80451 21.0502 8.27454 20.8253 8.27454 20.384L8.27454 12.9756C8.27454 12.6994 8.49839 12.4756 8.77454 12.4756L16.4443 12.4756Z" fill="${color}"/></svg>`;
		},
		cross: (color) => {
			return `<svg width="34" height="34" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.1257 10.4365L16 14.5621L11.8744 10.4365C11.1243 9.68634 10.1866 9.68634 9.43649 10.4365C8.68637 11.1866 8.68637 12.1242 9.43649 12.8743L13.5621 17L9.43649 21.1256C8.68637 21.8758 8.68637 22.8134 9.43649 23.5635C10.1866 24.3137 11.1243 24.3137 11.8744 23.5635L16 19.4379L20.1257 23.5635C20.8758 24.3137 21.8134 24.3137 22.5636 23.5635C23.3137 22.8134 23.3137 21.8758 22.5636 21.1256L18.4379 17L22.5636 12.8743C23.3137 12.1242 23.3137 11.1866 22.5636 10.4365C21.8134 9.87387 20.6883 9.87387 20.1257 10.4365Z" fill="${color}"/></svg>`;
		},
		search: () => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.5629 19.8585C16.3096 20.6815 14.8101 21.1602 13.1988 21.1602C8.80185 21.1602 5.23743 17.5957 5.23743 13.1988C5.23743 8.80185 8.80185 5.23743 13.1988 5.23743C17.5957 5.23743 21.1602 8.80185 21.1602 13.1988C21.1602 14.8101 20.6815 16.3096 19.8585 17.5629L24.1733 21.8777C24.5638 22.2682 24.5638 22.9013 24.1733 23.2919L23.2919 24.1733C22.9013 24.5638 22.2682 24.5638 21.8777 24.1733L17.5629 19.8585ZM18.8855 13.1988C18.8855 16.3395 16.3395 18.8855 13.1988 18.8855C10.0581 18.8855 7.5121 16.3395 7.5121 13.1988C7.5121 10.0581 10.0581 7.5121 13.1988 7.5121C16.3395 7.5121 18.8855 10.0581 18.8855 13.1988Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'arrow-outgoing': () => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M15.7897 6.03929C15.3959 5.64556 14.7227 5.92442 14.7227 6.48123V11.2223H9.375C9.02982 11.2223 8.75 11.5022 8.75 11.8473V18.0974C8.75 18.4425 9.02982 18.7224 9.375 18.7224H14.7227V22.9089C14.7227 23.4657 15.3959 23.7446 15.7897 23.3508L24.0035 15.137C24.2476 14.8929 24.2476 14.4972 24.0035 14.2531L15.7897 6.03929Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		'arrow-incoming': () => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M16.7103 23.3506C17.1041 23.7443 17.7773 23.4655 17.7773 22.9086L17.7773 18.1675L23.125 18.1675C23.4702 18.1675 23.75 17.8877 23.75 17.5425L23.75 11.2925C23.75 10.9473 23.4702 10.6675 23.125 10.6675L17.7773 10.6675L17.7773 6.48097C17.7773 5.92416 17.1041 5.6453 16.7103 6.03903L8.49651 14.2529C8.25243 14.4969 8.25243 14.8927 8.49651 15.1367L16.7103 23.3506Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		done: () => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M13.7961 16.2028L11.561 13.9677L9.98089 15.5478L13.7101 19.277L13.7117 19.2755L13.7977 19.3615L20.7345 12.4246L19.1544 10.8445L13.7961 16.2028ZM14.9264 24.9597C9.41466 24.9597 4.94653 20.4916 4.94653 14.9798C4.94653 9.46813 9.41466 5 14.9264 5C20.4381 5 24.9062 9.46813 24.9062 14.9798C24.9062 20.4916 20.4381 24.9597 14.9264 24.9597Z" fill="${AppTheme.colors.accentMainSuccess}"/></svg>`;
		},
		check: (color) => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M13.7959 16.2028L11.5607 13.9677L9.98065 15.5478L13.7099 19.277L13.7114 19.2755L13.7974 19.3615L20.7343 12.4246L19.1542 10.8445L13.7959 16.2028ZM14.9261 24.9597C9.41442 24.9597 4.94629 20.4916 4.94629 14.9798C4.94629 9.46813 9.41442 5 14.9261 5C20.4378 5 24.906 9.46813 24.906 14.9798C24.906 20.4916 20.4378 24.9597 14.9261 24.9597Z" fill="${color}"/> </svg>`;
		},
		clock: () => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.91496 19.0853C7.58204 22.7924 11.3345 25.1147 15.396 24.9527C20.7842 24.8425 25.0645 20.3879 24.9597 14.9996C24.9595 10.9348 22.4894 7.27802 18.7186 5.76016C14.9479 4.24231 10.6332 5.16795 7.81694 8.09893C5.00065 11.0299 4.24788 15.3781 5.91496 19.0853ZM13.75 10C13.75 9.30966 14.3096 8.75001 15 8.75001C15.6904 8.75001 16.25 9.30966 16.25 10V13.75H18.75C19.4404 13.75 20 14.3097 20 15C20 15.6904 19.4404 16.25 18.75 16.25H15C14.3096 16.25 13.75 15.6904 13.75 15V10Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
		comment: () => {
			return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.87439 7.66357C6.80332 7.66357 5.12439 9.34251 5.12439 11.4136V18.6314C5.12439 20.7025 6.80332 22.3814 8.87439 22.3814H9.40018V25.1608C9.40018 25.9464 10.3552 26.334 10.9027 25.7706L14.1959 22.3814H21.1669C23.2379 22.3814 24.9169 20.7025 24.9169 18.6314V11.4136C24.9169 9.34251 23.2379 7.66357 21.1669 7.66357H8.87439ZM8.38389 12.2156C8.38389 11.6277 8.86046 11.1511 9.44835 11.1511H18.4369C19.0248 11.1511 19.5014 11.6277 19.5014 12.2156C19.5014 12.8035 19.0248 13.28 18.4369 13.28H9.44835C8.86047 13.28 8.38389 12.8035 8.38389 12.2156ZM9.44835 15.3784C8.86046 15.3784 8.38389 15.855 8.38389 16.4429C8.38389 17.0308 8.86047 17.5073 9.44835 17.5073H18.4369C19.0248 17.5073 19.5014 17.0308 19.5014 16.4429C19.5014 15.855 19.0248 15.3784 18.4369 15.3784H9.44835Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`;
		},
	};

	module.exports = { TimelineItemIconLogo };
});
