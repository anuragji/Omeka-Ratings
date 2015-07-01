<?php
/**
 *  Ratings - Plugin
 *  @copyright Copyright 2013-15 The Digital Ark, Corp.
 *  @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3 or any later version
 */
?>
class Ratings_View_Helper_GetAvgRatings extends Zend_View_Helper_Abstract
{
    /**
     * @param $record_id
     * @return float - returns rating
     */
    public function getAvgRatings($record_id)
    {
        $db = get_db();
        $ratingsTable = $db->getTable('Rating');
        $ratingsTableName = $db->getTable('Rating')->getTableName();

        $sql = "SELECT AVG(rating) FROM $ratingsTableName WHERE record_id = $record_id";

        $result = $ratingsTable->query($sql);
        $rating = $result->fetchAll();

        // AVG the result - http://bit.ly/I6Geib
        $avgRating = (floor($rating[0]['AVG(rating)'] * 2))/2;

        return $avgRating;
    }
}
?>