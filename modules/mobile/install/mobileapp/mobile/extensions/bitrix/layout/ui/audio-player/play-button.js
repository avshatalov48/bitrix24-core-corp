/**
 * @module layout/ui/audio-player/play-button
 */
jn.define('layout/ui/audio-player/play-button', (require, exports, module) => {
	const { Haptics } = require('haptics');

	const PlayButton = ({ play, onClick, isLoading, onLoadAudio, duration}) => {
		return View(
			{

			},
			isLoading ? View(
				{
					style: {
						width: 44,
						height: 44,
						justifyContent: 'center',
						alignItems: 'center',
					}
				},
				Loader({
					style: {
						width: 26,
						height: 26,
						marginTop: 6,
						marginRight: 7,
						marginBottom: 10,
					},
					tintColor: '#d5d7db',
					animating: true,
					size: 'small'
				})
			) : Image({
				style: {
					width: 44,
					height: 44,
				},
				svg: {
					content: play ? svgImages.pauseButtonIcon : svgImages.playButtonIcon,
				},
				onClick: () => {
					Haptics.impactLight();
					if (!isLoading && !duration)
					{
						onLoadAudio()
					}
					else if (onClick)
					{
						onClick()
					}
				},
			})
		);
	}

	const svgImages = {
		playButtonIcon: `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M38 22C38 30.8366 30.8366 38 22 38C13.1634 38 6 30.8366 6 22C6 13.1634 13.1634 6 22 6C30.8366 6 38 13.1634 38 22Z" fill="#2FC6F6"/><path fill-rule="evenodd" clip-rule="evenodd" d="M27.7071 21.4139L19.0449 15.1259C18.8407 14.9756 18.5746 14.9586 18.3548 15.0818C18.1351 15.205 17.9983 15.4479 18 15.7118V28.2871C17.9974 28.5513 18.1341 28.795 18.3542 28.9183C18.5743 29.0417 18.8409 29.0242 19.0449 28.8729L27.7071 22.585C27.8903 22.4536 28 22.2342 28 21.9994C28 21.7647 27.8903 21.5453 27.7071 21.4139Z" fill="white"/><rect width="44" height="44" rx="22" fill="white" fill-opacity="0.01"/></svg>`,
		pauseButtonIcon: `<svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="21.5111" cy="22" r="15.6444" fill="#c3f0ff"/><path d="M19.7332 17.1333C19.7332 16.581 19.2855 16.1333 18.7332 16.1333H17.6221C17.0698 16.1333 16.6221 16.581 16.6221 17.1333V26.8667C16.6221 27.419 17.0698 27.8667 17.6221 27.8667H18.7332C19.2855 27.8667 19.7332 27.4189 19.7332 26.8667V17.1333Z" fill="#2FC6F6"/><path d="M26.3998 17.1333C26.3998 16.581 25.9521 16.1333 25.3998 16.1333H24.2887C23.7365 16.1333 23.2887 16.581 23.2887 17.1333V26.8667C23.2887 27.419 23.7365 27.8667 24.2887 27.8667H25.3998C25.9521 27.8667 26.3998 27.4189 26.3998 26.8667V17.1333Z" fill="#2FC6F6"/><rect y="0.488892" width="43.0222" height="43.0222" rx="21.5111" fill="white" fill-opacity="0.01"/></svg>`,
	};

	module.exports = { PlayButton };
});