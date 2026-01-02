/**
 * @module layout/ui/app-rating/src/box-strategy
 */
jn.define('layout/ui/app-rating/src/box-strategy', (require, exports, module) => {
	const { Area } = require('ui-system/layout/area');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Color, Indent, Component } = require('tokens');
	const { H3, H4 } = require('ui-system/typography/heading');
	const { Loc } = require('loc');
	const { Text4 } = require('ui-system/typography/text');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { defaultStrategyFactory } = require('layout/ui/app-rating/src/strategies/strategy-factory');

	/**
	 * @class BoxContent
	 * @extends LayoutComponent
	 *
	 * This class represents a strategy for rendering in the appRating UI.
	 * It determines the appropriate strategy to use based on user input and executes it.
	 *
	 * @property {Object} props
	 * @property {LayoutWidget} props.parentWidget
	 * @property {number} props.userRate
	 * @property {Function} [props.onGoToStoreButtonClick]
	 * @property {boolean} [props.shouldAnimate]
	 * @property {string} [props.triggerEvent]
	 * @property {string} [props.testId]
	 */
	class BoxContent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				strategy: null,
				botId: null,
				text: '',
			};

			this.sendAnalytics = false;
			this.componentRef = null;
			this.strategyFactory = props.strategyFactory || defaultStrategyFactory;
		}

		/**
		 * @returns {void}
		 */
		animateDrawer()
		{
			if (this.props.shouldAnimate)
			{
				this.componentRef.animate({ opacity: 1, duration: 250 });
			}
		}

		async componentDidMount()
		{
			const botId = await this.#getBotId();
			const strategyProps = { ...this.props, botId };
			const strategy = this.strategyFactory.getApplicableStrategy(strategyProps);

			if (strategy)
			{
				strategy.execute(strategyProps);
				this.setState({ strategy, botId }, () => {
					this.animateDrawer();
				});
			}
		}

		componentWillUnmount()
		{
			this.sendAnalytics = false;
		}

		async #getBotId()
		{
			try
			{
				const response = await new RunActionExecutor(
					'mobile.Support.getBotId',
					{},
				)
					.setSkipRequestIfCacheExists()
					.call(true);

				return response.data?.botId || null;
			}
			catch (error)
			{
				console.error(error);

				return null;
			}
		}

		/**
		 * @returns {string}
		 */
		get testId()
		{
			return this.props.testId ?? 'app-rating-additional-drawer';
		}

		render()
		{
			const { strategy } = this.state;

			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.componentRef = ref;
						}
					},
					style: {
						opacity: 0,
					},
				},
				strategy && strategy.shouldRender() && Box(
					{
						safeArea: {
							bottom: false,
						},
						footer: this.renderFooter(),
					},
					this.renderAreaList(),
				),
			);
		}

		renderAreaList()
		{
			return AreaList(
				{
					withScroll: false,
				},
				Area(
					{
						isFirst: true,
					},
					this.renderTitle(),
					this.renderContent(),
				),
			);
		}

		renderTitle()
		{
			return H4({
				text: this.props.title ?? Loc.getMessage('M_UI_APP_RATING_GRATEFUL_TITLE'),
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
						flexDirection: 'column',
						alignItems: 'center',
						justifyContent: 'space-between',
						padding: Indent.XL3.toNumber(),
					},
				},
				this.renderImage(),
				this.renderDescription(),
				this.renderSubtitle(),
			);
		}

		renderImage()
		{
			return View(
				{
					style: {
						width: '100%',
					},
				},
				this.state.strategy.renderContent(),
			);
		}

		renderDescription()
		{
			return H3({
				text: this.state.strategy.getDescription(),
				color: Color.base1,
				style: {
					textAlign: 'center',
					marginTop: Indent.XL2.toNumber(),
				},
			});
		}

		renderSubtitle()
		{
			return Text4({
				text: this.state.strategy.getSubtitle(),
				color: Color.base2,
				style: {
					textAlign: 'center',
					marginTop: Indent.M.toNumber(),
				},
			});
		}

		renderFooter()
		{
			const { strategy } = this.state;
			const { triggerEvent } = this.props;

			return BoxFooter(
				{},
				Button(
					{
						testId: `${this.testId}-button`,
						text: strategy.getButtonText(),
						design: strategy.isFilledButton() ? ButtonDesign.FILLED : ButtonDesign.OUTLINE_ACCENT_2,
						size: ButtonSize.L,
						stretched: true,
						style: {
							alignSelf: 'center',
							marginVertical: Indent.XL.toNumber(),
						},
						onClick: () => {
							if (!this.sendAnalytics)
							{
								this.sendAnalytics = true;
								strategy.buttonHandler(triggerEvent, this.sendAnalytics);
							}
						},
					},
				),
			);
		}
	}

	BoxContent.propTypes = {
		userRate: PropTypes.number.isRequired,
		parentWidget: PropTypes.object.isRequired,
		testId: PropTypes.string,
		onGoToStoreButtonClick: PropTypes.func,
		triggerEvent: PropTypes.string,
		shouldAnimate: PropTypes.bool,
	};

	module.exports = {
		BoxContent: (props) => new BoxContent(props),
		BoxContentClass: BoxContent,
	};
});
