/**
 * @module crm/assets/stage
 */
jn.define('crm/assets/stage', (require, exports, module) => {
	const getStageNavigationIcon = (color) => `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15 29.9987C23.2843 29.9987 30 23.2833 30 14.9993C30 6.71543 23.2843 0 15 0C6.71572 0 0 6.71543 0 14.9993C0 23.2833 6.71572 29.9987 15 29.9987Z" fill="white"/><path d="M7.84082 11.1644C7.84082 9.89354 8.89255 8.86328 10.1899 8.86328H16.724C17.4802 8.86328 18.1901 9.21988 18.6315 9.82139L21.9382 14.3281C22.2326 14.7294 22.2326 15.2699 21.9382 15.6712L18.6315 20.1779C18.1901 20.7794 17.4802 21.136 16.724 21.136L10.1899 21.136C8.89255 21.136 7.84082 20.1058 7.84082 18.8349V11.1644Z" fill="${color}"/></svg>`;

	const getStageIcon = (color) => `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15 29.9987C23.2843 29.9987 30 23.2833 30 14.9993C30 6.71543 23.2843 0 15 0C6.71572 0 0 6.71543 0 14.9993C0 23.2833 6.71572 29.9987 15 29.9987Z" fill="white" fill-opacity="0.9"/><path d="M6 9.89286C6 8.29518 7.32217 7 8.95315 7H17.1674C18.118 7 19.0106 7.4483 19.5654 8.20448L23.7224 13.8701C24.0925 14.3745 24.0925 15.0541 23.7224 15.5585L19.5654 21.2241C19.0106 21.9803 18.118 22.4286 17.1674 22.4286L8.95315 22.4286C7.32217 22.4286 6 21.1334 6 19.5357V9.89286Z" fill="${color}"/></svg>`;

	module.exports = { getStageNavigationIcon, getStageIcon };
});
