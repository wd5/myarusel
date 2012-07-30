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

			$feedFile = $carousel->client->getFeedFile(true);
			$items = YMLHelper::getItems($feedFile, $carousel->categories, $carousel->onlyCheap);
			foreach ($items as &$itemAttributes) {
				$tempFile = tempnam(sys_get_temp_dir(), 'myarusel-image');
				CurlHelper::downloadToFile($itemAttributes['picture'], $tempFile);
				if (ImageHelper::checkImageCorrect($tempFile))
					$itemAttributes['imageUid'] = $fs->publishFile($tempFile, $itemAttributes['picture']);
				$itemAttributes['carouselId'] = $carousel->id;
				unset($itemAttributes['picture']);
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
