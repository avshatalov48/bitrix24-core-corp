/**
 * @module crm/document/details
 */
jn.define('crm/document/details', (require, exports, module) => {
	const { FadeView } = require('animation/components/fade-view');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { shortTime, date } = require('utils/date/formats');
	const { CrmDocumentEditor } = require('crm/document/edit');
	const { CrmDocumentQrCode } = require('crm/document/qr-code');
	const { CrmDocumentContextMenu } = require('crm/document/context-menu');
	const { CrmDocumentShareDialog } = require('crm/document/share-dialog');
	const { CrmDocumentDetailsLoadingScreen, LoadingMode } = require('crm/document/details/loading-screen');
	const { CrmDocumentDetailsErrorPanel } = require('crm/document/details/error-panel');
	const { CrmDocumentDetailsPdfView } = require('crm/document/details/pdf-view');
	const {
		CrmDocumentDetailsBottomToolbar,
		CrmDocumentDetailsBottomToolbarIcon,
	} = require('crm/document/details/bottom-toolbar');
	const { showTooltip } = require('crm/document/shared-utils');
	const { showInternalAlert } = require('crm/error');
	const { Filesystem, utils } = require('native/filesystem');
	const { withCurrentDomain } = require('utils/url');

	const ViewStage = {
		Loading: 1,
		TransformationError: 2,
		DocumentReady: 3,
	};

	const FadeIn = (innerContent) => new FadeView({
		visible: false,
		fadeInOnMount: true,
		style: {
			flexGrow: 1,
		},
		slot: () => innerContent(),
	});

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
				viewStage: ViewStage.Loading,
				document: {},
				myCompanyRequisites: null,
				clientRequisites: null,
				entityId: null,
				entityTypeId: null,
				entityDetailUrl: null,
				localPdfPath: null,
				isSigningEnabled: false,
				isSigningEnabledInCurrentTariff: false,
				signingInfoHelperSliderCode: null,
				channelSelector: null,
			};

			/** @type {CrmDocumentPageNav|null} */
			this.pageNavRef = null;

			/** @type {function|null} */
			this.pushSubscriptionCancel = null;
		}

		/**
		 * @return {CrmDocumentProps}
		 */
		get document()
		{
			return this.state.document;
		}

		/**
		 * @param {number} stage
		 * @return {boolean}
		 */
		isViewStage(stage)
		{
			return this.state.viewStage === stage;
		}

		componentDidMount()
		{
			this.renderWidgetTitle();

			const action = 'crmmobile.DocumentGenerator.Document.get';
			const data = { id: this.props.documentId };

			BX.ajax.runAction(action, { json: data })
				.then((response) => {
					/** @type {CrmDocumentProps|null} */
					const document = response.data.document;
					if (!document)
					{
						throw new Error('Document loading problem');
					}

					this.subscribeToPushEvents(document.pullTag);

					this.setDocumentState(document, {
						myCompanyRequisites: response.data.myCompanyRequisites,
						clientRequisites: response.data.clientRequisites,
						entityId: response.data.entityId,
						entityTypeId: response.data.entityTypeId,
						entityDetailUrl: response.data.entityDetailUrl,
						isSigningEnabled: response.data.isSigningEnabled,
						isSigningEnabledInCurrentTariff: response.data.isSigningEnabledInCurrentTariff,
						signingInfoHelperSliderCode: response.data.signingInfoHelperSliderCode,
						channelSelector: response.data.channelSelector,
					});
				})
				.catch((response) => {
					console.error(response);
					showInternalAlert(() => this.layoutWidget.close());
				});
		}

		componentWillUnmount()
		{
			if (this.pushSubscriptionCancel)
			{
				this.pushSubscriptionCancel();
			}
		}

		subscribeToPushEvents(listenTag)
		{
			this.pushSubscriptionCancel = BX.PULL.subscribe({
				moduleId: 'documentgenerator',
				callback: (pushEvent) => {
					const command = BX.prop.getString(pushEvent, 'command', '');
					const params = BX.prop.getObject(pushEvent, 'params', {});
					const tag = BX.prop.getString(params, 'pullTag', '');

					if (command === 'showImage' && tag === listenTag)
					{
						this.setDocumentState(params);
					}
				},
			});
		}

		render()
		{
			return View(
				{},
				this.isViewStage(ViewStage.Loading) && this.renderLoadingScreen(),
				this.isViewStage(ViewStage.TransformationError) && this.renderTransformationError(),
				this.isViewStage(ViewStage.DocumentReady) && this.renderPdfViewer(),
			);
		}

		renderLoadingScreen()
		{
			return new CrmDocumentDetailsLoadingScreen({
				mode: this.document.id ? LoadingMode.PDF : LoadingMode.Document,
				onDownloadButtonClick: () => this.downloadDocx(),
			});
		}

		renderTransformationError()
		{
			return FadeIn(() => View(
				{
					style: {
						backgroundColor: '#EEF2F4',
						flexDirection: 'column',
						justifyContent: 'center',
						flexGrow: 1,
					},
				},
				CrmDocumentDetailsErrorPanel({
					title: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_TRANSFORM_ERROR_TITLE'),
					subtitle: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_TRANSFORM_ERROR_BODY'),
				}),
			));
		}

		renderPdfViewer()
		{
			return FadeIn(() => View(
				{
					style: {
						flex: 1,
						backgroundColor: '#eef2f4',
					},
				},
				CrmDocumentDetailsPdfView({
					uri: this.state.localPdfPath,
					onChangePage: ({ currentPage, totalPage }) => {
						if (this.pageNavRef)
						{
							this.pageNavRef.setData({ currentPage, totalPage });
						}
					},
				}),
				this.renderBottomPanel(),
			));
		}

		renderBottomPanel()
		{
			return CrmDocumentDetailsBottomToolbar(
				{
					onShare: () => this.shareDocument(),
				},
				CrmDocumentDetailsBottomToolbarIcon({
					disabled: !this.document.qrCodeEnabled,
					onClick: () => this.openQrCode(),
					icon: SvgIcons.qr,
				}),
				CrmDocumentDetailsBottomToolbarIcon({
					onClick: () => this.openDownloadOptions(),
					icon: SvgIcons.download,
				}),
				CrmDocumentDetailsBottomToolbarIcon({
					onClick: () => this.printPdfFile(),
					icon: SvgIcons.print,
				}),
				CrmDocumentDetailsBottomToolbarIcon({
					onClick: () => this.openDocumentEditor(),
					icon: SvgIcons.edit,
				}),
			);
		}

		openDocumentEditor()
		{
			CrmDocumentEditor.open({
				parentWidget: this.layoutWidget,
				documentId: this.document.id,
				onChange: (values) => {
					this.updateDocument({ values });
				},
			});
		}

		downloadDocx()
		{
			return new Promise((resolve, reject) => {
				const path = this.document.downloadUrl;
				Filesystem.downloadFile(withCurrentDomain(path))
					.then((uri) => {
						this.saveFileToSystemDownloadsFolder(uri);
						resolve();
					})
					.catch(reject);
			});
		}

		downloadPdf()
		{
			this.saveFileToSystemDownloadsFolder(this.state.localPdfPath);
		}

		saveFileToSystemDownloadsFolder(localPath)
		{
			if (utils && 'saveFile' in utils)
			{
				utils.saveFile(localPath).catch(() => dialogs.showSharingDialog({ uri: localPath }));
			}
			else
			{
				dialogs.showSharingDialog({ uri: localPath });
			}
		}

		printPdfFile()
		{
			if (utils && 'printFile' in utils)
			{
				utils.printFile(this.state.localPdfPath)
					.catch(() => this.downloadPdf());
			}
			else
			{
				this.downloadPdf();
			}
		}

		openDownloadOptions()
		{
			const menu = new ContextMenu({
				actions: [
					{
						id: 'pdf',
						title: 'PDF',
						sectionCode: 'default',
						subTitle: '',
						data: {
							svgIcon: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.0562 5.00244C12.7111 5.00244 12.4312 5.28226 12.4312 5.62744V11.5528H8.16095C7.81819 11.5528 7.65508 11.9747 7.9087 12.2052L13.5555 17.3387C14.0323 17.7721 14.7604 17.7721 15.2372 17.3387L20.884 12.2052C21.1376 11.9747 20.9745 11.5528 20.6317 11.5528H16.3614V5.62744C16.3614 5.28226 16.0816 5.00244 15.7364 5.00244H13.0562ZM8.125 21.2668C7.77982 21.2668 7.5 21.5466 7.5 21.8918V23.1418C7.5 23.487 7.77982 23.7668 8.125 23.7668H20.6315C20.9767 23.7668 21.2565 23.487 21.2565 23.1418V21.8918C21.2565 21.5466 20.9767 21.2668 20.6315 21.2668H8.125Z" fill="#A8ADB4"/></svg>',
						},
						onClickCallback: () => {
							menu.close(() => this.downloadPdf());
							return Promise.resolve();
						},
					},
					{
						id: 'docx',
						title: 'DOCX',
						sectionCode: 'default',
						subTitle: '',
						data: {
							svgIcon: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.0562 5.00244C12.7111 5.00244 12.4312 5.28226 12.4312 5.62744V11.5528H8.16095C7.81819 11.5528 7.65508 11.9747 7.9087 12.2052L13.5555 17.3387C14.0323 17.7721 14.7604 17.7721 15.2372 17.3387L20.884 12.2052C21.1376 11.9747 20.9745 11.5528 20.6317 11.5528H16.3614V5.62744C16.3614 5.28226 16.0816 5.00244 15.7364 5.00244H13.0562ZM8.125 21.2668C7.77982 21.2668 7.5 21.5466 7.5 21.8918V23.1418C7.5 23.487 7.77982 23.7668 8.125 23.7668H20.6315C20.9767 23.7668 21.2565 23.487 21.2565 23.1418V21.8918C21.2565 21.5466 20.9767 21.2668 20.6315 21.2668H8.125Z" fill="#A8ADB4"/></svg>',
						},
						onClickCallback: () => {
							menu.close(() => this.downloadDocx());
							return Promise.resolve();
						},
					},
				],
				params: {
					showActionLoader: false,
					showCancelButton: false,
					title: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOWNLOAD_FILE'),
				},
			});

			void menu.show(this.layoutWidget);
		}

		openQrCode()
		{
			if (this.document.qrCodeEnabled)
			{
				CrmDocumentQrCode.open({
					documentId: this.document.id,
					parentWidget: this.layoutWidget,
				});
			}
			else
			{
				const message = this.document.changeQrCodeDisabledReason
					|| Loc.getMessage('M_CRM_DOCUMENT_SHARED_PHRASES_QR_CODE_CHANGE_DISABLED_DEFAULT_REASON');

				showTooltip(message);
			}
		}

		openContextMenu()
		{
			const menu = new CrmDocumentContextMenu({
				layoutWidget: this.props.layoutWidget,
				document: this.document,
				myCompanyRequisites: this.state.myCompanyRequisites,
				clientRequisites: this.state.clientRequisites,
				onChangeQrCode: (enabled) => {
					const data = {
						values: {
							PaymentQrCode: enabled ? 'this.SOURCE.PAYMENT_QR_CODE' : '',
						},
					};
					this.updateDocument(data);
				},
				onChangeStamps: (stampsEnabled) => {
					const data = {
						stampsEnabled: Number(stampsEnabled),
					};
					this.updateDocument(data);
				},
			});

			menu.open();
		}

		updateDocument(data)
		{
			this.setState({ viewStage: ViewStage.Loading });
			const action = 'documentgenerator.document.update';
			const { id, stampsEnabled, changeStampsEnabled } = this.document;

			data = {
				id,
				stampsEnabled: Number(stampsEnabled && changeStampsEnabled),
				values: {},
				...data,
			};

			BX.ajax.runAction(action, { data })
				.then((response) => {
					/** @type {CrmDocumentProps|null} */
					const document = response.data.document || null;
					if (document === null)
					{
						throw new Error('Document update problem');
					}

					this.setDocumentState(document);
				})
				.catch((err) => {
					console.error(err);
					showInternalAlert(() => this.setState({ viewStage: ViewStage.DocumentReady }));
				});
		}

		setDocumentState(document, other = {})
		{
			const setState = (localPdfPath) => {
				let viewStage = ViewStage.Loading;
				if (document.isTransformationError)
				{
					viewStage = ViewStage.TransformationError;
				}
				else if (localPdfPath)
				{
					viewStage = ViewStage.DocumentReady;
				}
				const nextState = {
					document,
					localPdfPath,
					viewStage,
					...other,
				};

				this.setState(nextState, () => this.renderWidgetTitle());
			};

			if (document.pdfUrl)
			{
				Filesystem.downloadFile(withCurrentDomain(document.pdfUrl))
					.then((localPdfPath) => setState(localPdfPath))
					.catch(() => setState(null));

				return;
			}

			setState(null);
		}

		shareDocument()
		{
			CrmDocumentShareDialog.open({
				entityId: this.state.entityId,
				entityTypeId: this.state.entityTypeId,
				entityDetailUrl: this.state.entityDetailUrl,
				localPdfPath: this.state.localPdfPath,
				isSigningEnabled: this.state.isSigningEnabled,
				isSigningEnabledInCurrentTariff: this.state.isSigningEnabledInCurrentTariff,
				signingInfoHelperSliderCode: this.state.signingInfoHelperSliderCode,
				channelSelector: this.state.channelSelector,
				document: this.document,
				parentWidget: this.layoutWidget,
				onImDialogOpened: () => this.layoutWidget.close(),
			});
		}

		renderWidgetTitle()
		{
			const text = this.document.title || this.props.title || Loc.getMessage('M_CRM_DOCUMENT_SHARED_PHRASES_LOADING');
			const createdAtTimestamp = this.document.createTime || this.props.createdAt;
			let detailText = null;
			if (createdAtTimestamp)
			{
				detailText = Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOCUMENT_TITLE_DATE_CREATE', {
					'#DATE#': (new Moment(createdAtTimestamp)).format(`${date()}, ${shortTime()}`),
				});
			}

			this.layoutWidget.setTitle({
				text,
				detailText,
				useProgress: false,
				svg: {
					content: SvgIcons.title,
				},
			});
			if (!this.document.id || this.document.isTransformationError)
			{
				this.layoutWidget.setRightButtons([]);
			}
			else
			{
				this.layoutWidget.setRightButtons([
					{
						type: 'svg',
						svg: {
							content: SvgIcons.contextMenu,
						},
						callback: () => this.openContextMenu(),
					},
				]);
			}
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;

			parentWidget
				.openWidget('layout', {
					modal: true,
					backdrop: {
						onlyMediumPosition: true,
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						mediumPositionPercent: 90,
						swipeAllowed: true,
						swipeContentAllowed: false,
						horizontalSwipeAllowed: false,
						hideNavigationBar: false,
						navigationBarColor: '#eef2f4',
						helpUrl: helpdesk.getArticleUrl('17393988'),
					},
				})
				.then((layoutWidget) => layoutWidget.showComponent(new CrmDocumentDetails({
					...props,
					layoutWidget,
				})));
		}
	}

	const SvgIcons = {
		qr: '<svg width="29" height="28" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.45821 8.75H7.82488V7.11666H9.45821V8.75Z" fill="#A8ADB4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.00821 4.66666H10.2749C11.1773 4.66666 11.9082 5.39758 11.9082 6.3V9.56666C11.9082 10.4691 11.1773 11.2 10.2749 11.2H7.00821C6.10579 11.2 5.37488 10.4691 5.37488 9.56666V6.3C5.37488 5.39758 6.10579 4.66666 7.00821 4.66666ZM7.00821 9.56666H10.2749V6.3H7.00821V9.56666Z" fill="#A8ADB4"/><path d="M9.45821 20.8833H7.82488V19.25H9.45821V20.8833Z" fill="#A8ADB4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.00821 16.8H10.2749C11.1773 16.8 11.9082 17.5309 11.9082 18.4333V21.7C11.9082 22.6024 11.1773 23.3333 10.2749 23.3333H7.00821C6.10579 23.3333 5.37488 22.6024 5.37488 21.7V18.4333C5.37488 17.5309 6.10579 16.8 7.00821 16.8ZM7.00821 21.7H10.2749V18.4333H7.00821V21.7Z" fill="#A8ADB4"/><path d="M19.9582 8.75H21.5915V7.11666H19.9582V8.75Z" fill="#A8ADB4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M22.4082 4.66666H19.1415C18.2391 4.66666 17.5082 5.39758 17.5082 6.3V9.56666C17.5082 10.4691 18.2391 11.2 19.1415 11.2H22.4082C23.3106 11.2 24.0415 10.4691 24.0415 9.56666V6.3C24.0415 5.39758 23.3106 4.66666 22.4082 4.66666ZM22.4082 9.56666H19.1415V6.3H22.4082V9.56666Z" fill="#A8ADB4"/><path d="M14.7836 7C14.4842 7 14.2415 7.2427 14.2415 7.54208V7.85792C14.2415 8.1573 14.4842 8.4 14.7836 8.4H15.0995C15.3988 8.4 15.6415 8.1573 15.6415 7.85792V7.54208C15.6415 7.2427 15.3988 7 15.0995 7H14.7836Z" fill="#A8ADB4"/><path d="M14.2415 10.3421C14.2415 10.0427 14.4842 9.8 14.7836 9.8H15.0995C15.3988 9.8 15.6415 10.0427 15.6415 10.3421V10.6579C15.6415 10.9573 15.3988 11.2 15.0995 11.2H14.7836C14.4842 11.2 14.2415 10.9573 14.2415 10.6579V10.3421Z" fill="#A8ADB4"/><path d="M14.7836 13.5333C14.4842 13.5333 14.2415 13.776 14.2415 14.0754V14.3913C14.2415 14.6906 14.4842 14.9333 14.7836 14.9333H15.0995C15.3988 14.9333 15.6415 14.6906 15.6415 14.3913V14.0754C15.6415 13.776 15.3988 13.5333 15.0995 13.5333H14.7836Z" fill="#A8ADB4"/><path d="M10.5082 14.0754C10.5082 13.776 10.7509 13.5333 11.0503 13.5333H11.3661C11.6655 13.5333 11.9082 13.776 11.9082 14.0754V14.3913C11.9082 14.6906 11.6655 14.9333 11.3661 14.9333H11.0503C10.7509 14.9333 10.5082 14.6906 10.5082 14.3913V14.0754Z" fill="#A8ADB4"/><path d="M8.25029 13.5333C7.95091 13.5333 7.70821 13.776 7.70821 14.0754V14.3913C7.70821 14.6906 7.95091 14.9333 8.25029 14.9333H8.56613C8.86551 14.9333 9.10821 14.6906 9.10821 14.3913V14.0754C9.10821 13.776 8.86551 13.5333 8.56613 13.5333H8.25029Z" fill="#A8ADB4"/><path d="M17.5082 14.0754C17.5082 13.776 17.7509 13.5333 18.0503 13.5333H18.3661C18.6655 13.5333 18.9082 13.776 18.9082 14.0754V14.3913C18.9082 14.6906 18.6655 14.9333 18.3661 14.9333H18.0503C17.7509 14.9333 17.5082 14.6906 17.5082 14.3913V14.0754Z" fill="#A8ADB4"/><path d="M20.8503 13.5333C20.5509 13.5333 20.3082 13.776 20.3082 14.0754V14.3913C20.3082 14.6906 20.5509 14.9333 20.8503 14.9333H21.1661C21.4655 14.9333 21.7082 14.6906 21.7082 14.3913V14.0754C21.7082 13.776 21.4655 13.5333 21.1661 13.5333H20.8503Z" fill="#A8ADB4"/><path d="M15.6415 15.4754C15.6415 15.176 15.8842 14.9333 16.1836 14.9333H16.4995C16.7988 14.9333 17.0415 15.176 17.0415 15.4754V15.7913C17.0415 16.0906 16.7988 16.3333 16.4995 16.3333H16.1836C15.8842 16.3333 15.6415 16.0906 15.6415 15.7913V15.4754Z" fill="#A8ADB4"/><path d="M19.4503 14.9333C19.1509 14.9333 18.9082 15.176 18.9082 15.4754V15.7913C18.9082 16.0906 19.1509 16.3333 19.4503 16.3333H19.7661C20.0655 16.3333 20.3082 16.0906 20.3082 15.7913V15.4754C20.3082 15.176 20.0655 14.9333 19.7661 14.9333H19.4503Z" fill="#A8ADB4"/><path d="M17.5082 16.8754C17.5082 16.576 17.7509 16.3333 18.0503 16.3333H18.3661C18.6655 16.3333 18.9082 16.576 18.9082 16.8754V17.1913C18.9082 17.4906 18.6655 17.7333 18.3661 17.7333H18.0503C17.7509 17.7333 17.5082 17.4906 17.5082 17.1913V16.8754Z" fill="#A8ADB4"/><path d="M14.7836 16.3333C14.4842 16.3333 14.2415 16.576 14.2415 16.8754V17.1913C14.2415 17.4906 14.4842 17.7333 14.7836 17.7333H15.0995C15.3988 17.7333 15.6415 17.4906 15.6415 17.1913V16.8754C15.6415 16.576 15.3988 16.3333 15.0995 16.3333H14.7836Z" fill="#A8ADB4"/><path d="M15.6415 18.7421C15.6415 18.4427 15.8842 18.2 16.1836 18.2H16.4995C16.7988 18.2 17.0415 18.4427 17.0415 18.7421V19.0579C17.0415 19.3573 16.7988 19.6 16.4995 19.6H16.1836C15.8842 19.6 15.6415 19.3573 15.6415 19.0579V18.7421Z" fill="#A8ADB4"/><path d="M18.0503 19.6C17.7509 19.6 17.5082 19.8427 17.5082 20.1421V20.4579C17.5082 20.7573 17.7509 21 18.0503 21H18.3661C18.6655 21 18.9082 20.7573 18.9082 20.4579V20.1421C18.9082 19.8427 18.6655 19.6 18.3661 19.6H18.0503Z" fill="#A8ADB4"/><path d="M15.6415 22.0087C15.6415 21.7094 15.8842 21.4667 16.1836 21.4667H16.4995C16.7988 21.4667 17.0415 21.7094 17.0415 22.0087V22.3246C17.0415 22.624 16.7988 22.8667 16.4995 22.8667H16.1836C15.8842 22.8667 15.6415 22.624 15.6415 22.3246V22.0087Z" fill="#A8ADB4"/><path d="M23.1836 14.9333C22.8842 14.9333 22.6415 15.176 22.6415 15.4754V15.7913C22.6415 16.0906 22.8842 16.3333 23.1836 16.3333H23.4995C23.7988 16.3333 24.0415 16.0906 24.0415 15.7913V15.4754C24.0415 15.176 23.7988 14.9333 23.4995 14.9333H23.1836Z" fill="#A8ADB4"/><path d="M22.6415 18.7421C22.6415 18.4427 22.8842 18.2 23.1836 18.2H23.4995C23.7988 18.2 24.0415 18.4427 24.0415 18.7421V19.0579C24.0415 19.3573 23.7988 19.6 23.4995 19.6H23.1836C22.8842 19.6 22.6415 19.3573 22.6415 19.0579V18.7421Z" fill="#A8ADB4"/><path d="M19.4503 18.2C19.1509 18.2 18.9082 18.4427 18.9082 18.7421V19.0579C18.9082 19.3573 19.1509 19.6 19.4503 19.6H19.7661C20.0655 19.6 20.3082 19.3573 20.3082 19.0579V18.7421C20.3082 18.4427 20.0655 18.2 19.7661 18.2H19.4503Z" fill="#A8ADB4"/><path d="M18.9082 22.0087C18.9082 21.7094 19.1509 21.4667 19.4503 21.4667H19.7661C20.0655 21.4667 20.3082 21.7094 20.3082 22.0087V22.3246C20.3082 22.624 20.0655 22.8667 19.7661 22.8667H19.4503C19.1509 22.8667 18.9082 22.624 18.9082 22.3246V22.0087Z" fill="#A8ADB4"/><path d="M20.8503 19.6C20.5509 19.6 20.3082 19.8427 20.3082 20.1421V20.4579C20.3082 20.7573 20.5509 21 20.8503 21H21.1661C21.4655 21 21.7082 20.7573 21.7082 20.4579V20.1421C21.7082 19.8427 21.4655 19.6 21.1661 19.6H20.8503Z" fill="#A8ADB4"/><path d="M20.3082 16.8754C20.3082 16.576 20.5509 16.3333 20.8503 16.3333H21.1661C21.4655 16.3333 21.7082 16.576 21.7082 16.8754V17.1913C21.7082 17.4906 21.4655 17.7333 21.1661 17.7333H20.8503C20.5509 17.7333 20.3082 17.4906 20.3082 17.1913V16.8754Z" fill="#A8ADB4"/><path d="M14.7836 19.6C14.4842 19.6 14.2415 19.8427 14.2415 20.1421V20.4579C14.2415 20.7573 14.4842 21 14.7836 21H15.0995C15.3988 21 15.6415 20.7573 15.6415 20.4579V20.1421C15.6415 19.8427 15.3988 19.6 15.0995 19.6H14.7836Z" fill="#A8ADB4"/></svg>',
		download: '<svg width="29" height="28" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.7082 23.3333C19.8629 23.3333 24.0415 19.1547 24.0415 14C24.0415 8.84534 19.8629 4.66666 14.7082 4.66666C9.55355 4.66666 5.37488 8.84534 5.37488 14C5.37488 19.1547 9.55355 23.3333 14.7082 23.3333ZM13.7623 9.33333C13.5186 9.33333 13.3211 9.54942 13.3211 9.81599V13.9474H10.3068C10.0648 13.9474 9.94969 14.2732 10.1287 14.4513L14.1147 18.4156C14.4512 18.7503 14.9652 18.7503 15.3017 18.4156L19.2877 14.4513C19.4667 14.2732 19.3516 13.9474 19.1096 13.9474H16.0953V9.81599C16.0953 9.54942 15.8978 9.33333 15.6542 9.33333H13.7623Z" fill="#A8ADB4"/></svg>',
		print: '<svg width="29" height="28" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.0172 10.525C22.4681 10.525 22.8353 10.9153 22.8353 11.3945V17.4804C22.8353 17.9596 22.4681 18.3498 22.0172 18.3498H21.1992V21.2751C21.1992 21.7543 20.832 22.1445 20.3811 22.1445H8.9285C8.47761 22.1445 8.11045 21.7543 8.11045 21.2751V18.3498H7.29241C6.84152 18.3498 6.47437 17.9596 6.47437 17.4804V11.3945C6.47437 10.9153 6.84152 10.525 7.29241 10.525H22.0172ZM19.5631 17.4804H9.74654V20.4057H19.5631V17.4804ZM20.3811 12.2639C19.9302 12.2639 19.5631 12.6541 19.5631 13.1333C19.5631 13.6125 19.9302 14.0027 20.3811 14.0027C20.832 14.0027 21.1992 13.6125 21.1992 13.1333C21.1992 12.6541 20.832 12.2639 20.3811 12.2639ZM20.1732 5.7661C20.3551 5.7661 20.4987 5.92818 20.4987 6.1255V8.98665C20.4987 9.18749 20.3519 9.34605 20.1732 9.34605H9.13718C8.95845 9.34605 8.81164 9.18749 8.81164 8.99017V6.1255C8.81164 5.92818 8.95845 5.7661 9.13718 5.7661L20.1732 5.7661Z" fill="#A8ADB4"/></svg>',
		edit: '<svg width="29" height="28" viewBox="0 0 29 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.0067 6.37557C19.2028 6.18013 19.5203 6.18122 19.7151 6.378L22.4292 9.1207C22.6229 9.31648 22.6218 9.63209 22.4268 9.82653L11.8065 20.412L8.38896 16.9585L19.0067 6.37557ZM6.80182 21.5653C6.7695 21.6876 6.80413 21.8168 6.89185 21.9068C6.98187 21.9968 7.11114 22.0314 7.23348 21.9968L11.0538 20.9676L7.83135 17.7461L6.80182 21.5653Z" fill="#A8ADB4"/></svg>',
		title: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="15" cy="15" r="14.5" fill="white" stroke="#EEF2F4"/><g clip-path="url(#clip0_105_129)"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 7C9 5.89543 9.89543 5 11 5H17.5029C18.0658 5 18.6026 5.2372 18.9816 5.65338L21.4787 8.39544C21.8141 8.76374 22 9.24394 22 9.74207V22C22 23.1046 21.1046 24 20 24H11C9.89543 24 9 23.1046 9 22V7Z" fill="white"/><path opacity="0.54" d="M11 6H17.5029C17.7843 6 18.0527 6.1186 18.2422 6.32669L20.7394 9.06875C20.9071 9.2529 21 9.493 21 9.74207V22C21 22.5523 20.5523 23 20 23H11C10.4477 23 10 22.5523 10 22V7C10 6.44772 10.4477 6 11 6Z" fill="black" fill-opacity="0.01" stroke="#2FC6F6" stroke-width="2"/><rect x="11.7368" y="8.95833" width="5.47368" height="0.791667" rx="0.395833" fill="#2FC6F6"/><rect x="11.7368" y="12.125" width="6.8421" height="0.791667" rx="0.395833" fill="#8FE0FA"/><rect x="11.7368" y="15.2917" width="6.8421" height="0.791666" rx="0.395833" fill="#8FE0FA"/><rect x="11.7368" y="13.7083" width="6.1579" height="0.791667" rx="0.395833" fill="#8FE0FA"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4338 18.5551C16.5552 18.6861 16.5574 18.9009 16.4386 19.0349L15.5791 20.0042C15.5197 20.0712 15.4379 20.1081 15.353 20.1062C15.2681 20.1043 15.1877 20.0638 15.1308 19.9943L14.7711 19.5545L14.0625 20.4718C14.0051 20.5461 13.9214 20.5896 13.8328 20.5909C13.7442 20.5923 13.6594 20.5515 13.6001 20.479L13.0355 19.7888L12.2728 20.7213C12.159 20.8604 11.9645 20.8715 11.8384 20.7459C11.7122 20.6204 11.7022 20.4059 11.816 20.2668L12.8072 19.055C12.8655 18.9837 12.9485 18.943 13.0355 18.943C13.1226 18.943 13.2056 18.9837 13.2639 19.055L13.8218 19.7371L14.5304 18.8199C14.5878 18.7455 14.6716 18.7021 14.7601 18.7007C14.8487 18.6993 14.9335 18.7401 14.9929 18.8127L15.3682 19.2716L15.9988 18.5604C16.1176 18.4264 16.3123 18.424 16.4338 18.5551Z" fill="#2FC6F6"/></g><defs><clipPath id="clip0_105_129"><rect width="13" height="19" fill="white" transform="translate(9 5)"/></clipPath></defs></svg>',
		contextMenu: '<svg width="26" height="25" viewBox="0 0 26 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.74996 14.5834C7.90055 14.5834 8.83329 13.6506 8.83329 12.5C8.83329 11.3494 7.90055 10.4167 6.74996 10.4167C5.59937 10.4167 4.66663 11.3494 4.66663 12.5C4.66663 13.6506 5.59937 14.5834 6.74996 14.5834Z" fill="#A8ADB4"/><path d="M13 14.5834C14.1506 14.5834 15.0833 13.6506 15.0833 12.5C15.0833 11.3494 14.1506 10.4167 13 10.4167C11.8494 10.4167 10.9166 11.3494 10.9166 12.5C10.9166 13.6506 11.8494 14.5834 13 14.5834Z" fill="#A8ADB4"/><path d="M21.3333 12.5C21.3333 13.6506 20.4006 14.5834 19.25 14.5834C18.0994 14.5834 17.1666 13.6506 17.1666 12.5C17.1666 11.3494 18.0994 10.4167 19.25 10.4167C20.4006 10.4167 21.3333 11.3494 21.3333 12.5Z" fill="#A8ADB4"/></svg>',
	};

	module.exports = { CrmDocumentDetails };
});
