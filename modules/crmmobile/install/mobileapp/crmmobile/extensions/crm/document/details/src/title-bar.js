/**
 * @module crm/document/details/title-bar
 */
jn.define('crm/document/details/title-bar', (require, exports, module) => {
	const { CrmDocumentPageNav } = require('crm/document/pagenav');

	const CrmDocumentDetailTitleBar = (props) => {
		const { title, subtitle, onPageNavRef, hideContextMenu, onContextMenuClick } = props;

		return View(
			{
				style: {
					backgroundColor: '#fff',
					paddingTop: 16,
					paddingBottom: 16,
					paddingLeft: 16,
					flexDirection: 'row',
					justifyContent: 'space-between',
					alignItems: 'center',
				},
			},
			Icon(),
			// middle
			View(
				{
					style: {
						flexGrow: 1,
						flexShrink: 1,
					},
				},
				View(
					{},
					Text({
						text: title,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							fontSize: 18,
							color: '#333333',
							marginBottom: 2,
						},
					}),
				),
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					Text({
						text: subtitle,
						numberOfLines: 1,
						ellipsize: 'end',
						style: {
							fontSize: 14,
							color: '#6A737F',
							marginRight: 12,
						},
					}),
					new CrmDocumentPageNav({
						ref: (ref) => onPageNavRef(ref),
						currentPage: null,
						totalPage: null,
					}),
				),
			),
			!hideContextMenu && ContextMenu({
				onClick: onContextMenuClick,
			}),
		);
	};

	const Icon = () => View(
		{},
		Image({
			svg: {
				content: '<svg width="26" height="33" viewBox="0 0 26 33" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_406_272235)"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.5 2.5C0.5 1.39543 1.39543 0.5 2.5 0.5H17.8059C18.3217 0.5 18.8176 0.699276 19.1899 1.05621L24.9651 6.59222C25.3586 6.96943 25.5811 7.49091 25.5811 8.03601V30.5C25.5811 31.6046 24.6857 32.5 23.5811 32.5H2.5C1.39543 32.5 0.5 31.6046 0.5 30.5V2.5Z" fill="white"/><path opacity="0.54" d="M1.5 2.5C1.5 1.94772 1.94772 1.5 2.5 1.5H17.8059C18.0638 1.5 18.3117 1.59964 18.4979 1.7781L24.2731 7.31412C24.4698 7.50272 24.5811 7.76346 24.5811 8.03601V30.5C24.5811 31.0523 24.1334 31.5 23.5811 31.5H2.5C1.94772 31.5 1.5 31.0523 1.5 30.5V2.5Z" fill="black" fill-opacity="0.01" stroke="#2FC6F6" stroke-width="2"/><rect x="5.78027" y="7.16669" width="10.5605" height="1.33333" rx="0.666667" fill="#2FC6F6"/><rect x="5.78027" y="12.5" width="13.2006" height="1.33333" rx="0.666667" fill="#8FE0FA"/><rect x="5.78027" y="17.8333" width="13.2006" height="1.33333" rx="0.666666" fill="#8FE0FA"/><rect x="5.78027" y="15.1667" width="11.8805" height="1.33333" rx="0.666667" fill="#8FE0FA"/><path fill-rule="evenodd" clip-rule="evenodd" d="M14.8422 23.3296C15.0765 23.5503 15.0807 23.9121 14.8515 24.1377L13.1932 25.7703C13.0786 25.8831 12.9208 25.9453 12.757 25.9421C12.5932 25.9389 12.4381 25.8707 12.3284 25.7536L11.6344 25.0129L10.2673 26.5577C10.1565 26.683 9.99495 26.7561 9.82407 26.7585C9.65318 26.7608 9.48955 26.6921 9.37507 26.5699L8.2859 25.4075L6.81429 26.978C6.59474 27.2124 6.2195 27.2309 5.97617 27.0195C5.73284 26.8081 5.71356 26.4468 5.93311 26.2125L7.84531 24.1716C7.95783 24.0516 8.11793 23.983 8.2859 23.983C8.45387 23.983 8.61397 24.0516 8.72649 24.1716L9.80291 25.3205L11.17 23.7757C11.2808 23.6504 11.4423 23.5772 11.6132 23.5749C11.7841 23.5726 11.9477 23.6413 12.0622 23.7635L12.7864 24.5364L14.003 23.3386C14.2322 23.1129 14.6079 23.1089 14.8422 23.3296Z" fill="#2FC6F6"/></g><defs><clipPath id="clip0_406_272235"><rect width="25.0811" height="32" fill="white" transform="translate(0.5 0.5)"/></clipPath></defs></svg>',
			},
			style: {
				width: 26,
				height: 33,
				marginRight: 11,
			},
		}),
	);

	const ContextMenu = ({ onClick }) => View(
		{
			onClick,
			style: {
				paddingHorizontal: 12,
				paddingVertical: 5,
			},
		},
		Image({
			svg: {
				content: '<svg width="26" height="25" viewBox="0 0 26 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.74996 14.5834C7.90055 14.5834 8.83329 13.6506 8.83329 12.5C8.83329 11.3494 7.90055 10.4167 6.74996 10.4167C5.59937 10.4167 4.66663 11.3494 4.66663 12.5C4.66663 13.6506 5.59937 14.5834 6.74996 14.5834Z" fill="#A8ADB4"/><path d="M13 14.5834C14.1506 14.5834 15.0833 13.6506 15.0833 12.5C15.0833 11.3494 14.1506 10.4167 13 10.4167C11.8494 10.4167 10.9166 11.3494 10.9166 12.5C10.9166 13.6506 11.8494 14.5834 13 14.5834Z" fill="#A8ADB4"/><path d="M21.3333 12.5C21.3333 13.6506 20.4006 14.5834 19.25 14.5834C18.0994 14.5834 17.1666 13.6506 17.1666 12.5C17.1666 11.3494 18.0994 10.4167 19.25 10.4167C20.4006 10.4167 21.3333 11.3494 21.3333 12.5Z" fill="#A8ADB4"/></svg>',
			},
			style: {
				width: 26,
				height: 25,
			},
		}),
	);

	module.exports = { CrmDocumentDetailTitleBar };
});
