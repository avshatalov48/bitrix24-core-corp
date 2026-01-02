/**
 * @module layout/ui/app-rating
 */
jn.define('layout/ui/app-rating', (require, exports, module) => {
	const Apptheme = require('apptheme');
	const { Loc } = require('loc');
	const { Color, Indent, Component } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Text2 } = require('ui-system/typography/text');
	const { BoxContent } = require('layout/ui/app-rating/src/box-strategy');
	const { StarRating } = require('layout/ui/star-rating');
	const { H4 } = require('ui-system/typography/heading');
	const {
		MinRateForStore,
		BackdropHeight,
		LottiePath,
	} = require('layout/ui/app-rating/src/rating-constants');
	const { AppRatingAnalytics } = require('layout/ui/app-rating/src/analytics');
	const { FeedbackFormStrategy } = require('layout/ui/app-rating/src/strategies/feedback-form-strategy');

	const sharedStorageKey = 'app-rating-manager';
	const storedDataKey = `app_rate_attempt_${env.userId}`;
	const storage = Application.sharedStorage(sharedStorageKey);

	/**
	 * @class AppRating
	 * @param {AppRatingProps} props
	 */
	class AppRating extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				rating: 0,
				showAppRating: true,
			};
			this.appRatingRef = null;
		}

		get triggerEvent()
		{
			return this.props.triggerEvent ?? 'unknown';
		}

		componentDidMount()
		{
			this.props.layoutWidget?.preventBottomSheetDismiss(false);
		}

		animateTransition()
		{
			this.appRatingRef.animate({ opacity: 0, duration: 100 }, () => {
				this.setState({ showAppRating: false });
			});
		}

		#getTestId = (suffix) => {
			const prefix = 'app-rating';

			return suffix ? `${prefix}-${suffix}` : prefix;
		};

		/**
		 * @typedef {Object} AppRatingProps
		 * @property {Function} onGoToStoreButtonClick
		 * @property {Function} onRateAppButtonClick
		 * @property {LayoutWidget} parentWidget
		 * @property {string} title

		 * @param {AppRatingProps} props
		 * @returns {Promise<void>}
		 */
		static open(props = {})
		{
			const parentWidget = props.parentWidget ?? PageManager;

			new BottomSheet({
				component: (layoutWidget) => new AppRating({
					...props,
					layoutWidget,
				}),
			})
				.setParentWidget(parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setMediumPositionHeight(BackdropHeight, true)
				.preventBottomSheetDismiss()
				.open()
				.catch(console.error)
			;

			AppRatingAnalytics.sendDrawerOpen({
				section: props.triggerEvent,
			});
		}

		render()
		{
			const { showAppRating, rating } = this.state;
			const { layoutWidget, onGoToStoreButtonClick } = this.props;

			if (showAppRating)
			{
				return this.renderAppRating();
			}

			return BoxContent({
				parentWidget: layoutWidget,
				userRate: rating,
				onGoToStoreButtonClick,
				triggerEvent: this.triggerEvent,
				shouldAnimate: true,
			});
		}

		renderAppRating()
		{
			return Box(
				{
					footer: this.renderFooter(),
				},
				AreaList(
					{},
					Area(
						{
							isFirst: true,
						},
						View(
							{
								ref: (ref) => {
									if (ref)
									{
										this.appRatingRef = ref;
									}
								},
								style: {
									opacity: this.state.showAppRating ? 1 : 0,
								},
							},
							this.renderTitle(),
							this.renderContent(),
						),
					),
				),
			);
		}

		renderTitle()
		{
			return H4({
				testId: this.#getTestId('title'),
				text: this.props.title ?? Loc.getMessage('M_UI_APP_RATING_TITLE'),
				color: Color.base1,
				style: {
					alignSelf: 'center',
					marginBottom: Indent.XL.toNumber(),
				},
			});
		}

		renderContent()
		{
			return View(
				{
					style: {
						marginTop: Component.areaPaddingTFirst.toNumber(),
						padding: Indent.XL3.toNumber(),
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				this.renderAnimation(),
				this.renderRateZone(),
			);
		}

		renderAnimation()
		{
			return LottieView({
				testId: this.#getTestId('video'),
				style: {
					width: 208,
					height: 162,
					alignSelf: 'center',
					backgroundColor: Color.bgSecondary.toHex(),
					marginBottom: Indent.XL4.toNumber(),
				},
				data: {
					uri: AppRating.getVideoPath(),
				},
				params: {
					loopMode: 'playOnce',
				},
				autoPlay: true,
			});
		}

		renderRateZone()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				this.renderDescription(),
				StarRating({
					testId: this.#getTestId('star-rating'),
					onStarClick: this.onStarClick,
				}),
			);
		}

		onStarClick = (starIndex) => {
			this.setState({
				rating: starIndex,
			});
		};

		renderDescription()
		{
			return Text2({
				testId: this.#getTestId('description'),
				color: Color.base1,
				text: Loc.getMessage('M_UI_APP_RATING_DESCRIPTION'),
				style: {
					marginBottom: Indent.XL3.toNumber(),
				},
			});
		}

		renderFooter()
		{
			const { rating } = this.state;

			return BoxFooter(
				{},
				Button(
					{
						testId: this.#getTestId('button'),
						text: Loc.getMessage('M_UI_APP_RATING_BUTTON_TEXT'),
						design: ButtonDesign.FILLED,
						size: ButtonSize.L,
						stretched: true,
						disabled: rating === 0,
						onClick: () => this.onPressButton(),
					},
				),
			);
		}

		onPressButton()
		{
			const { rating } = this.state;

			this.#incrementRateAttempt();

			this.animateTransition();

			this.props.onRateAppButtonClick?.(rating);

			AppRatingAnalytics.sendSubmitRate({
				section: this.triggerEvent,
				attempt: this.#getCurrentRateAttempt(),
				rating,
			});
		}

		#incrementRateAttempt()
		{
			const newValue = this.#getCurrentRateAttempt() + 1;
			storage.set(storedDataKey, newValue);
		}

		#getCurrentRateAttempt()
		{
			return storage.get(storedDataKey) || 0;
		}

		/**
		 * @returns {string}
		 */
		static getVideoPath()
		{
			return Apptheme.id === 'dark' ? `${LottiePath}/rating-dark.json` : `${LottiePath}/rating-light.json`;
		}
	}

	AppRating.defaultProps = {
		parentWidget: PageManager,
		title: Loc.getMessage('M_UI_APP_RATING_TITLE'),
		triggerEvent: 'unknown',
	};

	AppRating.propTypes = {
		parentWidget: PropTypes.object,
		title: PropTypes.string,
		triggerEvent: PropTypes.string,
		onGoToStoreButtonClick: PropTypes.func,
		onRateAppButtonClick: PropTypes.func,
	};

	module.exports = {
		AppRating,
		MinRateForStore,
		AppRatingAnalytics,
		FeedbackFormStrategy,
	};
});
