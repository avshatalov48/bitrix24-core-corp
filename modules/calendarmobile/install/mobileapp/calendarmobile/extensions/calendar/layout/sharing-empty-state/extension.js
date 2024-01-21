/**
 * @module calendar/layout/sharing-empty-state
 */
jn.define('calendar/layout/sharing-empty-state', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');

	const SharingEmptyState = () => {
		return View(
			{
				testId: 'SharingEmptyState',
				style: {
					paddingTop: 24,
					paddingHorizontal: 60,
					backgroundColor: AppTheme.colors.bgContentPrimary,
					borderRadius: 12,
				},
			},
			Title(),
			DeactivatedIcons(),
			Description(),
		);
	};

	const Title = () => {
		return Text(
			{
				testId: 'SharingEmptyStateTitle',
				style: {
					fontWeight: '400',
					textAlign: 'center',
					color: AppTheme.colors.base3,
					fontSize: 18,
					lineHeight: 23,
				},
				text: Loc.getMessage('L_SD_TITLE'),
			},
		);
	};

	const DeactivatedIcons = () => {
		return View(
			{
				style: {
					justifyContent: 'space-between',
					alignItems: 'center',
					flexDirection: 'row',
					marginTop: 20,
				},
			},
			Image(
				{
					svg: {
						content: icons.calendarDisabled,
					},
					style: {
						width: 64,
						height: 64,
					},
				},
			),
			Image(
				{
					svg: {
						content: icons.link,
					},
					style: {
						width: 80,
						height: 44,
					},
				},
			),
			Image(
				{
					svg: {
						content: icons.users,
					},
					style: {
						width: 74,
						height: 74,
					},
				},
			),
		);
	};

	const Description = () => {
		return Text(
			{
				testId: 'SharingEmptyStateDescription',
				style: {
					marginTop: 20,
					marginBottom: 30,
					textAlign: 'center',
					color: AppTheme.colors.base4,
					fontSize: 14,
					fontWeight: '400',
					lineHeight: 18,
				},
				text: Loc.getMessage('L_SD_DESCRIPTION'),
			},
		);
	};

	const icons = {
		calendarDisabled: '<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_3538_176602)"><path d="M4.61136 14.8967C4.61136 10.899 7.8469 7.6582 11.8381 7.6582H52.6178C56.609 7.6582 59.8446 10.899 59.8446 14.8967V20.5841C59.8446 24.5818 56.609 27.8226 52.6178 27.8226H11.8381C7.8469 27.8226 4.61136 24.5818 4.61136 20.5841V14.8967Z" fill="#DFE0E3"/></g><g filter="url(#filter1_d_3538_176602)"><path d="M4.61136 27.8738C4.61136 23.876 7.8469 20.6353 11.8381 20.6353H52.6178C56.609 20.6353 59.8446 23.876 59.8446 27.8738V52.1745C59.8446 56.1722 56.609 59.413 52.6178 59.413H11.8381C7.8469 59.413 4.61136 56.1722 4.61136 52.1745V27.8738Z" fill="#C9CCD0"/></g><path d="M4.61136 26.6577C4.61136 22.66 7.8469 19.4192 11.8381 19.4192H52.6178C56.609 19.4192 59.8446 22.66 59.8446 26.6577V50.9584C59.8446 54.9561 56.609 58.1969 52.6178 58.1969H11.8381C7.8469 58.1969 4.61136 54.9561 4.61136 50.9584V26.6577Z" fill="#E6E7E9"/><path d="M4.61118 25.4421C4.61118 21.4444 7.84671 18.2036 11.838 18.2036H52.6176C56.6089 18.2036 59.8444 21.4444 59.8444 25.4421V49.7428C59.8444 53.7405 56.6089 56.9813 52.6176 56.9813H11.838C7.84672 56.9813 4.61118 53.7405 4.61118 49.7428V25.4421Z" fill="white"/><rect x="9.80124" y="24.0103" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="9.80124" y="34.3455" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="9.80124" y="44.6804" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="19.4843" y="24.0103" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect opacity="0.8" x="19.4843" y="34.3455" width="6.06959" height="6.07944" rx="0.730529" fill="#B9BDC3"/><rect x="19.4843" y="44.6804" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="29.1672" y="24.0103" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="29.1672" y="34.3455" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect opacity="0.8" x="29.1672" y="44.6804" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect opacity="0.8" x="38.8502" y="24.0103" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="38.8502" y="34.3455" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect opacity="0.8" x="48.5332" y="24.0103" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect x="48.5332" y="34.3455" width="6.06959" height="6.07944" rx="0.730529" fill="#DFE0E3"/><rect opacity="0.2" x="14.9297" y="4.03906" width="4.24871" height="10.943" rx="2.12436" fill="#BFC2C7"/><rect x="14.9297" y="2.823" width="4.24871" height="10.943" rx="2.12436" fill="#BDC1C6"/><rect opacity="0.2" x="45.8848" y="4.03906" width="4.24871" height="10.943" rx="2.12436" fill="#BFC2C7"/><rect x="45.8848" y="2.823" width="4.24871" height="10.943" rx="2.12436" fill="#BDC1C6"/><defs><filter id="filter0_d_3538_176602" x="2.74749" y="6.10498" width="58.961" height="23.8921" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="0.310645"/><feGaussianBlur stdDeviation="0.931936"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0.423529 0 0 0 0 0.423529 0 0 0 0 0.423529 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3538_176602"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3538_176602" result="shape"/></filter><filter id="filter1_d_3538_176602" x="2.74749" y="19.082" width="58.961" height="42.5056" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="0.310645"/><feGaussianBlur stdDeviation="0.931936"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0.423529 0 0 0 0 0.423529 0 0 0 0 0.423529 0 0 0 0.1 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3538_176602"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3538_176602" result="shape"/></filter></defs></svg>',
		link: '<svg width="80" height="44" viewBox="0 0 80 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M65 22L79 22" stroke="#C3F0FF" stroke-linecap="round"/><path d="M1 22L15 22" stroke="#C3F0FF" stroke-linecap="round"/><g opacity="0.6"><path fill-rule="evenodd" clip-rule="evenodd" d="M35.0779 24.1506L37.6379 21.5906C35.0669 20.2172 31.7989 20.6145 29.631 22.7824L26.8847 25.5287C24.2305 28.1829 24.2305 32.4864 26.8847 35.1406C29.539 37.7949 33.8424 37.7949 36.4967 35.1406L39.243 32.3944C41.4283 30.2091 41.8144 26.906 40.4014 24.3257L37.8591 26.868C38.0931 27.9848 37.7766 29.1939 36.9096 30.0609L34.1633 32.8072C32.7977 34.1727 30.5837 34.1727 29.2182 32.8072C27.8526 31.4416 27.8526 29.2277 29.2182 27.8621L31.9645 25.1158C32.811 24.2693 33.9835 23.9476 35.0779 24.1506ZM45.0471 19.6809L42.5048 22.2232C45.0851 23.6362 48.3882 23.2501 50.5735 21.0648L53.3197 18.3185C55.974 15.6642 55.974 11.3608 53.3197 8.70653C50.6655 6.05225 46.362 6.05225 43.7078 8.70653L40.9615 11.4528C38.7936 13.6207 38.3963 16.8887 39.7697 19.4597L42.3297 16.8997C42.1267 15.8053 42.4484 14.6328 43.2949 13.7863L46.0412 11.04C47.4068 9.67443 49.6207 9.67443 50.9863 11.04C52.3518 12.4055 52.3518 14.6195 50.9863 15.9851L48.24 18.7314C47.373 19.5984 46.1639 19.9149 45.0471 19.6809ZM50.8436 33.1397C51.4121 32.5712 51.4121 31.6494 50.8436 31.0808L47.7552 27.9925C47.1867 27.424 46.2649 27.424 45.6964 27.9925C45.1278 28.5611 45.1278 29.4829 45.6964 30.0514L48.7847 33.1397C49.3532 33.7083 50.275 33.7083 50.8436 33.1397ZM24.7136 18.0578C24.5055 18.8344 24.9664 19.6327 25.743 19.8408L29.9618 20.9712C30.7384 21.1793 31.5367 20.7184 31.7448 19.9418C31.9529 19.1651 31.492 18.3668 30.7154 18.1587L26.4966 17.0283C25.72 16.8202 24.9217 17.2811 24.7136 18.0578ZM44.2149 37.5587C44.9915 37.3506 45.4524 36.5523 45.2443 35.7757L44.1139 31.5569C43.9058 30.7803 43.1075 30.3194 42.3308 30.5275C41.5542 30.7356 41.0933 31.5339 41.3014 32.3106L42.4318 36.5293C42.6399 37.3059 43.4382 37.7668 44.2149 37.5587ZM35.5225 7.24919C34.7459 7.45729 34.285 8.25558 34.4931 9.03223L35.6235 13.251C35.8316 14.0276 36.6299 14.4885 37.4065 14.2804C38.1832 14.0723 38.6441 13.274 38.436 12.4974L37.3056 8.27863C37.0975 7.50198 36.2992 7.04109 35.5225 7.24919ZM55.0241 26.7501C55.2322 25.9734 54.7713 25.1751 53.9947 24.967L49.776 23.8366C48.9993 23.6285 48.201 24.0894 47.9929 24.866C47.7848 25.6427 48.2457 26.441 49.0224 26.6491L53.2411 27.7795C54.0177 27.9876 54.816 27.5267 55.0241 26.7501ZM29.3249 11.6217C28.7564 12.1903 28.7564 13.1121 29.3249 13.6806L32.4132 16.7689C32.9818 17.3375 33.9036 17.3375 34.4721 16.7689C35.0406 16.2004 35.0406 15.2786 34.4721 14.71L31.3838 11.6217C30.8152 11.0532 29.8934 11.0532 29.3249 11.6217Z" fill="#11A9D9"/></g></svg>',
		users: '<svg width="74" height="74" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg"><g filter="url(#filter0_d_3538_176607)"><circle cx="48.6724" cy="48.6074" r="18.0788" fill="#F8FAFB"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M49.0184 46.662C50.8088 46.662 52.2603 45.2105 52.2603 43.4201C52.2603 41.6297 50.8088 40.1782 49.0184 40.1782C47.2279 40.1782 45.7765 41.6297 45.7765 43.4201C45.7765 45.2105 47.2279 46.662 49.0184 46.662ZM49.0183 55.5771C52.5201 55.5771 55.359 55.5174 55.359 53.3296C55.359 50.2863 52.5201 47.8193 49.0183 47.8193C45.5164 47.8193 42.6776 50.2863 42.6776 53.3296C42.6776 55.5174 45.5164 55.5771 49.0183 55.5771Z" fill="#DFE0E3"/><g filter="url(#filter1_d_3538_176607)"><circle cx="27.8693" cy="28.7948" r="21.0507" fill="white"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M28.2153 25.8795C30.705 25.8795 32.7234 23.8611 32.7234 21.3714C32.7234 18.8816 30.705 16.8633 28.2153 16.8633C25.7255 16.8633 23.7072 18.8816 23.7072 21.3714C23.7072 23.8611 25.7255 25.8795 28.2153 25.8795ZM28.2153 38.2768C33.0849 38.2768 37.0325 38.1938 37.0325 35.1515C37.0325 30.9195 33.0849 27.4888 28.2153 27.4888C23.3456 27.4888 19.398 30.9195 19.398 35.1515C19.398 38.1938 23.3456 38.2768 28.2153 38.2768Z" fill="#C9CCD0"/><defs><filter id="filter0_d_3538_176607" x="26.5936" y="28.5286" width="44.1576" height="44.1577" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="2"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.12 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3538_176607"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3538_176607" result="shape"/></filter><filter id="filter1_d_3538_176607" x="0.818618" y="3.74414" width="54.1014" height="54.1013" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="2"/><feGaussianBlur stdDeviation="3"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0.423529 0 0 0 0 0.423529 0 0 0 0 0.423529 0 0 0 0.16 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_3538_176607"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_3538_176607" result="shape"/></filter></defs></svg>',
	};

	module.exports = { SharingEmptyState };
});
