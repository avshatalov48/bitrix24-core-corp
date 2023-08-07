/**
 * @module im/messenger/assets/file-download-menu
 */
jn.define('im/messenger/assets/file-download-menu', (require, exports, module) => {
	/**
	 * @class FileDownloadMenuSvg
	 */
	class FileDownloadMenuSvg
	{
		static getDownloadToDevice()
		{
			return `
				<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M16.3614 5.50211H12.4312V12.0524H7.19098L14.3963 18.6028L21.6017 12.0524H16.3614V5.50211ZM21.2565 21.7664H7.5V24.2664H21.2565V21.7664Z" fill="#6A737F"/>
				</svg>
			`;
		}

		static getDownloadToDisk()
		{
			return `
				<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M24.539 15.0617C25.0864 15.0617 25.5301 15.5054 25.5301 16.0528V21.0082C25.5301 21.8292 24.8645 22.4948 24.0435 22.4948H5.95634C5.13531 22.4948 4.46973 21.8292 4.46973 21.0082V16.0528C4.46973 15.5054 4.91345 15.0617 5.4608 15.0617H24.539ZM18.5927 17.5397H11.4074C11.0379 17.5397 10.7314 17.8093 10.6738 18.1625L10.6641 18.283V19.2741C10.6641 19.6436 10.9336 19.9501 11.2868 20.0077L11.4074 20.0174H18.5927C18.9622 20.0174 19.2687 19.7479 19.3263 19.3947L19.336 19.2741V18.283C19.336 17.8725 19.0032 17.5397 18.5927 17.5397ZM8.1865 17.5397C7.5023 17.5397 6.94765 18.0944 6.94765 18.7786C6.94765 19.4628 7.5023 20.0174 8.1865 20.0174C8.8707 20.0174 9.42535 19.4628 9.42535 18.7786C9.42535 18.0944 8.8707 17.5397 8.1865 17.5397ZM21.3366 7.62863C21.7989 7.62863 22.2227 7.88599 22.4358 8.29616L24.9107 13.058H5.21306L7.56934 8.31619C7.77864 7.89498 8.20842 7.62863 8.67876 7.62863H21.3366Z" fill="#828B95"/>
				</svg>
			`;
		}
	}

	module.exports = {
		FileDownloadMenuSvg,
	};
});