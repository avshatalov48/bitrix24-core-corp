/**
 * @module assets/communication/connection
 */
jn.define('assets/communication/connection', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const getColor = (enabled) => (enabled ? AppTheme.colors.accentExtraDarkblue : AppTheme.colors.base6);

	/**
	 * @class ConnectionSvg
	 */
	class ConnectionSvg
	{
		static phone(enabled = false)
		{
			const color = getColor(enabled);

			return `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 18C13.9706 18 18 13.9706 18 9C18 4.02944 13.9706 0 9 0C4.02944 0 0 4.02944 0 9C0 13.9706 4.02944 18 9 18ZM13.2578 11.1232L12.0073 10.1862C11.5632 9.85252 10.904 10.0132 10.5798 10.4712L10.2091 10.9967C10.1293 11.1056 9.96498 11.1397 9.85057 11.0737C8.70336 10.3253 7.72244 9.28096 7.04349 8.08927C6.97875 7.96587 7.01236 7.80485 7.133 7.728L7.658 7.37758C8.12796 7.06393 8.32144 6.4242 8.01512 5.95467L7.14051 4.67555C6.87546 4.28161 6.32068 4.2281 5.97243 4.55154C4.665 5.801 4.09464 7.97149 6.96255 11.0035C9.83041 14.0356 12.0089 13.5505 13.3163 12.3011C13.665 11.9775 13.6356 11.4102 13.2578 11.1232Z" fill="${color}"/></svg>`;
		}

		static im(enabled = false)
		{
			const color = getColor(enabled);

			return `<svg width="17" height="15" viewBox="0 0 17 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.42474 0C1.53331 0 0 1.50968 0 3.37197V8.85776C0 10.6758 1.46133 12.1578 3.29057 12.2272V13.8739C3.29057 14.8298 4.42561 15.3494 5.16794 14.7332L8.18414 12.2297H13.5753C15.4667 12.2297 17 10.72 17 8.85776V3.37197C17 1.50968 15.4667 0 13.5753 0H3.42474Z" fill="${color}"/></svg>`;
		}

		static email(enabled = false)
		{
			const color = getColor(enabled);

			return `<svg width="18" height="12" viewBox="0 0 18 12" fill="none" xmlns="http://www.w3.org/2000/svg"><g><path d="M9.00002 4.88332L1.38462 0H16.6154L9.00002 4.88332Z" fill="${color}"/><path d="M18 1.47201L9.00002 7.66886L0 1.47198V10.9425C0 11.5273 0.579747 12 1.29438 12H16.7056C17.422 12 18 11.5266 18 10.9425V1.47201Z" fill="${color}"/></g></svg>`;
		}
	}

	module.exports = { ConnectionSvg };
});
