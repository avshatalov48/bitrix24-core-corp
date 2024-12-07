/**
 * @module calendar/layout/sharing-switcher
 */
jn.define('calendar/layout/sharing-switcher', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { confirmDestructiveAction } = require('alert');
	const { settings } = require('assets/common');
	const { lighten, withPressed } = require('utils/color');
	const { BooleanField } = require('layout/ui/fields/boolean');

	const { ModelSharingStatus } = require('calendar/model/sharing');

	/**
	 * @class SharingSwitcher
	 */
	class SharingSwitcher extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.onOpenLinkClickHandler = this.onOpenLinkClickHandler.bind(this);
		}

		get model()
		{
			return this.props.model;
		}

		askChangeSharingOff()
		{
			return new Promise((resolve, reject) => {
				if (this.props.isOn)
				{
					confirmDestructiveAction({
						title: '',
						description: Loc.getMessage('L_MS_CONFIRMATION_TEXT_1'),
						destructionText: Loc.getMessage('L_MS_CONFIRMATION_BUTTON_OK'),
						onDestruct: () => resolve(),
						onCancel: () => reject(),
					});
				}
				else
				{
					resolve();
				}
			});
		}

		render()
		{
			const { isCalendarContext } = this.props;

			return View(
				{
					style: {
						...styles.container,
						backgroundColor: isCalendarContext ? AppTheme.colors.accentSoftBlue2 : AppTheme.colors.base8,
					},
				},
				isCalendarContext && View(
					{
						style: styles.leftCircle,
					},
				),
				isCalendarContext && View(
					{
						style: styles.rightCircle,
					},
				),
				!isCalendarContext && Image({
					style: {
						position: 'absolute',
						width: 112,
						height: 68,
						left: 40,
						bottom: 30,
					},
					svg: {
						content: icons.clouds,
					},
				}),
				this.renderSwitcherBody(),
				isCalendarContext && this.renderCalendarIcon(),
			);
		}

		renderSwitcher()
		{
			return BooleanField({
				readOnly: false,
				showEditIcon: false,
				showTitle: false,
				value: this.props.isOn,
				config: {
					styles: {
						fontSize: 18,
						lineHeight: 21,
						fontWeight: '400',
						color: AppTheme.colors.base0,
						activeToggleColor: AppTheme.colors.accentBrandBlue,
					},
					deepMergeStyles: {
						description: {
							fontSize: 18,
							lineHeight: 21,
							fontWeight: '400',
							color: AppTheme.colors.base0,
						},
					},
					description: Loc.getMessage('L_MS_SWITCHER'),
				},
				testId: 'sharingSwitcher',
				onBeforeChange: () => this.askChangeSharingOff(),
				onChange: (value) => this.props.onChange(value
					? ModelSharingStatus.ENABLE
					: ModelSharingStatus.DISABLE)
				,
			});
		}

		renderTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Text(
					{
						style: {
							flex: 1,
							fontSize: 18,
							lineHeight: 21,
							marginBottom: 10,
							marginTop: 10,
						},
						text: Loc.getMessage('L_MS_SWITCHER'),
					},
				),
				this.props.onSettingsClick && Image({
					onClick: this.props.onSettingsClick,
					style: {
						width: 24,
						height: 24,
					},
					svg: {
						content: settings({ color: AppTheme.colors.base4 }),
					},
				}),
			);
		}

		renderDescription()
		{
			const { isCalendarContext } = this.props;

			return View(
				{
					testId: 'sharingSwitcherDescription',
				},
				Text(
					{
						style: {
							fontSize: 14,
							fontWeight: '400',
							color: AppTheme.colors.base1,
						},
						text: isCalendarContext
							? Loc.getMessage('L_MS_DESCRIPTION')
							: Loc.getMessage('L_MS_DESCRIPTION_CRM')
						,
					},
				),
			);
		}

		renderOpenLink()
		{
			const { isCalendarContext } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingTop: 15,
						paddingBottom: 10,
					},
				},
				Button({
					onClick: this.onOpenLinkClickHandler,
					text: Loc.getMessage('CALENDARMOBILE_SHARING_SWITCHER_OPEN_LINK'),
					style: {
						color: withPressed(AppTheme.colors.accentMainLinks),
						height: 20,
						...(isCalendarContext ? styles.openLinkBorder : {}),
					},
				}),
			);
		}

		onOpenLinkClickHandler()
		{
			Application.openUrl(this.model.getPublicShortUrl());
		}

		renderSwitcherBody()
		{
			const { isCalendarContext } = this.props;

			return View(
				{
					style: {
						width: isCalendarContext ? '65%' : '100%',
					},
				},
				View(
					{},
					isCalendarContext ? this.renderSwitcher() : this.renderTitle(),
				),
				this.renderDescription(),
				this.model.isEnabled() && this.renderOpenLink(),
			);
		}

		renderCalendarIcon()
		{
			return View(
				{
					style: {
						paddingHorizontal: 10,
						justifyContent: 'center',
					},
				},
				Image(
					{
						svg: {
							content: icons.calendar,
						},
						style: {
							width: 116,
							height: 96,
						},
					},
				),
			);
		}
	}

	const styles = {
		leftCircle: {
			position: 'absolute',
			bottom: -140,
			left: -130,
			width: 260,
			height: 260,
			borderRadius: 130,
			borderWidth: 45,
			borderColor: AppTheme.colors.bgContentSecondary,
			opacity: 0.4,
		},
		rightCircle: {
			position: 'absolute',
			top: -110,
			right: -80,
			width: 198,
			height: 198,
			borderRadius: 99,
			borderWidth: 26,
			borderColor: AppTheme.colors.bgContentSecondary,
			opacity: 0.4,
		},
		container: {
			paddingLeft: 20,
			paddingRight: 20,
			display: 'flex',
			flexDirection: 'row',
			justifyContent: 'space-between',
			alignItems: 'flex-start',
			paddingTop: 10,
			paddingBottom: 10,
		},
		openLinkBorder: {
			borderBottomColor: lighten(AppTheme.colors.accentMainLinks, 0.4),
			borderBottomWidth: 2,
			borderStyle: 'dash',
		},
	};

	const softBlue = AppTheme.colors.accentSoftBlue3;

	const icons = {
		calendar: '<svg width="117" height="96" viewBox="0 0 117 96" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.7"><path fill-rule="evenodd" clip-rule="evenodd" d="M84.0907 3.50899C84.8013 3.50899 85.3774 2.93291 85.3774 2.22227C85.3774 1.51163 84.8013 0.935547 84.0907 0.935547C83.3801 0.935547 82.804 1.51163 82.804 2.22227C82.804 2.93291 83.3801 3.50899 84.0907 3.50899ZM20.5972 14.6242C22.1378 14.6242 23.3867 13.3754 23.3867 11.8348C23.3867 10.2942 22.1378 9.04538 20.5972 9.04538C19.0567 9.04538 17.8078 10.2942 17.8078 11.8348C17.8078 13.3754 19.0567 14.6242 20.5972 14.6242ZM20.5972 13.069C21.2788 13.069 21.8314 12.5164 21.8314 11.8348C21.8314 11.1532 21.2788 10.6006 20.5972 10.6006C19.9156 10.6006 19.3631 11.1532 19.3631 11.8348C19.3631 12.5164 19.9156 13.069 20.5972 13.069ZM6.42619 41.8556C8.50111 41.8556 10.1832 40.1736 10.1832 38.0987C10.1832 36.0237 8.50111 34.3417 6.42619 34.3417C4.35127 34.3417 2.66921 36.0237 2.66921 38.0987C2.66921 40.1736 4.35127 41.8556 6.42619 41.8556ZM6.42619 39.8949C7.41821 39.8949 8.2224 39.0907 8.2224 38.0987C8.2224 37.1066 7.41821 36.3024 6.42619 36.3024C5.43417 36.3024 4.62998 37.1066 4.62998 38.0987C4.62998 39.0907 5.43417 39.8949 6.42619 39.8949ZM101.812 89.1296C103.289 89.1296 104.486 87.9321 104.486 86.4548C104.486 84.9776 103.289 83.78 101.812 83.78C100.334 83.78 99.1368 84.9776 99.1368 86.4548C99.1368 87.9321 100.334 89.1296 101.812 89.1296ZM101.812 87.7376C102.52 87.7376 103.094 87.1633 103.094 86.4548C103.094 85.7463 102.52 85.172 101.812 85.172C101.103 85.172 100.529 85.7463 100.529 86.4548C100.529 87.1633 101.103 87.7376 101.812 87.7376ZM58.9998 96C84.4049 96 105 75.4051 105 50C105 24.5949 84.4049 4.00003 58.9998 4.00003C33.5947 4.00003 12.9998 24.5949 12.9998 50C12.9998 75.4051 33.5947 96 58.9998 96ZM12.9778 92.7847H4.03527C3.95875 92.7847 3.88289 92.7818 3.80779 92.776C1.78676 92.7288 0.1627 91.0303 0.162598 88.9421C0.163185 87.9243 0.557169 86.9485 1.25788 86.2292C1.61705 85.8605 2.04173 85.5748 2.50378 85.3839C2.49605 85.2803 2.49211 85.1756 2.49211 85.07C2.49275 83.9961 2.90847 82.9664 3.64783 82.2075C4.38719 81.4486 5.38962 81.0226 6.43459 81.0233C7.77318 81.025 8.95499 81.7129 9.66524 82.7629C10.0025 82.64 10.3656 82.5733 10.7438 82.5735C12.4148 82.5756 13.7872 83.8846 13.9463 85.5586C15.5473 85.9173 16.7454 87.3831 16.744 89.1357C16.7424 91.1554 15.1483 92.7916 13.1831 92.7907C13.1141 92.7907 13.0457 92.7887 12.9778 92.7847ZM107.748 21.6471H114.094C114.143 21.6498 114.191 21.6512 114.24 21.6512C115.635 21.6518 116.766 20.5362 116.767 19.1591C116.768 17.9642 115.918 16.9648 114.782 16.7202C114.669 15.5788 113.695 14.6863 112.509 14.6849C112.241 14.6848 111.983 14.7303 111.744 14.814C111.24 14.0981 110.401 13.6291 109.451 13.6279C108.709 13.6275 107.998 13.9179 107.473 14.4354C106.948 14.9528 106.653 15.6549 106.653 16.3871C106.653 16.4591 106.656 16.5304 106.661 16.601C106.333 16.7312 106.032 16.9261 105.777 17.1774C105.28 17.6678 105 18.3332 105 19.0272C105 20.4509 106.152 21.6089 107.587 21.6412C107.64 21.6451 107.694 21.6471 107.748 21.6471Z" fill="white"/></g><g filter="url(#filter0_d_4327_11905)"><path d="M29.8994 31.8276C29.8994 27.5801 33.3372 24.1367 37.5779 24.1367H80.9063C85.147 24.1367 88.5847 27.5801 88.5847 31.8276V37.8705C88.5847 42.1181 85.147 45.5614 80.9063 45.5614H37.5779C33.3372 45.5614 29.8994 42.1181 29.8994 37.8705V31.8276Z" fill="#55D0E0"/></g><g filter="url(#filter1_d_4327_11905)"><path d="M29.8994 45.6157C29.8994 41.3681 33.3372 37.9248 37.5779 37.9248H80.9063C85.147 37.9248 88.5847 41.3681 88.5847 45.6157V71.4352C88.5847 75.6828 85.147 79.1261 80.9063 79.1261H37.5779C33.3372 79.1261 29.8994 75.6828 29.8994 71.4352V45.6157Z" fill="#55D0E0"/></g><path d="M29.8994 44.324C29.8994 40.0764 33.3372 36.6331 37.5779 36.6331H80.9063C85.147 36.6331 88.5847 40.0764 88.5847 44.324V70.1435C88.5847 74.391 85.147 77.8344 80.9063 77.8344H37.5779C33.3372 77.8344 29.8994 74.391 29.8994 70.1435V44.324Z" fill="#55D0E0"/><path d="M29.8994 43.0322C29.8994 38.7846 33.3372 35.3413 37.5779 35.3413H80.9063C85.147 35.3413 88.5847 38.7846 88.5847 43.0322V68.8517C88.5847 73.0993 85.147 76.5426 80.9063 76.5426H37.5779C33.3372 76.5426 29.8994 73.0993 29.8994 68.8517V43.0322Z" fill="white"/><rect x="35.4141" y="41.511" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="35.4141" y="52.4919" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="35.4141" y="63.4729" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="45.7021" y="41.511" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect opacity="0.8" x="45.7021" y="52.4919" width="6.44894" height="6.4594" rx="1.17582" fill="#2FC6F6"/><rect x="45.7021" y="63.4729" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="55.9902" y="41.511" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="55.9902" y="52.4919" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect opacity="0.8" x="55.9902" y="63.4729" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect opacity="0.8" x="66.2783" y="41.511" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="66.2783" y="52.4919" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect opacity="0.8" x="76.5664" y="41.511" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect x="76.5664" y="52.4919" width="6.44894" height="6.4594" rx="1.17582" fill="#DFE0E3"/><rect opacity="0.2" x="40.8628" y="20.2915" width="4.51426" height="11.6269" rx="2.25713" fill="#29A8DF"/><rect x="40.8628" y="18.9993" width="4.51426" height="11.6269" rx="2.25713" fill="#29A8DF"/><rect opacity="0.2" x="73.7524" y="20.2915" width="4.51426" height="11.6269" rx="2.25713" fill="#29A8DF"/><rect x="73.7524" y="18.9993" width="4.51426" height="11.6269" rx="2.25713" fill="#29A8DF"/><path d="M85.339 62.3244C84.9967 62.0507 84.4511 62.2664 84.4511 62.6754V67.781C84.4511 67.8794 84.3529 67.9494 84.2547 67.9519C72.5296 68.2476 68.905 78.1489 67.94 81.9788C67.826 82.4314 68.4483 82.7103 68.8267 82.3905C71.296 80.3032 77.3081 75.9186 84.2414 75.9179C84.3456 75.9179 84.4511 75.9917 84.4511 76.096V82.0494C84.4511 82.4583 84.9967 82.674 85.339 82.4004L97.3085 72.8304C97.6214 72.5802 97.6214 72.1445 97.3085 71.8944L85.339 62.3244Z" fill="#9DCF00"/><path fill-rule="evenodd" clip-rule="evenodd" d="M85.3388 62.3245C84.9965 62.0508 84.4509 62.2665 84.4509 62.6755V67.781C84.4509 67.8794 84.3527 67.9495 84.2545 67.9519C75.2881 68.1781 71.0588 74.0215 69.1335 78.4714C68.9603 78.8716 68.8058 79.2606 68.6682 79.6331C68.4149 80.3185 68.2188 80.9484 68.0687 81.4912C68.0212 81.6628 67.9784 81.8257 67.9398 81.9789C67.8258 82.4315 68.4481 82.7104 68.8265 82.3905C68.882 82.3436 68.9392 82.2956 68.9983 82.2464C69.4352 81.8826 69.9686 81.4595 70.5863 81.0076C70.8714 80.799 71.1746 80.5843 71.4945 80.3665C74.004 78.6581 77.5462 76.76 81.5483 76.1323C81.5482 76.1323 81.5484 76.1323 81.5483 76.1323C81.6284 76.1197 81.7089 76.1076 81.7894 76.0961C82.5904 75.9813 83.4092 75.9181 84.2412 75.918C84.3454 75.918 84.4509 75.9917 84.4509 76.0961V82.0494C84.4509 82.4584 84.9965 82.6741 85.3388 82.4004L97.3083 72.8304C97.6212 72.5803 97.6212 72.1446 97.3083 71.8945L85.3388 62.3245ZM81.5483 79.0815V82.0494C81.5483 83.712 82.6809 84.7821 83.7009 85.1853C84.7188 85.5876 86.0638 85.5408 87.1496 84.6726L99.1191 75.1026C100.888 73.6886 100.888 71.0363 99.1191 69.6223L87.1496 60.0523C86.0638 59.1842 84.7188 59.1373 83.7009 59.5397C82.6809 59.9429 81.5483 61.0129 81.5483 62.6755V65.2643C76.1758 66.0387 72.395 68.7034 69.8337 71.7771C66.9493 75.2386 65.6608 79.1426 65.1254 81.2674C64.6427 83.1833 65.8642 84.6026 66.9923 85.1188C68.0757 85.6146 69.5498 85.5834 70.6985 84.6124C72.7026 82.9183 76.8062 79.9642 81.5483 79.0815Z" fill="white"/><defs><filter id="filter0_d_4327_11905" x="26.8994" y="21.6367" width="64.6855" height="27.4248" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="0.5"/><feGaussianBlur stdDeviation="1.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.470588 0 0 0 0 0.619608 0 0 0 0.14 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4327_11905"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4327_11905" result="shape"/></filter><filter id="filter1_d_4327_11905" x="26.8994" y="35.4248" width="64.6855" height="47.2014" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="0.5"/><feGaussianBlur stdDeviation="1.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.470588 0 0 0 0 0.619608 0 0 0 0.14 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_4327_11905"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_4327_11905" result="shape"/></filter></defs></svg>',
		clouds: `<svg width="112" height="68" viewBox="0 0 112 68" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.6"><path fill-rule="evenodd" clip-rule="evenodd" d="M25.5045 67.9881H7.70733C7.55504 67.9881 7.40407 67.9825 7.25461 67.9713C3.23239 67.8789 0.000203217 64.5592 0 60.4778C0.00116939 58.4884 0.785268 56.5811 2.1798 55.1753C2.89463 54.4546 3.73982 53.8961 4.65937 53.5229C4.64399 53.3205 4.63616 53.1159 4.63616 52.9095C4.63742 50.8105 5.46479 48.798 6.93625 47.3146C8.40771 45.8313 10.4027 44.9987 12.4824 45C15.1464 45.0033 17.4985 46.3478 18.912 48.4001C19.5832 48.16 20.3058 48.0296 21.0586 48.03C24.3841 48.034 27.1154 50.5925 27.432 53.8645C30.6183 54.5656 33.0028 57.4306 33 60.8561C32.9968 64.8037 29.8243 68.0016 25.9131 68C25.776 67.9999 25.6397 67.996 25.5045 67.9881Z" fill="${softBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M81.7819 46.9758H45.6482C45.339 46.9758 45.0325 46.9642 44.729 46.9414C36.5627 46.7525 30.0004 39.9688 30 31.6285C30.0024 27.5633 31.5943 23.6657 34.4257 20.7929C35.877 19.3204 37.593 18.1789 39.4599 17.4164C39.4287 17.0027 39.4128 16.5847 39.4128 16.1629C39.4154 11.8736 41.0952 7.76107 44.0827 4.72993C47.0702 1.6988 51.1207 -0.00261137 55.3431 3.00848e-06C60.7519 0.00670295 65.5272 2.75429 68.3971 6.94797C69.7599 6.45746 71.227 6.19086 72.7554 6.19168C79.507 6.19993 85.0524 11.4283 85.6953 18.1144C92.1645 19.5471 97.0057 25.4017 97 32.4017C96.9934 40.4684 90.5524 47.0033 82.6114 47C82.333 46.9999 82.0564 46.9917 81.7819 46.9758Z" fill="${softBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M102.46 37.985H79.8093C79.6155 37.985 79.4234 37.9779 79.2331 37.9638C74.1139 37.8473 70.0003 33.6616 70 28.5154C70.0015 26.0072 70.9994 23.6022 72.7743 21.8297C73.6841 20.9211 74.7598 20.2168 75.9301 19.7463C75.9105 19.4911 75.9006 19.2331 75.9006 18.9729C75.9022 16.3263 76.9552 13.7887 78.828 11.9185C80.7007 10.0482 83.2398 8.99839 85.8867 9C89.2773 9.00414 92.2708 10.6995 94.0698 13.287C94.9241 12.9844 95.8438 12.8199 96.8019 12.8204C101.034 12.8255 104.51 16.0515 104.913 20.177C108.969 21.061 112.004 24.6734 112 28.9925C111.996 33.9699 107.958 38.0021 102.98 38C102.806 37.9999 102.632 37.9949 102.46 37.985Z" fill="${softBlue}"/></g></svg>`,
	};

	module.exports = { SharingSwitcher };
});
