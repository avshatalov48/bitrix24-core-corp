/**
 * @module crm/document/edit
 */
jn.define('crm/document/edit', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { FadeView } = require('animation/components/fade-view');
	const { StringField } = require('layout/ui/fields/string');
	const { DateTimeField } = require('layout/ui/fields/datetime');
	const { SelectField } = require('layout/ui/fields/select');
	const { get, set, clone } = require('utils/object');
	const { hashCode } = require('utils/hash');
	const { debounce } = require('utils/function');
	const { Moment } = require('utils/date');
	const { date } = require('utils/date/formats');
	const { Alert } = require('alert');

	const FieldTypes = {
		IMAGE: 'IMAGE',
		STAMP: 'STAMP',
		DATE: 'DATE',
	};

	const UnsupportedFieldTypes = new Set([FieldTypes.IMAGE, FieldTypes.STAMP]);

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
				documentFields: [],
				rawFields: {},
				values: {},
				defaultValues: {},
			};

			this.fieldRefs = {};
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
						mediumPositionPercent: 80,
						swipeAllowed: true,
						swipeContentAllowed: false,
						horizontalSwipeAllowed: false,
						hideNavigationBar: false,
						navigationBarColor: AppTheme.colors.bgSecondary,
					},
				})
				.then((layoutWidget) => layoutWidget.showComponent(
					new CrmDocumentEditor({
						...props,
						layoutWidget,
					}),
				)).catch(console.error);
		}

		componentDidMount()
		{
			this.layoutWidget.setTitle({
				text: Loc.getMessage('M_CRM_DOCUMENT_EDIT_DOCUMENT_TITLE'),
				detailText: Loc.getMessage('M_CRM_DOCUMENT_EDIT_DOCUMENT_TITLE_LOADING'),
				useProgress: true,
			});
			this.layoutWidget.enableNavigationBarBorder(false);

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
				this.layoutWidget.setRightButtons([
					{
						name: Loc.getMessage('M_CRM_DOCUMENT_EDIT_SAVE'),
						type: 'text',
						color: AppTheme.colors.accentMainLinks,
						callback: () => this.save(),
					},
				]);
			}).catch(() => {
				this.setState({ loading: false });
				this.layoutWidget.setTitle({
					text: Loc.getMessage('M_CRM_DOCUMENT_EDIT_DOCUMENT_TITLE'),
					useProgress: false,
				});
				Alert.alert('', Loc.getMessage('M_CRM_DOCUMENT_EDIT_LOAD_DATA_ERROR'), () => {
					this.layoutWidget.close();
				});
			});
		}

		fetchDocument()
		{
			return new Promise((resolve, reject) => {
				const action = 'documentgenerator.document.get';
				const data = { id: this.props.documentId };

				BX.ajax.runAction(action, { data })
					.then((response) => {
						this.state.document = response.data.document || null;
						resolve(response.data);
					})
					.catch((response) => {
						// alert error
						console.error(response);
						reject(response);
					});
			});
		}

		fetchFields(values = {})
		{
			return new Promise((resolve, reject) => {
				const action = 'crmmobile.DocumentGenerator.Document.getFields';
				const data = {
					id: this.props.documentId,
					values,
				};

				BX.ajax.runAction(action, { json: data })
					.then((response) => {
						this.state.rawFields = response.data.documentFields;
						this.state.documentFields = Object.values((response.data.documentFields || {}))
							.filter((field) => !UnsupportedFieldTypes.has(field.type))
							.map((field) => {
								if (!field.group || (field.group && field.group.length === 0))
								{
									field.group = [Loc.getMessage('M_CRM_DOCUMENT_EDIT_UNKNOWN_GROUP_NAME')];
									field.sort = -10000 + field.sort; // ungrouped fields must go very first
								}

								if (isFieldWithMultipleValues(field))
								{
									field.value = extractMultipleValues(field);
								}

								return field;
							})
							.sort((a, b) => {
								if (a.group.length !== b.group.length)
								{
									return a.group.length - b.group.length;
								}

								const aMultiple = isFieldWithMultipleValues(a) ? 1 : 0;
								const bMultiple = isFieldWithMultipleValues(b) ? 1 : 0;
								if (aMultiple !== bMultiple)
								{
									return bMultiple - aMultiple;
								}

								return a.sort - b.sort;
							});
						this.state.documentFields.forEach((field) => {
							if (isFieldWithMultipleValues(field))
							{
								const selected = field.value.find((item) => item.selected);
								this.state.values[field.key] = selected ? selected.value : '';
							}
							else
							{
								this.state.values[field.key] = field.type === FieldTypes.DATE ? field.timestamp : field.value;
								this.state.defaultValues[field.key] = field.default || '';
							}
						});
						resolve(response.data);
					})
					.catch((response) => {
						console.error(response);
						reject(response);
					});
			});
		}

		/**
		 * @return {CrmDocumentProps}
		 */
		get document()
		{
			return this.state.document;
		}

		/**
		 * @return {object[]}
		 */
		get fieldsTree()
		{
			const tree = {};
			const setIfNotExists = makeMemoizedSetter();

			this.state.documentFields.forEach((field) => {
				const path = [];
				field.group.forEach((pathItem) => {
					path.push(hashCode(pathItem));
					setIfNotExists(tree, path, {
						props: {
							name: pathItem,
							sort: field.sort,
							fields: [],
						},
					});
				});
			});

			this.state.documentFields.forEach((field) => {
				const path = clone(field.group).map((item) => hashCode(item));

				const currentNodeFields = get(tree, [...path, 'props', 'fields'], []);
				currentNodeFields.push(clone(field));
			});

			return Object.values(tree).sort((a, b) => a.props.sort - b.props.sort);
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
				},
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
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: AppTheme.colors.bgSecondary,
					},
				},
				ScrollView(
					{
						style: {
							flexDirection: 'column',
							flexGrow: 1,
							backgroundColor: AppTheme.colors.bgContentPrimary,
							borderRadius: 12,
						},
						safeArea: { bottom: true },
					},
					View(
						{},
						...this.fieldsTree.map((node) => this.renderGroup(node, 1)),
					),
				),
			);
		}

		renderGroup(node, level = 1)
		{
			return View(
				{
					style: {
						padding: 0,
						backgroundColor: level > 2 ? AppTheme.colors.bgSecondary : AppTheme.colors.bgContentPrimary,
						marginBottom: 12,
						marginHorizontal: level === 3 ? 16 : 0,
						borderRadius: level === 3 ? 12 : 0,
					},
				},
				Title(node.props.name, level),
				FormGroup(
					...node.props.fields.map((field) => this.renderField(field)),
				),
				...getChildNodes(node).map((childNode) => this.renderGroup(childNode, level + 1)),
			);
		}

		renderField(field)
		{
			const onChange = (newVal) => {
				const { values } = this.state;
				values[field.key] = newVal;
				this.setState(values);
			};

			const ref = (fieldRef) => {
				this.fieldRefs[field.key] = fieldRef;
			};

			if (isFieldWithMultipleValues(field))
			{
				return SelectField({
					title: field.title || field.key,
					value: this.state.values[field.key],
					config: {
						items: field.value.map(({ title, value }) => ({
							value,
							name: title,
						})),
					},
					readOnly: false,
					required: (field.value.length > 0),
					showRequired: false,
					hasHiddenEmptyView: true,
					onChange: (newVal) => this.refillValues(field, newVal),
					ref,
				});
			}

			if (field.type === FieldTypes.DATE)
			{
				return DateTimeField({
					title: field.title || field.key,
					value: this.state.values[field.key],
					readOnly: false,
					required: field.required === 'Y',
					hasHiddenEmptyView: true,
					config: {
						enableTime: false,
					},
					onChange,
					ref,
				});
			}

			return StringField({
				title: field.title || field.key,
				value: this.state.values[field.key],
				readOnly: false,
				required: field.required === 'Y',
				hasHiddenEmptyView: true,
				onChange: debounce(onChange, 300),
				ref,
			});
		}

		save()
		{
			if (!this.validate())
			{
				return;
			}

			if (this.props.onChange)
			{
				const values = clone(this.state.values);
				this.state.documentFields.forEach((field) => {
					if (field.type === FieldTypes.DATE)
					{
						const moment = Moment.createFromTimestamp(values[field.key]);
						values[field.key] = moment.format(date()); // todo What format should we actually use?
					}
				});
				this.props.onChange(values);
			}

			this.layoutWidget.close();
		}

		validate()
		{
			let isFormValid = true;

			Object.values(this.fieldRefs).forEach((ref) => {
				if (ref)
				{
					isFormValid = isFormValid && ref.validate();
				}
			});

			return isFormValid;
		}

		refillValues(dropdownField, newVal)
		{
			const currentValues = {};
			Object.keys(this.state.values).forEach((key) => {
				const field = this.state.rawFields[key];
				let currentValue = null;
				// todo extract field conversion to separate method
				if (field && field.type === FieldTypes.DATE)
				{
					const moment = Moment.createFromTimestamp(this.state.values[field.key]);
					currentValue = moment.format(date());
				}
				else
				{
					currentValue = this.state.values[key];
				}

				if (this.state.defaultValues.hasOwnProperty(key) && this.state.defaultValues[key] === currentValue)
				{
					return;
				}

				currentValues[key] = currentValue;
			});

			currentValues[dropdownField.key] = newVal;

			this.fetchFields(currentValues)
				.then(() => this.setState({}))
				.catch(console.error);
		}
	}

	const isFieldWithMultipleValues = (field) => {
		return Array.isArray(field.value) || BX.type.isPlainObject(field.value);
	};

	const extractMultipleValues = (field) => {
		if (BX.type.isPlainObject(field.value))
		{
			return Object.values(field.value);
		}

		return field.value;
	};

	const makeMemoizedSetter = () => {
		const cache = {};

		return (obj, path, value) => {
			const key = path.join('.');
			if (cache[key])
			{
				return;
			}
			set(obj, path, value);
			cache[key] = true;
		};
	};

	const getChildNodes = (node) => {
		const result = [];
		Object.keys(node).forEach((key) => {
			if (key !== 'props')
			{
				result.push(node[key]);
			}
		});

		return result;
	};

	const FormGroup = (...fields) => FieldsWrapper({
		fields,
		config: {
			styles: {
				paddingHorizontal: 16,
			},
		},
	});

	const Title = (text, level) => View(
		{
			style: {
				paddingHorizontal: 16,
			},
		},
		Text({
			style: {
				color: AppTheme.colors.base1,
				fontWeight: level < 3 ? 'normal' : 'bold',
				fontSize: level < 3 ? 18 : 16,
				width: '100%',
				textAlign: 'left',
				paddingTop: 0,
				paddingBottom: 0,
				marginTop: 16,
				marginBottom: 8,
			},
			text: String(text),
		}),
	);

	module.exports = { CrmDocumentEditor };
});
