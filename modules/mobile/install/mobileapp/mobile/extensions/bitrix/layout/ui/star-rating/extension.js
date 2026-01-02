/**
 * @module layout/ui/star-rating
 */
jn.define('layout/ui/star-rating', (require, exports, module) => {
	const { Haptics } = require('haptics');
	const { Indent } = require('tokens');

	const ANIMATION_ZOOM = 8;
	const ANIMATION_DURATION = 100;
	const STAR_SIZE = 40;
	const STAR_QUANTITY = 5;

	class StarRating extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				currentRating: 0,
				finalRating: 0,
				isAnimating: false,
			};
			this.containerRef = null;
			this.starRefs = {};
		}

		get testId()
		{
			return this.props.testId ?? '';
		}

		get starSize()
		{
			return this.props.starSize ?? STAR_SIZE;
		}

		get starQuantity()
		{
			return this.props.starQuantity ?? STAR_QUANTITY;
		}

		bindRef = (ref) => {
			if (ref)
			{
				this.containerRef = ref;
			}
		};

		bindStarRef = (index) => (ref) => {
			if (ref)
			{
				this.starRefs[index] = ref;
			}
		};

		animateStar = (starIndex) => {
			if (this.state.finalRating === starIndex)
			{
				return;
			}

			const starRef = this.starRefs[starIndex];
			if (!starRef)
			{
				return;
			}

			this.setState({ finalRating: starIndex, isAnimating: true, currentRating: starIndex }, () => {
				this.animate(starRef, starIndex);
			});
		};

		animate(starRef, starIndex)
		{
			const originalSize = this.starSize;
			const enlargedSize = originalSize + ANIMATION_ZOOM;

			starRef.animate({
				duration: ANIMATION_DURATION,
				width: enlargedSize,
				height: enlargedSize,
			}, () => {
				starRef.animate({
					duration: ANIMATION_DURATION,
					width: originalSize,
					height: originalSize,
				}, () => {
					this.setState({ isAnimating: false }, () => {
						this.props.onStarClick?.(starIndex);
					});
					Haptics.impactLight();
				});
			}).start(starRef);
		}

		render()
		{
			const stars = [];
			for (let i = 1; i <= this.starQuantity; i++)
			{
				stars.push(this.renderStar(i));
			}

			return View({
				testId: this.testId,
				style: {
					position: 'relative',
					width: this.calculateContainerWidth(),
					flexDirection: 'row',
					alignItems: 'center',
					justifyContent: 'center',
					height: this.starSize + ANIMATION_ZOOM,
				},
				ref: this.bindRef,
				onClick: () => {},
				onTouchesEnded: this.handleTouchesEnded,
			}, ...stars);
		}

		calculateContainerWidth()
		{
			return this.starQuantity * this.starSize + (this.starQuantity - 1) * Indent.XL.toNumber();
		}

		handleTouchesEnded = ({ x, y }) => {
			if (this.containerRef && !this.state.isAnimating)
			{
				const starIndex = this.getStarIndexFromCoordinates(x, y);

				if (starIndex !== null)
				{
					this.setState({ currentRating: starIndex }, () => {
						this.animateStar(starIndex);
					});
				}
			}
		};

		getStarIndexFromCoordinates(x, y)
		{
			const starWidthWithMargin = this.starSize + Indent.XL.toNumber();
			const starIndex = Math.floor(x / starWidthWithMargin) + 1;

			if (starIndex < 1)
			{
				return 1;
			}

			if (starIndex > this.starQuantity)
			{
				return this.starQuantity;
			}

			return starIndex;
		}

		renderStar(index)
		{
			const starImage = this.getStarImageUrl(index);
			const leftPosition = (index - 1) * (this.starSize + Indent.XL.toNumber());

			return View({
				style: {
					position: 'absolute',
					left: leftPosition,
					width: this.starSize,
					height: this.starSize,
					backgroundResizeMode: 'stretch',
					backgroundImageSvg: starImage,
				},
				testId: this.getStarTestId(index),
				ref: this.bindStarRef(index),
				clickable: false,
			});
		}

		isStarFilled(index)
		{
			const { finalRating, currentRating, isAnimating } = this.state;

			if (isAnimating && index === currentRating)
			{
				return true;
			}

			return index <= finalRating || index < currentRating;
		}

		getStarImageUrl(index)
		{
			return this.isStarFilled(index) ? filledStarSvg : defaultStarSvg;
		}

		getStarTestId(index)
		{
			return this.isStarFilled(index)
				? `star-filled-${index}`
				: `star-default-${index}`;
		}
	}

	const filledStarSvg = `
		<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M21.3057 6.91719C20.7815 5.80234 19.2179 5.80234 18.6937 6.9172L15.1709 14.4092L7.31333 15.6076C6.07672 15.7962 5.71262 17.2891 6.50112 18.0958L12.2151 23.9418L10.8643 32.208C10.6856 33.302 11.826 34.3782 12.9833 33.7397L19.9997 29.868L27.0162 33.7397C28.1734 34.3783 29.3139 33.302 29.1351 32.208L27.7843 23.9418L33.4983 18.0958C34.2868 17.2891 33.9227 15.7962 32.6861 15.6076L24.8285 14.4092L21.3057 6.91719Z" fill="#FAA72C"/>
		</svg>
	`;

	const defaultStarSvg = `
		<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path fill-rule="evenodd" clip-rule="evenodd" d="M23.2078 15.6625L19.9997 8.8399L16.7917 15.6625C16.6262 16.0144 16.2962 16.2607 15.9118 16.3194L8.64474 17.4277L13.9398 22.845C14.199 23.1102 14.3167 23.4827 14.2569 23.8487L13.0169 31.4368L19.4361 27.8947C19.7869 27.7011 20.2125 27.7011 20.5634 27.8947L26.9825 31.4368L25.7426 23.8487C25.6828 23.4827 25.8005 23.1102 26.0596 22.845L31.3547 17.4277L24.0876 16.3194C23.7032 16.2607 23.3732 16.0144 23.2078 15.6625ZM21.3057 6.91719C20.7815 5.80234 19.2179 5.80234 18.6937 6.9172L15.1709 14.4092L7.31333 15.6076C6.07672 15.7962 5.71262 17.2891 6.50112 18.0958L12.2151 23.9418L10.8643 32.208C10.6856 33.302 11.826 34.3782 12.9833 33.7397L19.9997 29.868L27.0162 33.7397C28.1734 34.3783 29.3139 33.302 29.1351 32.208L27.7843 23.9418L33.4983 18.0958C34.2868 17.2891 33.9227 15.7962 32.6861 15.6076L24.8285 14.4092L21.3057 6.91719Z" fill="#909090"/>
		</svg>
	`;

	StarRating.defaultProps = {
		starSize: STAR_SIZE,
		starQuantity: STAR_QUANTITY,
	};

	StarRating.propTypes = {
		testId: PropTypes.string.isRequired,
		starQuantity: PropTypes.number,
		starSize: PropTypes.number,
		onStarClick: PropTypes.func,
	};

	module.exports = {
		StarRating: (props) => new StarRating(props),
		StarRatingClass: StarRating,
	};
});
