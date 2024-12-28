/**
 * @module layout/ui/plan-restriction
 */
jn.define('layout/ui/plan-restriction', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { qrauth } = require('qrauth/utils');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { StatusBlock } = require('ui-system/blocks/status-block');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { getIsDemoAvailable, activateDemo } = require('layout/ui/plan-restriction/provider');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const { AnalyticsEvent } = require('analytics');
	const { getMediumHeight } = require('utils/page-manager');

	const PlanId = {
		PRO: 'PRO',
	};

	const safeAreaBottom = true;
	const BACKDROP_HEIGHT = 434;

	/**
	 * @class PlanRestriction
	 */
	class PlanRestriction extends LayoutComponent
	{
		static getWidgetParams(props)
		{
			return {
				modal: true,
				titleParams: {
					type: 'dialog',
					text: props.title || '',
				},
				backdrop: {
					mediumPositionHeight: getMediumHeight({ height: BACKDROP_HEIGHT }),
					onlyMediumPosition: true,
					forceDismissOnSwipeDown: true,
					horizontalSwipeAllowed: false,
				},
				enableNavigationBarBorder: false,
			};
		}

		static async open(props, parentWidget = PageManager)
		{
			const widgetParams = PlanRestriction.getWidgetParams(props);
			const component = new PlanRestriction({ ...props, parentWidget });
			const entityBottomSheet = new BottomSheet({
				component,
				titleParams: widgetParams.titleParams,
			});

			const bottomLayout = await entityBottomSheet
				.setParentWidget(parentWidget)
				.setMediumPositionHeight(BACKDROP_HEIGHT, true)
				.enableOnlyMediumPosition()
				.enableForceDismissOnSwipeDown()
				.disableHorizontalSwipe()
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.open()
				.catch(console.error);

			component.setParentWidget(bottomLayout);

			return bottomLayout;
		}

		static openComponent(props, parentWidget = PageManager)
		{
			const {
				text = Loc.getMessage('PLAN_RESTRICTION_TEXT'),
				isPromo = false,
				planId = PlanId.PRO,
				featureId = '',
				analyticsData = {},
			} = props;

			// eslint-disable-next-line no-undef
			ComponentHelper.openLayout(
				{
					name: 'tariff-plan-restriction',
					object: 'layout',
					canOpenInDefault: true,
					componentParams: {
						text,
						isPromo,
						planId,
						featureId,
						analyticsData,
					},
					widgetParams: PlanRestriction.getWidgetParams(props),
				},
				parentWidget,
			);
		}

		constructor(props)
		{
			super(props);

			this.setParentWidget(props.parentWidget);

			this.state = {
				isLoading: true,
				isDemoAvailable: false,
				isDemoActivating: false,
			};
		}

		componentDidMount()
		{
			getIsDemoAvailable()
				.then((isDemoAvailable) => this.setState({ isDemoAvailable, isLoading: false }))
				.catch(console.error)
			;

			this.sendAnalyticsEvent('show');
		}

		render()
		{
			const { isPromo = false, planId = PlanId.PRO } = this.props;
			const { isLoading } = this.state;

			if (isLoading)
			{
				return new LoadingScreenComponent({ showAirStyle: true });
			}

			return Box(
				{
					safeArea: {
						bottom: safeAreaBottom,
					},
					backgroundColor: Color.bgContentPrimary,
					footer: BoxFooter(
						{},
						...this.getButtons(),
					),
				},
				StatusBlock({
					image: Image({
						style: {
							width: 108,
							height: 108,
						},
						svg: {
							content: '<svg xmlns="http://www.w3.org/2000/svg" width="109" height="108" viewBox="0 0 109 108" fill="none"><path id="Vector" d="M65.6421 40.8904V34.4504C65.6421 18.9197 54.9117 5.39121 41.0422 4.23401C26.3647 3.00953 13.7763 15.9621 13.7763 33.1748V40.3145C7.97391 40.6213 3.26923 45.7991 3.26923 52.2498V92.8729C3.26923 99.5686 8.31945 104.45 14.4196 103.786L65.1185 98.2741C70.0013 97.7439 73.8846 92.6011 73.8846 86.7801V51.4613C73.8846 45.8502 70.2697 41.2698 65.6421 40.8904ZM41.0422 14.2909C50.2868 14.8776 57.5618 23.823 57.5618 34.2513V40.737L23.3638 40.3521V33.4116C23.3638 22.2487 31.4468 13.6827 41.0449 14.2909H41.0422ZM44.9176 72.63V80.5124C44.9176 82.9614 43.1899 85.082 41.0449 85.2488C38.8999 85.4157 37.111 83.5373 37.111 81.0506V73.0444C34.7401 71.602 33.1134 68.787 33.1134 65.4338C33.1134 60.4175 36.6937 56.2812 41.0449 56.195C45.396 56.1089 48.7345 59.9842 48.7345 64.8444C48.7345 68.0954 47.1982 70.9776 44.9203 72.6273L44.9176 72.63Z" fill="#0075FF" fill-opacity="0.78"/><path id="Vector (Stroke)" fill-rule="evenodd" clip-rule="evenodd" d="M64.2575 42.1661V34.4504C64.2575 19.4727 53.9238 6.69821 40.9271 5.61383C27.2321 4.47131 15.161 16.5853 15.161 33.1748V41.6278L13.8495 41.6971C8.86304 41.9608 4.65385 46.4517 4.65385 52.2498V92.8729C4.65385 98.8615 9.1008 102.972 14.2696 102.409L64.9688 96.8976C68.9578 96.4645 72.5 92.1047 72.5 86.7801V51.4613C72.5 46.3605 69.2459 42.5751 65.529 42.2704L64.2575 42.1661ZM41.0422 14.2907C41.0276 14.2898 41.013 14.2889 40.9984 14.288C31.4207 13.7103 23.3638 22.2667 23.3638 33.4116V40.3521L57.5618 40.737V34.2513C57.5618 23.8404 50.3111 14.9075 41.0886 14.2939C41.074 14.2929 41.0595 14.292 41.0449 14.2911C41.044 14.291 41.0431 14.2908 41.0422 14.2907ZM41.1324 12.9092C51.2634 13.5537 58.9464 23.2634 58.9464 34.2513V42.1373L21.9792 41.7213V33.4116C21.9792 21.6375 30.5607 12.2393 41.1324 12.9092ZM65.6421 40.8904V34.4504C65.6421 18.9197 54.9117 5.39121 41.0422 4.23401C26.3647 3.00953 13.7763 15.9621 13.7763 33.1748V40.3145C7.97391 40.6213 3.26923 45.7991 3.26923 52.2498V92.8729C3.26923 99.5686 8.31945 104.45 14.4196 103.786L65.1185 98.2741C70.0013 97.7439 73.8846 92.6011 73.8846 86.7801V51.4613C73.8846 45.8502 70.2697 41.2698 65.6421 40.8904ZM46.3022 73.3029V80.5124C46.3022 83.5241 44.1677 86.3947 41.1523 86.6293C38.0223 86.8728 35.7264 84.1515 35.7264 81.0506V73.7805C33.2891 71.9794 31.7288 68.925 31.7288 65.4338C31.7288 59.8365 35.7505 54.9149 41.0175 54.8107C46.3302 54.7055 50.1191 59.409 50.1191 64.8444C50.1191 68.2538 48.6279 71.3415 46.3022 73.3029ZM44.9203 72.6273C47.1982 70.9776 48.7345 68.0954 48.7345 64.8444C48.7345 59.9842 45.396 56.1089 41.0449 56.195C36.6937 56.2812 33.1134 60.4175 33.1134 65.4338C33.1134 68.787 34.7401 71.602 37.111 73.0444V81.0506C37.111 83.5373 38.8999 85.4157 41.0449 85.2488C43.1899 85.082 44.9176 82.9614 44.9176 80.5124V72.63L44.9203 72.6273Z" fill="white" fill-opacity="0.18"/><path id="Vector_2" d="M105.731 89.9864C105.731 95.6012 101.53 100.328 95.9537 100.987L72.4153 103.768C65.8282 104.547 60.0385 99.4007 60.0385 92.7679V67.1159C60.0385 61.0797 64.8715 56.1547 70.9067 56.041L94.4451 55.5973C100.643 55.4805 105.731 60.473 105.731 66.6723V89.9864Z" fill="#1BCE7B" fill-opacity="0.78"/><path id="Vector (Stroke)_2" fill-rule="evenodd" clip-rule="evenodd" d="M72.2528 102.393L95.7912 99.6117C100.67 99.0352 104.346 94.8993 104.346 89.9864V66.6723C104.346 61.2479 99.8946 56.8795 94.4712 56.9817L70.9327 57.4253C65.652 57.5249 61.4231 61.8342 61.4231 67.1159V92.7679C61.4231 98.5716 66.4891 103.074 72.2528 102.393ZM95.9537 100.987C101.53 100.328 105.731 95.6012 105.731 89.9864V66.6723C105.731 60.473 100.643 55.4805 94.4451 55.5973L70.9067 56.041C64.8715 56.1547 60.0385 61.0797 60.0385 67.1159V92.7679C60.0385 99.4007 65.8282 104.547 72.4153 103.768L95.9537 100.987Z" fill="white" fill-opacity="0.18"/><path id="Vector_3" fill-rule="evenodd" clip-rule="evenodd" d="M78.3176 86.5156C79.8633 87.8531 81.6749 88.4718 83.6112 88.1006C83.9464 89.0747 84.5253 90.665 85.3287 92.5765C88.9519 89.7093 89.4671 86.1523 89.3979 84.0565C89.7497 83.6221 90.1015 83.1482 90.4533 82.6374C94.7054 76.437 95.2899 64.9972 94.1347 63.9098C92.9768 62.8172 83.1902 65.8845 78.7248 72.1402C78.3536 72.6615 78.0157 73.1775 77.7137 73.683C75.8107 74.0937 72.7414 75.5155 71.1154 80.2889C73.0544 80.7259 74.6334 80.9866 75.5918 81.1261C75.7608 83.3483 76.7663 85.1728 78.3204 86.5182L78.3176 86.5156ZM80.9935 82.69C82.3342 83.8669 84.3203 83.5141 85.4311 81.9133C86.5364 80.3205 86.3647 78.0799 85.0461 76.8977C83.7192 75.7077 81.7303 76.0289 80.6001 77.6297C79.4672 79.2357 79.6445 81.5079 80.9935 82.6927V82.69ZM88.7248 74.6098C88.1375 75.4523 87.0904 75.6234 86.3841 74.9863C85.6777 74.3465 85.5835 73.1406 86.1791 72.2955C86.7719 71.453 87.8189 71.2924 88.5198 71.9322C89.2178 72.5719 89.3093 73.7673 88.7248 74.6098ZM80.0156 89.238C79.0406 88.9984 78.1181 88.5113 77.276 87.8215C76.4312 87.1291 75.7497 86.2971 75.2733 85.3413C73.8827 89.2485 73.2816 92.3737 73.7082 92.7186C74.1348 93.0609 76.8301 91.6418 80.0156 89.238Z" fill="white" fill-opacity="0.9"/></svg>',
						},
					}),
					description: this.getText(),
					descriptionColor: Color.base1,
					testId: (isPromo ? `PLAN_RESTRICTION_PROMO_${planId}` : 'PLAN_RESTRICTION'),
				}),
			);
		}

		getText()
		{
			const {
				text = Loc.getMessage('PLAN_RESTRICTION_TEXT'),
				isPromo = false,
				planId = PlanId.PRO,
			} = this.props;

			if (isPromo)
			{
				return Loc.getMessage(`PLAN_RESTRICTION_PROMO_TEXT_${planId}`);
			}

			const { isDemoAvailable } = this.state;

			if (isDemoAvailable)
			{
				return Loc.getMessage('PLAN_RESTRICTION_TEXT_DEMO');
			}

			return text;
		}

		getButtons()
		{
			const {
				isPromo = false,
				planId = PlanId.PRO,
				featureId = '',
			} = this.props;

			const layout = this.getParentWidget();

			if (isPromo)
			{
				const promoButton = Button({
					testId: `BUTTON_PROMO_${planId}`,
					text: Loc.getMessage('PLAN_RESTRICTION_PROMO_BUTTON'),
					size: ButtonSize.L,
					stretched: true,
					onClick: () => {
						qrauth.open({
							layout,
							showHint: true,
							redirectUrl: `/?feature_promoter_by_id=${featureId}&utm_medium=b24_slider_mobile&utm_source=${featureId}`,
							analyticsSection: 'tariff_slider',
						});
						this.sendAnalyticsEvent('button_gift_click');
					},
				});

				return [promoButton];
			}

			const activateDemoButton = Button({
				testId: 'BUTTON_ENABLE_DEMO',
				text: Loc.getMessage('PLAN_RESTRICTION_ENABLE_DEMO_BUTTON'),
				size: ButtonSize.L,
				loading: this.state.isDemoActivating,
				stretched: true,
				style: {
					marginBottom: Indent.L.toNumber(),
				},
				onClick: async () => {
					this.setState({ isDemoActivating: true });
					this.sendAnalyticsEvent('demo_activated');

					const isDemoAvailable = await activateDemo();
					if (isDemoAvailable === false)
					{
						const { DemoActivationSuccess } = await requireLazy(
							'layout/ui/plan-restriction/demo-activation-success',
						);
						DemoActivationSuccess.open(layout);
					}
				},
			});

			const { isDemoAvailable } = this.state;

			const choosePlanButton = Button({
				testId: 'BUTTON_CHOOSE_PLAN',
				text: Loc.getMessage('PLAN_RESTRICTION_CHOOSE_PLAN_BUTTON'),
				size: ButtonSize.L,
				design: isDemoAvailable ? ButtonDesign.PLAN_ACCENT : ButtonDesign.FILLED,
				stretched: true,
				onClick: () => {
					qrauth.open({
						layout,
						showHint: true,
						redirectUrl: (
							Type.isStringFilled(featureId)
								? `/settings/license_all.php?utm_medium=b24_slider_mobile&utm_source=${featureId}`
								: '/settings/license_all.php?utm_medium=b24_slider_mobile'
						),
						analyticsSection: 'tariff_slider',
					});
					this.sendAnalyticsEvent('button_buy_click');
				},
			});

			return [
				isDemoAvailable && activateDemoButton,
				choosePlanButton,
			];
		}

		sendAnalyticsEvent(action)
		{
			const { featureId = '', analyticsData = {} } = this.props;

			new AnalyticsEvent({
				...analyticsData,
				tool: 'infoHelper',
				category: 'drawer',
				event: action,
				type: (Type.isStringFilled(featureId) ? featureId : null),
			}).send();
		}

		getParentWidget()
		{
			return this.parentWidget;
		}

		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}
	}

	setTimeout(() => getIsDemoAvailable(), 2000);

	module.exports = { PlanRestriction, PlanId };
});
