/**
 * @module user-profile/common-tab/src/block/combine
 */
jn.define('user-profile/common-tab/src/block/combine', (require, exports, module) => {
	const { ViewMode } = require('user-profile/common-tab/src/block/base-view');
	const { Color, Indent, Component } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const {
		BlockOrderRegistry,
		Section,
		SectionRegistry,
	} = require('user-profile/common-tab/src/const/block-config');
	const { Type } = require('type');

	class BlockCombiner
	{
		/**
		 * @param {Array} blocks
		 * @param {boolean} isEditMode
		 * @returns {Array}
		 */
		static combine(blocks, isEditMode)
		{
			if (!Type.isArrayFilled(blocks))
			{
				return [];
			}

			const sections = this.#groupBySections(blocks);

			if (isEditMode)
			{
				return this.#renderEditSections(sections);
			}

			return this.#renderViewSections(sections);
		}

		static #renderEditSections(sections)
		{
			const allBlocks = [];
			for (const blocks of sections.values())
			{
				allBlocks.push(...blocks);
			}
			const processedBlocks = this.#processBlocks(allBlocks);
			const areaStyles = {
				excludePaddingSide: {
					horizontal: true,
				},
			};

			return [this.#createCommonArea(processedBlocks, true, areaStyles)];
		}

		static #groupBySections(blocks)
		{
			const sections = new Map();
			blocks.forEach((block) => {
				const sectionId = BlockOrderRegistry[block.type].section;
				if (!sections.has(sectionId))
				{
					sections.set(sectionId, []);
				}
				sections.get(sectionId).push(block);
			});

			return sections;
		}

		static #renderViewSections(sections)
		{
			const result = [];
			const sortedSectionIds = [...sections.keys()].sort((a, b) => {
				return (SectionRegistry[a]?.order || 0) - (SectionRegistry[b]?.order || 0);
			});

			sortedSectionIds.forEach((sectionId, idx) => {
				const sectionBlocks = sections.get(sectionId);
				const processedBlocks = this.#processBlocks(sectionBlocks);
				const isFirst = idx === 0;
				if (sectionId === Section.HEADER)
				{
					result.push(this.#createHeaderArea(processedBlocks, isFirst));
				}
				else
				{
					result.push(this.#createCommonArea(processedBlocks, isFirst));
				}
			});

			return result;
		}

		static #processBlocks(blocks)
		{
			const result = [];
			let currentGroup = [];

			const uniteBlocks = () => {
				if (currentGroup.length === 0)
				{
					return;
				}

				result.push(this.#createGroupContainer(currentGroup));
				currentGroup = [];
			};

			blocks.forEach((item) => {
				const currentBlock = item.block;

				if (currentBlock.getViewMode() === ViewMode.HALF_WIDTH)
				{
					currentGroup.push(item);
					if (currentGroup.length === 2)
					{
						uniteBlocks();
					}
				}
				else
				{
					uniteBlocks();
					result.push(currentBlock.render());
				}
			});

			uniteBlocks();

			return result;
		}

		static #createGroupContainer(blocks)
		{
			return View(
				{
					style: {
						display: 'flex',
						flexDirection: 'row',
						flexWrap: 'no-wrap',
						justifyContent: 'flex-start',
						alignItems: 'center',
						marginBottom: Component.cardListGap.toNumber(),
					},
				},
				...blocks.map((item, idx) => {
					const currentBlock = item.block;
					const isLast = idx === blocks.length - 1;
					if (!isLast)
					{
						currentBlock.props.style = {
							...currentBlock.props.style,
							marginRight: Indent.XL.toNumber(),
						};
					}

					return currentBlock.render();
				}),
			);
		}

		static #createCommonArea(children, isFirst, areaParams = {})
		{
			return Area(
				{
					...areaParams,
					style: {
						...areaParams.style,
						alignItems: 'stretch',
						justifyContent: 'center',
					},
					isFirst,
				},
				...children,
			);
		}

		static #createHeaderArea(children, isFirst)
		{
			return Area(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					isFirst,
				},
				...children,
			);
		}
	}

	module.exports = {
		BlockCombiner,
	};
});
