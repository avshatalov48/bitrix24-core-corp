<?php

namespace Bitrix\FaceId;

abstract class PhotoIdentifier
{
	abstract public function identify($imageContent, $galleryId = null);
	abstract public function addPerson($imageContent, $meta, $galleryId = null);
	abstract public function createGallery($id);
}