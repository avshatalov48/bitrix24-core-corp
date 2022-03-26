(() => {
	class FieldComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				test: ['1', '2', '3'],
				text: 'new',
				number: '',
				list: '1',
				files: [],
				date: '',
				dateTime: '',
				imageSelect: '',
				menuSelect: '',
				select: '',
				singleUser: {
					value: 1,
					entityList: [
						{
							id: 1,
							title: 'admin'
						}
					]
				},
				multipleUser: {
					value: [],
					entityList: []
				},
				status: [
					{
						name: 'FIRST',
						backgroundColor: '#eaebed',
						color: '#535c69'
					},
					{
						name: 'SECOND',
						backgroundColor: '#e4f5c8',
						color: '#589308'
					},
					{
						name: 'THIRD',
						backgroundColor: '#ffdfa1',
						color: '#b47a00'
					}
				],
				money: {amount: 555, currency: 'RUB'},
				barcode: '',
				multipleSelector: {
					value: [],
					entityList: []
				},
				singleSelector: {
					value: 1,
					entityList: [
						{
							id: 1,
							title: 'admin'
						}
					]
				},
			};
		}

		onChangeText(text)
		{
			this.setState({ text })
		}

		onChangeList(value)
		{
			this.setState({
				list: value
			})
		}

		getInputs(fieldOptions)
		{
			const fields = [];

			fieldOptions.forEach((options) => {
				fields.push(
					FieldFactory.create(options.type, {
						title: `Title for {${options.type}}, readOnly: false`,
						value: this.state[options.stateVar],
						readOnly: false,
						onChange: (value) => this.setState({[options.stateVar]: value}),
						...options.config
					})
				);

				fields.push(
					FieldFactory.create(options.type, {
						title: `Title for {${options.type}}, readOnly: true`,
						value: this.state[options.stateVar],
						readOnly: true,
						onChange: (value) => this.setState({[options.stateVar]: value}),
						...options.config
					})
				);
			});

			return fields;
		}

		render()
		{
			const fieldOptions = [
				{
					type: FieldFactory.Type.BARCODE,
					stateVar: 'barcode',
				},
				{
					type: FieldFactory.Type.MONEY,
					stateVar: 'money',
				},
				{
					type: FieldFactory.Type.MONEY,
					stateVar: 'money',
					config: {
						config: {
							amountReadOnly: true,
							currencyReadOnly: false,
						},
					}
				},
				{
					type: FieldFactory.Type.STRING,
					stateVar: 'text'
				},
				{
					type: FieldFactory.Type.NUMBER,
					stateVar: 'number'
				},
				{
					type: FieldFactory.Type.DATE,
					stateVar: 'date'
				},
				{
					type: FieldFactory.Type.DATETIME,
					stateVar: 'dateTime'
				},
				{
					type: FieldFactory.Type.FILE,
					stateVar: 'files',
				},
				{
					type: FieldFactory.Type.MENU_SELECT,
					stateVar: 'menuSelect',
				},
				{
					type: FieldFactory.Type.SELECT,
					stateVar: 'select',
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
					}
				},
				{
					type: FieldFactory.Type.STATUS,
					stateVar: 'status'
				}
				// {
				// 	type: FieldFactory.Type.IMAGE_SELECT,
				// 	stateVar: 'imageSelect',
				// }
			];

			const singleUser = FieldFactory.create(
				FieldFactory.Type.USER,
				{
					title: 'User Title, readOnly: false, multiple: false',
					readOnly: false,
					multiple: false,
					value: this.state.singleUser.value,
					config: {
						provider: {
							context: 'TEST_PICKER_CONTEXT',
						},
						entityList: this.state.singleUser.entityList
					},
					onChange: (value, entityList) => this.setState({singleUser: {value, entityList}})
				}
			);

			const singleUserReadOnly = FieldFactory.create(
				FieldFactory.Type.USER,
				{
					title: 'User Title, readOnly: false, multiple: false',
					readOnly: true,
					multiple: false,
					value: this.state.singleUser.value,
					config: {
						provider: {
							context: 'TEST_PICKER_CONTEXT',
						},
						entityList: this.state.singleUser.entityList,
						reloadEntityListFromProps: true
					}
				}
			);

			const multipleUser = FieldFactory.create(
				FieldFactory.Type.USER,
				{
					title: 'User Title, readOnly: false, multiple: false',
					readOnly: false,
					multiple: true,
					value: this.state.multipleUser.value,
					config: {
						provider: {
							context: 'TEST_PICKER_CONTEXT',
						},
						entityList: this.state.multipleUser.entityList
					},
					onChange: (value, entityList) => this.setState({multipleUser: {value, entityList}})
				}
			);

			const multipleUserReadOnly = FieldFactory.create(
				FieldFactory.Type.USER,
				{
					title: 'User Title, readOnly: false, multiple: false',
					readOnly: true,
					multiple: true,
					value: this.state.multipleUser.value,
					config: {
						provider: {
							context: 'TEST_PICKER_CONTEXT',
						},
						entityList: this.state.multipleUser.entityList,
						reloadEntityListFromProps: true
					}
				}
			);

			const combined = FieldFactory.create('combined', {
				primaryField: FieldFactory.create('string',
					{
						title: 'Select Title readOnly: false',
						readOnly: false,
						value: this.state.text,
						config: {
							defaultListTitle: 'Title'
						},
						onChange: this.onChangeText.bind(this)
					}
				),
				secondaryField: FieldFactory.create('select', {
					title: 'Select Title readOnly: false',
					readOnly: false,
					value: this.state.list,
					items: [
						{
							name: 'Select Title readOnly: false',
							value: '1'
						},
						{
							name: '1',
							value: '2'
						},
						{
							name: 'Some text',
							value: '3'
						}
					],
					config: {
						defaultListTitle: 'Title'
					},
					onChange: this.onChangeList.bind(this)
				})
			});

			const selector1 = FieldFactory.create('entity-selector', {
				title: 'multiple SELECTOR',
				value: this.state.multipleSelector.value,
				readOnly: false,
				multiple: true,
				config: {
					selectorType: 'section',
					enableCreation: true,
					provider: {
						options: result['section']
					},
					entityList: this.state.multipleSelector.entityList,
				},
				onChange: (value, entityList) => this.setState({multipleSelector: {value, entityList}})
			});

			const selector2 = FieldFactory.create('entity-selector', {
				title: 'single SELECTOR',
				value: this.state.singleSelector.value,
				readOnly: false,
				multiple: false,
				config: {
					selectorType: 'section',
					enableCreation: true,
					provider: {
						options: result['section']
					},
					entityList: this.state.singleSelector.entityList,
				},
				onChange: (value, entityList) => this.setState({singleSelector: {value, entityList}})
			});

			const multiField = FieldFactory.create('string', {
				title: 'multiple String',
				multiple: true,
				value: this.state.test,
				readOnly: false,
				onChange: (value) => {
					this.setState({
						test: value
					})
				},
				config: {
					formatTitle: (index) => `multiple String #${index}`
				}
			});

			const multiFieldReadOnly = FieldFactory.create('string', {
				title: 'multiple String',
				multiple: true,
				value: this.state.test,
				readOnly: true,
				onChange: (value) => {
					this.setState({
						test: value
					})
				},
				config: {
					formatTitle: (index) => `multiple String #${index}`
				}
			});

			return ScrollView(
				{},
				View

				(
					{
						style: {
							padding: 16,
							width: '100%'
						}
						,
						resizableByKeyboard: true
					}

					,
					multiField,
					multiFieldReadOnly,
					...this.getInputs(fieldOptions),
					singleUser,
					singleUserReadOnly,
					multipleUser,
					multipleUserReadOnly,
					combined,
					selector1,
					selector2
				)
			)
		}
	}
	layout.showComponent(new FieldComponent());
})();
