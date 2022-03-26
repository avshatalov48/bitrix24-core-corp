<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $file */
/** @var string $id */

?>
<img style="display: none"<?php
?> class="disk-ui-file-thumbnails-grid-img"<?php
?> id="<?= $id ?>"<?php
?> src="<?= CMobileLazyLoad::getBase64Stub() ?>"<?php
?> data-src="<?= $file['BASIC']['src'] ?>"<?php
?> alt="<?= htmlspecialcharsbx($file['NAME']) ?>"<?php
?> border="0"<?php
?> data-bx-title="<?= htmlspecialcharsbx($file['NAME']) ?>"<?php
?> data-bx-size="<?= $file['SIZE'] ?>"<?php
?> data-bx-width="<?= $file['BASIC']['width'] ?>"<?php
?> data-bx-height="<?=$file['BASIC']['height']?>"<?php
?> bx-attach-file-id="<?= $file['FILE_ID'] ?>"<?php
if ($file['XML_ID'])
{
	?> bx-attach-xml-id="<?= $file['XML_ID'] ?>"<?php
}
?> data-bx-src="<?= $file['BASIC']['src'] ?>"<?php
?> data-bx-preview="<?= $file['PREVIEW']['src'] ?>"<?php
?> data-bx-image="<?= $file['BASIC']['src'] ?>"<?php
?> />
<?php
