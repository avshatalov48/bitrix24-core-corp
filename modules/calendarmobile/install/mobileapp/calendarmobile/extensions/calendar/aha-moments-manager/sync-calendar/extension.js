/**
 * @module calendar/aha-moments-manager/sync-calendar
 */
jn.define('calendar/aha-moments-manager/sync-calendar', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventAjax } = require('calendar/ajax');
	const { cross } = require('assets/common');
	const { Loc } = require('loc');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class SyncCalendar
	 */
	class SyncCalendar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isVisible: false,
				isClosed: false,
			};

			this.nodeRef = null;
			this.popupRef = null;

			this.close = this.close.bind(this);
		}

		show()
		{
			if (this.nodeRef && this.popupRef)
			{
				const animationOptions = {
					duration: 300,
					opacity: 1,
					option: 'easeInOut',
				};

				this.nodeRef.animate(animationOptions);
				this.popupRef.animate(animationOptions);
			}
		}

		actualize()
		{
			const isVisible = this.isVisible();

			if (isVisible)
			{
				this.setState({ isVisible }, () => {
					setTimeout(() => this.show(), 300);
				});
			}
		}

		isVisible()
		{
			return !this.state.isClosed;
		}

		render()
		{
			const { isVisible } = this.state;
			const ahaMomentPosition = -10;
			const bottomTriangleRightPosition = 10;

			return View(
				{
					style: {
						position: 'absolute',
						display: isVisible ? 'flex' : 'none',
						zIndex: 10,
						bottom: 0,
						top: 0,
						left: 0,
						right: 0,
						opacity: 0,
					},
					ref: (ref) => {
						this.nodeRef = ref;
					},
				},
				View(
					{
						style: {
							backgroundColor: AppTheme.colors.baseBlackFixed,
							opacity: 0.6,
							flex: 1,
							position: 'absolute',
							top: 0,
							left: 0,
							right: 0,
							bottom: 0,
						},
						onClick: isVisible && this.close,
					},
				),
				View(
					{
						style: {
							alignItems: 'flex-end',
							justifyContent: 'center',
							width: '100%',
							paddingVertical: 14,
							paddingHorizontal: 18,
							position: 'absolute',
							opacity: 0,
							top: ahaMomentPosition,
							left: isAndroid ? 5 : 10,
							flexDirection: 'column',
						},
						ref: (ref) => {
							this.popupRef = ref;
						},
					},
					Image({
						style: {
							width: 22,
							height: 12,
							zIndex: 20,
							top: isAndroid ? 1 : 0,
							right: bottomTriangleRightPosition,
						},
						svg: {
							content: icons.bottomTriangle,
						},
						tintColor: AppTheme.colors.bgContentPrimary,
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								borderRadius: 8,
								padding: 12,
								width: '100%',
								backgroundColor: AppTheme.colors.bgContentPrimary,
							},
						},
						View(
							{
								style: {
									backgroundColor: AppTheme.colors.accentSoftBlue2,
									borderRadius: 6,
									width: 94,
									alignItems: 'center',
									justifyContent: 'center',
									alignContent: 'center',
									marginRight: 12,
								},
							},
							View(
								{
									style: styles.circle,
								},
							),
							Image({
								style: {
									width: 74,
									height: 74,
								},
								svg: {
									content: icons.calendar,
								},
							}),
						),
						View(
							{
								style: {
									flex: 1,
									justifyContent: 'center',
									alignItems: 'flex-start',
								},
							},
							Text({
								style: {
									fontSize: 16,
									fontWeight: '500',
									color: AppTheme.colors.base1,
									paddingBottom: 5,
								},
								text: Loc.getMessage('M_CALENDAR_AHA_SYNC_CALENDAR_TITLE'),
							}),
							Text({
								style: {
									fontSize: 14,
									color: AppTheme.colors.base2,
									lineHeightMultiple: 1.2,
								},
								text: Loc.getMessage('M_CALENDAR_AHA_SYNC_CALENDAR_DESC'),
							}),
						),
						Image({
							style: {
								position: 'absolute',
								width: 24,
								height: 24,
								right: 3,
								top: 4,
							},
							svg: {
								content: cross(AppTheme.colors.base5),
							},
							onClick: isVisible && this.close,
						}),
					),
				),
			);
		}

		close()
		{
			EventAjax.setAhaViewed('SyncCalendar');

			this.nodeRef.animate({
				duration: 0,
				opacity: 0,
			}, () => this.setState({
				isClosed: true,
				isVisible: false,
			}));
		}
	}

	const icons = {
		calendar: '<svg width="78" height="78" viewBox="0 0 78 78" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M33.6699 76.7135C12.8413 73.7699 -1.65732 54.4987 1.28628 33.6702C4.22987 12.8416 23.501 -1.65707 44.3296 1.28652C65.1582 4.23011 79.6568 23.5013 76.7132 44.3299C73.7696 65.1584 54.4985 79.6571 33.6699 76.7135Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M44.2087 2.14385C23.8536 -0.732824 5.02053 13.4362 2.14385 33.7913C-0.732825 54.1464 13.4362 72.9795 33.7913 75.8562C54.1464 78.7328 72.9795 64.5638 75.8561 44.2087C78.7328 23.8536 64.5638 5.02053 44.2087 2.14385ZM0.388574 33.5433C3.40225 12.2188 23.1323 -2.62511 44.4568 0.388573C65.7813 3.40225 80.6251 23.1323 77.6114 44.4568C74.5977 65.7813 54.8678 80.6251 33.5432 77.6114C12.2187 74.5978 -2.62511 54.8678 0.388574 33.5433Z" fill="#7FDEFC"/><path d="M16.2095 24.276C16.2095 20.3947 19.3559 17.2482 23.2373 17.2482H54.8623C58.7437 17.2482 61.8901 20.3947 61.8901 24.276V53.9489C61.8901 57.8303 58.7437 60.9767 54.8623 60.9767H23.2373C19.3559 60.9767 16.2095 57.8303 16.2095 53.9489V24.276Z" fill="#F1FBD0"/><path fill-rule="evenodd" clip-rule="evenodd" d="M54.8623 18.4195H23.2373C20.0028 18.4195 17.3808 21.0416 17.3808 24.276V53.9489C17.3808 57.1834 20.0028 59.8054 23.2373 59.8054H54.8623C58.0968 59.8054 60.7188 57.1834 60.7188 53.9489V24.276C60.7188 21.0416 58.0968 18.4195 54.8623 18.4195ZM23.2373 17.2482C19.3559 17.2482 16.2095 20.3947 16.2095 24.276V53.9489C16.2095 57.8303 19.3559 60.9767 23.2373 60.9767H54.8623C58.7437 60.9767 61.8901 57.8303 61.8901 53.9489V24.276C61.8901 20.3947 58.7437 17.2482 54.8623 17.2482H23.2373Z" fill="#BBDE4D"/><path d="M20.1138 31.3038C20.1138 28.7163 22.2114 26.6187 24.799 26.6187H53.3006C55.8881 26.6187 57.9858 28.7163 57.9858 31.3038V52.3872C57.9858 54.9748 55.8881 57.0724 53.3006 57.0724H24.799C22.2114 57.0724 20.1138 54.9748 20.1138 52.3872V31.3038Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.1319 28.9612H24.9673C23.5679 28.9612 22.4562 30.0731 22.4562 31.4168V52.2742C22.4562 53.6179 23.5679 54.7298 24.9673 54.7298H53.1319C54.5313 54.7298 55.643 53.6179 55.643 52.2742V31.4168C55.643 30.0731 54.5313 28.9612 53.1319 28.9612ZM24.9673 28.1804C23.1492 28.1804 21.6753 29.6294 21.6753 31.4168V52.2742C21.6753 54.0616 23.1492 55.5107 24.9673 55.5107H53.1319C54.95 55.5107 56.4238 54.0616 56.4238 52.2742V31.4168C56.4238 29.6294 54.95 28.1804 53.1319 28.1804H24.9673Z" fill="#DFE0E3"/><path d="M47.8345 14.5152C47.8345 13.6527 48.5337 12.9535 49.3962 12.9535C50.2587 12.9535 50.9579 13.6527 50.9579 14.5152V20.7622C50.9579 21.6247 50.2587 22.3239 49.3962 22.3239C48.5337 22.3239 47.8345 21.6247 47.8345 20.7622V14.5152Z" fill="#BBDE4D"/><path d="M27.1416 14.5152C27.1416 13.6527 27.8408 12.9535 28.7033 12.9535C29.5659 12.9535 30.2651 13.6527 30.2651 14.5152V20.7622C30.2651 21.6247 29.5659 22.3239 28.7033 22.3239C27.8408 22.3239 27.1416 21.6247 27.1416 20.7622V14.5152Z" fill="#BBDE4D"/><path fill-rule="evenodd" clip-rule="evenodd" d="M41.9677 42.0752L41.9675 42.0741L45.1962 42.0731C45.1962 38.8454 42.579 36.2141 39.3513 36.2141C37.9067 36.2141 36.584 36.7386 35.5641 37.6077L33.9057 35.9504C35.3522 34.6607 37.2603 33.8765 39.3512 33.8765C43.8706 33.8765 47.5349 37.5558 47.5349 42.0752H50.2112L46.0544 46.218L41.9677 42.0752ZM31.1678 42.0752L28.4116 42.0751L32.4894 37.9853L36.5644 42.0741H33.5065C33.5065 45.3018 36.1237 47.9042 39.3514 47.9042C40.7361 47.9042 42.0077 47.4237 43.0087 46.6195L44.6691 48.2808C43.2384 49.5046 41.3814 50.2438 39.3515 50.2438C34.832 50.2438 31.1678 46.5947 31.1678 42.0752Z" fill="#2FC6F6"/></svg>',
		bottomTriangle: '<svg width="22" height="12" viewBox="0 0 22 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 12L9.89427 1.20625C10.4888 0.557701 11.5112 0.557701 12.1057 1.20625L22 12L0 12Z" fill="white"/></svg>',
	};

	const styles = {
		circle: {
			position: 'absolute',
			bottom: -140,
			left: -130,
			width: 260,
			height: 260,
			borderRadius: 130,
			borderWidth: 45,
			borderColor: AppTheme.colors.base8,
			opacity: 0.6,
		},
	};

	module.exports = { SyncCalendar };
});
