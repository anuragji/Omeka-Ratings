<?php
/**
 *  Ratings - Plugin
 *  @copyright Copyright 2013-15 The Digital Ark, Corp.
 *  @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3 or any later version
 */
?>
<?php
class Ratings_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init()
    {
        $this->_helper->db->setDefaultModelName('Rating');
    }

    public function indexAction()
    {
        $db = $this->_helper->db;
    }

    public function addAction()
    {
        $db = $this->_helper->db->getTable('Ratings');

        // Allow only AJAX requests.
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->redirector->gotoUrl('/');
        }

        // get request parameters
        $record_id = $this->_getParam('record_id');
        $user_id = $this->_getParam('user_id');
        $rating = $this->_getParam('rating');

        // validation needed?
        if (isset($record_id) && isset($user_id) && isset($rating) ) {
            $db->addRating($record_id, $rating, $user_id);
        }

        // get new AVG rating
        $updatedAvgRating = $db->getAvgRating($record_id);

        // write AVGRating to item
        $item = get_record_by_id('Item', $record_id);

        // Set values for update_item
        $metaData = array('overwriteElementTexts' => true);
        $elementText = array( 'Ratings' => array( 'Average Rating' => array( array('text' => $updatedAvgRating, 'html' => false))));

        // Update Item
        $itemUpdated = update_item($item, $metaData, $elementText);

        $result = array(
            'updatedAvgRating' => $updatedAvgRating
        );

        $this->_response->setBody(json_encode($result));


    }

    public function unrateAction()
    {
        $db = $this->_helper->db->getTable('Ratings');

        // Allow only AJAX requests.
        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->redirector->gotoUrl('/');
        }

        $record_id = $this->_getParam('record_id');
        $user_id = $this->_getParam('user_id');

        // validation needed?
        if (isset($record_id) && isset($user_id)) {
            $this->_helper->db->getTable('Ratings')->removeRating($record_id, $user_id);
        }

        // update AVG rating
        $avgRating = $db->getAvgRating($record_id);

        // Get Item
        $item = get_record_by_id('Item', $record_id);

        // Update the average rating for this item
        $metaData = array('overwriteElementTexts' => true);
        $elementText = array( 'Ratings' => array( 'Average Rating' => array( array('text' => $avgRating, 'html' => false))));
        $itemUpdated = update_item($item, $metaData, $elementText);
    }


}
?>