/**
 * @module layout/ui/detail-card/tabs/shimmer/editor
 */
jn.define('layout/ui/detail-card/tabs/shimmer/editor', (require, exports, module) => {
	const { BaseShimmer } = require('layout/ui/detail-card/tabs/shimmer');
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { EntityEditorSection } = require('layout/ui/entity-editor/control/section');
	const { EntityEditorMode } = require('layout/ui/entity-editor/editor-enum/mode');

	/**
	 * @class EditorTabShimmer
	 */
	class EditorTabShimmer extends BaseShimmer
	{
		renderContent()
		{
			return View(
				{
					style: {
						marginTop: 12,
					},
				},
				this.renderSection(),
				this.renderSection(),
			);
		}

		renderSection()
		{
			return View(
				{
					style: EntityEditorSection.Styles.sectionWrapper,
				},
				View(
					{
						style: EntityEditorSection.Styles.sectionContainer(EntityEditorMode.view),
					},
					this.renderTitleBar(),
					this.renderFields(),
					this.renderSectionManaging(),
				),
			);
		}

		renderTitleBar()
		{
			return View(
				{
					style: {
						...EntityEditorSection.Styles.titleBarContainer,
						marginBottom: 0,
					},
				},
				this.renderTitle(),
				this.renderToggleModeButton(),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: EntityEditorSection.Styles.titleContainer,
				},
				this.renderLine(67, 3, 5, 7),
			);
		}

		renderToggleModeButton()
		{
			return View(
				{
					style: {
						paddingTop: 12,
						paddingBottom: 10,
						paddingLeft: 16,
					},
				},
				this.renderLine(39, 3, 5, 7),
			);
		}

		renderFields()
		{
			return View(
				{},
				this.renderField(
					this.renderLine(65, 3, 19),
					this.renderLine(54, 6, 15, 18),
				),
				this.renderField(
					this.renderLine(65, 3, 20),
					this.renderLine(124, 6, 15, 18),
				),
				this.renderField(
					this.renderLine(34, 3, 20),
					this.renderLine(101, 6, 15, 18),
				),
				this.renderField(
					this.renderLine(34, 3, 20),
					this.renderLine(124, 6, 15, 18),
				),
				this.renderField(
					this.renderLine(34, 3, 20),
					View(
						{
							style: {
								flexDirection: 'row',
								marginTop: 6,
							},
						},
						this.renderCircle(),
						this.renderLine(124, 6, 7, 18),
					),
				),
				this.renderField(
					this.renderLine(66, 3, 20),
					this.renderLine(168, 6, 15, 18),
				),
			);
		}

		renderField(...children)
		{
			const {
				externalWrapper,
				wrapper,
			} = EntityEditorField.Styles.defaultFieldWrapper(true, EntityEditorMode.view, true);

			return View(
				{
					style: externalWrapper,
				},
				View(
					{
						style: wrapper,
					},
					...children,
				),
			);
		}

		renderSectionManaging()
		{
			return View(
				{
					style: EntityEditorSection.Styles.sectionManagingContainer,
				},
				View(
					{
						style: EntityEditorSection.Styles.sectionManagingSeparator,
					},
					View(
						{
							style: {
								...EntityEditorSection.Styles.sectionManagingTextContainer,
								marginRight: 12,
							},
						},
						this.renderLine(72, 3, 14, 6),
					),
					View(
						{
							style: EntityEditorSection.Styles.sectionManagingTextContainer,
						},
						this.renderLine(78, 3, 14, 6),
					),
				),
				View(
					{
						style: EntityEditorSection.Styles.sectionManagingTextContainer,
					},
					this.renderLine(92, 3, 14, 6),
				),
			);
		}
	}

	module.exports = { EditorTabShimmer };
});
