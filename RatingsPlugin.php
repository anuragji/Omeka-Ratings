<?php
/**
 *  Ratings - Plugin
 *  @copyright Copyright 2013-15 The Digital Ark, Corp.
 *  @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3 or any later version
 */

// TODO: Upon install, set configured default rating for each item

define('RATINGS_PLUGIN_DIR', PLUGIN_DIR . '/Ratings');
define('ELEMENT_SET_NAME', 'Ratings');
define('ELEMENT_NAME', 'Average Rating');

class RatingsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'config_form',
        'config',
        'install',
        'uninstall',
        'upgrade',
        'define_acl',
        'public_head',
        'after_delete_item',
        'public_display_ratings',
        'public_items_browse_each',
        'public_ratings_show_with_favorites',
        'show_ratings_with_favorites_footer',
        'before_save_item'
    );

    private $_elementSet;
    private $_elements;
    private $_elementSetName;

    public function __construct()
    {
        parent::__construct();

        // Set the elements.
        include 'elements.php';
        $this->_elements = $elements;
        $this->_elementSet = $elementSetMetadata;
        $this->_elementSetName = $elementSetMetadata['name'];
    }

    /**
     * Write the config results to the options table
     * @param $args
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach($post as $key=>$value) {
            if( ($key == 'ratings_rating_roles') ) {
                $value = serialize($value);
            }
            set_option($key, $value);
        }
    }

    /**
     * Define config form
     */
    public function hookConfigForm()
    {
        include RATINGS_PLUGIN_DIR . '/config_form.php';
    }

    /*
     * Define the default options for install
     */
    protected $_options = array(
        'ratings_enabled'=> '1',
        'ratings_display_public'=> '1',
        'ratings_rating_roles' => '',
        'ratings_show_in_listgridview' =>'1',
        'ratings_default_rating' => '0'
    );

    /**
     * Install table, options and element set
     */
    public function hookInstall()
    {
        $db = $this->_db;
        $sql = "
            CREATE TABLE IF NOT EXISTS `$db->Rating` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `record_id` int(10) unsigned NOT NULL,
              `rating` float(2,1) NOT NULL DEFAULT '0',
              `user_id` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ";

        $db->query($sql);

        // Install and set basic options
        $this->_installOptions();
        set_option('ratings_rating_roles', serialize(array()));

        insert_element_set($this->_elementSet, $this->_elements);

    }


    public function hookUninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->Ratings`";
        $db->query($sql);

        $this->_uninstallOptions();

        $this->_deleteElementSet($this->_elementSetName);

    }

    public function hookUpgrade($args) {
    }

    /**
     * Register the Add / Edit actions with the ACL
     *
     * @param $args
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];

        // add the ability to rate to the ACL
        $acl->addResource('Ratings_Rate');

        $ratingRoles = unserialize(get_option('ratings_rating_roles'));

        if(!empty($ratingRoles)) {
            // add the rating roles to the ACL
            foreach($ratingRoles as $role) {
                if($acl->hasRole($role)) {
                    //check that all the roles exist, in case a plugin-added role has been removed (e.g. GuestUser)
                    $acl->allow($role, 'Ratings_Rate', 'add');
                }
            }
        }
    }

    /**
     * Load CSS + JS for frontend
     */
    public function hookPublicHead()
    {
        queue_css_file('rateit');
        queue_js_file('jquery.rateit.min');
    }

    /**
     * Remove all related ratings after an item has been deleted
     *
     * @param $args
     */
    public function hookAfterDeleteItem($args)
    {
        $db = get_db();

        $ratingsTable = $db->Ratings;

        $record_id = $args['record']->id;

        $sql = "DELETE FROM $ratingsTable WHERE `record_id`=$record_id";

        $db->query($sql);
    }

    /**
     * Set default rating for newly added items
     *
     * @param $args
     */
    public function hookBeforeSaveItem($args) {

        $item = $args['record'];

        // Get element for ratings
        $ratingsElement = $item->getElement(ELEMENT_SET_NAME, ELEMENT_NAME);
        $ratingsElementId = $ratingsElement['id'];

        // Check if value has been submitted
        if(empty($item['Elements'][$ratingsElementId][0]['text'])) {

            // Add text to Item Type Metadata:Text
            $item->addTextForElement($ratingsElement, get_option('ratings_default_rating'));

        }

    }


    /*
     * Load our showRatings function with the Public Items Show hook
     */
    public function hookPublicDisplayRatings($args)
    {
        $this::showRatings($args);
    }

    /**
     * @param $args
     */
    public function hookPublicItemsBrowseEach($args) {

        if(get_option('ratings_show_in_listgridview')) {
            $this::showRatingsListGridview($args);
        }
    }

    /**
     * @param $args
     */
    public function hookPublicRatingsShowWithFavorites ($args) {

        if(get_option('ratings_show_in_listgridview')) {
            $this::showRatingsWithFavorites($args);
        }
    }

    /**
     * @param $args
     */
    public function hookShowRatingsWithFavoritesFooter ($args) {

        if(get_option('ratings_show_in_listgridview')) {
            $this::showRatingsWithFavoritesFooter($args);
        }
    }

    /**
     * Output the ratings for each item
     *
     * @param array $args
     */
    public static function showRatings($args = array())
    {
        // get item and current user

        $item = $args['item'];
        $user = current_user();

        if(isset($args['view'])) {
            $view = $args['view'];
        } else {
            $view = get_view();
        }

        // get average user rating
        $view->addHelperPath(RATINGS_PLUGIN_DIR. '/helpers', 'Ratings_View_Helper_');

        $avgRating = metadata($item, array('Ratings', 'Average Rating'));

        // Output the public display and average rating for an item
        if( (get_option('ratings_enabled') == 1) && (get_option('ratings_display_public') == 1) ) { ?>
            <div id="ratingContainer">
                <div id="ratings">
                    <h5><?php echo __('Rating'); ?></h5>
                    <div class="rateit" id="avgRating" data-rateit-value="<?php echo $avgRating; ?>" data-rateit-ispreset="true" data-rateit-readonly="true"></div><br />

                    <?php
                    // Output personal rating of current user, if allowed
                    if(is_allowed('Ratings_Rate', 'add')) { ?>

                        <?php $myRating = $view->getMyRatings($item['id'], $user['id']); ?>
                        <h5>My Rating</h5></p>
                        <div class="rateit" id="<?php echo $item['id']; ?>" data-rateit-value="<?php echo $myRating; ?>" data-rateit-ispreset="true" data-rateit-readonly="false"></div>

                    <?php // Add scripts for rating and resetting ?>
                        <script type="text/javascript">
                            (function($) {
                                $(document).ready(function() {
                                    // add rating
                                    $(".rateit").on("rated", function(event, value) {

                                        jQuery.ajax({
                                            url: '/ratings/index/add',
                                            type: 'POST',
                                            data: {'record_id': event.target.id, 'user_id': <?php echo $user['id']; ?>, 'rating': value},
                                            dataType: "json",
                                            success: function(result){
                                                var updatedAvgRating = result.updatedAvgRating;
                                            }
                                        });

                                    });
                                    // remove rating
                                    $(".rateit").on("reset", function(event, value) {

                                        jQuery.ajax({
                                            url: '/ratings/index/unrate',
                                            type: 'POST',
                                            data: {'record_id': event.target.id, 'user_id': <?php echo $user['id']; ?>},
                                            dataType: "json",
                                            success: function(result){

                                            }
                                        });

                                    });
                                });
                            }) (jQuery);
                        </script>
                    <?php
                    } else {?>
                        <p class="footnote"><i><a href="<?php echo url('/guest-user/user/login') ?>" title="Login to your account">Login</a> or <a href="<?php echo url('/guest-user/user/register') ?>" title="Register Account">Register</a> to rate items, add comments, and save favorites.</i></p>
                    <?php
                    } ?>
                </div><!-- /<div id="ratings"> -->
            </div><!-- /<div id="ratingContainer"> -->
        <?php }

    }

    /**
     * @param $args
     */
    public static function showRatingsListGridview($args) {
        $item = $args['item'];
        $view = $args['view'];

        $view->addHelperPath(RATINGS_PLUGIN_DIR. '/helpers', 'Ratings_View_Helper_');
        $avgRating = $view->getAvgRatings($item['id']);
        ?>
        <div class="rateit" data-rateit-value="<?php echo $avgRating; ?>" data-rateit-ispreset="true" data-rateit-readonly="true"></div>
    <?php
    }

    /**
     * @param $args
     */
    public static function showRatingsWithFavorites($args) {
        $view = $args['view'];
        $item = $args['item'];
        $user = current_user();


        $view->addHelperPath(RATINGS_PLUGIN_DIR. '/helpers', 'Ratings_View_Helper_');
        // $avgRating = $view->getAvgRatings($item['id']);

        $avgRating = metadata($item, array('Ratings', 'Average Rating'));
        ?>
        <div class="rateit" id="avgRating-<?php echo $item['id'];?>" data-rateit-value="<?php echo $avgRating; ?>" data-rateit-ispreset="true" data-rateit-readonly="true"></div>
        <?php
        if(is_allowed('Ratings_Rate', 'add')) : ?>

            <?php $myRating = $view->getMyRatings($item['id'], $user['id']); ?>
            <div><b><?php echo __('My Rating');?></b></div>
            <div class="rateit" id="<?php echo $item['id']; ?>" data-rateit-value="<?php echo $myRating; ?>" data-rateit-ispreset="true" data-rateit-readonly="false"></div>

        <?php endif;
    }

    /**
     * @param $args
     */
    public static function showRatingsWithFavoritesFooter($args) {
        $user = current_user();
        ?>
        <script type="text/javascript">
            (function($) {
                $(document).ready(function() {
                    // add rating
                    $(".rateit").on("rated", function(event, value) {

                        console.log('ID:' + event.target.id + 'Rating:' + value);

                        jQuery.ajax({
                            url: '/ratings/index/add',
                            type: 'POST',
                            data: {'record_id': event.target.id, 'user_id': <?php echo $user['id']; ?>, 'rating': value},
                            dataType: "json",
                            success: function(result){
                            }
                        });


                    });
                    // remove rating
                    $(".rateit").on("reset", function(event, value) {

                        jQuery.ajax({
                            url: '/ratings/index/unrate',
                            type: 'POST',
                            data: {'record_id': event.target.id, 'user_id': <?php echo $user['id']; ?>},
                            dataType: "json",
                            success: function(result){
                            }
                        });

                    });
                });
            }) (jQuery);
        </script>
    <?php
    }

    /**
     * @param $elementSetName
     * @return mixed
     */
    private function _getElementSet($elementSetName)
    {
        return $this->_db
            ->getTable('ElementSet')
            ->findByName($elementSetName);
    }

    /**
     * @param $elementSetName
     */
    private function _deleteElementSet($elementSetName)
    {

        $elementSet = $this->_getElementSet($elementSetName);

        if($elementSet) {
            $elementSet->delete();
        }
    }

}
