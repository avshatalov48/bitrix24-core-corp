/**
 * @module crm/timeline/ui/sticky-date
 */
jn.define('crm/timeline/ui/sticky-date', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Moment } = require('utils/date');
	const { DateDivider } = require('crm/timeline/ui/date-divider');

	const DEFAULT_OPACITY = 1;
	const INNER_PADDING = 16;
	const breakpointsMap = {};
	let sortedBreakpoints = [];
	let initialOffset = 0;

	/** @type StickyDate */
	let instance = null;

	/**
	 * @class StickyDate
	 */
	class StickyDate extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false,
				transparent: true,
				moment: new Moment(),
			};

			this.containerRef = null;
		}

		/**
		 * @public
		 * @param {number} offsetTop
		 * @param {Moment} moment
		 */
		static registerBreakpoint(offsetTop, moment)
		{
			breakpointsMap[offsetTop] = moment;
			sortedBreakpoints = Object.keys(breakpointsMap)
				.map(Number)
				.sort((a, b) => b - a);
		}

		/**
		 * @public
		 * @param {number} offsetTop
		 */
		static registerInitialOffset(offsetTop)
		{
			initialOffset = offsetTop;
		}

		/**
		 * @public
		 * @param {StickyDate} ref
		 */
		static useRef(ref)
		{
			instance = ref;
		}

		/**
		 * @public
		 * @param {number} top
		 */
		static onScroll(top)
		{
			if (!instance)
			{
				return;
			}

			let visible = false;

			for (const pos of sortedBreakpoints)
			{
				if (top > (pos + initialOffset - INNER_PADDING))
				{
					visible = true;
					instance.setMoment(breakpointsMap[String(pos)]);
					break;
				}
			}

			return visible ? instance.fadeIn() : instance.fadeOut();
		}

		/**
		 * @public
		 * @param {Moment} moment
		 */
		setMoment(moment)
		{
			if (!this.state.moment.equals(moment))
			{
				this.setState({ moment });
			}
		}

		/**
		 * @public
		 */
		fadeIn()
		{
			if (!this.state.visible && this.containerRef)
			{
				this.setState({ visible: true }, () => {
					this.containerRef.animate({
						opacity: DEFAULT_OPACITY,
						duration: 300,
					}, () => {
						this.setState({ transparent: false });
					});
				});
			}
		}

		/**
		 * @public
		 */
		fadeOut()
		{
			if (this.state.visible && this.containerRef)
			{
				this.containerRef.animate({
					opacity: 0,
					duration: 300,
				}, () => {
					this.setState({ transparent: true, visible: false });
				});
			}
		}

		render()
		{
			return View(
				{
					ref: (ref) => {
						this.containerRef = ref;
					},
					style: {
						position: 'absolute',
						top: 0,
						width: '100%',
						opacity: this.state.transparent ? 0 : DEFAULT_OPACITY,
						display: this.state.visible ? 'flex' : 'none',
					},
				},
				View(
					{
						style: {
							paddingTop: INNER_PADDING,
						},
					},
					DateDivider({
						moment: this.state.moment,
						showLine: false,
					}),
				),
			);
		}
	}

	module.exports = { StickyDate };
});
