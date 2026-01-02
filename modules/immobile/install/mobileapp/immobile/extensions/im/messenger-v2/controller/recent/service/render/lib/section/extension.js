/**
 * @module im/messenger-v2/controller/recent/service/render/lib/section
 */
jn.define('im/messenger-v2/controller/recent/service/render/lib/section', (require, exports, module) => {
	const RecentSection = {
		general: {
			title: '',
			id: 'general',
			backgroundColor: '#ffffff',
			sortItemParams: { order: 'desc' },
		},
		pinned: {
			title: '',
			id: 'pinned',
			backgroundColor: '#ffffff',
			sortItemParams: { order: 'desc' },
		},
		call: {
			title: '',
			id: 'call',
			backgroundColor: '#ffffff',
			sortItemParams: { order: 'desc' },
		},
	};

	module.exports = { RecentSection };
});
