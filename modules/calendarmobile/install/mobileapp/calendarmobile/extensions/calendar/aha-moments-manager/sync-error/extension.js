/**
 * @module calendar/aha-moments-manager/sync-error
 */
jn.define('calendar/aha-moments-manager/sync-error', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventAjax } = require('calendar/ajax');
	const { cross } = require('assets/common');
	const { Loc } = require('loc');
	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class SyncError
	 */
	class SyncError extends LayoutComponent
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
									backgroundColor: AppTheme.colors.accentSoftRed2,
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
								text: Loc.getMessage('M_CALENDAR_AHA_SYNC_ERROR_TITLE'),
							}),
							Text({
								style: {
									fontSize: 14,
									color: AppTheme.colors.base2,
									lineHeightMultiple: 1.2,
								},
								text: Loc.getMessage('M_CALENDAR_AHA_SYNC_ERROR_DESC'),
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
			EventAjax.setAhaViewed('SyncError');

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
		calendar: '<svg width="78" height="78" viewBox="0 0 78 78" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M33.6702 76.7135C12.8416 73.7699 -1.65708 54.4987 1.28652 33.6702C4.23011 12.8416 23.5013 -1.65707 44.3299 1.28652C65.1584 4.23011 79.6571 23.5013 76.7135 44.3299C73.7699 65.1584 54.4987 79.6571 33.6702 76.7135Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M44.2087 2.14385C23.8536 -0.732824 5.02053 13.4362 2.14385 33.7913C-0.732825 54.1464 13.4362 72.9795 33.7913 75.8561C54.1464 78.7328 72.9795 64.5638 75.8561 44.2087C78.7328 23.8536 64.5638 5.02053 44.2087 2.14385ZM0.388574 33.5433C3.40225 12.2188 23.1323 -2.62511 44.4568 0.388573C65.7813 3.40225 80.6251 23.1322 77.6114 44.4567C74.5977 65.7812 54.8677 80.6251 33.5432 77.6114C12.2187 74.5977 -2.62511 54.8677 0.388574 33.5433Z" fill="#FFCDCC"/><path d="M16.2093 24.276C16.2093 20.3947 19.3557 17.2482 23.2371 17.2482H54.8622C58.7435 17.2482 61.89 20.3947 61.89 24.276V53.9489C61.89 57.8303 58.7435 60.9767 54.8622 60.9767H23.2371C19.3557 60.9767 16.2093 57.8303 16.2093 53.9489V24.276Z" fill="#FFE8E8"/><path fill-rule="evenodd" clip-rule="evenodd" d="M54.8622 18.4195H23.2371C20.0026 18.4195 17.3806 21.0416 17.3806 24.276V53.9489C17.3806 57.1834 20.0026 59.8054 23.2371 59.8054H54.8622C58.0966 59.8054 60.7187 57.1834 60.7187 53.9489V24.276C60.7187 21.0416 58.0966 18.4195 54.8622 18.4195ZM23.2371 17.2482C19.3557 17.2482 16.2093 20.3947 16.2093 24.276V53.9489C16.2093 57.8303 19.3557 60.9767 23.2371 60.9767H54.8622C58.7435 60.9767 61.89 57.8303 61.89 53.9489V24.276C61.89 20.3947 58.7435 17.2482 54.8622 17.2482H23.2371Z" fill="#FF9A97"/><path d="M20.1136 31.3038C20.1136 28.7163 22.2113 26.6187 24.7988 26.6187H53.3005C55.888 26.6187 57.9856 28.7163 57.9856 31.3038V52.3872C57.9856 54.9748 55.888 57.0724 53.3005 57.0724H24.7988C22.2113 57.0724 20.1136 54.9748 20.1136 52.3872V31.3038Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M53.1319 28.9612H24.9673C23.5679 28.9612 22.4562 30.0731 22.4562 31.4168V52.2742C22.4562 53.6179 23.5679 54.7298 24.9673 54.7298H53.1319C54.5313 54.7298 55.643 53.6179 55.643 52.2742V31.4168C55.643 30.0731 54.5313 28.9612 53.1319 28.9612ZM24.9673 28.1804C23.1492 28.1804 21.6754 29.6294 21.6754 31.4168V52.2742C21.6754 54.0616 23.1492 55.5107 24.9673 55.5107H53.1319C54.95 55.5107 56.4239 54.0616 56.4239 52.2742V31.4168C56.4239 29.6294 54.95 28.1804 53.1319 28.1804H24.9673Z" fill="#DFE0E3"/><path d="M47.9535 14.5152C47.9535 13.6527 48.6527 12.9535 49.5152 12.9535C50.3777 12.9535 51.077 13.6527 51.077 14.5152V20.7622C51.077 21.6247 50.3777 22.3239 49.5152 22.3239C48.6527 22.3239 47.9535 21.6247 47.9535 20.7622V14.5152Z" fill="#FF9A97"/><path d="M27.1414 14.5152C27.1414 13.6527 27.8406 12.9535 28.7032 12.9535C29.5657 12.9535 30.2649 13.6527 30.2649 14.5152V20.7622C30.2649 21.6247 29.5657 22.3239 28.7032 22.3239C27.8406 22.3239 27.1414 21.6247 27.1414 20.7622V14.5152Z" fill="#FF9A97"/><path fill-rule="evenodd" clip-rule="evenodd" d="M41.8026 41.4901L41.8025 41.489L44.9206 41.4881C44.9206 38.371 42.393 35.8299 39.2759 35.8299C37.8808 35.8299 36.6035 36.3363 35.6185 37.1757L34.017 35.5751C35.4139 34.3297 37.2566 33.5723 39.2758 33.5723C43.6404 33.5723 47.179 37.1256 47.179 41.4901H49.7636L45.7493 45.4909L41.8026 41.4901ZM31.3729 41.4901L28.7112 41.49L32.6492 37.5404L36.5845 41.489H33.6314C33.6314 44.6061 36.159 47.1193 39.276 47.1193C40.6132 47.1193 41.8413 46.6552 42.808 45.8786L44.4114 47.483C43.0299 48.6648 41.2364 49.3787 39.2761 49.3787C34.9115 49.3787 31.3729 45.8546 31.3729 41.4901Z" fill="#FF9A97"/><rect x="46.0458" y="33.0734" width="2.20346" height="21.799" transform="rotate(45 46.0458 33.0734)" fill="#FF9A97"/><rect x="45.0538" y="32.0814" width="1.47485" height="21.799" transform="rotate(45 45.0538 32.0814)" fill="white"/></svg>',
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

	module.exports = { SyncError };
});
