<?php
/**
 * @package html5-slideshow
 */
/*
Plugin Name: html5-slideshow
Plugin URI: http://html5-slideshow.com/
Description: The Slideshow plugin gives you a shortcode called [ht5_slider id="1"], which pulls any image attachments for a post (or any post type) and formats them into a nicely-designed slideshow.

 Canvas Slider is a jQuery banner rotator plugin with animation effects, animated captions, responsive layout, and touch support for mobile devices. The thumbnails and bullets control allow for easy navigation of your slider.
This slider includes a smooth animation effect created using HTML5 Canvas which is completely configurable and compatible with all major browsers (including IE, Firefox Chrome, Opera, and Safari) and mobile platforms like iphone / ipad. The slider also work well in older browsers with fade transition.
Multiple customized slider instances can happily live on the same page, and the slider offers a simple API to control the slider’s behaviour from within your custom scripts..
Version: 3.1.5
Author: Miqayel
Author URI: https://profiles.wordpress.org/miqo1996/
License: GPLv2 or later
Text Domain: html5-slideshow
*/

/*
This program is free software
Copyright 2016 Automattic, Inc.
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

function install()
{
    global $wpdb;
    $sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "ht5_slider (
			`id` int(11) NOT NULL AUTO_INCREMENT,
 `attachment_ids` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; ";
    $wpdb->query($sql);
}

function unistall()
{
    global $wpdb;
    $table = $wpdb->prefix."ht5_slider";
    $wpdb->query("DROP TABLE ". $table);
}

register_activation_hook(__FILE__,  'install');
register_uninstall_hook(__FILE__,  'unistall');

define("HT5_PATH", dirname(__FILE__).'/');
define("CLASSES_HT5", HT5_PATH."classes/");
require_once(CLASSES_HT5."ht5_list_table.php");

$plugin_url = plugins_url() . '/html5-slideshow';
wp_register_script('html5-slideshow-js', $plugin_url . '/script.js', array('jquery'));
wp_enqueue_script('html5-slideshow-js');
wp_register_style('html5-slideshow-css', $plugin_url . "/styles.css");
wp_enqueue_style('html5-slideshow-css');


function ht5_slider_func( $atts ) {
    if(isset($atts['id']) && intval($atts['id']))
    {
        global $wpdb;
        $table = $wpdb->prefix."ht5_slider";
        $id = intval($atts['id']);
        $data = $wpdb->get_row("SELECT * FROM ".$table." WHERE `id`='$id';");
        if(!isset($data) || empty($data))
            return;

        $data->attachment_ids = unserialize($data->attachment_ids);
        if(!isset($data->attachment_ids) || empty($data->attachment_ids))
            return;
        ?>
        <h1 class="ht5-title"><?php echo trim($data->title); ?></h1>
        <div class="slideshow-css">
            <ul class="slides">
                <?php
                foreach($data->attachment_ids as $attachment_id):
                    $image_src = wp_get_attachment_image_src($attachment_id, 'medium');
                    if(!isset($image_src[0]))
                        break;
                    ?>
                    <li><img src="<?php echo $image_src[0] ?>" width="620" height="320" alt=""/></li>
                <?php endforeach; ?>
            </ul>
            <span style="left: 0;" class="arrow previous"></span>
            <span style="right: 10px;margin-left: -30px;" class="arrow next"></span>
        </div>
        <div class="ht5-description">
            <?php echo apply_filters('the_content', $data->description); ?>
        </div>
        <?php
    }
}
add_shortcode( 'ht5_slider', 'ht5_slider_func' );

function setup_adminmenu_html5_slideshow()
{
    add_menu_page('Html5 slideshow css', 'Html5 slideshow', 'manage_options', 'slideshow', 'page_init_html5_slideshow', '');
    add_submenu_page('slideshow', 'Add new', 'Add new', 'manage_options', 'page_init_html5_slideshow_addnew', 'page_init_html5_slideshow_addnew');
    add_submenu_page('slideshow', 'Demo', 'Demo', 'manage_options', 'page_init_html5_slideshow', 'page_init_html5_slideshow_demo');
}

function page_init_html5_slideshow()
{
    global $wpdb;
    $ht5_table = new Ht5_list_table();
    $table = $wpdb->prefix."ht5_slider";
    $sldarDatas = $wpdb->get_results('SELECT * FROM '.$table);
    $ht5_table->items = $sldarDatas;
    $ht5_table->display();
}

function page_init_html5_slideshow_demo()
{
    global $plugin_url;
    ?>
    <div style="margin: 40px;">
        <div class="slideshow-css">
            <ul class="slides">
                <li><img src="<?php echo $plugin_url ?>/img/photos/1.jpg" width="620" height="320"
                         alt="Marsa Alam underawter close up"/></li>
                <li><img src="<?php echo $plugin_url ?>/img/photos/2.jpg" width="620" height="320"
                         alt="Turrimetta Beach - Dawn"/></li>
                <li><img src="<?php echo $plugin_url ?>/img/photos/3.jpg" width="620" height="320" alt="Power Station"/>
                </li>
                <li><img src="<?php echo $plugin_url ?>/img/photos/4.jpg" width="620" height="320"
                         alt="Colors of Nature"/></li>
            </ul>
            <span class="arrow previous"></span>
            <span class="arrow next"></span>
        </div>
    </div>
    <?php
}

function page_init_html5_slideshow_addnew()
{
    global $wpdb;
    $errors = array();
    if(isset($_POST) && !empty($_POST)) {
        if(isset($_POST['attach_ids']) && !empty($_POST['attach_ids'])) {
            $attach_ids = serialize($_POST['attach_ids']);
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $description = isset($_POST['slider_description']) ? $_POST['slider_description'] : '';
            $sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."ht5_slider (attachment_ids, title, description) VALUES (%s, %s, %s)",$attach_ids,$title,$description);
            if($wpdb->query($sql)) {
               ?>
                <script>
                    location.href = "<?php echo admin_url("admin.php?page=slideshow") ?>";
                </script>
                <?php
                exit;
            }
        }else {
            $errors[] = 'no images for slider';
        }
    }
    ?>
    <?php if(!empty($errors)): ?>
    <ul class="ht5-errors">
        <li class="ht5-errors-close">x</li>
        <?php foreach($errors as $error): ?>
            <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <form method="post" action="" class="slshow-add">
        <div class="pull-left">
            <div class="img-upload">
                <div class="all-images-slider">

                </div>
                <span class="img-upload-control">
                    <a href="#" class="custom_media_upload button">Upload image</a>
                    <input class="custom_media_url" type="hidden" name="attachment_url" value="">
                    <input class="custom_media_id" type="hidden" name="attachment_id" value="">
                </span>
            </div>
        </div>
        <div style="width: 480px;" class="pull-right">
            <div class="row">
                <div id="titlewrap">
                    <input placeholder="Enter title here" class="deff-inp" type="text" name="title" size="255" value=""
                           id="title" spellcheck="true" autocomplete="off">
                </div>
            </div>

            <div class="row">
                <?php wp_editor('', 'slider_description'); ?>
            </div>
        </div>
        <div class="clear">
            <input style="width: 54%;" type="submit" value="Add" class="button button-primary">
        </div>
    </form>

    <?php

}

add_action('admin_menu', 'setup_adminmenu_html5_slideshow');
?>