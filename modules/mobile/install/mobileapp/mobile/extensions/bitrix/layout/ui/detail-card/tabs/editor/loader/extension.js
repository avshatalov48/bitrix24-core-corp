/**
 * @module layout/ui/detail-card/tabs/editor/loader
 */
jn.define('layout/ui/detail-card/tabs/editor/loader', (require, exports, module) => {

	/**
	 * @function EditorTabLoader
	 */
	const EditorTabLoader = ({ onRef }) => {
		return View(
			{
				style: {
					marginTop: 12,
				},
				ref: onRef,
			},
			renderSection(),
			renderSection(),
		);
	};

	const renderSection = () => View(
		{
			style: EntityEditorSection.Styles.sectionWrapper,
		},
		View(
			{
				style: EntityEditorSection.Styles.sectionContainer(BX.UI.EntityEditorMode.view),
			},
			renderTitleBar(),
			renderFields(),
			renderSectionManaging(),
		),
	);

	const renderTitleBar = () => View(
		{
			style: {
				...EntityEditorSection.Styles.titleBarContainer,
				marginBottom: 0,
			},
		},
		renderTitle(),
		renderToggleModeButton(),
	);

	const renderTitle = () => View(
		{
			style: EntityEditorSection.Styles.titleContainer,
		},
		renderLine(67, 3, 5, 7),
	);

	const renderToggleModeButton = () => View(
		{
			style: {
				paddingTop: 12,
				paddingBottom: 10,
				paddingLeft: 16,
			},
		},
		renderLine(39, 3, 5, 7),
	);

	const renderFields = () => View(
		{},
		renderField(
			renderLine(65, 3, 19),
			renderLine(54, 6, 15, 18),
		),
		renderField(
			renderLine(65, 3, 20),
			renderLine(124, 6, 15, 18),
		),
		renderField(
			renderLine(34, 3, 20),
			renderLine(101, 6, 15, 18),
		),
		renderField(
			renderLine(34, 3, 20),
			renderLine(124, 6, 15, 18),
		),
		renderField(
			renderLine(34, 3, 20),
			View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 6,
					},
				},
				renderCircle(0),
				renderLine(124, 6, 7, 18),
			),
		),
		renderField(
			renderLine(66, 3, 20),
			renderLine(168, 6, 15, 18),
		),
	);

	const renderField = (...children) => {
		const {
			externalWrapper,
			wrapper,
		} = EntityEditorField.Styles.defaultFieldWrapper(true, BX.UI.EntityEditorMode.view, true);

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
	};

	const renderSectionManaging = () => View(
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
				renderLine(72, 3, 14, 6),
			),
			View(
				{
					style: EntityEditorSection.Styles.sectionManagingTextContainer,
				},
				renderLine(78, 3, 14, 6),
			),
		),
		View(
			{
				style: EntityEditorSection.Styles.sectionManagingTextContainer,
			},
			renderLine(92, 3, 14, 6),
		),
	);

	const renderCircle = (marginTop = 0) => View(
		{
			style: {
				marginRight: 10,
				marginVertical: 18,
				marginTop,
			},
		},
		View({
			style: {
				height: 18,
				width: 18,
				borderRadius: 9,
				backgroundColor: '#dfe0e3',
				position: 'relative',
				left: 0,
				top: 0,
			},
		}),
	);

	const renderLine = (width, height, marginTop = 0, marginBottom = 0) => {
		const style = {
			width,
			height,
			borderRadius: height / 2,
			backgroundColor: '#dfe0e3',
		};

		if (marginTop)
		{
			style.marginTop = marginTop;
		}

		if (marginBottom)
		{
			style.marginBottom = marginBottom;
		}

		return View({
			style,
		});
	};

	module.exports = { EditorTabLoader };
});
