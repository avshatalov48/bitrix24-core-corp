<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
if ($this->__template_is_buffering === true)
{
	if ($this->__template->__page !== "menu")
	{
		$this->__template_html = ob_get_clean();
		$this->IncludeComponentTemplate("menu");
	}
	else
	{
		echo $this->__template_html; 
	}
}
?>