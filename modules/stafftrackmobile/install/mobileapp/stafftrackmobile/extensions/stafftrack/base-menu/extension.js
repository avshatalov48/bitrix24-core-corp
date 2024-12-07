/**
 * @module stafftrack/base-menu
 */
jn.define('stafftrack/base-menu', (require, exports, module) => {
	class BaseMenu
	{
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
				this.props.onItemSelected(item.id);
			}
		}
	}

	const baseSectionType = 'base';
	const customSectionType = 'custom';

	module.exports = { BaseMenu, baseSectionType, customSectionType };
});
