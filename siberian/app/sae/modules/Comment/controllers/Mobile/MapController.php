<?php

class Comment_Mobile_MapController extends Application_Controller_Mobile_Default {

    public function findallAction() {

        // Functionality shared with NewsWall, but only available for FanWall
        if ($this->getCurrentOptionValue()->getCode() != "fanwall") return;

        if($value_id = $this->getRequest()->getParam('value_id')) {
            $application = $this->getApplication();
            $comment = new Comment_Model_Comment();
            $comments = $comment->findAllWithLocationAndPhoto($value_id);

            $data = [
                "collection" => [],
                "page_title" => $this->getCurrentOptionValue()->getTabbarName()
            ];

            foreach($comments as $comment) {
                $data['collection'][] = [
                    "comment_id" => $comment->getId(),
                    "text" => \Siberian\Xss::sanitize($comment->getText()),
                    "image" => $comment->getImageUrl() ? $this->getRequest()->getBaseUrl().$comment->getImageUrl() : null,
                    "latitude" => $comment->getLatitude(),
                    "longitude" => $comment->getLongitude(),
                    "link" => $this->getPath("comment/mobile_view", ["value_id" => $value_id, "comment_id" => $comment->getId()])
                ];
            }

            $this->_sendJson($data);
        }
    }
}