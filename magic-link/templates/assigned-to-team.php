<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly.

add_filter('dt_magic_link_template_types', function( $types ) {
    $types['contacts'][] = [
        'value' => 'list-team-contacts',
        'text' => 'List Team Contacts',
    ];
    // This is commented out until we update the magic link plugins supports connected post types
    // $types['teams'][] = [
    //     'value' => 'list-team-contacts',
    //     'text' => 'List Team Contacts',
    // ];
    return $types;
});

add_action('dt_magic_link_template_load', function ( $template ) {
    if ( !empty( $template ) && $template['type'] === 'list-team-contacts' ) {
        new Team_Assigned_List( $template );
    }
} );

/**
 * Class Team_Assigned_List
 */
class Team_Assigned_List extends Disciple_Tools_Magic_Links_Template_Single_Record {

    protected $template_type = 'list-team-contacts';
    public $page_title = 'List Team Contacts';
    public $page_description = 'List Team Contacts Description';
    public $root = 'team';
    public $teamColors = [];

    public function header_style() {
        ?>
        <style>
            body {
                background-color: white;
                padding: 1em;
            }

            #assigned_contacts_div {
            }

            .api-content-div-style {
                height: 45dvh;
                overflow-x: hidden;
                overflow-y: scroll;
                text-align: start;
                border-bottom: 1px solid #cacaca;
            }

            .api-content-table thead {
                border: none;
                border-bottom: 1px solid #f1f1f1;
            }
            .api-content-table tbody {
                border: none;
            }

            .api-content-table tr {
                cursor: pointer;
                background: #ffffff;
                padding: 0px;
            }

            .api-content-table tr:hover {
                background-color: #f5f5f5;
            }

            .teamBadgeContainer {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5em;
            }

            .teamBadge {
                background-color: #7899;
                color: #f5f5f5;
                border-radius: 5px;
                padding: 0.2em .5em;
                font-size: .95rem;
            }
        </style>
        <?php
    }

    public function convertToClassName($str) {
        // Convert the string to lowercase
        $str = strtolower($str);

        // Replace non-alphanumeric characters with hyphens
        $str = preg_replace('/[^a-z0-9]+/', '-', $str);

        // Remove leading and trailing hyphens
        $str = preg_replace('/^-+|-+$/', '', $str);

        return $str;
    }

    public function stringToColor($str) {
        $hash = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $hash = ord($str[$i]) + (int)(($hash << 5) - $hash);
        }
        $color = '#';
        for ($i = 0; $i < 3; $i++) {
            $value = ($hash >> ($i * 8)) & 0xFF;
            if ($i === 3) { // Increase the blue component
                $value = min($value - 100, 255); // Ensure it doesn't exceed 255
            } elseif ($i === 2) { // Increase the blue component
                $value = min($value + 100, 255); // Ensure it doesn't exceed 255
            } else {
                $value = max($value - 50, 0); // Decrease red and green components
            }
            $color .= substr('00' . dechex($value), -2);
        }

        // Calculate luminance
        $r = hexdec(substr($color, 1, 2)) / 255;
        $g = hexdec(substr($color, 3, 2)) / 255;
        $b = hexdec(substr($color, 5, 2)) / 255;
        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Determine contrasting text color
        $textColor = $luminance > 0.6 ? '#333333' : '#f5f5f5';

        return ['backgroundColor' => $color, 'textColor' => $textColor];
    }

    public function getTeamColor($teamName) {
        global $teamColors;
        if (!isset($teamColors[$teamName])) {
            $teamColors[$teamName] = $this->stringToColor($teamName);
        }
        return $teamColors[$teamName];
    }

    public function get_users_team_contacts() {
        // Get all teams assigned to the contact and sends the team ids to get_team_contacts
        $contact_id = $this -> post['ID'];

        $connections = p2p_get_connections( 'teams_to_contacts', [
            'from' => $contact_id,
        ]);

        $team_ids = array_map( function ( $connection ) {
            return $connection->p2p_to;
        }, $connections );

        if (empty($team_ids)) {
            return [];
        }

        return $this->get_team_contacts($team_ids);
    }

    public function get_team_contacts($team_ids) {
        // Get all contacts assigned to the team
        $team_connections = p2p_get_connections( 'contacts_to_teams', [
            'from' => $team_ids,
        ]);

        $team_contacts = array_unique(array_map(function ($connection) {
            return $connection->p2p_to;
        }, $team_connections));

        $assigned_posts = []; // Initialize as an array

        if (is_array($team_contacts)) {
            foreach ($team_contacts as $contact_id) {
                $post = DT_Posts::get_post('contacts', $contact_id, true, false, false);

                if ($post && $post['overall_status']['key'] !== 'closed') {
                    $assigned_posts[] = $post; // Append each post to the array
                    $comments[] = DT_Posts::get_post_comments( 'contacts', $contact_id, false, 'all', [ 'number' => $this->template['show_recent_comments'] ] );
                }
            }
        } else {
            $assigned_posts = DT_Posts::get_post('contacts', $team_contacts, false);
        }

        $this->post = null;
        if ( !empty( $assigned_posts ) ) {
            $this->post = $assigned_posts[0];
        }

        return $assigned_posts;
    }
    public function body() {
        $has_title = ! empty( $this->template ) && ( isset( $this->template['title'] ) && ! empty( $this->template['title'] ) );
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title">
                        <b>
                            <?php echo esc_html( $has_title ? $this->adjust_template_title_translation( $this->template['title'], $this->template['title_translations'] ) : '' ); ?>
                        </b>
                    </h2>
                </div>
            </div>
            <?php
            if ( $has_title ) {
                ?>
                <hr/>
                <?php
            }
            ?>
            <div id="content">
                <div id="alert_notice" style="display: none; border-style: solid; border-width: 2px; border-color: #4caf50; background-color: rgba(142,195,81,0.2); border-radius: 5px; padding: 2em; margin: 1em 0">
                    <div style="display: flex; grid-gap: 1em">
                        <div style="display: flex; align-items: center">
                            <img style="width: 2em; filter: invert(52%) sepia(77%) saturate(383%) hue-rotate(73deg) brightness(98%) contrast(83%);"
                                 src="<?php echo esc_url( plugin_dir_url( __DIR__ ) . 'exclamation-circle.svg' ); ?>" alt="Exclamation Circle"/>
                        </div>
                        <div id="alert_notice_content" style="display: flex; align-items: center"></div>
                    </div>
                </div>

                <!-- TEMPLATE MESSAGE -->
                <p id="template_msg">
                    <?php echo nl2br( esc_html( ! empty( $this->template ) && isset( $this->template['message'] ) ? $this->template['message'] : '' ) ); ?>
                </p>

                <?php
                // Determine if template type list of assigned contacts is to be displayed.
                if ( isset( $this->template['type'] ) && ( $this->template['type'] == 'list-team-contacts' ) && !empty( $this->post ) ){
                    if ( isset( $this->parts ) && (isset ( $this->parts['post_type'] )) && $this->parts['post_type'] === 'contacts' ){
                        // This Magic Link is from a Contact so we will get all teams assigned to that contact then get all contacts assigned to those teams
                        $assigned_posts = $this->get_users_team_contacts();
                    }

                    if ( isset( $this->parts ) && (isset ( $this->parts['post_type'] )) && $this->parts['post_type'] === 'teams' ){
                        // This Magic Link is from a Team not a contact so we will get all contacts assigned to that team
                        $assigned_posts = $this->get_team_contacts($this->post['ID']);
                    }
                    // Display only if there are valid hits!
                    if ( isset( $assigned_posts ) && count( $assigned_posts ) > 0 ){
                        ?>
                        <!-- List Team Contacts -->
                        <div id="assigned_contacts_div">
                            <h3><?php esc_html_e( 'Team Contacts', 'disciple_tools_bulk_magic_link_sender' ) ?> [ <span
                                    id="total"><?php echo esc_html( count( $assigned_posts ) ); ?></span>
                                ]</h3>
                            <hr>
                            <div class="grid-x api-content-div-style" id="api-content">
                                <table class="api-content-table">
                                    <thead>
                                        <th><?php esc_html_e( 'Name', 'disciple_tools' ) ?></th>
                                        <th><?php esc_html_e( 'Teams', 'disciple_tools' ) ?>
                                        <th><?php esc_html_e( 'Creation Date', 'disciple_tools' ) ?></th>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach ( $assigned_posts as $assigned ){
                                        ?>
                                        <tr onclick="get_assigned_details('<?php echo esc_html( $assigned['post_type'] ); ?>','<?php echo esc_html( $assigned['ID'] ); ?>','<?php echo esc_html( str_replace( "'", '&apos;', $assigned['name'] ) ); ?>')">
                                            <td><?php echo esc_html( $assigned['name'] ) ?></td>
                                            <td class="teamBadgeContainer"><?php
                                                if ( isset( $assigned['teams'] ) && count( $assigned['teams'] ) > 0 ){
                                                    foreach ( $assigned['teams'] as $team ){
                                                        $teamName = $team['post_title'];
                                                        $teamClassName = $this->convertToClassName($teamName);
                                                        $teamColors = $this->getTeamColor($teamName);
                                                        $teamBackgroundColor = $teamColors['backgroundColor'];
                                                        $teamTextColor = $teamColors['textColor'];
                                                        echo '<span class="teamBadge ' . $teamClassName . '" style="background-color:' . $teamBackgroundColor . '; color:' . $teamTextColor . '">' . $teamName . '</span>';
                                                    }
                                                }
                                            ?></td>
                                            <td><?php echo esc_html( $assigned['post_date']['formatted'] ) ?></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                            <br>
                        </div>
                        <?php
                    }

                    // Determine if new item creation is enabled.
                    if ( isset( $this->template['support_creating_new_items'] ) && $this->template['support_creating_new_items'] ) {
                        ?>
                        <br>
                        <button id="add_new" class="button select-button" data-post_type="<?php echo esc_attr( $this->post['post_type'] ) ?>" data-post_id="0" data-post_name="<?php esc_html_e( 'New Record', 'disciple_tools' ) ?>">
                            <?php esc_html_e( 'Add New', 'disciple_tools' ) ?>
                        </button>
                        <br>
                        <?php
                    }
                }
                ?>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>
                <?php if ( $assigned_posts === null || count( $assigned_posts ) === 0 ) { ?>
                    <h3 id="no_results"><?php esc_html_e( 'You are not a member of a Team with contacts', 'disciple_tools_bulk_magic_link_sender' ) ?></h3>
                <?php } else { ?>
                <h3>
                    <span id="contact_name" style="font-weight: bold">
                        <?php echo esc_html( ! empty( $this->post ) ? $this->post['name'] : '---' ); ?>
                    </span>
                </h3>
                <hr>
                <div class="grid-x" id="form-content">
                    <input id="post_id" type="hidden"
                           value="<?php echo esc_html( ! empty( $this->post ) ? $this->post['ID'] : '' ); ?>"/>
                    <input id="post_type" type="hidden"
                           value="<?php echo esc_html( ! empty( $this->post ) ? $this->post['post_type'] : '' ); ?>"/>
                    <?php
                    // Revert back to dt translations
                    $this->hard_switch_to_default_dt_text_domain();
                    ?>
                    <table style="<?php echo( ! empty( $this->post ) ? '' : 'display: none;' ) ?>"
                           class="form-content-table">
                        <tbody>
                        <?php

                        /**
                         * If a valid post is present, then display fields accordingly,
                         * based on hidden flags!
                         */

                        $this->post_field_settings = DT_Posts::get_post_field_settings( $this->post_type, false );
                        if ( ! empty( $this->post ) && ! empty( $this->post_field_settings ) && ! empty( $this->template ) ) {

                            // Display selected fields
                            foreach ( $this->template['fields'] ?? [] as $field ) {
                                if ( $field['enabled'] && $this->is_link_obj_field_enabled( $field['id'] ) ) {

                                    $post_field_type = '';
                                    if ( $field['type'] === 'dt' && isset( $this->post_field_settings[ $field['id'] ]['type'] ) ) {
                                        $post_field_type = $this->post_field_settings[ $field['id'] ]['type'];
                                    }
                                    if ( $field['type'] === 'dt' && empty( $post_field_type ) ) {
                                        continue;
                                    }
                                    // Field types to be supported.
                                    if ( $field['type'] === 'dt' && ! in_array( $post_field_type, [
                                            'text',
                                            'textarea',
                                            'date',
                                            'boolean',
                                            'key_select',
                                            'multi_select',
                                            'number',
                                            'link',
                                            'communication_channel',
                                            'location',
                                            'location_meta'
                                    ] ) ) {
                                        continue;
                                    }

                                    // Generate hidden values to assist downstream processing
                                    $hidden_values_html = '<input id="form_content_table_field_id" type="hidden" value="' . $field['id'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_type" type="hidden" value="' . $post_field_type . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_template_type" type="hidden" value="' . $field['type'] . '">';
                                    $hidden_values_html .= '<input id="form_content_table_field_meta" type="hidden" value="">';

                                    // Render field accordingly, based on template field type!
                                    switch ( $field['type'] ) {
                                        case 'dt':

                                            // Capture rendered field html
                                            ob_start();
                                            $this->post_field_settings[$field['id']]['custom_display'] = false;
                                            $this->post_field_settings[$field['id']]['readonly'] = !empty( $field['readonly'] );
                                            render_field_for_display( $field['id'], $this->post_field_settings, $this->post, true );
                                            $rendered_field_html = ob_get_contents();
                                            ob_end_clean();

                                            // Only display if valid html content has been generated
                                            if ( ! empty( $rendered_field_html ) ) {
                                                ?>
                                                <tr>
                                                    <?php
                                                    // phpcs:disable
                                                    echo $hidden_values_html;
                                                    // phpcs:enable
                                                    ?>
                                                    <td>
                                                        <?php
                                                        // phpcs:disable
                                                        echo $rendered_field_html;
                                                        // phpcs:enable
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            break;
                                        case 'custom':
                                            ?>
                                            <tr>
                                                <?php
                                                // phpcs:disable
                                                echo $hidden_values_html;
                                                // phpcs:enable
                                                ?>
                                                <td>
                                                    <?php
                                                    $this->render_custom_field_for_display( $field );
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php
                                            break;
                                    }
                                }
                            }

                            // If requested, display recent comments
                            if ( $this->template['show_recent_comments'] ) {
                                $comment_count = is_bool( $this->template['show_recent_comments'] ) ? 2 : intval( $this->template['show_recent_comments'] );
                                $recent_comments = DT_Posts::get_post_comments( $this->post['post_type'], $this->post['ID'], false, 'all', [ 'number' => $comment_count ] );
                                foreach ( $recent_comments['comments'] ?? [] as $comment ) {
                                    ?>
                                    <tr class="dt-comment-tr">
                                        <td>
                                            <div class="section-subheader dt-comment-subheader">
                                                <?php echo esc_html( $comment['comment_author'] . ' @ ' . $comment['comment_date'] ); ?>
                                            </div>
                                            <span class="dt-comment-content"><?php echo esc_html( $comment['comment_content'] ); ?></span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <br>

                <!-- SUBMIT UPDATES -->
                <button id="content_submit_but"
                        style="<?php echo( ! empty( $this->post ) ? '' : 'display: none;' ) ?> min-width: 100%;"
                        class="button select-button">
                    <?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?>
                    <span class="update-loading-spinner loading-spinner" style="height: 17px; width: 17px; vertical-align: text-bottom;"></span>
                </button>
            <?php } ?>
            </div>
        </div>
        <?php
    }
}
