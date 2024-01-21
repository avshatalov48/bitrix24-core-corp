/**
 * @module layout/ui/audio-player/play-button
 */
jn.define('layout/ui/audio-player/play-button', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const AppTheme = require('apptheme');

	const PlayButton = ({ compact, ...props }) => {
		return compact ? CompactLayout(props) : DefaultLayout(props);
	};

	/**
	 * @class CompactLayout
	 */
	const CompactLayout = ({ play, onClick, isLoading, onLoadAudio, duration }) => View(
		{
			style: {
				width: 40,
				height: 19,
				flexDirection: 'row',
				justifyContent: 'center',
				alignItems: 'center',
				borderColor: play ? AppTheme.colors.bgSeparatorPrimary : AppTheme.colors.accentExtraAqua,
				borderWidth: 1,
				borderRadius: 512,
				marginRight: 7,
			},
		},
		isLoading && Loader({
			style: {
				width: 16,
				height: 16,
			},
			tintColor: AppTheme.colors.base6,
			animating: true,
			size: 'small',
		}),
		!isLoading && Image({
			style: {
				width: 19,
				height: 19,
			},
			svg: {
				content: play ? svgImages.pauseCompact : svgImages.playCompact,
			},
			onClick: () => handleClick({ isLoading, duration, onClick, onLoadAudio }),
		}),
	);

	const DefaultLayout = ({ play, onClick, isLoading, onLoadAudio, duration }) => View(
		{},
		isLoading ? View(
			{
				style: {
					width: 44,
					height: 44,
					justifyContent: 'center',
					alignItems: 'center',
				},
			},
			Loader({
				style: {
					width: 26,
					height: 26,
					marginTop: 6,
					marginRight: 7,
					marginBottom: 10,
				},
				tintColor: AppTheme.colors.base6,
				animating: true,
				size: 'small',
			}),
		) : Image({
			style: {
				width: 44,
				height: 44,
			},
			svg: {
				content: play ? svgImages.pauseButtonIcon : svgImages.playButtonIcon,
			},
			onClick: () => handleClick({ isLoading, duration, onClick, onLoadAudio }),
		}),
	);

	const handleClick = ({ isLoading, duration, onClick, onLoadAudio }) => {
		Haptics.impactLight();
		if (!isLoading && !duration)
		{
			onLoadAudio();
		}
		else if (onClick)
		{
			onClick();
		}
	};

	const svgImages = {
		playButtonIcon: `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M38 22C38 30.8366 30.8366 38 22 38C13.1634 38 6 30.8366 6 22C6 13.1634 13.1634 6 22 6C30.8366 6 38 13.1634 38 22Z" fill="${AppTheme.colors.accentBrandBlue}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M27.7071 21.4139L19.0449 15.1259C18.8407 14.9756 18.5746 14.9586 18.3548 15.0818C18.1351 15.205 17.9983 15.4479 18 15.7118V28.2871C17.9974 28.5513 18.1341 28.795 18.3542 28.9183C18.5743 29.0417 18.8409 29.0242 19.0449 28.8729L27.7071 22.585C27.8903 22.4536 28 22.2342 28 21.9994C28 21.7647 27.8903 21.5453 27.7071 21.4139Z" fill="white"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
		pauseButtonIcon: `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="21.5111" cy="22" r="15.6444" fill="${AppTheme.colors.accentSoftBlue1}"/><path d="M19.7332 17.1333C19.7332 16.581 19.2855 16.1333 18.7332 16.1333H17.6221C17.0698 16.1333 16.6221 16.581 16.6221 17.1333V26.8667C16.6221 27.419 17.0698 27.8667 17.6221 27.8667H18.7332C19.2855 27.8667 19.7332 27.4189 19.7332 26.8667V17.1333Z" fill="${AppTheme.colors.accentBrandBlue}"/><path d="M26.3998 17.1333C26.3998 16.581 25.9521 16.1333 25.3998 16.1333H24.2887C23.7365 16.1333 23.2887 16.581 23.2887 17.1333V26.8667C23.2887 27.419 23.7365 27.8667 24.2887 27.8667H25.3998C25.9521 27.8667 26.3998 27.4189 26.3998 26.8667V17.1333Z" fill="${AppTheme.colors.accentBrandBlue}"/><rect y="0.488892" width="43.0222" height="43.0222" rx="21.5111" fill="white" fill-opacity="0.01"/></svg>`,
		playCompact: `<svg width="19" height="20" viewBox="0 0 19 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M13.4831 9.29253L7.90344 5.08726C7.77188 4.98672 7.60048 4.97533 7.45892 5.05774C7.31736 5.14014 7.22923 5.30261 7.23037 5.47908V13.8892C7.2287 14.0659 7.31675 14.2289 7.45852 14.3114C7.60028 14.3939 7.77199 14.3822 7.90344 14.281L13.4831 10.0758C13.6011 9.98785 13.6718 9.84112 13.6718 9.68414C13.6718 9.52717 13.6011 9.38044 13.4831 9.29253Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		pauseCompact: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.0158 6.08073C9.0158 5.80459 8.79194 5.58073 8.5158 5.58073H7.50183C7.22569 5.58073 7.00183 5.80459 7.00183 6.08073V13.2878C7.00183 13.5639 7.22569 13.7878 7.50183 13.7878H8.5158C8.79194 13.7878 9.0158 13.5639 9.0158 13.2878V6.08073Z" fill="${AppTheme.colors.base0}"/><path d="M13.3314 6.08073C13.3314 5.80459 13.1076 5.58073 12.8314 5.58073H11.8175C11.5413 5.58073 11.3175 5.80459 11.3175 6.08073V13.2878C11.3175 13.5639 11.5413 13.7878 11.8175 13.7878H12.8314C13.1076 13.7878 13.3314 13.5639 13.3314 13.2878V6.08073Z" fill="${AppTheme.colors.base0}"/></svg>`,
	};

	module.exports = { PlayButton };
});
