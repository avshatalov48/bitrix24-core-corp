(() => {

	/**
	 * @class ImageStack
	 */
	class ImageStack extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {array<string>} props.images
		 * @param {object} props.style
		 */
		constructor(props)
		{
			super(props);

			this.innerScale = 0.8;
			this.defaultSize = 80;
		}

		render()
		{
			return View(
				{
					style: {
						width: this.containerWidth,
						height: this.containerHeight,
						marginTop: this.style.marginTop || 0,
						marginRight: this.style.marginRight || 0,
						marginBottom: this.style.marginBottom || 0,
						marginLeft: this.style.marginLeft || 0,
						justifyContent: 'center',
						alignItems: 'center',
					}
				},
				this.renderBorders(),
				this.renderNoPhotoCover(),
				this.renderTopImage()
			);
		}

		renderBorders()
		{
			if (this.images.length < 2)
			{
				return null;
			}

			const rotateFirst = this.containerWidth > 120 ? 2 : 3;
			const rotateSecond = this.containerWidth > 120 ? 4 : 8;
			const rotateSingle = Math.round((rotateSecond - rotateFirst) / 2) + rotateFirst;

			const makeDoubleBorder = () => this.makeSvg(
				this.makeRect(rotateSecond),
				this.makeRect(rotateFirst)
			);

			const makeSingleBorder = () => this.makeSvg(
				this.makeRect(rotateSingle)
			);

			return Image({
				style: {
					width: '100%',
					height: '100%',
					position: 'absolute',
				},
				svg: {
					content: this.images.length > 2 ? makeDoubleBorder() : makeSingleBorder()
				}
			});
		}

		renderNoPhotoCover()
		{
			return Image({
				style: {
					width: this.innerWidth,
					height: this.innerHeight,
					position: 'absolute',
				},
				resizeMode: 'contain',
				svg: SvgIcons.noPhoto
			});
		}

		renderTopImage()
		{
			const src = this.images[0];
			if (src)
			{
				const absPath = src.startsWith('/') ? currentDomain + src : src;

				return Image({
					style: {
						width: this.innerWidth,
						height: this.innerHeight,
						borderRadius: 4,
					},
					resizeMode: 'cover',
					uri: encodeURI(absPath),
				});
			}
		};

		makeSvg(...children)
		{
			return `
				<svg width="${this.containerWidth}" height="${this.containerHeight}" xmlns="http://www.w3.org/2000/svg">
					${children.join('')}
				</svg>
			`;
		}

		makeRect(rotate)
		{
			const radius = 4;
			const transform = [
				`translate(${this.translate.x}, ${this.translate.y})`,
				`rotate(${rotate}, ${this.center.x}, ${this.center.y})`
			];
			const style = [
				`fill: ${this.style.fill || '#ffffff'}`,
				`stroke-width: 1`,
				`stroke: #000000`,
				`stroke-opacity: 0.14`
			];

			return `
				<rect 
					width="${this.innerWidth}" 
					height="${this.innerHeight}" 
					rx="${radius}" 
					ry="${radius}" 
					transform="${transform.join(',')}" 
					style="${style.join(';')}" />
			`;
		}

		get images()
		{
			return BX.type.isArray(this.props.images) ? this.props.images : [];
		}

		get containerWidth()
		{
			return this.style.width || this.defaultSize;
		}

		get containerHeight()
		{
			return this.style.height || this.defaultSize;
		}

		get style()
		{
			return this.props.style || {};
		}

		get innerWidth()
		{
			return this.containerWidth * this.innerScale;
		}

		get innerHeight()
		{
			return this.containerHeight * this.innerScale;
		}

		get center()
		{
			const x = this.containerWidth / 2;
			const y = this.containerHeight / 2;

			return {x, y};
		}

		get translate()
		{
			const x = this.center.x - (this.innerWidth / 2);
			const y = this.center.y - (this.innerHeight / 2);

			return {x, y};
		}
	}

	const SvgIcons = {
		noPhoto: {
			content: `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.41333 0H70.5867C75.7867 0 80 4.21333 80 9.41333V70.5867C80 75.7867 75.7867 80 70.5867 80H9.41333C4.21333 80 0 75.7867 0 70.5867V9.41333C0 4.21333 4.21333 0 9.41333 0ZM9.41333 70.5867H70.5867V65.8827L54.2773 47.056L46.1173 56.4693L25.7227 32.9387L9.41333 51.7653V70.592V70.5867ZM58.8213 28.24C60.6941 28.24 62.4902 27.496 63.8144 26.1718C65.1387 24.8475 65.8827 23.0514 65.8827 21.1787C65.8827 19.3059 65.1387 17.5098 63.8144 16.1855C62.4902 14.8613 60.6941 14.1173 58.8213 14.1173C56.9829 14.1679 55.2366 14.9337 53.9541 16.252C52.6716 17.5702 51.954 19.3368 51.954 21.176C51.954 23.0152 52.6716 24.7818 53.9541 26.1C55.2366 27.4183 56.9829 28.1841 58.8213 28.2347V28.24Z" fill="#A8ADB4"/></svg>`
		}
	};

	jnexport(ImageStack);

})();
