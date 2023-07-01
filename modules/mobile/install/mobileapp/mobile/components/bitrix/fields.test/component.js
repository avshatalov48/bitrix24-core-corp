(() => {

	const {
		FieldFactory,
		BarcodeType,
		MoneyType,
		StringType,
		NumberType,
		DateTimeType,
		FileType,
		MenuSelectType,
		SelectType,
		StatusType,
		BooleanType,
		UrlType,
		UserType,
		CombinedType,
		EntitySelectorType,
	} = jn.require('layout/ui/fields');

	const { useCallback } = jn.require('utils/function');

	class FieldComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				test: [
					{
						id: 1,
						value: '11',
					},
					{
						id: 2,
						value: '22',
					},
					{
						id: 3,
						value: '33',
					},
				],
				text: 'new',
				text2: 'aaa',
				number: '',
				list: '1',
				files: [],
				date: '',
				url: 'https://bitrix.ru/test/',
				boolean: true,
				dateTime: '',
				imageSelect: '',
				menuSelect: '',
				select: '',
				singleUser: {
					value: 1,
					entityList: [
						{
							id: 1,
							title: 'admin',
						},
					],
				},
				multipleUser: {
					value: [],
					entityList: [],
				},
				status: [
					{
						name: 'FIRST',
						backgroundColor: '#eaebed',
						color: '#535c69',
					},
					{
						name: 'SECOND',
						backgroundColor: '#e4f5c8',
						color: '#589308',
					},
					{
						name: 'THIRD',
						backgroundColor: '#ffdfa1',
						color: '#b47a00',
					},
				],
				money: { amount: 555, currency: 'RUB' },
				barcode: '',
				multipleSelector: {
					value: [],
					entityList: [],
				},
				singleSelector: {
					value: 1,
					entityList: [
						{
							id: 1,
							title: 'admin',
						},
					],
				},
			};
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			console.time('render');
		}

		componentDidUpdate(prevProps, prevState)
		{
			console.timeEnd('render');
		}

		getInputs(fieldOptions)
		{
			const fields = [];

			fieldOptions.forEach((options) => {
				const onChange = useCallback((value) => this.setState({ [options.stateVar]: value }), [options.stateVar]);

				if (options.type === MoneyType)
				{
					console.log('a');
					console.log({
						id: options.stateVar,
						title: `Title for {${options.type}}, readOnly: false`,
						value: this.state[options.stateVar],
						readOnly: false,
						onChange,
						...options.config,
					});
				}

				fields.push(
					FieldFactory.create(options.type, {
						id: options.stateVar,
						title: `Title for {${options.type}}, readOnly: false`,
						value: this.state[options.stateVar],
						readOnly: false,
						onChange,
						...options.config,
					}),
				);

				fields.push(
					FieldFactory.create(options.type, {
						id: options.stateVar,
						title: `Title for {${options.type}}, readOnly: true`,
						value: this.state[options.stateVar],
						readOnly: true,
						onChange,
						...options.config,
					}),
				);
			});

			return fields;
		}

		render()
		{
			const fieldOptions = [
				{
					type: BarcodeType,
					stateVar: 'barcode',
				},
				{
					type: MoneyType,
					stateVar: 'money',
				},
				{
					type: MoneyType,
					stateVar: 'money',
					config: {
						config: {
							amountReadOnly: true,
							currencyReadOnly: false,
						},
					},
				},
				{
					type: StringType,
					stateVar: 'text',
				},
				{
					type: NumberType,
					stateVar: 'number',
				},
				{
					type: DateTimeType,
					config: {
						config: {
							enableTime: false,
						},
					},
					stateVar: 'date',
				},
				{
					type: DateTimeType,
					stateVar: 'dateTime',
				},
				{
					type: FileType,
					stateVar: 'files',
				},
				{
					type: MenuSelectType,
					stateVar: 'menuSelect',
					config: {
						config: {
							menuItems: [
								{
									id: '1',
									title: 'First',
								},
								{
									id: '2',
									title: 'Second',
								},
								{
									id: '3',
									title: 'Third',
								},
							],
						},
					},
				},
				{
					type: SelectType,
					stateVar: 'select',
					config: {
						config: {
							items: [
								{
									name: 'First',
									value: '1',
								},
								{
									name: 'Second',
									value: '2',
								},
								{
									name: 'Third',
									value: '3',
								},
							],
						},
					},
				},
				{
					type: StatusType,
					stateVar: 'status',
				},
				{
					type: BooleanType,
					stateVar: 'boolean',
				},
				{
					type: UrlType,
					stateVar: 'url',
					config: {
						config: {
							showFavicon: true,
						},
					},
				},
				// {
				// 	type: ImageSelectType,
				// 	stateVar: 'imageSelect',
				// }
			];

			const singleUser = FieldFactory.create(UserType, {
				title: 'User Title, readOnly: false, multiple: false',
				readOnly: false,
				multiple: false,
				value: this.state.singleUser.value,
				config: {
					provider: {
						context: 'TEST_PICKER_CONTEXT',
					},
					entityList: this.state.singleUser.entityList,
				},
				onChange: useCallback((value, entityList) => this.setState({
					singleUser: {
						value,
						entityList,
					},
				}), []),
			});

			const singleUserReadOnly = FieldFactory.create(UserType, {
				title: 'User Title, readOnly: false, multiple: false',
				readOnly: true,
				multiple: false,
				value: this.state.singleUser.value,
				config: {
					provider: {
						context: 'TEST_PICKER_CONTEXT',
					},
					entityList: this.state.singleUser.entityList,
					reloadEntityListFromProps: true,
				},
			});

			const multipleUser = FieldFactory.create(UserType, {
				title: 'User Title, readOnly: false, multiple: false',
				readOnly: false,
				multiple: true,
				value: this.state.multipleUser.value,
				config: {
					provider: {
						context: 'TEST_PICKER_CONTEXT',
					},
					entityList: this.state.multipleUser.entityList,
				},
				onChange: useCallback((value, entityList) => this.setState({
					multipleUser: {
						value,
						entityList,
					},
				}), []),
			});

			const multipleUserReadOnly = FieldFactory.create(UserType, {
				title: 'User Title, readOnly: false, multiple: false',
				readOnly: true,
				multiple: true,
				value: this.state.multipleUser.value,
				config: {
					provider: {
						context: 'TEST_PICKER_CONTEXT',
					},
					entityList: this.state.multipleUser.entityList,
					reloadEntityListFromProps: true,
				},
			});

			const combined = FieldFactory.create(CombinedType, {
				onChange: useCallback(({ first, second }) => this.setState({ text2: first, list: second }), []),
				value: {
					first: this.state.text2,
					second: this.state.list,
				},
				config: {
					primaryField: {
						id: 'first',
						type: 'string',
						title: 'Combined Title readOnly: false',
						readOnly: false,
						config: {
							defaultListTitle: 'Title',
						},
					},
					secondaryField: {
						id: 'second',
						type: 'select',
						title: 'Select Title readOnly: false',
						readOnly: false,
						config: {
							defaultListTitle: 'Title',
							items: [
								{
									name: 'Select Title readOnly: false',
									value: '1',
								},
								{
									name: '1',
									value: '2',
								},
								{
									name: 'Some text',
									value: '3',
								},
							],
						},
					},
				},
			});

			const selector1 = FieldFactory.create(EntitySelectorType, {
				title: 'multiple SELECTOR',
				value: this.state.multipleSelector.value,
				readOnly: false,
				multiple: true,
				config: {
					selectorType: 'section',
					enableCreation: true,
					provider: {
						options: result['section'],
					},
					entityList: this.state.multipleSelector.entityList,
				},
				onChange: useCallback((value, entityList) => this.setState({
					multipleSelector: {
						value,
						entityList,
					},
				}), []),
			});

			const selector2 = FieldFactory.create(EntitySelectorType, {
				title: 'single SELECTOR',
				value: this.state.singleSelector.value,
				readOnly: false,
				multiple: false,
				config: {
					selectorType: 'section',
					enableCreation: true,
					provider: {
						options: result['section'],
					},
					entityList: this.state.singleSelector.entityList,
				},
				onChange: useCallback((value, entityList) => this.setState({
					singleSelector: {
						value,
						entityList,
					},
				}), []),
			});

			const multiField = FieldFactory.create(StringType, {
				title: 'multiple String',
				multiple: true,
				value: this.state.test,
				readOnly: false,
				onChange: useCallback((value) => {
					this.setState({
						test: value,
					});
				}, []),
			});

			const multiFieldReadOnly = FieldFactory.create(StringType, {
				title: 'multiple String',
				multiple: true,
				value: this.state.test,
				readOnly: true,
				onChange: useCallback((value) => {
					this.setState({
						test: value,
					});
				}, []),
				config: {
					formatTitle: useCallback((index) => `multiple String #${index}`, []),
				},
			});

			return ScrollView(
				{},
				View
				(
					{
						style: {
							padding: 16,
							width: '100%',
						},
						resizableByKeyboard: true,
					},
					multiField,
					multiFieldReadOnly,
					...this.getInputs(fieldOptions),
					singleUser,
					singleUserReadOnly,
					multipleUser,
					multipleUserReadOnly,
					combined,
					selector1,
					selector2,
				),
			);
		}
	}

	layout.showComponent(new FieldComponent());
})();
