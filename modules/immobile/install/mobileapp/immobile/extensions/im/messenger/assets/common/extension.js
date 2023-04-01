/**
 * @module im/messenger/assets/common
 */
jn.define('im/messenger/assets/common', (require, exports, module) => {

	const cross = ({color= '#A8ADB4', strokeWight = 6}) => `<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M32 59.4286C47.1484 59.4286 59.4286 47.1484 59.4286 32C59.4286 16.8516 47.1484 4.57143 32 4.57143C16.8516 4.57143 4.57141 16.8516 4.57141 32C4.57141 47.1484 16.8516 59.4286 32 59.4286Z" fill="${color}" stroke="white" stroke-width="${strokeWight}"/><path d="M22 23.0921C22 23.7682 23.8942 25.9319 26.059 28.0956L30.118 32.0173L25.7884 36.4799C20.2411 42.2948 22 44.0528 27.8179 38.5083L32.2828 34.181L36.6124 38.5083C38.9125 40.9425 41.3478 42.43 41.889 41.8891C42.4302 41.3482 40.9419 38.914 38.5066 36.6151L34.177 32.2877L38.5066 27.8251C44.0538 22.0102 42.2949 20.2522 36.4771 25.7967L32.0122 30.1241L28.0885 26.0671C24.3001 22.0102 22 20.9284 22 23.0921Z" fill="white" stroke="white" stroke-width="1"/></svg>`;

	const lens = (color = '#A8ADB4') => `<svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.2014 18.5331L15.6288 17.9605L14.9519 18.405C13.5365 19.3344 11.8436 19.8751 10.0215 19.8751C5.05326 19.8751 1.0257 15.8475 1.0257 10.8793C1.0257 5.91105 5.05326 1.88349 10.0215 1.88349C14.9897 1.88349 19.0173 5.91105 19.0173 10.8793C19.0173 12.7014 18.4766 14.3942 17.5472 15.8097L17.1027 16.4866L17.6753 17.0592L22.868 22.2519C23.0921 22.476 23.0921 22.8393 22.868 23.0634L22.2056 23.7258C21.9815 23.9499 21.6182 23.9499 21.3941 23.7258L16.2014 18.5331ZM10.0215 19.0103C14.5121 19.0103 18.1525 15.3699 18.1525 10.8793C18.1525 6.38868 14.5121 2.74832 10.0215 2.74832C5.53089 2.74832 1.89053 6.38868 1.89053 10.8793C1.89053 15.3699 5.53089 19.0103 10.0215 19.0103Z" fill="${color}" stroke="${color}" stroke-width="1.3"/></svg>`;

	const arrowRight = (color = '#A8ADB4') => `<svg width="10" height="15" viewBox="0 0 10 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.651" fill-rule="evenodd" clip-rule="evenodd" d="M0.5 -0.5C1.5 -0.5 2.5 -0.5 3.5 -0.5C5.68021 1.84785 7.68021 4.34785 9.5 7C7.51192 10.8303 4.51192 13.3303 0.5 14.5C1.09132 11.3864 2.75799 8.8864 5.5 7C2.75799 5.1136 1.09132 2.6136 0.5 -0.5Z" fill="${color}"/></svg>`;

	const statusPath = currentDomain + '/bitrix/mobileapp/immobile/components/im/messenger/images/';

	const statusImage = {
		owner: statusPath + 'status_user_owner.png',
	}

	const statusType = {
		owner: 'owner',
	};

	const getStatus = (type) => {
		if (!statusType[type])
		{
			return '';
		}

		return statusImage[type];
	}

	module.exports = {
		cross,
		lens,
		arrowRight,
		statusType,
		getStatus,
	};
});