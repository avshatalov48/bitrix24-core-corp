type CopyButtonStyle = {
	borderRadius: number,
	width: number,
	height: number,
};

type CopyButtonProps = {
	text: string,
	onClick: Function,
	style: CopyButtonStyle,
	copyText: string

};