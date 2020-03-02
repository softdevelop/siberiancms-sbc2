<?php

/**
 * Class Comment_Mobile_AnswerController
 */
class Comment_Mobile_AnswerController extends Application_Controller_Mobile_Default
{

    /**
     * @var array
     */
    public $cache_triggers = [
        "add" => [
            "tags" => [
                "feature_paths_valueid_#VALUE_ID#",
                "assets_paths_valueid_#VALUE_ID#",
            ],
        ],
        "addlike" => [
            "tags" => [
                "feature_paths_valueid_#VALUE_ID#",
                "assets_paths_valueid_#VALUE_ID#",
            ],
        ],
    ];

    public function findallAction()
    {

        if ($comment_id = $this->getRequest()->getParam('comment_id')) {

            $comment = new Comment_Model_Comment();
            $comment->find($comment_id);
            $customer = new Customer_Model_Customer();
            $noLogo = $customer->getImageLink();
            if ($comment->getId()) {

                $answer = new Comment_Model_Answer();
                $answers = $answer->findByComment($comment->getId());
                $data = [];
                foreach ($answers as $answer) {
                    $data[] = [
                        "id" => $answer->getId(),
                        "author" => $answer->getCustomerName(),
                        "picture" => $noLogo,
                        "message" => \Siberian\Xss::sanitize($answer->getText()),
                        "created_at" => $this->_durationSince($answer->getCreatedAt())
                    ];

                }

                $this->_sendJson($data);
            }

        }

    }

    public function addAction()
    {

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $customer_id = $this->getSession()->getCustomerId();

                if (empty($customer_id) OR empty($data['comment_id']) OR empty($data['text'])) {
                    throw new Exception($this->_("#106: An error occurred while saving"));
                }

                $comment_id = $data['comment_id'];
                $text = \Siberian\Xss::sanitize($data['text']);

                $answer = new Comment_Model_Answer();
                $answer->setCommentId($comment_id)
                    ->setCustomerId($customer_id)
                    ->setText($text)
                    ->save();

                $html = ['success' => 1];

                $message = $this->_('Your message has been successfully saved.');
                if (!$answer->isVisible()) $message .= ' ' . $this->_('It will be visible only after validation by our team.');
                else {

                    $customer = $this->getSession()->getCustomer();

                    $html["answer"] = [
                        "author" => $customer->getFirstname() . ' ' . mb_substr($customer->getLastname(), 0, 1) . '.',
                        "picture" => $customer->getImageLink(),
                        "message" => \Siberian\Xss::sanitize($answer->getText()),
                        "created_at" => $answer->getFormattedCreatedAt()
                    ];

                }

                $html["message"] = $message;

            } catch (Exception $e) {
                $html = ['error' => 1, 'message' => $e->getMessage()];
            }

            $this->_sendJson($html);
        }

    }

    public function addlikeAction()
    {

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $customer_id = $this->getSession()->getCustomerId();
                $ip = md5($_SERVER['REMOTE_ADDR']);
                $ua = md5($_SERVER['HTTP_USER_AGENT']);
                $like = new Comment_Model_Like();

                if (!$like->findByIp($data['comment_id'], $customer_id, $ip, $ua)) {

                    $like->setCommentId($data['comment_id'])
                        ->setCustomerId($customer_id)
                        ->setCustomerIp($ip)
                        ->setAdminAgent($ua);

                    $like->save();

                    $message = $this->_('Your like has been successfully added');
                    $html = ['success' => 1, 'message' => $message];

                } else {
                    throw new Exception($this->_("You can't like more than once the same news"));
                }

            } catch (Exception $e) {
                $html = ['error' => 1, 'message' => $e->getMessage()];
            }

            $this->_sendHtml($html);
        }

    }

}