<?php
/**
 *  Ratings - Plugin
 *  @copyright Copyright 2013-15 The Digital Ark, Corp.
 *  @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3 or any later version
 */

class Ratings_View_Helper_GetMyRatings extends Zend_View_Helper_Abstract
{
    /**
     * @param $record_id
     * @param $user_id
     * @return int
     */
    public function getMyRatings($record_id, $user_id)
    {
        $db = get_db();
        $ratingsTable = $db->getTable('Rating');
        $ratingsTableName = $db->getTable('Rating')->getTableName();

        $sql = "SELECT rating FROM $ratingsTableName WHERE record_id = $record_id AND user_id = $user_id";

        $result = $ratingsTable->query($sql);
        $myRating = $result->fetchAll();

        if(empty($myRating)) {
            return 0;
        } else {
            return $myRating[0]['rating'];
        }

    }
}
?>