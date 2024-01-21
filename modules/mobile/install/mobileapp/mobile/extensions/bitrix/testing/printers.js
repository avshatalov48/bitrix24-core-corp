/**
 * @module testing/printers
 */
jn.define('testing/printers', (require, exports, module) => {

	const AppTheme = require('apptheme');

	/**
	 * @abstract
	 */
	class Printer
	{
		/**
		 * @abstract
		 * @param {TestingReport} report
		 */
		print(report) {}
	}

	class ConsolePrinter extends Printer
	{
		print(report)
		{
			const recordHandlers = {
				groupStart: (r) => this.printGroupStart(r),
				groupEnd: (r) => this.printGroupEnd(r),
				success: (r) => this.printSuccess(r),
				fail: (r) => this.printFail(r),
			};

			const nothing = () => {};

			report.log.forEach((record) => {
				const handler = recordHandlers[record.type] || nothing;
				handler(record);
			});
		}

		printGroupStart({ title })
		{
			console.group(title);
		}

		printGroupEnd(record)
		{
			console.groupEnd();
		}

		printSuccess({ message })
		{
			console.log(`✅ [ok] ${message}`);
		}

		printFail({ message, expected, actual })
		{
			console.group(`🛑 [fail] ${message}`);
			console.log('expected:', expected);
			console.log('actual:', actual);
			console.groupEnd();
		}
	}

	class JnLayoutPrinter extends Printer
	{
		print(report)
		{
			const recordHandlers = {
				groupStart: (r) => this.renderGroupStart(r),
				success: (r) => this.renderSuccess(r),
				fail: (r) => this.renderFail(r),
			};

			const nothing = () => null;

			return View(
				{
					style: {
						paddingHorizontal: 12,
						marginBottom: 24,
					},
				},
				...report.log.map((record) => {
					const handler = recordHandlers[record.type] || nothing;

					return handler(record);
				}),
			);
		}

		renderGroupStart({ title })
		{
			return View(
				{
					style: {
						marginTop: 24,
						marginBottom: 12,
					},
				},
				Text({
					text: title,
					style: {
						fontWeight: 'bold',
						fontSize: 18,
					},
				}),
			);
		}

		renderSuccess({ message })
		{
			return View(
				{
					style: {
						marginBottom: 6,
					},
				},
				Text({
					text: `✅ [ok] ${message}`,
					fontSize: 16,
				}),
			);
		}

		renderFail({ message, expected, actual })
		{
			return View(
				{
					style: {
						marginBottom: 12,
					},
				},
				Text({
					text: `🛑 [fail] ${message}`,
					style: {
						fontSize: 16,
						fontWeight: 'bold',
						color: AppTheme.colors.accentMainAlert,
					},
				}),
				View(
					{
						style: {
							marginLeft: 28,
							marginTop: 4,
						},
					},
					Text({
						text: `expected: ${JSON.stringify(expected)}`,
						style: {
							fontSize: 14,
						},
					}),
					Text({
						text: `actual: ${JSON.stringify(actual)}`,
						style: {
							fontSize: 14,
						},
					}),
				),
			);
		}
	}

	module.exports = {
		ConsolePrinter,
		JnLayoutPrinter,
	};
});
