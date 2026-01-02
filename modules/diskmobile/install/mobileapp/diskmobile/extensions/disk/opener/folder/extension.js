/**
 * @module disk/opener/folder
 */
jn.define('disk/opener/folder', (require, exports, module) => {
	const { fetchObjectWithRights } = require('disk/rights');

	async function openFolder(folderId, context = null, parentWidget = PageManager, scrollToItemId = null)
	{
		const folder = await fetchObjectWithRights(folderId);

		PageManager.openComponent(
			'JSStackComponent',
			{
				componentCode: 'disk.disk.folder',
				// eslint-disable-next-line no-undef
				scriptPath: availableComponents['disk:disk.folder'].publicUrl,
				canOpenInDefault: true,
				rootWidget: {
					name: 'layout',
					settings: {
						titleParams: {
							text: folder.name,
						},
						objectName: 'layout',
						swipeToClose: true,
					},
				},
				params: {
					folderId,
					context,
					scrollToItemId,
				},
			},
		);
	}

	module.exports = { openFolder };
});
