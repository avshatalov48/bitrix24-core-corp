/**
 * @module ui-system/popups/aha-moment
 */
jn.define('ui-system/popups/aha-moment', (require, exports, module) => {
	const { Hint } = require('ui-system/popups/aha-moment/src/hint');
	const { PropTypes } = require('utils/validation');
	const { AnalyticsEvent } = require('analytics');
	const { Type } = require('type');

	const ButtonType = {
		CLOSE: 'close',
		GO_TO_FEATURE: 'go_to_feature',
	};

	/**
	 * @class AhaMoment
	 * @param {object} props
	 * @return AhaMoment
	 */
	class AhaMoment extends LayoutComponent
	{
		static isShown = false;

		/**
		 * @public
		 * @param {object} props
		 * @param {number} props.testId
		 * @param {object} props.targetRef
		 * @param {boolean} [props.disableHideByOutsideClick]
		 * @param {object} [props.analyticsLabel]
		 * @param {string} [props.bottomButtonType]
		 * @param {object} [props.spotlightParams]
		 * @param {string} [props.title]
		 * @param {string} [props.description]
		 * @param {boolean} [props.closeButton=true]
		 * @param {function} [props.onClose]
		 * @param {function} [props.onClick]
		 * @param {function} [props.onHide]
		 * @param {number} [props.fadeInDuration=10]
		 * @param {object} [props.image]
		 * @param {number} [props.image.size=78]
		 * @param {delay} [props.delay = 0]
		 */
		static show(props)
		{
			const {
				targetRef,
				delay = 0,
			} = props;

			if (!targetRef)
			{
				return;
			}

			if (delay > 0)
			{
				setTimeout(() => {
					AhaMoment.showSpotlight(props);
				}, delay);

				return;
			}

			AhaMoment.showSpotlight(props);
		}

		static showSpotlight(props)
		{
			const { spotlight, targetParams } = AhaMoment.createSpotlight(props);
			const component = AhaMoment.createAhaMoment(props, spotlight, targetParams);

			if (!AhaMoment.isShown)
			{
				AhaMoment.isShown = true;
				spotlight.show();
			}

			component.sendAnalytics({
				event: 'show',
			});
		}

		static createSpotlight(props)
		{
			const {
				targetRef,
				closeButton = true,
				buttonText,
			} = props;

			let { disableHideByOutsideClick } = props;

			if (Type.isNil(disableHideByOutsideClick))
			{
				disableHideByOutsideClick = Boolean(buttonText) || closeButton;
			}

			const spotlight = dialogs.createSpotlight();
			const targetParams = spotlight.setTarget(targetRef, {
				useHighlight: false,
				type: 'rectangle',
				disableHideByOutsideClick,
			});

			return { spotlight, targetParams };
		}

		static createAhaMoment(props, spotlight, targetParams)
		{
			const {
				targetRef,
				spotlightParams = {},
				closeButton = true,
				buttonText,
				...restProps
			} = props;

			const component = new AhaMoment({
				...restProps,
				spotlightRef: spotlight,
				targetParams,
				closeButton,
				buttonText,
			});
			spotlight.setHandler(component.#eventHandler);
			spotlight.setComponent(component, {
				showPointer: false,
				pointerMargin: spotlightParams?.pointerMargin ?? 2,
				...spotlightParams,
			});

			return component;
		}

		sendAnalytics(params)
		{
			const { analyticsLabel } = this.props;

			if (!analyticsLabel)
			{
				console.warn('\'c_section\', \'c_sub_section\' and \'p1\' for analyticsLabel in AhaMoment is not provided');

				return;
			}

			new AnalyticsEvent({
				tool: 'intranet',
				category: 'aha',
				c_section: analyticsLabel.c_section,
				c_sub_section: analyticsLabel.c_sub_section,
				p1: analyticsLabel.p1,
				...params,
			}).send();
		}

		/**
		 * @param {SpotlightHandlersType} eventName
		 */
		#eventHandler = (eventName) => {
			this[eventName]?.();
		};

		onHide()
		{
			AhaMoment.isShown = false;

			this.props.onHide?.();
		}

		onOutsideClick = () => {
			if (this.props.disableHideByOutsideClick)
			{
				return;
			}

			this.sendAnalytics({
				event: 'сlick_button',
				type: 'close',
			});
		};

		closeSpotlight()
		{
			const { spotlightRef } = this.props;

			if (spotlightRef)
			{
				spotlightRef.hide();
				this.onHide();
			}
		}

		handleOnClose = () => {
			const { spotlightRef, onClose } = this.props;

			if (spotlightRef)
			{
				this.closeSpotlight();

				if (onClose)
				{
					onClose();
				}

				this.sendAnalytics({
					event: 'сlick_button',
					type: 'close',
				});
			}
		};

		handleOnClick = () => {
			const { spotlightRef, onClick, bottomButtonType } = this.props;

			if (spotlightRef)
			{
				this.closeSpotlight();

				if (onClick)
				{
					onClick();
				}

				if (!bottomButtonType)
				{
					console.warn('\'bottomButtonType\' for AhaMoment is not provided');
				}

				this.sendAnalytics({
					event: 'сlick_button',
					type: bottomButtonType,
				});
			}
		};

		render()
		{
			return Hint({
				...this.props,
				handleOnClose: this.handleOnClose,
				handleOnClick: this.handleOnClick,
			});
		}
	}

	AhaMoment.defaultProps = {
		title: null,
		description: null,
		buttonText: null,
		closeButton: true,
		onClick: null,
		onClose: null,
		onHide: null,
		image: null,
		delay: 0,
		spotlightParams: {},
	};

	AhaMoment.propTypes = {
		testId: PropTypes.string.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		buttonText: PropTypes.string,
		image: PropTypes.object,
		closeButton: PropTypes.bool,
		disableHideByOutsideClick: PropTypes.bool,
		onClick: PropTypes.func,
		onClose: PropTypes.func,
		onHide: PropTypes.func,
		delay: PropTypes.number,
		spotlightParams: PropTypes.object,
	};

	module.exports = {
		AhaMoment,
		ButtonType,
	};
});
