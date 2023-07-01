(() => {

	/**
	 * @class StoreDocumentProductContextMenu
	 */
	class StoreDocumentProductContextMenu
	{
		constructor(props)
		{
			this.props = props || {};
			this.items = this.buildItems();
			this.menuInstance = new ContextMenu({
				actions: this.items,
				params: {
					showCancelButton: true,
				}
			});
		}

		buildItems()
		{
			let actions;
			if (this.props.editable)
			{
				actions = [
					{
						id: 'edit',
						title: BX.message('CSPL_PRODUCT_CONTEXT_MENU_EDIT'),
						subTitle: '',
						data: {
							svgIcon: SvgIcons.edit.content
						},
						onClickCallback: this.callback.bind(this, 'onChooseEdit'),
					},
					{
						id: 'remove',
						type: 'delete',
						title: BX.message('CSPL_PRODUCT_CONTEXT_MENU_REMOVE'),
						subTitle: '',
						onClickCallback: this.confirmedCallback.bind(this, 'onChooseRemove'),
					}
				];
			}
			else
			{
				actions = [{
					id: 'open',
					title: BX.message('CSPL_PRODUCT_CONTEXT_MENU_OPEN'),
					subTitle: '',
					data: {
						svgIcon: SvgIcons.open.content
					},
					onClickCallback: this.callback.bind(this, 'onChooseOpen'),
				}];
			}

			return actions;
		}

		callback(eventName)
		{
			this.menuInstance.close(() => {
				if (this.props[eventName])
				{
					this.props[eventName]();
				}
			});
			return Promise.resolve();
		}

		confirmedCallback(eventName)
		{
			const OK = 1;
			return new Promise((resolve, reject) => {
				navigator.notification.confirm(
					BX.message('CSPL_PRODUCT_DELETE_CONFIRMATION'),
					(index) => {
						if (index === OK && this.props[eventName])
						{
							this.props[eventName]();
						}
						resolve();
					},
					'',
					[
						BX.message('CSPL_PRODUCT_DELETE_CONFIRMATION_OK'),
						BX.message('CSPL_PRODUCT_DELETE_CONFIRMATION_CANCEL')
					]
				);
			});
		}

		show()
		{
			this.menuInstance.show();
		}
	}

	const SvgIcons = {
		edit: {
			content: `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M19.7425 6.25L23.75 10.2997L12.1325 21.875L8.125 17.8253L19.7425 6.25ZM6.26396 23.2285C6.22606 23.3719 6.26667 23.5234 6.36953 23.629C6.47509 23.7345 6.62668 23.7751 6.77014 23.7345L11.25 22.5276L7.47122 18.75L6.26396 23.2285Z" fill="#525C69"/></svg>`
		},
		open: {
			content: `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.92308 0.10083C8.90652 0.105853 8.88925 0.1159 8.87125 0.124941L1.11441 3.19521C0.920058 3.28663 0.849518 3.48454 0.84375 3.7176V13.6032C0.84591 13.8243 0.9618 14.0342 1.11441 14.0935L8.79643 17.1313C8.89145 17.1735 9.01598 17.1675 9.11891 17.1394L16.8699 14.0774C17.0225 14.0151 17.1362 13.8011 17.1347 13.5791V3.78188C17.1391 3.48049 17.0642 3.30367 16.8641 3.21124L9.06708 0.125031C9.01381 0.0979062 8.97202 0.086765 8.92308 0.10083ZM8.96914 1.15369L15.4073 3.70946L8.96914 6.24915L2.52526 3.70143L8.96914 1.15369Z" fill="#bdc1c6"/></svg>`
		}
	};

	jnexport(StoreDocumentProductContextMenu);

})();