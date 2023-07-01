/**
 * @module layout/ui/detail-card/tabs/timeline/loader
 */
jn.define('layout/ui/detail-card/tabs/timeline/loader', (require, exports, module) => {

	/**
	 * @function TimelineTabLoader
	 */
	const TimelineTabLoader = ({ onRef }) => {
		return View(
			{
				style: {
					marginTop: 15,
					flexDirection: 'column',
				},
				ref: onRef,
			},
			renderDivider(77),
			renderCreateReminder(),
			renderDivider(41),
			renderCallIncoming(),
			renderRegularActivity(96, 221),
			renderRegularActivity(71, 159),
			renderRegularActivity(137, 257),
		);
	};

	const renderDivider = (width) => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'center',
				marginBottom: 16,
			},
		},
		renderDividerLine(),
		renderDividerBadge(width),
	);

	const renderDividerLine = () => View(
		{
			style: {
				height: 1,
				width: '100%',
				backgroundColor: '#dfe0e3',
				position: 'absolute',
				top: 10,
			},
		},
	);

	const renderDividerBadge = (width) => View(
		{
			style: {
				backgroundColor: '#ffffff',
				borderRadius: 100,
				paddingHorizontal: 18,
				height: 21,
				flexDirection: 'row',
				justifyContent: 'center',
			},
		},
		renderLine(width, 2, 9),
	);

	const renderCreateReminder = () => View(
		{
			style: {
				borderRadius: 12,
				backgroundColor: '#ffffff',
				padding: 12,
				marginBottom: 18,
				flexDirection: 'row',
			},
		},
		View(
			{
				style: {
					padding: 8,
					flexDirection: 'column',
					justifyContent: 'center',
				},
			},
			renderCircle(22, 3, 0),
		),
		View(
			{
				style: {
					flex: 1,
				},
			},
			renderLine(76, 6, 8),
			renderLine(222, 3, 13),
			renderLine(55, 3, 13, 5),
		),
	);

	const renderCallIncoming = () => View(
		{
			style: {
				borderRadius: 12,
				padding: 0,
				marginBottom: 16,
				backgroundColor: '#ffffff',
			},
		},
		View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
				},
			},
			renderCallIncomingLogo(),
			renderCallIncomingHeader(),
		),
		renderCallIncomingContent(),
		renderCallIncomingFooter(),
	);

	const renderCallIncomingLogo = () => View(
		{
			style: {
				paddingTop: 12,
				paddingLeft: 10,
				paddingBottom: 12,
			},
		},
		View(
			{
				style: {
					width: 80,
					height: 80,
					borderRadius: 12,
					backgroundColor: '#dfe0e3',
				},
			},
		),
	);

	const renderCallIncomingHeader = () => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				flexGrow: 1,
			},
		},
		View(
			{
				style: {
					paddingTop: 12,
					paddingLeft: 12,
					flexDirection: 'column',
					flex: 1,
				},
			},
			renderCallIncomingTitle(),
			renderCallIncomingTag(),
			renderCallIncomingTime(),
		),
		View(
			{
				style: {
					flexDirection: 'row',
					height: 38,
					marginTop: 3,
				},
			},
			renderCallIncomingUser(),
		),
	);

	const renderCallIncomingTitle = () => View(
		{
			style: {
				flexDirection: 'row',
				flexWrap: 'wrap',
			},
		},
		renderLine(145, 8, 6),
	);

	const renderCallIncomingTag = () => renderLine(49, 20, 9);

	const renderCallIncomingTime = () => renderLine(26, 3, 15);

	const renderCallIncomingUser = () => View(
		{
			style: {
				width: 38,
				justifyContent: 'center',
				alignItems: 'center',
			},
		},
		View({
			style: {
				width: 20,
				height: 20,
				borderRadius: 20,
				backgroundColor: '#dfe0e3',
			},
		}),
	);

	const renderCallIncomingContent = () => View(
		{
			style: {
				paddingHorizontal: 12,
				paddingTop: 0,
				paddingBottom: 9,
				flexDirection: 'row',
				flexWrap: 'wrap',
				alignItems: 'center',
			},
		},
		View(
			{
				style: {
					flexDirection: 'column',
					width: 130,
				},
			},
			renderLine(43, 3, 6),
			renderLine(71, 3, 26),
		),
		View(
			{
				style: {
					flexDirection: 'column',
					flex: 1,
				},
			},
			renderLine(181, 6, 6),
			renderLine(123, 6, 26),
		),
	);

	const renderCallIncomingFooter = () => View(
		{
			style: {
				padding: 12,
				flexDirection: 'row',
				flexWrap: 'wrap',
				justifyContent: 'space-between',
			},
		},
		renderLine(177, 39, 11),
		View(
			{
				style: {
					flexDirection: 'row',
					paddingTop: 20,
				},
			},
			renderCircle(17),
			View(
				{
					style: {
						marginLeft: 18,
					},
				},
				renderCircle(17),
			),
		),
	);

	const renderRegularActivity = (titleWidth, textWidth) => View(
		{
			style: {
				borderRadius: 12,
				padding: 0,
				marginBottom: 16,
				backgroundColor: '#ffffff',
			},
		},
		View(
			{
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
				},
			},
			renderRegularActivityHeader(titleWidth, textWidth),
		),
	);

	const renderRegularActivityHeader = (titleWidth, textWidth) => View(
		{
			style: {
				flexDirection: 'row',
				justifyContent: 'space-between',
				flexGrow: 1,
			},
		},
		View(
			{
				style: {
					paddingTop: 17,
					paddingLeft: 12,
					flexDirection: 'column',
					flex: 1,
				},
			},
			renderRegularActivityTitle(titleWidth),
			renderRegularActivityText(textWidth),
		),
		View(
			{
				style: {
					flexDirection: 'row',
					height: 38,
					marginTop: 3,
				},
			},
			renderRegularActivityUser(),
		),
	);

	const renderRegularActivityTitle = (width) => View(
		{
			style: {
				flexDirection: 'row',
				flexWrap: 'wrap',
			},
		},
		renderLine(width, 8),
	);

	const renderRegularActivityText = (width) => renderLine(width, 6, 22, 20);

	const renderRegularActivityUser = () => View(
		{
			style: {
				width: 38,
				justifyContent: 'center',
				alignItems: 'center',
			},
		},
		View({
			style: {
				width: 20,
				height: 20,
				borderRadius: 20,
				backgroundColor: '#dfe0e3',
			},
		}),
	);

	const renderCircle = (size, marginTop = 0, marginVertical = 18) => View(
		{
			style: {
				marginRight: 10,
				marginVertical,
				marginTop,
			},
		},
		View({
			style: {
				height: size,
				width: size,
				borderRadius: size / 2,
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

	module.exports = { TimelineTabLoader };
});
