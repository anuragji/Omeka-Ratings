<?php
/**
 *  Ratings - Plugin
 *  @copyright Copyright 2013-15 The Digital Ark, Corp.
 *  @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3 or any later version
 */

class Table_Ratings extends Omeka_Db_Table
{
    /**
     * @param Omeka_Db_Select $select
     * @param array $params
     */
    public function applySearchFilters($select, $params)
    {
        $alias = $this->getTableAlias();
        $paramNames = array('record_id',
            'rating',
            'user_id');

        foreach($paramNames as $paramName) {
            if (isset($params[$paramName])) {
                $select->where($alias . '.' . $paramName . ' = ?', array($params[$paramName]));
            }
        }

        if (isset($params['sort'])) {
            switch($params['sort']) {
                case 'asc':
                    $select->order("{$alias}.rating ASC");
                    break;
                case 'dsc':
                    $select->order("{$alias}.rating DSC");
                    break;
            }
        }
    }

    /**
     * Add or update new rating for a specific item by a specific user
     *
     * @param int $record_id
     * @param int $user_id
     * @param int $rating
     */
    public function addRating($record_id, $rating, $user_id) {

        $ratingsTable = $this->getDb()->Ratings;

        // Has this user already rated this item?
        $sql = "SELECT `rating` FROM $ratingsTable WHERE `record_id`=$record_id AND `user_id`=$user_id";
        $result = $this->fetchAll($sql);

        if($result) {
            // update existing rating
            $sql = "UPDATE $ratingsTable SET `rating` = $rating WHERE `record_id` = $record_id AND `user_id` = $user_id";
            $this->query($sql);
        } else {
            // add new rating
            $sql = "INSERT INTO $ratingsTable (`record_id`, `rating`, `user_id`) VALUES ($record_id, $rating, $user_id)";
            $this->query($sql);
        }

    }

    /**
     * Remove rating for a specific item by a specific user
     *
     * @param int $record_id
     * @param int $user_id
     */
    public function removeRating($record_id, $user_id) {

        $ratingsTable = $this->getDb()->Ratings;

        // has this user already rated this item?
        $sql = "DELETE FROM $ratingsTable WHERE `record_id` = $record_id AND `user_id` = $user_id";
        $result = $this->query($sql);

    }

    /**
     * Calculate Average rating for specific item
     *
     * @param $record_id
     * @return float
     */
    public function getAvgRating($record_id) {

        $ratingsTable = $this->getDb()->Ratings;

        $sql = "SELECT rating FROM $ratingsTable WHERE record_id = $record_id";

        $result = $this->query($sql);
        $rating = $result->fetchAll();

        // AVG the result - http://bit.ly/I6Geib
        $avgRating = (floor($rating[0]['rating'] * 2))/2;

        return $avgRating;
    }
}
?>