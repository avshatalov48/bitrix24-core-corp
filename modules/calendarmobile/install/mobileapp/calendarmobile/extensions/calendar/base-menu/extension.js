/**
 * @module calendar/base-menu
 */
jn.define('calendar/base-menu', (require, exports, module) => {
	/**
	 * @class BaseMenu
	 */
	class BaseMenu
	{
		/**
		 *
		 * @param {object} props
		 * @param {PageManager} [props.layoutWidget]
		 * @param {ref} [props.targetElementRef]
		 * @param {callback} [props.onItemSelected]
		 */
		constructor(props)
		{
			this.props = props;
			this.layoutWidget = props.layoutWidget || PageManager;
			this.targetElementRef = props.targetElementRef;
			this.menu = null;
		}

		show(bindElement)
		{
			if (!this.menu)
			{
				this.menu = dialogs.createPopupMenu();
			}

			this.menu.setData(this.getItems(), this.getSections(), (event, item) => {
				if (event === 'onItemSelected')
				{
					this.onItemSelected(item);
				}
			});
			this.menu.setTarget(bindElement ?? this.targetElementRef);
			this.menu.show();
		}

		getItems()
		{
			return [];
		}

		getSections()
		{
			return [
				{
					id: baseSectionType,
					title: '',
				},
			];
		}

		onItemSelected(item)
		{
			if (this.props.onItemSelected)
			{
				this.props.onItemSelected(item);
			}
		}
	}

	const baseSectionType = 'base';

	module.exports = { BaseMenu, baseSectionType };
});
