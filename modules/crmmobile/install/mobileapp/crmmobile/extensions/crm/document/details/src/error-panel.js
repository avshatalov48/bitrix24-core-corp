/**
 * @module crm/document/details/error-panel
 */
jn.define('crm/document/details/error-panel', (require, exports, module) => {
	const CrmDocumentDetailsErrorPanel = ({ title, subtitle }) => View(
		{
			style: {
				backgroundColor: '#fff',
				borderRadius: 12,
				marginHorizontal: 16,
				paddingVertical: 27,
				paddingHorizontal: 16,
				alignItems: 'center',
			},
		},
		title && Text({
			text: title,
			style: {
				fontSize: 18,
				color: '#333',
				textAlign: 'center',
				marginBottom: 12,
			},
		}),
		subtitle && Text({
			text: subtitle,
			style: {
				fontSize: 15,
				color: '#A8ADB4',
				textAlign: 'center',
			},
		}),
	);

	module.exports = { CrmDocumentDetailsErrorPanel };
});
