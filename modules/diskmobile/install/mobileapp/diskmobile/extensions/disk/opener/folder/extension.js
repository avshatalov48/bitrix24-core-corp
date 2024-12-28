/**
 * @module disk/opener/folder
 */
jn.define('disk/opener/folder', (require, exports, module) => {
	const { fetchObjectWithRights } = require('disk/rights');

	async function openFolder(folderId, context = null, parentWidget = PageManager)
	{
		const folder = await fetchObjectWithRights(folderId);

		PageManager.openComponent(
			'JSStackComponent',
			{
				componentCode: 'disk.disk.folder',
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
				},
			},
		);
	}

	module.exports = { openFolder };
});
