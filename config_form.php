<?php
/**
 *  Ratings - Plugin
 *  @copyright Copyright 2013-15 The Digital Ark, Corp.
 *  @license http://www.gnu.org/licenses/gpl-3.0.txt GPLv3 or any later version
 */
?>
<?php $view = get_view(); ?>
<h2><?php __('Ratings Settings') ?></h2>
<div class="field">
    <div class="three columns alpha">
        <label>Enable Ratings?</label>
    </div>
    <div class="inputs four columns omega">
        <p class="explanation">If checked, ratings will be enabled.</p>
        <div class="input-block">
            <?php echo $view->formCheckbox('ratings_enabled', null,
                array('checked'=> (bool) get_option('ratings_enabled') ? 'checked' : '',
                )
            ); ?>
        </div>
    </div>
</div>
<div class="field">
    <div class="three columns alpha">
        <label>Display Ratings publicly?</label>
    </div>
    <div class="inputs four columns omega">
        <p class="explanation">If checked, ratings will be displayed to all users & visitors.</p>
        <div class="input-block">
            <?php echo $view->formCheckbox('ratings_display_public', null,
                array('checked'=> (bool) get_option('ratings_display_public') ? 'checked' : '',
                )
            ); ?>
        </div>
    </div>
</div>

<div class="field" id='rating-options'>
    <div class="three columns alpha">
        <label>Which Users that can rate items?</label>
    </div>
    <div class="inputs four columns omega">
        <p class="explanation">The user roles that are allowed to rate items.</p>
        <div class="input-block">
            <?php
            $moderateRoles = unserialize(get_option('ratings_rating_roles'));
            $userRoles = get_user_roles();
            unset($userRoles['super']);
            echo '<ul>';

            foreach($userRoles as $role=>$label) {
                echo '<li>';
                echo $view->formCheckbox('ratings_rating_roles[]', $role,
                    array('checked'=> in_array($role, $moderateRoles) ? 'checked' : '')
                );
                echo $label;
                echo '</li>';
            }
            echo '</ul>';
            ?>
        </div>
    </div>
</div>

<div class="field">
    <div class="three columns alpha">
        <label>Default rating for new items</label>
    </div>
    <div class="inputs four columns omega">
        <p class="explanation">Set default rating for new items</p>
        <div class="input-block">
            <?php echo $view->formText('ratings_default_rating', get_option('ratings_default_rating')); ?>
        </div>
    </div>
</div>

<div class="field">
    <div class="three columns alpha">
        <label>Show ratings in grid / list view</label>
    </div>
    <div class="inputs four columns omega">
        <p class="explanation">Show ratings in grid / list view. Use CSS to disable in listview if required</p>
        <div class="input-block">
            <?php echo $view->formCheckbox('ratings_show_in_listgridview', null,
                array('checked'=> (bool) get_option('ratings_show_in_listgridview') ? 'checked' : '',
                )
            ); ?>
        </div>
    </div>
</div>
<p class="explanation">
    To implement the plugin in your item view, include the hook <br /><code>&lt;?php fire_plugin_hook('public_display_ratings', array('view' => $this, 'item' =&gt; $item)); ?&gt;</code> in <i>YOUR THEME/items/show.php</i>.
</p>