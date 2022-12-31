/**
 * @module crm/document/details
 */
jn.define('crm/document/details', (require, exports, module) => {

	const { FadeView } = require('animation/components/fade-view');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { shortTime, dayMonth } = require('utils/date/formats');
	const { CrmDocumentEditor } = require('crm/document/edit');
	const { Filesystem } = require('native/filesystem');
	const { withCurrentDomain } = require('utils/url');

	/**
	 * @class CrmDocumentDetails
	 */
	class CrmDocumentDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget;

			this.state = {
				loading: true,
				document: {},
			};
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;

			parentWidget
				.openWidget('layout', {})
				.then(layoutWidget => layoutWidget.showComponent(new CrmDocumentDetails({
					...props,
					layoutWidget,
				})));
		}

		/**
		 * @return {CrmDocumentProps}
		 */
		get document()
		{
			return this.state.document;
		}

		componentDidMount()
		{
			this.layoutWidget.setTitle({
				text: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOCUMENT_TITLE'),
				detailText: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOCUMENT_TITLE_LOADING'),
				useProgress: true,
			});
			this.layoutWidget.enableNavigationBarBorder(true);

			const action = 'documentgenerator.document.get';
			const data = { id: this.props.documentId };

			BX.ajax.runAction(action, { data })
				.then(response => {

					/** @type {CrmDocumentProps|null} */
					const document = response.data.document || null;

					console.log('document loaded', document);

					if (document === null)
					{
						// handle error
						return;
					}

					const moment = new Moment(document.createTime);

					this.layoutWidget.setTitle({
						text: document.title,
						detailText: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOCUMENT_TITLE_DATE_CREATE', {
							'#DATE#': moment.format(`${dayMonth()}, ${shortTime()}`)
						}),
						useProgress: false,
					});
					this.layoutWidget.setRightButtons([{
						name: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_EDIT_DOCUMENT'),
						type: 'text',
						color: '#0b66c3',
						callback: () => this.openDocumentEditor(),
					}]);
					this.setState({
						document,
						loading: false,
					})
				})
				.catch((response) => {
					// alert error
					console.error(response);
					this.setState({ loading: false })
				})
		}

		render()
		{
			return View(
				{},
				this.state.loading
					? new LoadingScreenComponent()
					: new FadeView({
						visible: false,
						fadeInOnMount: true,
						style: {
							flexGrow: 1,
						},
						slot: () => this.renderContent(),
					}),
			);
		}

		renderContent()
		{
			return ScrollView(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: '#eef2f4',
					}
				},
				View(
					{
						style: {
							paddingVertical: 12,
						}
					},
					this.renderPdfInProgressPanel(),
					this.renderPdfThumbnail(),
					this.renderButtons(),
					this.renderPublicLink(),
				)
			);
		}

		renderPdfInProgressPanel()
		{
			if (this.document.pdfUrl)
			{
				return null;
			}

			return View(
				{
					style: {
						alignItems: 'center',
						backgroundColor: '#ffffff',
						borderRadius: 12,
						padding: 16,
						paddingBottom: 0,
						marginBottom: 12,
					}
				},
				Text({ text: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_IN_PROGRESS') }),
				Loader({
					style: {
						width: 50,
						height: 50,
					},
					tintColor: '#82888F',
					animating: true,
					size: 'small',
				}),
				View(
					{
						style: {
							paddingBottom: 16,
						},
						onClick: () => this.downloadDocx()
					},
					Text({
						text: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOWNLOAD_DOCX'),
						style: {
							color: '#82888F'
						}
					})
				)
			);
		}

		renderPdfThumbnail()
		{
			if (!this.document.imageUrl)
			{
				return null;
			}

			return View(
				{
					style: {
						borderRadius: 12,
						backgroundColor: '#ffffff',
						padding: 16,
						marginBottom: 12,
						alignItems: 'center',
					},
					onClick: () => this.openPdf(),
				},
				Image({
					uri: withCurrentDomain(this.state.document.imageUrl),
					resizeMode: 'contain',
					style: {
						width: 320,
						height: 320,
					}
				}),
			);
		}

		renderButtons()
		{
			if (!this.document.pdfUrl)
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						padding: 16,
						borderRadius: 12,
						marginBottom: 12,
						backgroundColor: '#ffffff'
					}
				},
				new BaseButton({
					style: {
						button: {
							borderWidth: 1,
							borderColor: '#000',
							width: '45%',
							marginRight: 12,
						}
					},
					rounded: true,
					text: BX.message('M_CRM_DOCUMENT_DETAILS_VIEW'),
					onClick: () => this.openPdf(),
				}),
				new BaseButton({
					style: {
						button: {
							borderWidth: 1,
							borderColor: '#000',
							width: '45%',
						}
					},
					rounded: true,
					text: BX.message('M_CRM_DOCUMENT_DETAILS_DOWNLOAD_DOCX'),
					onClick: () => this.downloadDocx(),
				}),
			);
		}

		renderPublicLink()
		{
			const viewedAt = this.document.publicUrlView ? new Moment(this.document.publicUrlView.time) : null;
			const viewedAtText = viewedAt
				? Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PUBLIC_LINK_VIEWED_AT', {
					'#DATE#': viewedAt.format(`${dayMonth()}, ${shortTime()}`),
				})
				: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PUBLIC_LINK_NOT_VIEWED_YET');

			return View(
				{
					style: {
						padding: 16,
						marginBottom: 12,
						borderRadius: 12,
						backgroundColor: '#ffffff',
					},
					onClick: () => {
						// todo If pub link not yet generated, call API to create it
						Application.copyToClipboard(this.document.publicUrl);
						const params = {
							title: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_COPY_DONE'),
							showCloseButton: false,
							id: 'crm-document-public-link-copied',
							backgroundColor: '#000000',
							textColor: '#ffffff',
							hideOnTap: true,
							autoHide: true,
						};

						const callback = () => {
						};

						dialogs.showSnackbar(params, callback);
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'flex-start',
						}
					},
					View(
						{
							style: {
								flexDirection: 'column',
								justifyContent: 'center',
								marginRight: 16,
							}
						},
						Image({
							svg: {
								content: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.912 6.34321L10.2839 8.97047C8.83335 10.4218 6.48024 10.4218 5.02947 8.97047C4.80095 8.7426 4.62218 8.48584 4.46519 8.22105L5.68633 7C5.74438 6.94146 5.81606 6.90792 5.88455 6.86841C5.96898 7.15684 6.11635 7.4299 6.34319 7.65677C7.06759 8.38175 8.24633 8.38074 8.97031 7.65677L11.5976 5.02957C12.3226 4.30468 12.3226 3.12621 11.5976 2.40188C10.8736 1.67754 9.69517 1.67754 8.97031 2.40188L8.03604 3.33715C7.27788 3.04194 6.46106 2.96257 5.6659 3.0791L7.65675 1.08832C9.10812 -0.362774 11.4606 -0.362774 12.912 1.08832C14.3627 2.53935 14.3627 4.89221 12.912 6.34321ZM5.96438 10.6633L5.02944 11.5986C4.3051 12.3226 3.12629 12.3226 2.40183 11.5986C1.67743 10.8736 1.67743 9.69515 2.40183 8.97047L5.02944 6.34321C5.75436 5.61838 6.93237 5.61838 7.65671 6.34321C7.88303 6.56959 8.03056 6.84258 8.11578 7.13065C8.18467 7.09061 8.25546 7.05808 8.31348 6.99997L9.53453 5.77942C9.37855 5.51359 9.19886 5.25775 8.97024 5.02963C7.51985 3.57853 5.16652 3.57853 3.71542 5.02963L1.08821 7.65692C-0.362737 9.10844 -0.362737 11.4606 1.08821 12.912C2.53931 14.3627 4.89209 14.3627 6.34315 12.912L8.33452 10.9208C7.53893 11.038 6.7219 10.958 5.96438 10.6633Z" fill="#525C69"/></svg>`
							},
							style: {
								width: 14,
								height: 14,
							}
						}),
					),
					View(
						{
							style: {
								flexDirection: 'column',
							}
						},
						View(
							{},
							Text({
								text: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_COPY_PUBLIC_LINK'),
								style: {
									color: '#333333',
									fontSize: 15,
								}
							})
						),
						View(
							{},
							Text({
								text: viewedAtText,
								style: {
									color: '#82888F',
									fontSize: 13,
								}
							})
						)
					)
				),
			);
		}

		openPdf()
		{
			if (this.document.pdfUrl)
			{
				viewer.openDocument(withCurrentDomain(this.document.pdfUrl));
			}
		}

		openDocumentEditor()
		{
			CrmDocumentEditor.open({
				parentWidget: this.layoutWidget,
				documentId: this.document.id,
			});
		}

		downloadDocx()
		{
			const path = this.document.downloadUrl;

			Notify.showIndicatorLoading();
			Filesystem.downloadFile(withCurrentDomain(path)).then(uri => {
				Notify.hideCurrentIndicator();
				dialogs.showSharingDialog({ uri });
			});
		}
	}

	module.exports = { CrmDocumentDetails };

});