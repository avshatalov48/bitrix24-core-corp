/**
 * @module im/messenger/controller/file-download-menu/src/server-download
 */
jn.define('im/messenger/controller/file-download-menu/src/server-download', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Color, Indent } = require('tokens');
	const { Text5 } = require('ui-system/typography');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { ContextMenu, ContextMenuItemType } = require('layout/ui/context-menu');

	/**
	 * @class ServerDownloadMenu
	 */
	class ServerDownloadMenu
	{
		/**
		 * @param {ServerDownloadMenuProps} props
		 * @returns {Promise<*>}
		 */
		static async open(props)
		{
			const menu = new ServerDownloadMenu(props);

			return menu.show();
		}

		/**
		 * @constructor
		 * @param {ServerDownloadMenuProps} props
		 */
		constructor(props)
		{
			this.props = props;
			this.menu = new ContextMenu({
				actions: this.createActions(),
			});
		}

		createActions()
		{
			return [
				{
					id: 'download-from-server-info',
					testId: 'download-from-server-info',
					type: ContextMenuItemType.LAYOUT,
					title: this.renderInfoMenuItem(),
				},
				{
					id: 'download-from-server',
					testId: 'download-from-server',
					title: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_FROM_SERVER'),
					icon: Icon.CLOUD_DOWNLOAD,
					onClickCallback: this.handleOnDownload,
				},
			];
		}

		renderInfoMenuItem()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				IconView({
					icon: Icon.INFO_CIRCLE,
					size: 20,
					color: Color.base5,
					style: {
						marginRight: Indent.M.toNumber(),
					},
				}),
				Text5({
					text: Loc.getMessage('IMMOBILE_MESSENGER_FILE_DOWNLOAD_FROM_SERVER_INFO'),
					color: Color.base4,
					numberOfLines: 2,
					ellipsize: 'end',
				}),
			);
		}

		async show()
		{
			this.layoutWidget = await this.menu.show();

			return this.layoutWidget;
		}

		handleOnDownload = () => {
			const { onDownload } = this.props;

			return new Promise((resolve) => {
				this.menu.close(() => {
					onDownload?.();
					resolve();
				});
			});
		};
	}

	module.exports = {
		ServerDownloadMenu,
	};
});
