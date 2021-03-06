<?php

class UpdateCarouselsCommand extends CConsoleCommand
{
	public function actionIndex($id = null) {
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;

		if ($id === null)
			$carousels = Carousel::model()->findAll();
		else
			$carousels = array(Carousel::model()->findByPk($id));

		/** @var $carousel Carousel */
		foreach($carousels as $carousel) {
			if (!($carousel instanceof Carousel))
				throw new CException('Can\'t find carousel');

			if (!empty($carousel->client->logoUid))
				$fs->resizeImage($carousel->client->logoUid, array($carousel->logoSize, $carousel->logoSize));
			$feedFile = $carousel->client->getFeedFile(true);
			$items = YMLHelper::getItems($feedFile, $carousel->categories, $carousel->viewType);
			shuffle($items);
			$items = array_slice($items, 0, 300);
			foreach ($items as $id => &$itemAttributes) {
				$tempFile = tempnam(sys_get_temp_dir(), 'myarusel-image');
				try {
					if (!empty($itemAttributes['picture'])) {
						CurlHelper::downloadToFile($itemAttributes['picture'], $tempFile);
						if (ImageHelper::checkImageCorrect($tempFile)) {
							$itemAttributes['imageUid'] = $fs->publishFile($tempFile, $itemAttributes['picture']);
							$fs->resizeImage($itemAttributes['imageUid'], array($carousel->thumbSize, $carousel->thumbSize));
						}
						unlink($tempFile);
						$itemAttributes['carouselId'] = $carousel->id;
						unset($itemAttributes['picture']);
					} else {
						unset($items[$id]);
					}
				} catch (CurlException $e) {
					unset($items[$id]);
				}
			}
			unset($itemAttributes); // remove link

			/** @var $item Item */
			foreach($carousel->items as $item) {
				$item->delete();
			}

			foreach ($items as $itemAttributes) {
				$item = new Item();
				$item->setAttributes($itemAttributes);
				if (!$item->save())
					throw new CException("Can't save Item:\n".print_r($item->getErrors(), true).print_r($item->getAttributes(), true));
			}
			$carousel->invalidate();
		}
	}
}
