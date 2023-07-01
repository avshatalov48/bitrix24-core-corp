/**
 * @module crm/document/details/loading-screen
 */
jn.define('crm/document/details/loading-screen', (require, exports, module) => {
	const { BitrixCloudLoader } = require('layout/ui/loaders/bitrix-cloud');
	const { CrmDocumentDownloadLink } = require('crm/document/details/download-link');
	const { Loc } = require('loc');
	const { animate } = require('animation');
	const Spacer = () => View({ style: { height: 1 } });
	const LoadingMode = {
		Document: 1,
		PDF: 2,
	};

	class CrmDocumentDetailsLoadingScreen extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.mode = props.mode === LoadingMode.PDF ? LoadingMode.PDF : LoadingMode.Document;

			this.documentLoadingTextRef = null;
			this.pdfTransformationTextRef = null;
			this.downloadLinkContainerRef = null;
		}

		isMode(mode)
		{
			return this.mode === mode;
		}

		shouldComponentUpdate()
		{
			return false;
		}

		componentWillReceiveProps(props)
		{
			if (!this.isMode(props.mode))
			{
				this.mode = props.mode === LoadingMode.PDF ? LoadingMode.PDF : LoadingMode.Document;

				void animate(this.downloadLinkContainerRef, {
					duration: 100,
					opacity: this.mode === LoadingMode.PDF ? 1 : 0,
				});

				void animate(this.documentLoadingTextRef, {
					duration: 100,
					opacity: this.mode === LoadingMode.Document ? 1 : 0,
				});

				void animate(this.pdfTransformationTextRef, {
					duration: 100,
					opacity: this.mode === LoadingMode.PDF ? 1 : 0,
				});
			}
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: '#EEF2F4',
						flexDirection: 'column',
						justifyContent: 'space-between',
						flexGrow: 1,
					},
				},
				Spacer(),
				View(
					{
						style: {
							backgroundColor: '#fff',
							borderRadius: 12,
							marginHorizontal: 16,
							paddingTop: 32,
							paddingBottom: 27,
							alignItems: 'center',
						},
					},
					BitrixCloudLoader({
						width: 130,
						height: 130,
						lottieOptions: {
							style: {
								marginBottom: 16,
							},
						},
					}),
					View(
						{
							style: {
								height: 40,
								width: '100%',
							},
						},
						this.renderDocumentLoadingText(),
						this.renderPdfTransformationText(),
					),
				),
				View(
					{
						ref: (ref) => {
							if (ref)
							{
								this.downloadLinkContainerRef = ref;
							}
						},
						style: {
							opacity: this.isMode(LoadingMode.PDF) ? 1 : 0,
						},
					},
					new CrmDocumentDownloadLink({
						text: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOWNLOAD_DOCX'),
						loadingText: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_DOWNLOAD_IN_PROGRESS'),
						onClick: () => this.onDownloadButtonClick(),
					}),
				),
			);
		}

		renderDocumentLoadingText()
		{
			return View(
				{
					style: {
						position: 'absolute',
						width: '100%',
						opacity: this.isMode(LoadingMode.Document) ? 1 : 0,
					},
					ref: (ref) => {
						if (ref)
						{
							this.documentLoadingTextRef = ref;
						}
					},
				},
				Text({
					text: Loc.getMessage('M_CRM_DOCUMENT_SHARED_PHRASES_LOADING'),
					style: {
						fontSize: 15,
						color: '#A8ADB4',
						textAlign: 'center',
					},
				}),
			);
		}

		renderPdfTransformationText()
		{
			const lines = Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_IN_PROGRESS')
				.split('#BR#')
				.map((line) => line.trim());

			return View(
				{
					style: {
						position: 'absolute',
						width: '100%',
						opacity: this.isMode(LoadingMode.PDF) ? 1 : 0,
					},
					ref: (ref) => {
						if (ref)
						{
							this.pdfTransformationTextRef = ref;
						}
					},
				},
				...lines.map((line) => Text({
					text: line,
					style: {
						fontSize: 15,
						color: '#A8ADB4',
						textAlign: 'center',
					},
				})),
			);
		}

		// eslint-disable-next-line consistent-return
		onDownloadButtonClick()
		{
			if (this.props.onDownloadButtonClick && this.mode === LoadingMode.PDF)
			{
				return this.props.onDownloadButtonClick();
			}
		}
	}

	module.exports = { CrmDocumentDetailsLoadingScreen, LoadingMode };
});
