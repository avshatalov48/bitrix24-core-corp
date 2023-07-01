type CompleteButtonStyle = {
	borderRadius: number,
	color: string,
	width: number,
	height: number,
};

type CompleteButtonProps = {
	text: string,
	onClick: Function,
	style: CompleteButtonStyle,
	withoutIcon: boolean,

};