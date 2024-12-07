/**
 * @module intranet/recommendation-box/mobile-app-install
 */
jn.define('intranet/recommendation-box/mobile-app-install', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent, Corner } = require('tokens');
	const { makeLibraryImagePath } = require('asset-manager');
	const { RunActionExecutor } = require('rest/run-action-executor');

	const { Text5 } = require('ui-system/typography/text');

	const { RecommendationBox } = require('intranet/recommendation-box');
	const { PhoneField } = require('layout/ui/fields/phone');

	/**
	 * @class MobileAppInstallRecommendationBox
	 */
	class MobileAppInstallRecommendationBox extends RecommendationBox
	{
		constructor(props) {
			super(props);

			this.state = {
				countryCode: props.countryCode || '',
				phoneNumber: props.phoneNumber || '',
			};
		}

		get imageUri()
		{
			return this.props.imageUri || makeLibraryImagePath('install-mobile.svg', 'recommendation-box', 'intranet');
		}

		get title()
		{
			return this.props.title || Loc.getMessage('M_INTRANET_RECOMMENDATION_MOBILE_APP_INSTALL_TITLE');
		}

		get description()
		{
			return this.props.description || Loc.getMessage('M_INTRANET_RECOMMENDATION_MOBILE_APP_INSTALL_DESCRIPTION');
		}

		get buttonText()
		{
			return this.props.buttonText || Loc.getMessage('M_INTRANET_RECOMMENDATION_MOBILE_APP_INSTALL_BUTTON_TEXT');
		}

		get additionalContent()
		{
			return this.phoneView();
		}

		onButtonClick = () => {
			return new RunActionExecutor('intranet.controller.sms.sendsmsforapp', { phone: this.state.phoneNumber })
				.call(false);
		};

		phoneView()
		{
			return View(
				{
					style: {
						position: 'relative',
						width: '100%',
						paddingTop: Indent.XL3.toNumber(),
					},
				},
				View(
					{
						style: {
							height: 42,
							borderRadius: Corner.M.toNumber(),
							borderWidth: 1,
							borderColor: Color.accentMainPrimary.toHex(),
							flexDirection: 'row',
							justifyContent: 'center',
							alignContent: 'center',
							alignItems: 'center',
						},
					},
					PhoneField({
						value: { phoneNumber: this.state.phoneNumber, countryCode: this.state.countryCode },
						readOnly: false,
						hidden: false,
						required: false,
						showTitle: true,
						focus: false,
						onChange: (values) => {
							this.setState({
								countryCode: values.countryCode,
								phoneNumber: values.phoneNumber,
							});
						},
						config: {
							showDefaultIcon: true,
							deepMergeStyles: {
								externalWrapper: {
									marginBottom: 2,
								},
								contentWrapper: {
									justifyContent: 'center',
								},
								wrapper: {
									flex: null,
								},
								innerWrapper: {
									flex: null,
								},
								value: {
									color: '#A8ADB4',
									flex: null,
								},
								phoneFieldContainer: {
									flex: null,
									width: 200,
								},
							},
						},
					}),
				),
				View(
					{
						style: {
							position: 'absolute',
							top: Indent.XL3.toNumber() - 7,
							width: '100%',
							flexDirection: 'row',
							justifyContent: 'center',
						},
					},
					View(
						{
							style: {
								backgroundColor: Color.bgContentPrimary.toHex(),
								paddingHorizontal: Indent.XS.toNumber(),
							},
						},
						Text5(
							{
								color: Color.base3,
								text: Loc.getMessage('M_INTRANET_RECOMMENDATION_MOBILE_APP_INSTALL_BUTTON_TEXT'),
							},
						),
					),
				),
			);
		}
	}

	module.exports = { MobileAppInstallRecommendationBox };
});
