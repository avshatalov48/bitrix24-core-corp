/**
 * @module crm/document/list
 */
jn.define('crm/document/list', (require, exports, module) => {
	const { FadeView } = require('animation/components/fade-view');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { get } = require('utils/object');
	const { shortTime, date } = require('utils/date/formats');
	const { CrmDocumentDetails } = require('crm/document/details');
	const { TimelineSchedulerDocumentProvider } = require('crm/timeline/scheduler/providers/document');
	const { transparent } = require('utils/color');
	const { getEntityMessage } = require('crm/loc');
	const { NotifyManager } = require('notify-manager');

	const wait = (ms) => new Promise((resolve) => {
		setTimeout(resolve, ms);
	});

	/**
	 * @class CrmDocumentList
	 */
	class CrmDocumentList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget;

			this.state = {
				documents: get(props, 'documents', []),
			};
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;

			const { entityTypeId, entityId } = props;

			NotifyManager.showLoadingIndicator();

			this.fetchData({ entityId, entityTypeId })
				.then((response) => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					wait(500).then(() => {
						const documents = response.data.documents || [];

						parentWidget
							.openWidget('layout', {
								modal: true,
								backdrop: {
									onlyMediumPosition: true,
									showOnTop: false,
									forceDismissOnSwipeDown: true,
									mediumPositionHeight: this.calcBackdropHeight(documents.length),
									swipeAllowed: true,
									swipeContentAllowed: true,
									horizontalSwipeAllowed: false,
									hideNavigationBar: false,
									navigationBarColor: '#eef2f4',
									helpUrl: helpdesk.getArticleUrl('17393988'),
								},
								enableNavigationBarBorder: false,
								title: getEntityMessage('M_CRM_DOCUMENT_LIST_TITLE', entityTypeId),
							})
							.then((layoutWidget) => {
								layoutWidget.enableNavigationBarBorder(false);
								layoutWidget.showComponent(new CrmDocumentList({
									...props,
									documents,
									layoutWidget,
								}));
							});
					});
				})
				.catch((err) => {
					console.error(err);
					NotifyManager.hideLoadingIndicator(false);
				});
		}

		static calcBackdropHeight(documentsCount)
		{
			documentsCount += 4;

			return 60 * documentsCount;
		}

		static fetchData({ entityTypeId, entityId })
		{
			const action = 'crm.documentgenerator.document.list';
			const data = {
				filter: {
					entityTypeId,
					entityId,
				},
				order: {
					id: 'desc',
				},
			};

			return BX.ajax.runAction(action, { data });
		}

		render()
		{
			return View(
				{},
				new FadeView({
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
					},
				},
				View(
					{
						style: {
							backgroundColor: '#eef2f4',
						},
						safeArea: { bottom: true },
					},
					this.state.documents.length > 0
						? this.renderDocumentsList()
						: this.renderEmptyList(),
				),
			);
		}

		renderDocumentsList()
		{
			return View(
				{},
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: '#fff',
							marginBottom: 10,
						},
					},
					...this.state.documents.map((document, index) => this.renderDocument(document, index)),
				),
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: '#fff',
						},
					},
					this.renderAddButtons(),
				),
			);
		}

		renderDocument(document, index)
		{
			const createdAt = new Moment(document.createTime);

			return View(
				{
					onClick: () => this.openDocumentEditor(document),
					style: {
						paddingVertical: 10,
						paddingHorizontal: 16,
						borderTopColor: index === 0 ? '#fff' : transparent('#000', 0.08),
						borderTopWidth: 1,
						minHeight: 60,
					},
				},
				View(
					{},
					Text({
						text: document.title,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: '#333',
							fontSize: 18,
						},
					}),
				),
				View(
					{},
					Text({
						text: Loc.getMessage('M_CRM_DOCUMENT_LIST_DOCUMENT_DATE_CREATE', {
							'#DATE#': createdAt.format(`${date()}, ${shortTime()}`),
						}),
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: '#6A737F',
							fontSize: 14,
						},
					}),
				),
			);
		}

		renderEmptyList()
		{
			return View(
				{},
				View(
					{
						style: {
							borderRadius: 12,
							backgroundColor: '#fff',
							marginBottom: 10,
						},
					},
					View(
						{
							style: {
								paddingVertical: 18,
								paddingHorizontal: 18,
								borderBottomColor: transparent('#000', 0.08),
								borderBottomWidth: 1,
								minHeight: 60,
							},
						},
						Text({
							text: getEntityMessage('M_CRM_DOCUMENT_LIST_EMPTY', this.props.entityTypeId),
							numberOfLines: 1,
							ellipsize: 'end',
							style: {
								color: '#959CA4',
								fontSize: 18,
							},
						}),
					),
					this.renderAddButtons(),
				),
			);
		}

		renderAddButtons()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderButton({
					title: Loc.getMessage('M_CRM_DOCUMENT_LIST_CREATE_DOCUMENT'),
					onClick: () => this.openTemplateSelector(),
					icon: '<svg width="31" height="30" viewBox="0 0 31 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M20.3203 13.005C20.9984 12.8142 21.7137 12.7121 22.4529 12.7121C22.7653 12.7121 23.0734 12.7304 23.3762 12.7658V11.5963C23.3762 11.2162 23.2237 10.8525 22.9525 10.585L17.62 5.31375C17.4175 5.11375 17.1412 5 16.8525 5H8.26371C7.66371 5 7.17871 5.48 7.17871 6.0725V23.94C7.17871 24.5312 7.66371 25.0113 8.26371 25.0113H15.9704C15.5513 24.4008 15.2162 23.7281 14.9818 23.01H9.49246C9.33246 23.01 9.20371 22.8825 9.20371 22.7237V7.2875C9.20371 7.13 9.33246 7.00125 9.49246 7.00125H14.9887C15.1487 7.00125 15.2775 7.13 15.2775 7.2875V12.72C15.2775 12.8775 15.4075 13.005 15.5675 13.005H20.3203ZM16.9041 15.0063H11.5887C11.39 15.0063 11.2275 15.1663 11.2275 15.3638V16.65C11.2275 16.8463 11.39 17.0075 11.5887 17.0075H15.4474C15.826 16.2644 16.3194 15.5895 16.9041 15.0063ZM14.7505 19.0087H11.5887C11.39 19.0087 11.2275 19.1675 11.2275 19.365V20.6525C11.2275 20.8487 11.39 21.01 11.5887 21.01H14.6076C14.5995 20.8642 14.5954 20.7174 14.5954 20.5696C14.5954 20.0352 14.6488 19.5132 14.7505 19.0087ZM17.4112 7.98375C17.3512 7.98375 17.3025 8.0325 17.3025 8.09V10.8962C17.3025 10.955 17.3512 11.0037 17.4112 11.0037H20.25C20.28 11.0037 20.3062 10.9925 20.3275 10.9725C20.37 10.93 20.37 10.8625 20.3275 10.8212L17.4875 8.015C17.4675 7.995 17.44 7.98375 17.4112 7.98375ZM12.8187 13.005H11.6625C11.4225 13.005 11.2275 12.8125 11.2275 12.575V11.4325C11.2275 11.195 11.4225 11.0037 11.6625 11.0037H12.8187C13.0587 11.0037 13.2525 11.195 13.2525 11.4325V12.575C13.2525 12.8125 13.0587 13.005 12.8187 13.005ZM16.6477 20.5692C16.6477 23.7751 19.2466 26.374 22.4525 26.374C25.6584 26.374 28.2573 23.7751 28.2573 20.5692C28.2573 17.3633 25.6584 14.7644 22.4525 14.7644C19.2466 14.7644 16.6477 17.3633 16.6477 20.5692ZM21.6301 17.3474H23.2745V19.7475H25.6746V21.3919H23.2745V23.7919H21.6301V21.3919H19.2301V19.7475H21.6301V17.3474Z" fill="#6A737F"/></svg>',
					showBorder: true,
				}),
				this.renderButton({
					title: Loc.getMessage('M_CRM_DOCUMENT_LIST_CREATE_TEMPLATE'),
					onClick: () => this.createTemplate(),
					icon: '<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24.0457 13.9333C24.0449 13.9536 24.0442 13.9739 24.0442 13.9942C26.054 14.5989 27.4199 16.4619 27.3923 18.5605C27.4446 21.1086 25.4311 23.2215 22.8834 23.2918H17.4427L17.4427 18.0668H20.7595C21.111 18.0668 21.2809 17.6365 21.0242 17.3964L16.1158 12.8044L11.2074 17.3964C10.9507 17.6365 11.1207 18.0668 11.4721 18.0668H14.5286L14.5286 23.2918H10.5549V23.2864C10.5384 23.287 10.5218 23.2876 10.5053 23.2882C10.4558 23.29 10.4064 23.2918 10.3566 23.2918C7.06496 23.2918 4.397 20.5089 4.397 17.0764C4.36818 14.4918 5.9441 12.1599 8.35296 11.2227C8.3508 11.1922 8.3508 11.162 8.3508 11.1315C8.3508 8.32519 10.5302 6.04883 13.2211 6.04883C15.2312 6.07148 17.0215 7.32502 17.7304 9.20612C18.2345 9.02069 18.7675 8.92582 19.3047 8.92589C21.9234 8.92589 24.0471 11.1419 24.0471 13.8724C24.0471 13.8927 24.0464 13.913 24.0457 13.9333Z" fill="#6A737F"/></svg>',
				}),
			);
		}

		renderButton({ title, subtitle, onClick, icon, showBorder })
		{
			return View(
				{
					onClick,
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						paddingVertical: 10,
						paddingHorizontal: 16,
						borderBottomWidth: 1,
						borderBottomColor: showBorder ? transparent('#000', 0.08) : '#fff',
						minHeight: 60,
					},
				},
				View(
					{
						style: {
							marginRight: 16,
						},
					},
					Image({
						svg: {
							content: icon,
						},
						style: {
							width: 31,
							height: 30,
						},
					}),
				),
				View(
					{
						style: {
							flexGrow: 1,
						},
					},
					Text({
						text: title,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: '#333',
							fontSize: 18,
						},
					}),
					subtitle && Text({
						text: subtitle,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							color: '#6A737F',
							fonstSize: 14,
						},
					}),
				),
				View(
					{},
					Image({
						svg: {
							content: '<svg width="26" height="25" viewBox="0 0 26 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.9292 6.60786L13.6449 11.3235L14.8663 12.5L13.6449 13.6771L8.9292 18.3928L10.5932 20.0568L18.1496 12.5004L10.5932 4.94397L8.9292 6.60786Z" fill="#A8ADB4"/></svg>',
						},
						style: {
							width: 26,
							height: 25,
						},
					}),
				),
			);
		}

		openDocumentEditor(document)
		{
			CrmDocumentDetails.open({
				documentId: document.id,
				createdAt: document.createTime,
				title: document.title,
				parentWidget: this.layoutWidget,
			});
		}

		openTemplateSelector()
		{
			TimelineSchedulerDocumentProvider.open({
				scheduler: {
					entity: {
						typeId: this.props.entityTypeId,
						id: this.props.entityId,
						documentGeneratorProvider: this.props.documentGeneratorProvider,
					},
					parentWidget: this.layoutWidget,
					onActivityCreate: (document) => {
						this.layoutWidget.close(() => {
							CrmDocumentDetails.open({
								documentId: document.id,
								createdAt: document.createTime,
								title: document.title,
							});
						});
					},
				},
				context: {},
			});
		}

		createTemplate()
		{
			qrauth.open({
				title: Loc.getMessage('M_CRM_DOCUMENT_SHARED_PHRASES_DESKTOP_VERSION'),
				redirectUrl: `/crm/documents/templates/?entityTypeId=${this.props.documentGeneratorProvider}`,
				layout: this.layoutWidget,
			});
		}
	}

	module.exports = { CrmDocumentList };
});
