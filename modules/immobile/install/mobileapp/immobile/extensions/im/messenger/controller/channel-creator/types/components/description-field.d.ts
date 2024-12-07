declare type DescriptionFieldProps = {
	placeholder: string;
	badge: string;
	value?: string;
	onChange: (value: string) => void;
};

declare type DescriptionFieldState = {
	isTextEmpty: boolean,
	isFocused: boolean,
};