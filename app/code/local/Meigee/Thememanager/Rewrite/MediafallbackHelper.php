<?php
/**
 * Magento
 *
 * @author    Meigeeteam http://www.meaigeeteam.com <nick@meaigeeteam.com>
 * @copyright Copyright (C) 2010 - 2014 Meigeeteam
 *
 */
 
/**
 * Class implementing the media fallback layer for swatches
 */
class Meigee_Thememanager_Rewrite_MediafallbackHelper extends Mage_ConfigurableSwatches_Helper_Mediafallback
{
    function getConfigWidth()
    {
          return Mage::getStoreConfig(Mage_Catalog_Helper_Image::XML_NODE_PRODUCT_SMALL_IMAGE_WIDTH);
    }

    /**
     * For given product, get configurable images fallback array
     * Depends on following data available on product:
     * - product must have child attribute label mapping attached
     * - product must have media gallery attached which attaches and differentiates local images and child images
     * - product must have child products attached
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $imageTypes - image types to select for child products
     * @return array
     */
    public function getConfigurableImagesFallbackArray(Mage_Catalog_Model_Product $product, array $imageTypes,
        $keepFrame = false
    ) {
        $moreViews = Mage::app()->getLayout()->getMConfigResultByAlias('product_shop_col_xs_size');
        if (!$moreViews)
        {
            return parent::getConfigurableImagesFallbackArray($product, $imageTypes, $keepFrame);
        }
        
        if (!$product->hasConfigurableImagesFallbackArray())
        {
			if(Mage::registry('product'))
            {
			    $moreViews = Mage::app()->getLayout()->getMConfigResultByAlias('product_shop_col_xs_size');
			    $imgSize = $moreViews['value']['pm_image_size']['value'];
			}
            else
            {
				$imgSize = $this->getConfigWidth();
			}
            $mapping = $product->getChildAttributeLabelMapping();

            $mediaGallery = $product->getMediaGallery();

            if (!isset($mediaGallery['images'])) {
                return array(); //nothing to do here
            }

            // ensure we only attempt to process valid image types we know about
            $imageTypes = array_intersect(array('image', 'small_image'), $imageTypes);

            $imagesByLabel = array();
            $imageHaystack = array_map(function ($value) {
                return Mage_ConfigurableSwatches_Helper_Data::normalizeKey($value['label']);
            }, $mediaGallery['images']);

            // load images from the configurable product for swapping
			if(isset($mapping)){
				foreach ($mapping as $map) {
					$imagePath = null;

					//search by store-specific label and then default label if nothing is found
					$imageKey = array_search($map['label'], $imageHaystack);
					if ($imageKey === false) {
						$imageKey = array_search($map['default_label'], $imageHaystack);
					}

					//assign proper image file if found
					if ($imageKey !== false) {
						$imagePath = $mediaGallery['images'][$imageKey]['file'];
					}

					$imagesByLabel[$map['label']] = array(
						'configurable_product' => array(
							Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_SMALL => null,
							Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE => null,
						),
						'products' => $map['product_ids'],
					);

					if ($imagePath) {
						$imagesByLabel[$map['label']]['configurable_product']
							[Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_SMALL] =
								$this->_resizeProductImage($product, 'small_image', $keepFrame, $imagePath, false, $imgSize);

						$imagesByLabel[$map['label']]['configurable_product']
							[Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE] =
								$this->_resizeProductImage($product, 'image', $keepFrame, $imagePath, false, $imgSize);
					}
				}
			}

            $imagesByType = array(
                'image' => array(),
                'small_image' => array(),
            );

            // iterate image types to build image array, normally one type is passed in at a time, but could be two
            foreach ($imageTypes as $imageType) {
                // load image from the configurable product's children for swapping
                /* @var $childProduct Mage_Catalog_Model_Product */
                if ($product->hasChildrenProducts()) {
                    foreach ($product->getChildrenProducts() as $childProduct) {
                        if ($image = $this->_resizeProductImage($childProduct, $imageType, $keepFrame, null, true, $imgSize)) {
                            $imagesByType[$imageType][$childProduct->getId()] = $image;
                        }
                    }
                }

                // load image from configurable product for swapping fallback
                if ($image = $this->_resizeProductImage($product, $imageType, $keepFrame, null, true, $imgSize)) {
                    $imagesByType[$imageType][$product->getId()] = $image;
                }
            }

            $array = array(
                'option_labels' => $imagesByLabel,
                Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_SMALL => $imagesByType['small_image'],
                Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE => $imagesByType['image'],
            );

            $product->setConfigurableImagesFallbackArray($array);
        }

        return $product->getConfigurableImagesFallbackArray();
    }

    /**
     * Resize specified type of image on the product for use in the fallback and returns the image URL
     * or returns the image URL for the specified image path if present
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $type
     * @param bool $keepFrame
     * @param string $image
     * @param bool $placeholder
     * @return string|bool
     */
    protected function _resizeProductImage($product, $type, $keepFrame, $image = null, $placeholder = false, $width = null)
    {
        if (is_null($width))
        {
            $width = $this->getConfigWidth();
        }
        
        if (!Mage::app()->getLayout()->getMConfigResultByAlias('product_shop_col_xs_size'))
        {
	        return parent::_resizeProductImage($product, $type, $keepFrame, $image, $placeholder);
        }
       
        $hasTypeData = $product->hasData($type) && $product->getData($type) != 'no_selection';
        if ($image == 'no_selection') {
            $image = null;
        }
      
        if ($hasTypeData || $placeholder || $image)
        {
           $helper = Mage::helper('catalog/image')->init($product, $type, $image);
            if (is_numeric($width))
            {
                $resizeInfo = Mage::helper('thememanager/images')->getResizeInfo($width);
				if(isset($resizeInfo['width'])){
					$helper->keepAspectRatio($resizeInfo['keepAspectRatio']);
					$helper->keepFrame($resizeInfo['keepFrame']);
					$helper->constrainOnly(true)->resize($resizeInfo['width'], $resizeInfo['height']);
				}
            }
            return (string)$helper;
            //return Mage::helper('thememanager/images')->setProductImage($product, $width, $type)->getImageSrc();
        }
        return false;
    }
}

















