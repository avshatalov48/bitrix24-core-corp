<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var int $key */
/** @var int $count */
/** @var int $screenWidth */
/** @var bool $vertical */
/** @var array $file */
/** @var string $id */
/** @var array $gridBlockClassesList */

$imgClassList = [
	'disk-ui-file-thumbnails-grid-img'
];

switch($key)
{
	case 0:
		if ($count === 1)
		{
			$maxWidth = $screenWidth;
			$maxHeight = $screenWidth;
		}
		elseif (
			$count === 2
			|| $count === 3
		)
		{
			$maxWidth = ($vertical ? $screenWidth / 2 : $screenWidth);
			$maxHeight = ($vertical ? $screenWidth : $screenWidth / 2);
		}
		elseif ($count >= 4)
		{
			$maxWidth = ($vertical ? $screenWidth * 2/3 : $screenWidth);
			$maxHeight = ($vertical ? $screenWidth : $screenWidth * 2/3);
		}
		break;
	case 1:
		if ($count === 2)
		{
			$maxWidth = ($vertical ? $screenWidth / 2 : $screenWidth);
			$maxHeight = ($vertical ? $screenWidth : $screenWidth / 2);
		}
		elseif ($count === 3)
		{
			$maxWidth = $maxHeight = $screenWidth / 2;
		}
		elseif ($count >= 4)
		{
			$maxWidth = $maxHeight = $screenWidth / 3;
		}
		break;
	case 2:
		if ($count === 3)
		{
			$maxWidth = $maxHeight = $screenWidth / 2;
		}
		elseif ($count >= 4)
		{
			$maxWidth = $maxHeight = $screenWidth / 3;
		}
		break;
	default: // 3
		$maxWidth = $maxHeight = $screenWidth / 3;
}

if (
	$count > 1
	&& (
		$file['BASIC']['width'] > $maxWidth
		|| $file['BASIC']['height'] > $maxHeight
	)
)
{
	$imgClassList[] = 'disk-ui-file-thumbnails-grid-img-cover';
}

?>
<figure data-bx-disk-image-container="Y" class="disk-ui-file-thumbnails-grid-item disk-ui-file-thumbnails-grid-item-<?= ($key+1) ?>">
	<img<?php
	?> class="<?= implode(' ', $imgClassList) ?>"<?php
	?> id="<?= $id ?>"<?php
	?> src="<?= CMobileLazyLoad::getBase64Stub() ?>"<?php
	?> data-src="<?= $file['BASIC']['src'] ?>"<?php
	?> alt="<?= htmlspecialcharsbx($file['NAME']) ?>"<?php
	?> border="0"<?php
	?> data-bx-title="<?= htmlspecialcharsbx($file['NAME']) ?>"<?php
	?> data-bx-size="<?= $file['SIZE'] ?>"<?php
	?> data-bx-width="<?= $file['BASIC']['width'] ?>"<?php
	?> data-bx-height="<?= $file['BASIC']['height'] ?>"<?php
	?> bx-attach-file-id="<?= $file['FILE_ID'] ?>"<?php
	if ($file['XML_ID'])
	{
		?> bx-attach-xml-id="<?= $file['XML_ID'] ?>"<?php
	}
	?> data-bx-src="<?= $file['BASIC']['src'] ?>"<?php
	?> data-bx-preview="<?= $file['PREVIEW']['src'] ?>"<?php
	?> data-bx-image="<?= $file['BASIC']['src'] ?>"<?php
	if (
		$count === 1
		&& !in_array('disk-ui-file-thumbnails-grid-flexible-width-img', $gridBlockClassesList, true)
	)
	{
		?> width="<?= $file['BASIC']['width'] ?>"<?php
		?> height="<?= $file['BASIC']['height'] ?>"<?php
	}
	?> />
	<?php
	if (
		$key === 3
		&& $count > 4
	)
	{
		?>
		<span class="disk-ui-file-thumbnails-grid-number">+<?= ($count-4) ?></span>
		<?php
	}
	?>
</figure>
