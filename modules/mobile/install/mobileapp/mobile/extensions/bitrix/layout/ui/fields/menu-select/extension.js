(() => {
	/**
	 * @class Fields.MenuSelect
	 */
	class MenuSelect extends Fields.BaseField
	{
		getDefaultStyles()
		{
			return {
				...super.getDefaultStyles(),
				selectorWrapper: {
					flexDirection: 'row',
					alignItems: 'center',
				},
				value: {
					color: '#333333',
					fontSize: 16,
					marginRight: 4,
				},
			};
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return Text(
				{
					style: this.styles.value,
					text: this.props.value
				}
			);
		}

		renderEditableContent()
		{
			return View(
				{
					style: this.styles.selectorWrapper,
				},
				Text({
					style: this.styles.value,
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.value || BX.message('FIELDS_SELECT_EMPTY_TEXT'),
				}),
				Image({
					style: {
						width: 7,
						height: 5,
					},
					resizeMode: 'center',
					svg: {
						content: `<svg width="7" height="5" viewBox="0 0 7 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.09722 0.235352L4.02232 2.31025L3.49959 2.8249L2.98676 2.31025L0.91186 0.235352L0.179688 0.967524L3.50451 4.29235L6.82933 0.967524L6.09722 0.235352Z" fill="#A8ADB4"/></svg>`,
					},
				}),
			);
		}

		focus()
		{
			super.focus();

			const contextMenu = new ContextMenu({
				params: {
					showCancelButton: false,
					title: (this.props.menuTitle || ''),
				},
				actions: this.props.menuItems.map((item) => ({
					id: String(item.id),
					title: String(item.title),
					subtitle: (item.subtitle ? String(item.subtitle) : ''),
					isSelected: item.isSelected,
					isDisabled: item.isDisabled,
					data: {
						svgIcon: item.icon,
					},
					onClickCallback: () => new Promise((resolve) => {
						contextMenu.close(() => this.handleChange(item.id, item.title));
						resolve();
					}),
				})),
			});
			contextMenu.show(this.props.parentWidget).then(
				() => this.setListeners(contextMenu.layoutWidget),
				() => {}
			);
		}

		setListeners(contextMenuWidget)
		{
			contextMenuWidget.setListener((eventName, data) => {
				const callbackName = `${eventName}Listener`;
				if (typeof this[callbackName] === 'function')
				{
					this[callbackName].apply(this, [data]);
				}
			});
		}

		onViewHiddenListener()
		{
			super.removeFocus();
		}

		onViewWillHiddenListener()
		{
			super.removeFocus();
		}

		onViewRemovedListener()
		{
			super.removeFocus();
		}
	}

	this.Fields = this.Fields || {};
	this.Fields.MenuSelect = MenuSelect;
})();
