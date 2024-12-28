/**
 * @module disk/simple-list/items/file-redux
 */
jn.define('disk/simple-list/items/file-redux', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { FileContentView } = require('disk/simple-list/items/file-redux/file-content');

	class File extends Base
	{
		renderItemContent()
		{
			return FileContentView({
				id: this.props.item.id,
				testId: this.props.testId,
				customStyles: this.props.customStyles,
				showBorder: this.props.item.showBorder,
				order: this.props.item.order,
				parentWidget: this.props.parentWidget,
				showStorageName: this.props.item.showStorageName,
				context: this.props.item.context,
			});
		}
	}

	module.exports = { File };
});
