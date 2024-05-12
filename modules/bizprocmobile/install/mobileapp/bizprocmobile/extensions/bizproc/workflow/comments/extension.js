/**
 * @module bizproc/workflow/comments
*/
jn.define('bizproc/workflow/comments', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');

	class WorkflowComments extends PureComponent
	{
		render()
		{
			return View(
				{
					style: {
						marginHorizontal: 18,
						marginTop: 12,
						marginBottom: 40,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'space-between',
						},
					},
					Text({
						style: {
							fontSize: 12,
							fontWeight: '400',
							color: AppTheme.colors.base4,
						},
						text: Loc.getMessage('MBP_WORKFLOW_COMMENTS_TITLE'),
					}),
					View(
						{
							style: {
								flexDirection: 'row',
								borderWidth: 1,
								borderColor: AppTheme.colors.base6,
								borderRadius: 55,
								paddingLeft: 5,
								paddingRight: 9,
								height: 24,
							},
						},
						Image({
							style: {
								width: 24,
								height: 24,
							},
							svg: {
								content: `
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path
											opacity="0.7"
											fill-rule="evenodd"
											clip-rule="evenodd"
											d="M17.4799 9.90391L14.472 9.9045C14.3855 9.9045 14.3018 9.85442 14.2694 9.7749C14.1339 9.43617 14.1392 9.05444 14.2877 8.71807C14.5368 7.99291 14.5651 7.20942 14.3695 6.46776C14.1651 5.95761 14.1109 5.02567 13.1678 4.98267C12.8727 5.02626 12.6152 5.20357 12.4668 5.46218C12.4403 5.50813 12.4291 5.56233 12.4291 5.61476C12.4291 5.61476 12.4724 6.51967 12.4291 7.14874C12.3858 7.77782 11.182 9.38021 10.4168 10.3976C10.3685 10.4624 10.3007 10.5048 10.2206 10.5171C9.93371 10.5601 9.38648 10.6329 9.18923 10.6589C9.14306 10.665 9.11606 10.723 9.11606 10.7502C9.11606 12.022 9.11606 13.9244 9.11606 16.4574C9.11606 16.4784 9.14139 16.5241 9.1864 16.5316C9.34093 16.5571 9.72745 16.6278 10.1175 16.7532C10.6076 16.9105 11.0159 17.2828 11.8329 17.5585C11.8759 17.5732 11.9237 17.5809 11.969 17.5809H15.8057C16.3012 17.4925 16.6699 17.069 16.6953 16.5606C16.7023 16.2773 16.6458 15.9968 16.5297 15.7394C16.5132 15.7023 16.535 15.6634 16.5751 15.6558C17.067 15.5662 17.682 14.6325 16.9244 13.8803C16.9044 13.8608 16.9079 13.8343 16.935 13.8272C17.3527 13.7206 17.6726 13.3872 17.7704 12.9713C17.8081 12.8122 17.7945 12.6479 17.7474 12.4918C17.692 12.3056 17.6036 12.1312 17.4864 11.9757C17.457 11.9369 17.4735 11.8862 17.5206 11.8709C17.9365 11.7301 18.2228 11.3324 18.2198 10.88C18.2675 10.4364 17.9111 9.9045 17.4799 9.90391ZM7.93847 10.1443H5.76415C5.65105 10.1443 5.56504 10.2444 5.58448 10.3534L6.84748 17.4489C6.86692 17.5579 6.96294 17.6375 7.07546 17.6375H7.87957C8.00386 17.6375 8.1046 17.5391 8.1046 17.4177L8.12227 10.3245C8.12227 10.225 8.04039 10.1443 7.93847 10.1443Z"
											fill="${AppTheme.colors.base4}"
										/>
									</svg>
								`,
							},
						}),
						Text({
							style: {
								fontWeight: '400',
								fontSize: 12,
								color: AppTheme.colors.base4,
							},
							text: Loc.getMessage('MBP_WORKFLOW_COMMENTS_LIKE'),
						}),
					),
				),
				Image({
					style: {
						marginTop: 33,
						width: 60,
						height: 60,
						alignSelf: 'center',
					},
					svg: {
						content: `
							<svg width="61" height="60" viewBox="0 0 61 60" fill="none" xmlns="http://www.w3.org/2000/svg">
								<g opacity="0.3">
									<path 
										fill-rule="evenodd"
										clip-rule="evenodd"
										d="M30.5 47C39.8888 47 47.5 39.3888 47.5 30C47.5 20.6112 39.8888 13 30.5 13C21.1112 13 13.5 20.6112 13.5 30C13.5 39.3888 21.1112 47 30.5 47ZM50.5 30C50.5 41.0457 41.5457 50 30.5 50C19.4543 50 10.5 41.0457 10.5 30C10.5 18.9543 19.4543 10 30.5 10C41.5457 10 50.5 18.9543 50.5 30Z"
										fill="${AppTheme.colors.base4}"
									/>
									<path
										fill-rule="evenodd"
										clip-rule="evenodd"
										d="M30.2873 18.5163C31.1157 18.5163 31.7873 19.1878 31.7873 20.0163V28.7748L38.9198 28.7748C39.7482 28.7748 40.4198 29.4463 40.4198 30.2748C40.4198 31.1032 39.7482 31.7748 38.9198 31.7748L30.2873 31.7748C29.8895 31.7748 29.5079 31.6168 29.2266 31.3355C28.9453 31.0542 28.7873 30.6727 28.7873 30.2748V20.0163C28.7873 19.1878 29.4589 18.5163 30.2873 18.5163Z"
										fill="${AppTheme.colors.base4}"
									/>
								</g>
							</svg>
						`,
					},
				}),
				Text({
					style: {
						fontWeight: '400',
						fontSize: 14,
						color: AppTheme.colors.base4,
						alignSelf: 'center',
						marginTop: 4,
					},
					text: Loc.getMessage('MBP_WORKFLOW_COMMENTS_STUB'),
				}),
			);
		}
	}

	module.exports = { WorkflowComments };
});
