<?php

/**
 * Class Places_Mobile_Api_PlacesController
 */
class Places_Mobile_Api_PlacesController extends Application_Controller_Mobile_Default
{
    /**
     *
     */
    public function findAction()
    {

        if ($value_id = $this->getRequest()->getParam('value_id')
            && $place_id = $this->getRequest()->getParam('place_id')) {

            try {

                $pageRepository = new Cms_Model_Application_Page();
                $page = $pageRepository->find($place_id);

                $json = [];

                $blocks = $page->getBlocks();
                $data = ["blocks" => []];

                foreach ($blocks as $block) {

                    if ($block->getType() === "address") {
                        $json = $this->_toJson($page, $block);
                        // only one address per place
                        break;
                    }

                }
                $data = ["place" => $json];
            } catch (Exception $e) {
                $data = ['error' => 1, 'message' => $e->getMessage()];
            }

        } else {
            $data = [
                'error' => true,
                'message' => 'An error occurred during process. Please try again later.'
            ];
        }

        $this->_sendJson($data);

    }

    public function findallAction()
    {

        if ($value_id = $this->getRequest()->getParam('value_id')) {

            $latitude = $this->getRequest()->getParam('latitude');
            $longitude = $this->getRequest()->getParam('longitude');


            if ($latitude && $longitude) {
                // order by distance from specified latitude/longitude   

                $rad = pi() / 180;
                $lat_a = $latitude * $rad;
                $lon_a = $longitude * $rad;
            }

            try {

                $pageRepository = new Cms_Model_Application_Page();
                $pages = $pageRepository->findAll(['value_id' => $value_id]);

                $json = [];

                foreach ($pages as $page) {

                    $blocks = $page->getBlocks();
                    $data = ["blocks" => []];

                    foreach ($blocks as $block) {

                        if ($block->getType() == "address") {
                            $place = $this->_toJson($page, $block);
                            // only one address per place

                            if ($lat_a && $lon_a && $block->getLatitude() && $block->getLongitude()) {
                                // calculate distance from specified latitude/longitude   
                                $lat_b = $block->getLatitude() * $rad;
                                $lon_b = $block->getLongitude() * $rad;

                                $distance = 2 * asin(sqrt(pow(sin(($lat_a - $lat_b) / 2), 2) + cos($lat_a) * cos($lat_b) * pow(sin(($lon_a - $lon_b) / 2), 2)));
                                $distance *= 6371000 * 10000;
                                $place["distance"] = round($distance);
                            }

                            $json[] = $place;

                            break;
                        }

                    }

                }

                if ($latitude && $longitude) {
                    // order by distance from specified latitude/longitude   
                    usort($json, ['Places_Model_Place', 'sortPlacesByDistance']);
                }
                $data = ["places" => $json];
                $option = $this->getCurrentOptionValue();
                $data["page_title"] = $option->getTabbarName();

            } catch (Exception $e) {
                $data = ['error' => 1, 'message' => $e->getMessage()];
            }


        } else {
            $data = ['error' => 1, 'message' => 'An error occurred during process. Please try again later.'];
        }
        $this->_sendJson($data);
    }

    public function _toJson($page, $address)
    {

        $json = [
            "id" => $page->getId(),
            "title" => $page->getTitle(),
            "content" => $page->getContent(),
            "picture" => $page->getPictureUrl(),
            "url" => $this->getUrl("places/mobile_details/index", ["value_id" => $page->getValueId(), "place_id" => $page->getId()]),
            "address" => [
                "id" => $address->getId(),
                "position" => $address->getPosition(),
                "block_id" => $address->getBlockId(),
                "label" => $address->getLabel(),
                "address" => $address->getAddress(),
                "latitude" => $address->getLatitude(),
                "longitude" => $address->getLongitude(),
                "show_address" => $address->getShowAddress(),
                "show_geolocation_button" => $address->getGeolocationButton()]
        ];

        return $json;

    }


}