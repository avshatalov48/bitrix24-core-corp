declare type TitleFieldProps = {
	placeholder: string;
	customBadge?: string;
	value?: string;
	onChange: (value: string) => void;
};

declare type TitleFieldState = {
	isTextEmpty: boolean,
	isFocused: boolean,
};