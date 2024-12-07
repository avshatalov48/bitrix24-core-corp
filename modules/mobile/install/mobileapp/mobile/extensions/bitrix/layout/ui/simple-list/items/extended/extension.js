/**
 * @module layout/ui/simple-list/items/extended
 */
jn.define('layout/ui/simple-list/items/extended', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { FieldFactory } = require('layout/ui/fields');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { longDate, dayMonth } = require('utils/date/formats');
	const { mergeImmutable } = require('utils/object');
	const { Moment } = require('utils/date');
	const { WarnLogger } = require('utils/logger/warn-logger');

	/**
	 * @class Extended
	 */
	class Extended extends Base
	{
		hasCounter(counterValue)
		{
			return Number.isInteger(counterValue) && counterValue > 0;
		}

		getMenuFilledColor(svg, counterValue)
		{
			const filledColor = this.hasCounter(counterValue) ? this.colors.accentMainAlert : this.colors.base3;

			return svg.content.replaceAll('%color%', filledColor);
		}

		static getFieldDeepMergeStyles()
		{
			return {
				externalWrapper: {
					marginLeft: 24,
				},
			};
		}

		getStyles()
		{
			return mergeImmutable(super.getStyles(), {
				wrapper: {
					paddingBottom: 12,
					backgroundColor: this.colors.bgPrimary,
				},
				item: {
					position: 'relative',
					backgroundColor: this.colors.bgContentPrimary,
					borderRadius: 12,
				},
				itemContent: {
					paddingTop: 17,
					paddingBottom: 17,
				},
				header: {
					flexDirection: 'column',
					marginRight: 56,
					marginBottom: 4,
					marginLeft: 24,
				},
				title: {
					color: this.colors.base0,
					fontWeight: 'bold',
					fontSize: 18,
				},
				subTitle: {
					flexWrap: 'no-wrap',
					flexDirection: 'row',
				},
				date: {
					flexShrink: 2,
					fontSize: 13,
					color: this.colors.base3,
					paddingTop: 4,
				},
				menu: {
					width: 22,
					height: 22,
				},
				menuContainer: (hasCounter) => ({
					position: 'absolute',
					top: 8,
					right: Application.getPlatform() === 'android' ? 18 : 16,
					width: 42,
					height: 42,
					padding: 9,
					backgroundColor: (hasCounter ? this.colors.accentSoftRed2 : ''),
					borderRadius: 20,
					justifyContent: 'center',
					alignItems: 'center',
				}),
			});
		}

		/**
		 * @private
		 * @return View
		 */
		renderItemContent()
		{
			return View(
				{
					style: this.styles.itemContent,
				},
				this.renderMenuIcon(),
				this.renderHeader(),
				this.renderBody(),
			);
		}

		/**
		 * @private
		 * @returns View
		 */
		renderMenuIcon()
		{
			if (this.isMenuEnabled())
			{
				const { data } = this.props.item;
				const counterValue = (data.activityTotal || 0);

				return View(
					{
						style: this.styles.menuContainer(this.hasCounter(counterValue)),
						onClick: () => this.showMenuHandler(data.id),
						ref: this.props.menuViewRef,
					},
					ImageButton({
						tintColor: this.colors.base3,
						testId: `${this.testId}_CONTEXT_MENU_BTN`,
						style: this.styles.menu,
						svg: {
							content: this.getMenuFilledColor(svgImages.menu, counterValue),
						},
						onClick: () => this.showMenuHandler(data.id),
					}),
				);
			}

			return View();
		}

		/**
		 * @private
		 * @returns View
		 */
		renderHeader()
		{
			const { data } = this.props.item;

			return View(
				{
					testId: `${this.testId}_SECTION`,
					style: this.styles.header,
				},
				Text({
					testId: `${this.testId}_SECTION_TITLE`,
					style: this.styles.title,
					text: (data.name || data.id),
					numberOfLines: 2,
					ellipsize: 'end',
				}),
				View(
					{
						testId: `${this.testId}_SECTION_SUB_TITLE`,
						style: this.styles.subTitle,
					},
					...this.getSubTitleComponents(),
				),
			);
		}

		/**
		 * Implement this method in child class if you need to change components in subtitle
		 *
		 * @private
		 * @returns {Array<LayoutComponent>}
		 */
		getSubTitleComponents()
		{
			return [
				this.renderDate(),
			];
		}

		/**
		 * @private
		 * @returns {FriendlyDate|null}
		 */
		renderDate()
		{
			const { data } = this.props.item;
			if (!data.date || data.date <= 0)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(data.date);
			const defaultFormat = (moment.inThisYear ? dayMonth() : longDate());

			return new FriendlyDate({
				style: this.styles.date,
				moment,
				defaultFormat,
				showTime: true,
				useTimeAgo: true,
			});
		}

		/**
		 * Implement this method in child class if you need to change item body layout
		 *
		 * @private
		 * @returns View
		 */
		renderBody()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderFields('fields'),
				this.renderFields('userFields'),
			);
		}

		/**
		 * @protected
		 * @param section
		 * @returns View
		 */
		renderFields(section)
		{
			const { data } = this.props.item;
			if (!data[section])
			{
				return null;
			}

			const fields = [];
			data[section].forEach((field) => {
				const config = {
					...field.config,
					styles: this.getFieldStyle(field),
					reloadEntityListFromProps: true,
					ellipsize: true,
					deepMergeStyles: Extended.getFieldDeepMergeStyles(),
				};
				const fieldComponent = FieldFactory.create(field.type, {
					testId: `${this.testId}_${field.type}_${field.name}`.toUpperCase(),
					title: field.title,
					value: field.value,
					readOnly: (field.params && field.params.readOnly),
					config,
					multiple: (field.multiple || false),
					isShowAnimate: (field.isShowAnimate || false),
				});
				if (fieldComponent)
				{
					fields.push(fieldComponent);
				}
				else
				{
					(new WarnLogger()).warn(`Field ${field.title} with type ${field.type} is not yet supported.`);
				}
			});

			return View(
				{
					testId: `${this.testId}_FIELDS_LIST`,
					style: {
						marginBottom: 0,
						flexDirection: 'column',
					},
				},
				...fields,
			);
		}

		/**
		 * @private
		 * @param field
		 * @returns {object}
		 */
		getFieldStyle(field)
		{
			if (!field.params || !field.params.styleName)
			{
				return {};
			}

			if (this.styles[field.params.styleName])
			{
				return {
					value: this.styles[field.params.styleName],
				};
			}

			return {};
		}
	}

	const svgImages = {
		menu: {
			content: '<svg width="21" height="5" viewBox="0 0 21 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.83871 2.41935C4.83871 3.75553 3.75553 4.83871 2.41935 4.83871C1.08318 4.83871 0 3.75553 0 2.41935C0 1.08318 1.08318 0 2.41935 0C3.75553 0 4.83871 1.08318 4.83871 2.41935Z" fill="%color%"/><path d="M10.1613 4.83871C11.4975 4.83871 12.5806 3.75553 12.5806 2.41935C12.5806 1.08318 11.4975 0 10.1613 0C8.82512 0 7.74194 1.08318 7.74194 2.41935C7.74194 3.75553 8.82512 4.83871 10.1613 4.83871Z" fill="%color%"/><path d="M17.9032 4.83871C19.2394 4.83871 20.3226 3.75553 20.3226 2.41935C20.3226 1.08318 19.2394 0 17.9032 0C16.5671 0 15.4839 1.08318 15.4839 2.41935C15.4839 3.75553 16.5671 4.83871 17.9032 4.83871Z" fill="%color%"/></svg>',
		},
	};

	module.exports = { Extended };
});
