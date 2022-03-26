(() => {
	/**
	 * @class Fields.Select
	 */
	class Select extends Fields.BaseField
	{
		constructor(props)
		{
			super(props);

			this.list = this.prepareListData(this.props.items);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				defaultListTitle: BX.prop.getString(config, 'defaultListTitle', '')
			};
		}

		componentWillReceiveProps(newProps)
		{
			super.componentWillReceiveProps(newProps);

			this.list = this.prepareListData(newProps.items);
		}

		prepareListData(data)
		{
			if (!Array.isArray(data))
			{
				data = [];
			}

			if (!this.isRequired())
			{
				data = [
					{
						value: '',
						name: BX.message('FIELDS_SELECT_EMPTY_TEXT')
					},
					...data
				];
			}

			return data.map((item) => {
				return {
					value: String(item.value),
					name: String(item.name),
					selectedName: String(item.selectedName ? item.selectedName : item.name),
				}
			})
		}

		getDefaultStyles()
		{
			return {
				...super.getDefaultStyles(),
				selectorWrapper: {
					flexDirection: 'row',
					alignItems: 'center',
					position: 'relative'
				},
				value: {
					color: '#333333',
					fontSize: 16,
					marginRight: 10
				}
			}
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			const selectedItem = this.list.find(item => item.value === String(this.props.value));

			const selectedText = selectedItem
				? (selectedItem.selectedName ? selectedItem.selectedName : selectedItem.name)
				: (this.props.value ? this.props.value : BX.message('FIELDS_SELECT_EMPTY_TEXT'))
			;

			return Text(
				{
					style: this.styles.value,
					text: selectedText,
				}
			);
		}

		renderEditableContent()
		{
			const selectedItem = this.list.find(item => item.value === String(this.props.value));

			const selectedText = selectedItem
				? (selectedItem.selectedName ? selectedItem.selectedName : selectedItem.name)
				: BX.message('FIELDS_SELECT_EMPTY_TEXT')
			;

			return View(
				{
					style: this.styles.selectorWrapper,
				},
				Text(
					{
						style: this.styles.value,
						numberOfLines: 1,
						ellipsize: 'end',
						text: selectedText,
					}
				),
				Image(
					{
						style: {
							position: 'absolute',
							top: '50%',
							right: 0,
							width: 7,
							height: 5
						},
						svg: {
							content: `<svg width="7" height="5" viewBox="0 0 7 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.09722 0.235352L4.02232 2.31025L3.49959 2.8249L2.98676 2.31025L0.91186 0.235352L0.179688 0.967524L3.50451 4.29235L6.82933 0.967524L6.09722 0.235352Z" fill="#A8ADB4"/></svg>`
						}
					}
				)
			);
		}

		focus()
		{
			super.focus();

			dialogs.showPicker(
				{
					title: this.getConfig().defaultListTitle,
					items: this.list.map((item) => ({
						value: String(item.value),
						name: String(item.name),
					})),
					defaultValue: this.props.value
				},
				(event, item) => {
					this.removeFocus(() => {
						if (event === 'onPick')
						{
							this.handleChange(item.value);
						}
					});
				}
			);
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.Select = Select;
})();
