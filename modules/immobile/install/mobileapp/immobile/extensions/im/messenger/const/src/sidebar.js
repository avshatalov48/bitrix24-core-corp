/**
 * @module im/messenger/const/sidebar
 */
jn.define('im/messenger/const/sidebar', (require, exports, module) => {
	const SidebarFileType = Object.freeze({
		document: 'document',
	});

	const SidebarTab = Object.freeze({
		participant: 'participant',
		document: 'document',
		link: 'link',
	});

	module.exports = {
		SidebarFileType,
		SidebarTab,
	};
});
