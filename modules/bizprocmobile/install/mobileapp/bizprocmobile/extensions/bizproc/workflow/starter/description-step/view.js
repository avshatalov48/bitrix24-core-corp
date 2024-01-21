/**
 * @module bizproc/workflow/starter/description-step/view
 */
jn.define('bizproc/workflow/starter/description-step/view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { isNil } = require('utils/type');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { PureComponent } = require('layout/pure-component');
	const { inAppUrl } = require('in-app-url');

	class DescriptionStepView extends PureComponent
	{
		get description()
		{
			return isNil(this.props.description) ? '' : String(this.props.description).trim();
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						borderTopRightRadius: 12,
						borderTopLeftRadius: 12,
						flex: 1,
					},
				},
				ScrollView(
					{
						style: { flex: 1 },
					},
					View(
						{
							style: {
								display: 'flex',
								flexDirection: 'column',
								justifyContent: 'space-between',
							},
						},
						this.renderBody(),
						!this.description && this.renderStub(),
						this.renderExecuteTime(),
					),
				),
			);
		}

		renderBody()
		{
			return View(
				{ style: { marginHorizontal: 16, marginVertical: 12 } },
				Text({
					testId: 'workflowStarter_descriptionStep_title',
					text: this.props.name,
					style: {
						fontSize: 16,
						fontWeight: '500',
						color: AppTheme.colors.base1,
					},
				}),
				this.description && BBCodeText({
					testId: 'workflowStarter_descriptionStep_description',
					value: this.description,
					style: {
						marginTop: 10,
						fontSize: 14,
						fontWeight: '400',
						color: AppTheme.colors.base2,
						lineHeightMultiple: 1.1,
					},
					onLinkClick: ({ url }) => inAppUrl.open(url),
				}),
			);
		}

		renderStub()
		{
			return View(
				{ style: { height: 242 } },
				new EmptyScreen({
					backgroundColor: AppTheme.colors.bgContentPrimary,
					image: {
						uri: EmptyScreen.makeLibraryImagePath('workflows.png', 'bizproc'),
						style: { width: 148, height: 149 },
					},
					styles: { icon: { marginBottom: 24 } },
					description: Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_EMPTY_STATE_DESCRIPTION'),
				}),
			);
		}

		renderExecuteTime()
		{
			const formattedTime = this.props.formattedTime;

			return View(
				{},
				this.renderCorner(),
				View(
					{
						style: {
							margin: 16,
							borderColor: AppTheme.colors.accentSoftBlue1,
							borderRadius: 18,
							borderWidth: 1,
							paddingHorizontal: 16,
							paddingVertical: 12,
						},
					},
					this.renderExecuteTimeTitle(),
					this.renderExecuteTimeDescription(),
					formattedTime && this.renderExecuteTimeLink(),
				),
			);
		}

		renderCorner()
		{
			return Image({
				style: {
					width: 22,
					height: 27,
					position: 'absolute',
					top: 12,
					left: 3,
					zIndex: 100,
				},
				svg: {
					content: `
						<svg width="22" height="27" viewBox="0 0 22 27" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g clip-path="url(#clip0_1238_9544)">
								<path 
									opacity="0.96"
									d="M14.5837 22.1224C13.6399 20.1725 10.9762 15.3838 6.31298 12.3866C6.14114 12.2761 6.0516 12.0982 6.04488 11.9336C6.03854 11.7781 6.10306 11.6474 6.24478 11.5687C9.01174 10.0322 12.2135 9.64873 14.9635 9.71442C17.7085 9.77999 19.9592 10.2919 20.7905 10.5064C20.8917 10.5325 20.9825 10.598 21.0492 10.7046L25.702 18.1501C25.8722 18.4224 25.7453 18.7821 25.442 18.8874L15.2645 22.421C15.0116 22.5088 14.7121 22.3876 14.5837 22.1224Z"
									fill="${AppTheme.colors.bgContentPrimary}"
									stroke="${AppTheme.colors.accentSoftBlue1}"
									stroke-linejoin="round"
								/>
							</g>
							<defs>
								<clipPath id="clip0_1238_9544">
									<rect 
										width="11.7028"
										height="23"
										fill="${AppTheme.colors.bgContentPrimary}"
										transform="matrix(0.899448 0.437028 0.437028 -0.899448 0.443604 21.4171)"
									/>
								</clipPath>
							</defs>
						</svg>
					`,
				},
			});
		}

		renderExecuteTimeTitle()
		{
			const formattedTime = this.props.formattedTime;

			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'row',
					},
				},
				Text({
					style: {
						fontWeight: '500',
						fontSize: 14,
						color: AppTheme.colors.base2,
						lineHeightMultiple: 1.1,
						flexShrink: 1,
					},
					text: Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_AVERAGE_EXECUTION_TIME_TITLE'),
				}),
				View(
					{
						style: {
							paddingLeft: 8,
							flexGrow: 1,
							display: 'flex',
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'flex-end',
						},
					},
					Text({
						style: {
							flexShrink: 1,
							color: formattedTime ? AppTheme.colors.base2 : AppTheme.colors.base4,
							fontSize: 14,
							fontWeight: '500',
						},
						text: (formattedTime || Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_AVERAGE_EXECUTION_TIME_NO_DATA')),
						numberOfLines: 1,
						ellipsize: 'end',
					}),
					formattedTime && this.renderClock(),
				),
			);
		}

		renderClock()
		{
			const color = AppTheme.colors.base5;

			return Image({
				style: { width: 24, height: 24 },
				svg: {
					content: `
						<svg 
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path
								fill-rule="evenodd"
								clip-rule="evenodd"
								d="M11.9999 3.35359C8.58938 3.35359 5.84875 6.09314 5.84875 9.50477C5.84875 13.1057 9.5034 18.1321 11.1787 20.2469C11.6031 20.7828 12.3926 20.779 12.8133 20.2402C14.4849 18.0989 18.1511 13.0039 18.1511 9.50477C18.1511 6.09314 15.4105 3.35359 11.9999 3.35359ZM12 13.4947C9.76584 13.4947 8.00901 11.7394 8.00901 9.50374C8.00901 7.26959 9.76431 5.51276 12 5.51276C14.2342 5.51276 15.991 7.26805 15.991 9.50374C15.991 11.7394 14.2342 13.4947 12 13.4947Z"
								fill="${color}"
							/>
							<path
								d="M11.6931 6.77418C11.3843 6.77418 11.1339 7.02453 11.1339 7.33336V9.93778C11.1339 10.2466 11.3843 10.4971 11.6931 10.4971H12.0809C12.0852 10.4971 12.0896 10.4971 12.0939 10.497H14.1067C14.4155 10.497 14.6659 10.2466 14.6659 9.93778V9.54994C14.6659 9.24111 14.4155 8.99076 14.1067 8.99076H12.6401V7.33336C12.6401 7.02453 12.3897 6.77418 12.0809 6.77418H11.6931Z"
								fill="${color}"
							/>
						</svg>
					`,
				},
			});
		}

		renderExecuteTimeDescription()
		{
			return Text({
				style: {
					marginTop: 10,
					color: AppTheme.colors.base2,
					fontWeight: '400',
					fontSize: 14,
					lineHeightMultiple: 1.1,
				},
				text: (
					this.props.formattedTime
						? Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_AVERAGE_EXECUTION_TIME_DESCRIPTION')
						: Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_AVERAGE_EXECUTION_TIME_NO_DATA_DESCRIPTION')
				),
			});
		}

		renderExecuteTimeLink()
		{
			return View(
				{
					onClick: () => {
						helpdesk.openHelpArticle('18783714');
					},
				},
				Text({
					style: {
						marginTop: 10,
						fontWeight: '400',
						fontSize: 14,
						color: AppTheme.colors.accentMainLinks,
						lineHeightMultiple: 1.1,
					},
					text: Loc.getMessage('M_BP_WORKFLOW_STARTER_DESCRIPTION_STEP_AVERAGE_EXECUTION_TIME_LINK_TITLE'),
				}),
			);
		}
	}

	module.exports = { DescriptionStepView };
});
