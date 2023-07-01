/**
 * @module crm/document/details/bottom-toolbar
 */
jn.define('crm/document/details/bottom-toolbar', (require, exports, module) => {
	const { Loc } = require('loc');
	const { withPressed } = require('utils/color');

	const compactMode = device.screen.width < 400;

	const CrmDocumentDetailsBottomToolbar = ({ onShare }, ...icons) => {
		return View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
					paddingVertical: 10,
					paddingLeft: 7,
					paddingRight: 20,
					borderRadius: 12,
					backgroundColor: '#fff',
				},
				safeArea: { bottom: true },
			},
			View(
				{
					style: {
						flexDirection: 'row',
						marginRight: 12,
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
				},
				...icons,
			),
			View(
				{
					style: {
						flexDirection: 'row',
						flexShrink: 2,
					},
				},
				ToolbarButton({
					onClick: () => onShare(),
					text: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_SEND'),
					icon: '<svg width="15" height="16" viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.72952 10.2443C8.95043 10.2443 9.12952 10.0652 9.12952 9.84426V4.29602H11.1253C11.3985 4.29602 11.5295 3.96055 11.3286 3.77541L8.18947 0.882574C8.07461 0.776721 7.89774 0.776721 7.78287 0.882574L4.64374 3.77541C4.44283 3.96056 4.57382 4.29602 4.84704 4.29602H6.86189V9.84426C6.86189 10.0652 7.04097 10.2443 7.26189 10.2443H8.72952ZM1.37778 8.9762C1.15687 8.9762 0.977783 9.15528 0.977783 9.37619V12.9692C0.977783 14.2394 2.00753 15.2692 3.27778 15.2692H12.6778C13.948 15.2692 14.9778 14.2394 14.9778 12.9692V9.3762C14.9778 9.15528 14.7987 8.9762 14.5778 8.9762H13.1014C12.8805 8.9762 12.7014 9.15528 12.7014 9.3762V12.0319C12.7014 12.5841 12.2537 13.0319 11.7014 13.0319H4.25415C3.70187 13.0319 3.25415 12.5841 3.25415 12.0319V9.37619C3.25415 9.15528 3.07506 8.9762 2.85415 8.9762H1.37778Z" fill="white"/></svg>',
				}),
			),
		);
	};

	const ToolbarButton = ({ onClick, text, icon }) => View(
		{
			onClick,
			style: {
				backgroundColor: withPressed('#00A2E8'),
				borderRadius: 128,
				flexDirection: 'row',
				paddingHorizontal: compactMode ? 16 : 23,
				paddingVertical: 12,
				alignItems: 'center',
				justifyContent: 'center',
				flexShrink: 2,
				minWidth: compactMode ? 120 : 150,
			},
		},
		icon && Image({
			svg: {
				content: icon,
			},
			style: {
				width: 15,
				height: 16,
				marginRight: 8,
			},
		}),
		Text({
			text,
			numberOfLines: 1,
			ellipsize: 'end',
			style: {
				color: '#fff',
				fontSize: 16,
			},
		}),
	);

	const CrmDocumentDetailsBottomToolbarIcon = ({ onClick, disabled, icon }) => View(
		{
			onClick,
			style: {
				paddingVertical: 12,
				paddingHorizontal: compactMode ? 8 : 12,
				opacity: disabled ? 0.5 : 1,
			},
		},
		Image({
			svg: {
				content: icon,
			},
			style: {
				width: 29,
				height: 28,
			},
		}),
	);

	module.exports = { CrmDocumentDetailsBottomToolbar, CrmDocumentDetailsBottomToolbarIcon };
});
