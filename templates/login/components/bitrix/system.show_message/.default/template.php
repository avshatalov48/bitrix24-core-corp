<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
?>

<?php if ($arParams['STYLE'] === 'errortext'): ?>
<div class="intranet-login-error-block"><?=$arParams["MESSAGE"]?></div>
<?php endif ?>