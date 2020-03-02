<?php

/**
 * Class Media_Mobile_Api_Music_TrackController
 */
class Media_Mobile_Api_Music_TrackController extends Application_Controller_Mobile_Default
{
    /**
     * @param $track
     * @return array
     */
    public function _toJson($track)
    {
        $album_cover = null;
        $albumCover = $track->getData('artwork_url');
        if (stripos($albumCover, 'http') === false) {
            if (!file_exists(Core_Model_Directory::getBasePathTo($albumCover))) {
                $album_cover = $this->getRequest()->getBaseUrl() . Media_Model_Library_Image::getImagePathTo("/musics/default_album.jpg");
            } else {
                $album_cover = $this->getRequest()->getBaseUrl() . $albumCover;
            }
        }

        $json = [
            "id" => $track->getId(),
            "name" => $track->getName(),
            "artistName" => $track->getArtistName(),
            "albumName" => $track->getAlbumName(),
            "albumCover" => $album_cover,
            "albumId" => $track->getAlbumId(),
            "duration" => $track->getDuration(),
            "streamUrl" => $track->getStreamUrl(),
            "purchaseUrl" => $track->getPurchaseUrl()
        ];

        if ($track->getType() != 'podcast') {
            if ($track->getType() == 'itunes' && $track->getPrice() > 0) {
                $json["duration"] = "29000";
            }
            $json["formatedDuration"] = $track->getFormatedDuration($track->getDuration());
            if ($track->getType() == "soundcloud") {
                $json["streamUrl"] = $json["streamUrl"] . "?client_id=" . Api_Model_Key::findKeysFor("soundcloud")->getClientId();
            }
        } else {
            $json["formatedDuration"] = $track->getFormatedDuration();
        }

        return $json;
    }

    public function findbyalbumAction()
    {

        if ($value_id = $this->getRequest()->getParam('value_id')
            && ($album_id = $this->getRequest()->getParam('album_id') OR $track_id = $this->getRequest()->getParam('track_id'))) {

            try {

                $album_tracks = [];
                $json = [];

                if ($album_id) {

                    $album = new Media_Model_Gallery_Music_Album();
                    $album->find($album_id);

                    $album_tracks = $album->getAllTracks(true);

                } else if ($track_id) {

                    $track = new Media_Model_Gallery_Music_Track();
                    $track->find($track_id);

                    $album_tracks = [$track];

                }

                foreach ($album_tracks as $track) {
                    $json[] = $this->_toJson($track);
                }

                $data = ["tracks" => $json];

            } catch (Exception $e) {

            }

        } else {
            $data = ['error' => 1, 'message' => 'An error occurred during process. Please try again later.'];
        }
        $this->_sendHtml($data);
    }

    /** API v2 introduced in Siberian 5.0 with Progressive Web Apps. */


}