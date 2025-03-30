/**
 * @module sign/document/create
 */
jn.define('sign/document/create', (require, exports, module) => {
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { MemberStatus } = require('sign/type/member-status');
	const { Banner } = require('sign/banner');
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { SignDocument } = require('sign/document');
	const { SignDialog } = require('sign/dialog');
	const { AnalyticsEvent } = require('analytics');
	const { sendTemplate, getMember, getSigningLinkPromise } = require('sign/connector');

	/**
	 * @class CreateDocument
	 */
	class CreateDocument extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			const {
				baseLayout,
				templateUid,
				preparedFields,
				title
			} = props;

			this.layout = baseLayout;
			this.templateUid = templateUid;
			this.preparedFields = preparedFields;
			this.title = title;
			this.state.pending = true;
			this.isCloseButtonShow = false;
			this.openSigningSliderAfterPending = true;
			this.analyticsEvent = props.analyticsEvent;
			this.fromAvaMenu = props.fromAvaMenu;
			this.providerCodeForAnalytics = props.providerCodeForAnalytics;

			if (!(this.analyticsEvent instanceof AnalyticsEvent))
			{
				this.analyticsEvent = new AnalyticsEvent({
					tool: 'sign',
					category: 'documents',
					type: 'from_employee',
					c_section: this.fromAvaMenu ? 'ava_menu' : 'sign',
					c_element: 'create_button',
					p1: this.providerCodeForAnalytics ? this.providerCodeForAnalytics : 'integration_N'
				});
			}
			this.analyticsEvent.setEvent('sent_document_to_sign');
		}

		async launch()
		{
			this.layout.openWidget(
				'layout',
				{
					backgroundColor: Color.bgContentPrimary.toHex(),
					title: '',
					modal: true,
					backdrop: {
						hideNavigationBar: false,
						shouldResizeContent: true,
						swipeAllowed: false,
						showOnTop: true,
					},
					onReady: (readyLayout) => {
						this.widget = readyLayout;
						readyLayout.showComponent(this);
						readyLayout.setTitle({
							text: Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_CREATION_STEP_TITLE'),
							useLargeTitleMode: true,
						});
					},
				},
				this.layout,
			);
		}

		componentWillUnmount()
		{
			super.componentWillUnmount();
			this.setState({pending: false});
		}

		componentDidMount()
		{
			this.createDocument()
		}

		#processPullEvent(data)
		{
			if (!this.state.pending || !this.openSigningSliderAfterPending)
			{
				return;
			}

			this.state.pending = false;
			this.openSigningSliderAfterPending = false;
			this.openSigning(data.member.id, data.documentId)
		}

		async createDocument()
		{
			BX.PULL.subscribe({
				moduleId: 'sign',
				command: 'memberInvitedToSign',
				callback: data => this.#processPullEvent(data),
			});

			try
			{
				const sendTemplateResponse = await sendTemplate(this.templateUid, this.preparedFields);
				do
				{
					await this.sleep(5000);
					if (!this.openSigningSliderAfterPending || !this.state.pending)
					{
						break;
					}

					const memberResponse = await getMember(sendTemplateResponse.data.employeeMember.uid);

					if (MemberStatus.isReadyStatusFromPresentedView(memberResponse.data.status))
					{
						if (this.openSigningSliderAfterPending)
						{
							this.openSigningSliderAfterPending = false;
							this.openSigning(memberResponse.data.id, sendTemplateResponse.data.document.id)
						}
						this.state.pending = false;
					}
				}
				while (this.state.pending);
			}
			catch
			{
				this.analyticsEvent.setStatus('error');
				this.analyticsEvent.send();
				this.banner.rerender(
					'error.svg',
					Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_TITLE_CREATION_ERROR'),
					Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_DESCRIPTION_CREATION_ERROR'),
				);
				this.widget.setRightButtons([{
					type: 'cross',
					callback: () => this.layout.close(),
				}]);
				this.isCloseButtonShow = true;
				this.setState({pending: false});
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				View(
					{
						style: {
							flex: 1,
						},
					},
					new Banner({
						ref: (ref) => {
							this.banner = ref;
						},
						imageName: 'creation.svg',
						title: Loc.getMessage(
							'SIGN_MOBILE_MASTER_EMPTY_STATE_TITLE_DOCUMENT_CREATION',
							{ '#SELECTED_TEMPLATE_TITLE#': this.title },
						),
						description: Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_DESCRIPTION_DOCUMENT_CREATION'),
					}),
				),
				View(
					{
						style: {
							backgroundColor: Color.bgContentPrimary.toHex(),
							height: this.isCloseButtonShow ? 110 : 62,
							marginTop: 12,
							marginRight: 18,
							marginBottom: 6,
							marginLeft: 18,
						},
					},
					Button({
						ref: (ref) => {
							this.button = ref;
						},
						text: Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_CREATION_STEP_NEXT_STEP_BUTTON_TEXT'),
						testId: 'Button',
						size: ButtonSize.XL,
						design: ButtonDesign.FILLED,
						loading: this.state.pending,
						stretched: true,
						onClick: this.#onTryAgainButtonClickHandler,
					}),
					this.isCloseButtonShow
						? Button({
							style: {
								marginTop: 10,
								backgroundColor: Color.bgContentPrimary.toHex(),
							},
							text: Loc.getMessage('SIGN_MOBILE_MASTER_CLOSE_BUTTON_NAME'),
							testId: 'Button',
							size: ButtonSize.XL,
							design: ButtonDesign.PLAN_ACCENT,
							loading: false,
							stretched: true,
							disabled: this.state.pending,
							onClick: () => this.widget.close(),
						})
						: null
				),
			);
		}

		#onTryAgainButtonClickHandler = () => {
			if (!this.state.pending)
			{
				this.setState({pending: true});
				this.banner.rerender(
					'creation.svg',
					Loc.getMessage(
						'SIGN_MOBILE_MASTER_EMPTY_STATE_TITLE_DOCUMENT_CREATION',
						{ '#SELECTED_TEMPLATE_TITLE#': this.title },
					),
					Loc.getMessage('SIGN_MOBILE_MASTER_EMPTY_STATE_DESCRIPTION_DOCUMENT_CREATION'),
				);
				this.createDocument();
			}
		};

		sleep(ms)
		{
			return new Promise((resolve) => {
				setTimeout(resolve, ms);
			});
		}

		openSigning(memberId, documentId)
		{
			this.analyticsEvent.setStatus('success');
			this.analyticsEvent.setP5('docId_' + documentId);
			this.analyticsEvent.send();
			getSigningLinkPromise(memberId).then(({ data }) => {
				const {
					url,
					isReadyForSigning,
					isGoskey,
					isExternal,
					state,
					role,
					documentTitle = '',
					initiatedByType,
				} = data;

				if (isReadyForSigning)
				{
					this.widget.setTitle({
						text: Loc.getMessage('SIGN_MOBILE_MASTER_DOCUMENT_SIGNING_STEP_TITLE'),
						useLargeTitleMode: true,
					});
					this.widget.showComponent(new SignDocument({
						role,
						url,
						widget: this.widget,
						memberId,
						title: documentTitle,
						isGoskey,
						isExternal,
						initiatedByType,
					}));
				}
				else
				{
					SignDialog.show({
						type: state,
						memberId,
						layoutWidget: this.widget,
						fileDownloadUrl: url,
						documentTitle,
						initiatedByType,
					});
				}
			}).catch(() => {
				SignDialog.show({
					type: SignDialog.ERROR_BANNER_TYPE,
					layoutWidget: this.widget,
					initiatedByType,
				});
			});
		}
	}

	module.exports = { CreateDocument };
});