/**
 * @module crm/document/edit
 */
jn.define('crm/document/edit', (require, exports, module) => {

	const { FadeView } = require('animation/components/fade-view');
	const { Loc } = require('loc');
	const { Moment } = require('utils/date');
	const { shortTime, dayMonth } = require('utils/date/formats');
	const { Island, Title, FormGroup } = require('layout/ui/islands');
	const { StringField } = require('layout/ui/fields/string');

	/**
	 * @class CrmDocumentEditor
	 */
	class CrmDocumentEditor extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = props.layoutWidget;

			this.state = {
				loading: true,
				document: {},
				documentFields: {},
			};
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;

			parentWidget
				.openWidget('layout', {})
				.then(layoutWidget => layoutWidget.showComponent(new CrmDocumentEditor({
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
				text: Loc.getMessage('M_CRM_DOCUMENT_EDIT_DOCUMENT_TITLE'),
				detailText: Loc.getMessage('M_CRM_DOCUMENT_EDIT_DOCUMENT_TITLE_LOADING'),
				useProgress: true,
			});
			this.layoutWidget.enableNavigationBarBorder(true);

			// @todo use batch instead
			Promise.all([
				this.fetchDocument(),
				this.fetchFields(),
			]).then(() => {
				this.setState({ loading: false });
				this.layoutWidget.setTitle({
					text: Loc.getMessage('M_CRM_DOCUMENT_EDIT_DOCUMENT_TITLE'),
					detailText: this.document.title,
					useProgress: false,
				});
				this.layoutWidget.setRightButtons([{
					name: Loc.getMessage('M_CRM_DOCUMENT_EDIT_SAVE'),
					type: 'text',
					color: '#0b66c3',
					callback: () => this.save(),
				}]);
			}).catch(() => {
				this.setState({ loading: false });
			});
		}

		fetchDocument()
		{
			return new Promise((resolve, reject) => {
				const action = 'documentgenerator.document.get';
				const data = { id: this.props.documentId };

				BX.ajax.runAction(action, { data })
					.then(response => {

						/** @type {CrmDocumentProps|null} */
						const document = response.data.document || null;
						console.log('document loaded', document);

						this.state.document = document;
						resolve(response.data);
					})
					.catch((response) => {
						// alert error
						console.error(response);
						reject(response);
					})
			});
		}

		fetchFields()
		{
			return new Promise((resolve, reject) => {
				const action = 'documentgenerator.document.getFields';
				const data = { id: this.props.documentId };

				BX.ajax.runAction(action, { data })
					.then(response => {
						console.log('fields loaded', response.data);
						this.state.documentFields = response.data.documentFields;
						resolve(response.data);
					})
					.catch((response) => {
						console.error(response);
						reject(response);
					})
			});
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

					Island(
						Title(Loc.getMessage('M_CRM_DOCUMENT_EDIT_FIELDS')),
						FormGroup(
							...Object.values(this.state.documentFields).map(field => {
								console.log('render field', field);
								return StringField({
									title: field.title,
									value: field.value,
									readOnly: false,
									required: false,
									onChange: (newVal) => {
										console.log(newVal)
									},
								});
							})
						),
					),
				)
			);
		}

		save()
		{
			this.layoutWidget.back();
		}
	}

	module.exports = { CrmDocumentEditor };

});