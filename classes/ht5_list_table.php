<?php
if(class_exists('Ht5_list_table'))
    exit;
if ( ! class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

Class Ht5_list_table extends WP_List_Table
{
    public function __construct($args = array())
    {
        parent::__construct($args);
    }

    /**
     *
     * @return array
     */
    protected function get_sortable_columns()
    {
        return array(
            'cb' => 'cb',
            //'ID' => 'id',
            'title' => array('title', true),
            //'description' => 'description',
            'shortcode' => 'Shortcode',
            'date' => array( 'date', false ),
        );
    }

    public function get_columns()
    {
        $columns['cb'] = '<input type="checkbox" />';
        //$columns['ID'] = 'ID';
        $columns['title'] = 'Title';
        //$columns['description'] = 'Description';
        $columns['shortcode'] = 'Shortcode';
        $columns['date'] = 'Date';
        return $columns;
    }

    protected function get_column_info()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $primary = $this->get_primary_column_name();
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);

        return $this->_column_headers;
    }

    public function display()
    {
        if(isset($_GET['delID']) && intval($_GET['delID']))
        {
            $this->remove(intval($_GET['delID']));
        }

        if(isset($_GET['editID']) && intval($_GET['editID']))
        {
            $this->update(intval($_GET['editID']));
        }else {
            parent::display();
        }
    }

    protected function get_bulk_actions() {
        return array(
            'edit' => 'edit'
        );
    }

    public function column_cb($tableData)
    {
        echo '<input id="cb-select-'.$tableData->id.'" type="checkbox" name="post[]" value="'.$tableData->id.'" />';
    }

    public function _column_id($tableData, $classes, $data, $primary)
    {
        echo '<td class="' . $classes . 'id" ', $data, '>';
        echo $this->column_id( $tableData );
        echo $this->handle_row_actions( $tableData, 'id', $primary );
        echo '</td>';
    }

    public function column_id( $tableData ) {
        return sprintf('<strong>%s</strong>', $tableData->id, $tableData->id);
    }

    public function column_title( $item )
    {
        $edit_link = admin_url( 'admin.php?page=slideshow&editID=' . absint( $item->id ) );
        $remove_link = admin_url( 'admin.php?page=slideshow&delID=' . absint( $item->id ) );

        $actions = array(
            'edit' => sprintf( '<a href="%1$s">%2$s</a>',
                esc_url( $edit_link ),
                esc_html( __( 'Edit', 'ht5_slideshow' ) ) ) );

        if ( current_user_can( 'wpcf7_edit_contact_form', $item->id ) ) {
            $remove_link = wp_nonce_url($remove_link);

            $actions = array_merge( $actions, array(
                'remove' => sprintf( '<a href="%1$s">%2$s</a>',
                    esc_url( $remove_link ),
                    esc_html( __( 'Remove', 'ht5_slideshow' ) ) ) ) );
        }

        $a = sprintf( '<a class="row-title" href="%1$s" title="%2$s">%3$s</a>',
            esc_url( $edit_link ),
            esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'contact-form-7' ),
                $item->title ) ),
            esc_html( $item->title ) );

        return '<strong>' . $a . '</strong> ' . $this->row_actions( $actions );
    }

    public function _column_description($tableData, $classes, $data, $primary)
    {
        echo '<td class="' . $classes . 'description" ', $data, '>';
        echo $this->column_description( $tableData );
        echo $this->handle_row_actions( $tableData, 'description', $primary );
        echo '</td>';
    }

    public function column_description( $tableData ) {
        return sprintf('<p>%s</p>', $tableData->description);
    }

    public function _column_shortcode($tableData, $classes, $data, $primary)
    {
        echo '<td class="' . $classes . 'shortcode" ', $data, '>';
        echo $this->column_shortcode( $tableData );
        echo $this->handle_row_actions( $tableData, 'shortcode', $primary );
        echo '</td>';
    }

    public function column_shortcode( $tableData ) {
        return sprintf('<p><strong>[ht5_slider id="%s"]</strong></p>', $tableData->id);
    }

    public function _column_date($tableData, $classes, $data, $primary)
    {
        echo '<td class="' . $classes . 'date" ', $data, '>';
        echo $this->column_date( $tableData );
        echo $this->handle_row_actions( $tableData, 'date', $primary );
        echo '</td>';
    }

    public function column_date( $tableData ) {
        $t_time = mysql2date( __( 'Y/m/d g:i:s A', 'contact-form-7' ), $tableData->date, true );
        $m_time = $tableData->date;
        $time = mysql2date( 'G', $tableData->date ) - get_option( 'gmt_offset' ) * 3600;

        $time_diff = time() - $time;

        if ( $time_diff > 0 && $time_diff < 24*60*60 )
            $h_time = sprintf( __( '%s ago', 'contact-form-7' ), human_time_diff( $time ) );
        else
            $h_time = mysql2date( __( 'Y/m/d', 'contact-form-7' ), $m_time );

        return '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
    }

    protected function tableName()
    {
        global $wpdb;
        return $table = $wpdb->prefix."ht5_slider";
    }

    public function remove($id)
    {
        global $wpdb;
        if($wpdb->query("DELETE FROM ".$this->tableName() ." WHERE `id`='$id';")) {
            ?>
            <script>
                location.href = "<?php echo admin_url("admin.php?page=slideshow") ?>";
            </script>
            <?php
        }
    }

    public function update($id) {
        global $wpdb;
        $data = $wpdb->get_row("SELECT * FROM ".$this->tableName() ." WHERE `id`='$id';");
        if(!isset($data) || empty($data))
            return;

        $data->attachment_ids = unserialize($data->attachment_ids);
        if(!isset($data->attachment_ids) || empty($data->attachment_ids))
            return;

        $errors = array();
        if(isset($_POST) && !empty($_POST)) {
            if(isset($_POST['attach_ids']) && !empty($_POST['attach_ids'])) {
                $attach_ids = serialize($_POST['attach_ids']);
                $title = isset($_POST['title']) ? $_POST['title'] : '';
                $description = isset($_POST['slider_description']) ? $_POST['slider_description'] : '';
                $sql = $wpdb->prepare("UPDATE ".$wpdb->prefix."ht5_slider SET attachment_ids = '%s', title='%s', description='%s' where id='%d';",$attach_ids,$title,$description,$data->id);
                if($wpdb->query($sql)) {
                    ?>
                    <script>
                        location.reload();
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
                        <?php
                        foreach($data->attachment_ids as $attachment_id):
                        $image_src = wp_get_attachment_image_src($attachment_id, 'medium');
                            if(!isset($image_src[0]))
                                break;
                            ?>
                            <section data-id="<?php echo $attachment_id; ?>">
                                <img data-img="<?php echo $attachment_id; ?>" style="max-width: 100%;min-width: 371px;" class="custom_media_image" src="<?php echo $image_src[0]; ?>" />
                                <button type="button" class="button button-danger btn-r" data-img="<?php echo $attachment_id; ?>">x</button>
                                <input type="hidden" name="attach_ids[]" data-img="<?php echo $attachment_id; ?>" value="<?php echo $attachment_id; ?>" />
                            </section>
                        <?php endforeach; ?>
                    </div>
                <span class="img-upload-control">
                    <a href="#" class="custom_media_upload button">Upload image</a>
                    <input class="custom_media_url" type="hidden" name="attachment_url" value="<?php echo wp_get_attachment_image_src($attachment_id); ?>">
                    <input class="custom_media_id" type="hidden" name="attachment_id" value="<?php echo $attachment_id; ?>">
                </span>
                </div>
            </div>
            <div style="width: 480px;" class="pull-right">
                <div class="row">
                    <div id="titlewrap">
                        <input placeholder="Enter title here" class="deff-inp" type="text" name="title" size="255" value="<?php echo $data->title; ?>"
                               id="title" spellcheck="true" autocomplete="off">
                    </div>
                </div>

                <div class="row">
                    <?php wp_editor($data->description, 'slider_description'); ?>
                </div>
            </div>
            <div class="clear">
                <input style="width: 54%;" type="submit" value="Edit" class="button button-primary">
            </div>
        </form>

        <?php
    }
}