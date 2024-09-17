<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


/**
 * Class Disciple_Tools_Team_Module_Magic_User_App
 */
class Disciple_Tools_Team_Module_Magic_Login_User_App extends DT_Magic_Url_Base {

    public $page_title = 'Your Teams Contacts App';
    public $page_description = 'See All the Contacts Assigned to Your Team';
    public $root = 'teams_contacts_app'; // @todo define the root of the url {yoursite}/root/type/key/action
    public $type = 'teams_contacts_app'; // @todo define the type
    public $post_type = 'user';
    private $meta_key = '';
    public $show_bulk_send = true;
    public $show_app_tile = true;

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.
    public $translatable = [
        'query',
        'user',
        'contact'
    ]; // Order of translatable flags to be checked. Translate on first hit..!

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {

        /**
         * Specify metadata structure, specific to the processing of current
         * magic link type.
         *
         * - meta:              Magic link plugin related data.
         *      - app_type:     Flag indicating type to be processed by magic link plugin.
         *      - post_type     Magic link type post type.
         *      - contacts_only:    Boolean flag indicating how magic link type user assignments are to be handled within magic link plugin.
         *                          If True, lookup field to be provided within plugin for contacts only searching.
         *                          If false, Dropdown option to be provided for user, team or group selection.
         *      - fields:       List of fields to be displayed within magic link frontend form.
         */
        $this->meta = [
            'app_type'      => 'magic_link',
            'post_type'     => $this->post_type,
            'contacts_only' => false,
            'supports_create' => true,
            'fields'         => [
                [
                    'id'    => 'name',
                    'label' => ''
                ],
                [
                    'id'    => 'milestones',
                    'label' => ''
                ],
                [
                    'id'    => 'age',
                    'label' => 'Age'
                ],
                [
                    'id'    => 'seeker_path',
                    'label' => 'Seeker Path'
                ],
                [
                    'id'  => 'contact_address',
                    'label' => 'Address'
                ],
                [
                    'id'    => 'location',
                    'label' => 'Location'
                ],
                [
                    'id'    => 'faith_status',
                    'label' => ''
                ],
                [
                    'id'    => 'contact_phone',
                    'label' => ''
                ],
                [
                    'id'    => 'comments',
                    'label' => __( 'Comments', 'disciple_tools' ) // Special Case!
                ]
            ],
            'fields_refresh' => [
                'enabled'    => true,
                'post_type'  => 'contacts',
                'ignore_ids' => [ 'comments' ]
            ],
            'icon'           => 'mdi mdi-stack-exchange',
            'show_in_home_apps' => true,
        ];

        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        parent::__construct();

        /**
         * user_app and module section
         */
        add_filter( 'dt_settings_apps_list', [ $this, 'dt_settings_apps_list' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match() ){
            return;
        }

        if ( !is_user_logged_in() ) {
            /* redirect user to login page with a redirect_to back to here */
            wp_redirect( dt_login_url( 'login', '?redirect_to=' . rawurlencode( site_url( dt_get_url_path() ) ) . '&hide-nav' ) );
            exit;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function adjust_global_values_by_incoming_sys_type( $type ) {
        if ( ! empty( $type ) ) {
            switch ( $type ) {
                case 'wp_user':
                    $this->post_type = 'user';
                    break;
                case 'post':
                    $this->post_type = 'contacts';
                    break;
            }
        }
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        // @todo add or remove js files with this filter
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        // @todo add or remove js files with this filter
        return $allowed_css;
    }

    public function wp_enqueue_scripts() {
    }

    /**
     * Builds magic link type settings payload:
     * - key:               Unique magic link type key; which is usually composed of root, type and _magic_key suffix.
     * - url_base:          URL path information to map with parent magic link type.
     * - label:             Magic link type name.
     * - description:       Magic link type description.
     * - settings_display:  Boolean flag which determines if magic link type is to be listed within frontend user profile settings.
     *
     * @param $apps_list
     *
     * @return mixed
     */
    public function dt_settings_apps_list( $apps_list ) {
        $apps_list[ $this->meta_key ] = [
            'key'              => $this->meta_key,
            'url_base'         => $this->root . '/' . $this->type,
            'label'            => $this->page_title,
            'description'      => $this->page_description,
            'settings_display' => true
        ];

        return $apps_list;
    }

    /**
     * Writes custom styles to header
     *
     * @see DT_Magic_Url_Base()->header_style() for default state
     * @todo remove if not needed
     */
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

    /**
     * Writes javascript to the header
     *
     * @see DT_Magic_Url_Base()->header_javascript() for default state
     * @todo remove if not needed
     */
    public function header_javascript(){
        ?>
        <script>
        </script>
        <?php
    }

    /**
     * Writes javascript to the footer
     *
     * @see DT_Magic_Url_Base()->footer_javascript() for default state
     * @todo remove if not needed
     */
    public function footer_javascript() {
        ?>
        <script>
            let jsObject = [<?php echo json_encode( [
                'map_key'                 => DT_Mapbox_API::get_key(),
                'root'                    => esc_url_raw( rest_url() ),
                'nonce'                   => wp_create_nonce( 'wp_rest' ),
                'parts'                   => $this->parts,
                'milestones'              => DT_Posts::get_post_field_settings( 'contacts' )['milestones']['default'],
                'overall_status'          => DT_Posts::get_post_field_settings( 'contacts' )['overall_status']['default'],
                'faith_status'            => DT_Posts::get_post_field_settings( 'contacts' )['faith_status']['default'],
                'link_obj_id'             => Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) ),
                'sys_type'                => $this->fetch_incoming_link_param( 'type' ),
                'translations'            => [
                    'update_success' => __( 'Thank you for your successful submission. You may return to the form and re-submit if changes are needed.', 'disciple-tools-bulk-magic-link-sender' )
                ]
            ] ) ?>][0]

            /**
             * Fetch assigned contacts
             */

            window.get_magic = () => {
                jQuery.ajax({
                    type: "GET",
                    data: {
                        action: 'get',
                        parts: jsObject.parts
                    },
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                    }
                })
                    .done(function (data) {
                        window.load_magic(data)
                    })
                    .fail(function (e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
            };

            /** convert team name to a class name */
            function convertToClassName(str) {
               return str.toLowerCase()
              .replace(/[^a-z0-9]+/g, '-')  // Replace non-alphanumeric characters with hyphens
              .replace(/^-+|-+$/g, '');     // Remove leading and trailing hyphens
            }

            // Function to generate a unique color based on a string and the appropriate contrasting text color
            function stringToColor(str) {
                let hash = 0;
                for (let i = 0; i < str.length; i++) {
                    hash = str.charCodeAt(i) + ((hash << 5) - hash);
                }
                let color = '#';
                for (let i = 0; i < 3; i++) {
                    let value = (hash >> (i * 8)) & 0xFF;
                    if (i === 3) { // Increase the blue component
                        value = Math.min(value - 100, 255); // Ensure it doesn't exceed 255
                    } else if (i === 2) { // Increase the blue component
                        value = Math.min(value + 100, 255); // Ensure it doesn't exceed 255
                    }
                     else {
                        value = Math.max(value - 50, 0); // Decrease red and green components
                    }
                    color += ('00' + value.toString(16)).substr(-2);
                }

                // Calculate luminance
                const r = parseInt(color.substr(1, 2), 16) / 255;
                const g = parseInt(color.substr(3, 2), 16) / 255;
                const b = parseInt(color.substr(5, 2), 16) / 255;
                const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;

                // Determine contrasting text color
                const textColor = luminance > 0.6 ? '#333333' : '##f5f5f5';

                return { backgroundColor: color, textColor: textColor };
            }

            // Object to store generated colors for each team
            const teamColors = {};

            // Function to get or generate a color for a team
            function getTeamColor(teamName) {
                if (!teamColors[teamName]) {
                    teamColors[teamName] = stringToColor(teamName);
                }
                return teamColors[teamName];
            }

            // create locale date from timestamp
            function formatLocaleDate(phpTimestamp) {
                const locale = document.querySelector('html').getAttribute("lang")? document.querySelector('html').getAttribute("lang").replace('_', '-') : 'en-US';

                const jsTimestamp = phpTimestamp * 1000; // Convert PHP timestamp to JavaScript timestamp
                const date = new Date(jsTimestamp);
                return date.toLocaleDateString(locale, {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                });
            }

            /**
             * Display returned list of assigned contacts
             */
            window.load_magic = (data) => {
                let content = jQuery('#api-content');
                let table = jQuery('.api-content-table');
                let total = jQuery('#total');
                let spinner = jQuery('.loading-spinner');

                // Remove any previous entries
                table.find('tbody').empty()

                // Set total hits count
                total.html(data.posts ? data.posts.length : '0');
                // Iterate over returned posts
                if (data['posts']) {
                    data['posts'].forEach(v => {
                        let html = `<tr onclick="get_assigned_contact_details('${window.lodash.escape(v.ID)}', '${window.lodash.escape(window.lodash.replace(v.name, "'", "&apos;"))}');">
                                <td>${window.lodash.escape(v.name)}</td>
                                <td class="teamBadgeContainer">
                                    ${v.teams && v.teams.length > 0 ? v.teams.map(team => {
                                        let teamName = team.post_title;
                                        let teamClassName = convertToClassName(teamName);
                                        let teamColors = getTeamColor(teamName);
                                        let teamBackgroundColor = teamColors.backgroundColor;
                                        let teamTextColor = teamColors.textColor;
                                        return `<span class="teamBadge ${teamClassName}" style="background-color:${teamBackgroundColor}; color:${teamTextColor}">${window.lodash.escape(teamName)}</span>`;
                                    }).join('') : ''}
                                </td>
                                <td>${formatLocaleDate(v.post_date.timestamp)}</td>
                            </tr>`;

                        table.find('tbody').append(html);

                    });
                }
            };

            /**
             * Format comment @mentions.
             */

            let comments = jQuery('.dt-comment-content');
            if (comments) {
                jQuery.each(comments, function (idx, comment) {
                    let formatted_comment = window.SHAREDFUNCTIONS.formatComment(window.lodash.escape(jQuery(comment).html()));
                    jQuery(comment).html(formatted_comment);
                });
            }

            /**
             * Fetch requested contact details
             */
            window.get_contact = (post_id) => {
                let comment_count = 2;

                jQuery('.form-content-table').fadeOut('fast', function () {

                    // Dispatch request call
                    jQuery.ajax({
                        type: "GET",
                        data: {
                            action: 'get',
                            parts: jsObject.parts,
                            sys_type: jsObject.sys_type,
                            post_id: post_id,
                            comment_count: comment_count,
                            ts: moment().unix() // Alter url shape, so as to force cache refresh!
                        },
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/post',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce);
                            xhr.setRequestHeader('Cache-Control', 'no-store');
                        }

                    }).done(function (data) {

                        // Was our post fetch request successful...?
                        if (data['success'] && data['post']) {

                            // Display submit button
                            jQuery('#content_submit_but').fadeIn('fast');

                            // ID
                            jQuery('#post_id').val(data['post']['ID']);

                            // NAME
                            let post_name = window.lodash.escape(data['post']['name']);
                            jQuery('#contact_name').html(post_name);
                            if (window.is_field_enabled('name')) {
                                jQuery('#form_content_name_td').html(`
                                <input id="post_name" type="text" value="${post_name}" />
                                `);
                            } else {
                                jQuery('#form_content_name_tr').hide();
                            }

                            // Date of Birth
                            if (window.is_field_enabled('date_of_birth')) {
                                if (data['post']['date_of_birth']) {

                                    let date_of_birth = data['post']['date_of_birth']['formatted'];

                                    jQuery('#form_content_date_of_birth_td').html(date_of_birth);
                                }
                            } else {
                                jQuery('#form_content_date_of_birth_tr').hide();
                            }

                            // Address
                            if (window.is_field_enabled('contact_address')) {
                                if (data['post']['contact_address']) {

                                    let contact_address = [];
                                    data['post']['contact_address'].forEach(address => {
                                        contact_address.push(address['value']);
                                    });

                                    jQuery('#form_content_contact_address_td').html(contact_address.length > 0 ? contact_address.join('<br><br> ') : '');
                                }
                            } else {
                                jQuery('#form_content_date_of_birth_tr').hide();
                            }

                            // NedenIsa Prayer Request
                            if (window.is_field_enabled('nedenisa_prayer_request')) {
                                if (data['post']['nedenisa_prayer_request']) {

                                    let date_of_birth = data['post']['nedenisa_prayer_request'];

                                    jQuery('#form_content_nedenisa_prayer_request_td').html(date_of_birth);
                                }
                            } else {
                                jQuery('#form_content_nedenisa_prayer_request_tr').hide();
                            }

                            // MILESTONES
                            if (window.is_field_enabled('milestones')) {
                                let html_milestones = ``;
                                jQuery.each(jsObject.milestones, function (idx, milestone) {

                                    // Determine button selection state
                                    let button_select_state = 'empty-select-button';
                                    if (data['post']['milestones'] && (data['post']['milestones'].indexOf(idx) > -1)) {
                                        button_select_state = 'selected-select-button';
                                    }

                                    // Build button widget
                                    html_milestones += `<button id="${window.lodash.escape(idx)}"
                                                            type="button"
                                                            data-field-key="milestones"
                                                            class="dt_multi_select ${button_select_state} button select-button">
                                                        <img class="dt-icon" src="${window.lodash.escape(milestone['icon'])}"/>
                                                        ${window.lodash.escape(milestone['label'])}
                                                    </button>`;
                                });
                                jQuery('#form_content_milestones_td').html(html_milestones);

                                // Respond to milestone button state changes
                                jQuery('.dt_multi_select').on("click", function (evt) {
                                    let milestone = jQuery(evt.currentTarget);
                                    if (milestone.hasClass('empty-select-button')) {
                                        milestone.removeClass('empty-select-button');
                                        milestone.addClass('selected-select-button');
                                    } else {
                                        milestone.removeClass('selected-select-button');
                                        milestone.addClass('empty-select-button');
                                    }
                                });
                            } else {
                                jQuery('#form_content_milestones_tr').hide();
                            }

                            // OVERALL_STATUS
                            if (window.is_field_enabled('overall_status')) {
                                let html_overall_status = `<select id="post_overall_status" class="select-field">`;
                                jQuery.each(jsObject.overall_status, function (idx, overall_status) {

                                    // Determine selection state
                                    let select_state = '';
                                    if (data['post']['overall_status'] && (String(data['post']['overall_status']['key']) === String(idx))) {
                                        select_state = 'selected';
                                    }

                                    // Add option
                                    html_overall_status += `<option value="${window.lodash.escape(idx)}" ${select_state}>${window.lodash.escape(overall_status['label'])}</option>`;
                                });
                                html_overall_status += `</select>`;
                                jQuery('#form_content_overall_status_td').html(html_overall_status);
                            } else {
                                jQuery('#form_content_overall_status_tr').hide();
                            }

                            // FAITH_STATUS
                            if (window.is_field_enabled('faith_status')) {
                                let html_faith_status = `<select id="post_faith_status" class="select-field">`;
                                html_faith_status += `<option value=""></option>`;
                                jQuery.each(jsObject.faith_status, function (idx, faith_status) {

                                    // Determine selection state
                                    let select_state = '';
                                    if (data['post']['faith_status'] && (String(data['post']['faith_status']['key']) === String(idx))) {
                                        select_state = 'selected';
                                    }

                                    // Add option
                                    html_faith_status += `<option value="${window.lodash.escape(idx)}" ${select_state}>${window.lodash.escape(faith_status['label'])}</option>`;
                                });
                                html_faith_status += `</select>`;
                                jQuery('#form_content_faith_status_td').html(html_faith_status);
                            } else {
                                jQuery('#form_content_faith_status_tr').hide();
                            }

                            // CONTACT_PHONE
                            if (window.is_field_enabled('contact_phone')) {
                                if (data['post']['contact_phone']) {

                                    let phone_numbers = [];
                                    data['post']['contact_phone'].forEach(phone => {
                                        phone_numbers.push(phone['value']);
                                    });

                                    jQuery('#form_content_contact_phone_td').html(phone_numbers.length > 0 ? phone_numbers.join(', ') : '');
                                }
                            } else {
                                jQuery('#form_content_contact_phone_tr').hide();
                            }

                            // COMMENTS
                            if (window.is_field_enabled('comments')) {
                                let counter = 0;
                                let html_comments = `<textarea></textarea><br>`;
                                if (data['comments']['comments']) {
                                    data['comments']['comments'].forEach(comment => {
                                        if (counter++ < comment_count) { // Enforce comment count limit..!
                                            html_comments += `<b>${window.lodash.escape(comment['comment_author'])} @ ${window.lodash.escape(comment['comment_date'])}</b><br>`;
                                            html_comments += `${window.SHAREDFUNCTIONS.formatComment(window.lodash.escape(comment['comment_content']))}<hr>`;
                                        }
                                    });
                                }
                                jQuery('#form_content_comments_td').html(html_comments);
                            } else {
                                jQuery('#form_content_comments_tr').hide();
                            }

                            // Display updated post fields
                            jQuery('.form-content-table').fadeIn('fast');

                        } else {
                            // TODO: Error Msg...!
                        }

                    }).fail(function (e) {
                        console.log(e);
                        jQuery('#error').html(e);
                    });
                });
            };

            /**
             * Determine if field has been enabled
             */
            window.is_field_enabled = (field_id) => {

                // Enabled by default
                let enabled = true;

                // Iterate over type field settings
                if (jsObject.link_obj_id['type_fields']) {
                    jsObject.link_obj_id['type_fields'].forEach(field => {

                        // Is matched field enabled...?
                        if (String(field['id']) === String(field_id)) {
                            enabled = field['enabled'];
                        }
                    });
                }
                return enabled;
            }

            /**
             * Handle fetch request for contact details
             */
            window.get_assigned_contact_details = (post_id, post_name) => {
                let contact_container = document.querySelector('.contact_detail_container');

                let contact_name = document.querySelector('#contact_name');

                // Update contact name
                contact_name.innerHTML = post_name;

                // Fetch requested contact details
                window.get_contact(post_id);

                if (contact_container.style.display === 'none') {
                    contact_container.style.display = 'block';
                }
            };

            /**
             * Adjust visuals, based on incoming sys_type
             */
            let assigned_contacts_div = jQuery('#assigned_contacts_div');

            switch (jsObject.sys_type) {
                case 'post':
                    // Bypass contacts list and directly fetch requested contact details
                    assigned_contacts_div.fadeOut('fast');
                    window.get_contact(jsObject.parts.post_id);
                    break;
                default: // wp_user
                    // Fetch assigned contacts for incoming user
                    assigned_contacts_div.fadeIn('fast');
                    window.get_magic();
                    break;
            }

            /**
             * Submit contact details
             */
            jQuery('#content_submit_but').on("click", function () {
                const alert_notice = jQuery('#alert_notice');
                const spinner = jQuery('.update-loading-spinner');
                const submit_but = jQuery('#content_submit_but');
                let id = jQuery('#post_id').val();

                alert_notice.fadeOut('fast');

                // Reset error message field
                let error = jQuery('#error');
                error.html('');

                // Sanity check content prior to submission
                if (!id || String(id).trim().length === 0) {
                    error.html('Invalid post id detected!');

                } else {

                    // Build payload accordingly, based on enabled states
                    let payload = {
                        action: 'get',
                        parts: jsObject.parts,
                        sys_type: jsObject.sys_type,
                        post_id: id
                    }
                    if (window.is_field_enabled('name')) {
                        payload['name'] = String(jQuery('#post_name').val()).trim();
                    }
                    if (window.is_field_enabled('milestones')) {
                        let milestones = [];
                        jQuery('#form_content_milestones_td button').each(function () {
                            milestones.push({
                                'value': jQuery(this).attr('id'),
                                'delete': jQuery(this).hasClass('empty-select-button')
                            });
                        });

                        payload['milestones'] = milestones;
                    }
                    if (window.is_field_enabled('overall_status')) {
                        payload['overall_status'] = String(jQuery('#post_overall_status').val()).trim();
                    }
                    if (window.is_field_enabled('faith_status')) {
                        payload['faith_status'] = String(jQuery('#post_faith_status').val()).trim();
                    }
                    if (window.is_field_enabled('contact_phone')) {
                        // Ignored, as field currently shown in a read-only capacity!
                    }
                    if (window.is_field_enabled('comments')) {
                        payload['comments'] = jQuery('#form_content_comments_td').find('textarea').eq(0).val();
                    }

                    spinner.addClass('active');

                    // Submit data for post update
                    submit_but.prop('disabled', true);

                    jQuery.ajax({
                        type: "GET",
                        data: payload,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/update',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', jsObject.nonce)
                        }

                    }).done(function (data) {
                        alert_notice.find('#alert_notice_content').text(data['success'] ? jsObject.translations.update_success : data['message']);
                        alert_notice.fadeIn('slow', function () {

                            // Reactivate submit button and scroll up to notice.
                            spinner.removeClass('active');
                            submit_but.prop('disabled', false);
                            document.documentElement.scrollTop = 0;

                            // Refresh any identified record ids.
                            if (data?.id > 0) {
                                window.get_contact(data['id']);
                            }
                        });

                    }).fail(function (e) {
                        console.log(e);
                        alert_notice.find('#alert_notice_content').text(e['responseJSON']['message']);
                        alert_notice.fadeIn('slow', function () {
                            spinner.removeClass('active');
                            submit_but.prop('disabled', false);
                            document.documentElement.scrollTop = 0;
                        });
                    });
                }
            });
        </script>
        <?php
        return true;
    }

    public function body(){
         // Revert back to dt translations
         $this->hard_switch_to_default_dt_text_domain();
         $link_obj = Disciple_Tools_Bulk_Magic_Link_Sender_API::fetch_option_link_obj( $this->fetch_incoming_link_param( 'id' ) );

        // As we required the user to login before they could access this app,
        // we now have access to the logged in user record

        $user_id = get_current_user_id();
        $display_name = dt_get_user_display_name( $user_id );
        $user_contact = Disciple_Tools_Users::get_contact_for_user( $user_id );


        // We also know who owns this user app
        $app_owner_id = $this->parts['post_id'];
        $app_owner = get_user_by( 'ID', $app_owner_id );
        $app_owner_display_name = dt_get_user_display_name( $app_owner_id );

        // @todo Create an app here that interacts with both the logged in user and the user who owns the app

        ?>
         <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x header">
                <div class="cell center">
                    <h2 id="title"><b><?php esc_html_e( "Your Teams' Contacts", 'disciple_tools' ) ?></b></h2>
                </div>
            </div>
            <hr>
            <div id="content">
                <div id="alert_notice" style="display: none; border-style: solid; border-width: 2px; border-color: #4caf50; background-color: rgba(142,195,81,0.2); border-radius: 5px; padding: 2em; margin: 1em 0">
                    <div style="display: flex; grid-gap: 1em">
                        <div style="display: flex; align-items: center">
                            <img style="width: 2em; filter: invert(52%) sepia(77%) saturate(383%) hue-rotate(73deg) brightness(98%) contrast(83%);"
                                 src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'exclamation-circle.svg' ); ?>" alt="Exclamation Circle"/>
                        </div>
                        <div id="alert_notice_content" style="display: flex; align-items: center"></div>
                    </div>
                </div>
                <div id="assigned_contacts_div" style="display: none;">
                    <h3><?php esc_html_e( 'Contacts', 'disciple_tools' ) ?> [ <span id="total">0</span> ]</h3>
                    <hr>
                    <div class="grid-x api-content-div-style" id="api-content">
                        <table class="api-content-table">
                            <thead>
                                <th><?php esc_html_e( 'Name', 'disciple_tools' ) ?></th>
                                <th><?php esc_html_e( 'Teams', 'disciple_tools' ) ?>
                                <th><?php esc_html_e( 'Creation Date', 'disciple_tools' ) ?></th>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ERROR MESSAGES -->
                <span id="error" style="color: red;"></span>
                <br>

                <div class="contact_detail_container" style="display: none;">
                    <h3><span id="contact_name"></span></h3>
                    <hr>
                    <div class="grid-x" id="form-content">
                        <input id="post_id" type="hidden"/>
                        <?php
                        $field_settings = DT_Posts::get_post_field_settings( 'contacts', false );
                        ?>
                        <table style="display: none;" class="form-content-table">
                            <tbody>
                            <tr id="form_content_name_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['name']['name'] ); ?></b></td>
                                <td id="form_content_name_td"></td>
                            </tr>
                            <tr id="form_content_date_of_birth_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['date_of_birth']['name'] ); ?></b></td>
                                <td id="form_content_date_of_birth_td"></td>
                            </tr>
                            <tr id="form_content_contact_address_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['contact_address']['name'] ); ?></b></td>
                                <td id="form_content_contact_address_td"></td>
                            </tr>
                            <tr id="form_content_contact_phone_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['contact_phone']['name'] ); ?></b></td>
                                <td id="form_content_contact_phone_td"></td>
                            </tr>
                            <tr id="form_content_nedenisa_prayer_request_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['nedenisa_prayer_request']['name'] ); ?></b></td>
                                <td id="form_content_nedenisa_prayer_request_td"></td>
                            </td>
                            <tr id="form_content_milestones_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['milestones']['name'] ); ?></b></td>
                                <td id="form_content_milestones_td"></td>
                            </tr>
                            <tr id="form_content_overall_status_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['overall_status']['name'] ); ?></b></td>
                                <td id="form_content_overall_status_td"></td>
                            </tr>
                            <tr id="form_content_faith_status_tr">
                                <td style="vertical-align: top;">
                                    <b><?php echo esc_attr( $field_settings['faith_status']['name'] ); ?></b></td>
                                <td id="form_content_faith_status_td"></td>
                            </tr>
                            <tr id="form_content_comments_tr">
                                <td style="vertical-align: top;">
                                    <b><?php esc_html_e( 'Comments', 'disciple_tools' ) ?></b>
                                </td>
                                <td id="form_content_comments_td"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <br>

                    <!-- SUBMIT UPDATES -->
                    <button id="content_submit_but" style="display: none; min-width: 100%;" class="button select-button">
                        <?php esc_html_e( 'Submit Update', 'disciple_tools' ) ?>
                        <span class="update-loading-spinner loading-spinner" style="height: 17px; width: 17px; vertical-align: text-bottom;"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'endpoint_get' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/' . $this->type . '/post', [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'get_post' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        /**
                         * Adjust global values accordingly, so as to accommodate both wp_user
                         * and post requests.
                         */
                        $this->adjust_global_values_by_incoming_sys_type( $request->get_params()['sys_type'] );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'update_record' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );

        register_rest_route(
            $namespace, '/' . $this->type . '/update', [
                [
                    'methods'             => 'GET',
                    'callback'            => [ $this, 'update_record' ],
                    'permission_callback' => function ( WP_REST_Request $request ) {
                        $magic = new DT_Magic_URL( $this->root );

                        /**
                         * Adjust global values accordingly, so as to accommodate both wp_user
                         * and post requests.
                         */
                        $this->adjust_global_values_by_incoming_sys_type( $request->get_params()['sys_type'] );

                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function update_record( WP_REST_Request $request ) {
        $params = $request->get_params();
        dt_write_log('update_record');
        dt_write_log($params);

        if ( !isset( $params['post_id'], $params['parts'], $params['action'], $params['sys_type'] ) ){
            return new WP_Error( __METHOD__, 'Missing core parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( ! is_user_logged_in() ) {
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        // Capture name, if present
        $updates = [];
        if ( isset( $params['name'] ) && !empty( $params['name'] ) ){
            $updates['name'] = $params['name'];
        }

        // Capture overall status
        if ( isset( $params['overall_status'] ) && !empty( $params['overall_status'] ) ){
            $updates['overall_status'] = $params['overall_status'];
        }

        // Capture faith status
        if ( isset( $params['faith_status'] ) ){
            $updates['faith_status'] = $params['faith_status'];
        }

        // Capture milestones
        if ( isset( $params['milestones'] ) ){
            $milestones = [];
            foreach ( $params['milestones'] ?? [] as $milestone ){
                $entry = [];
                $entry['value'] = $milestone['value'];
                if ( strtolower( trim( $milestone['delete'] ) ) === 'true' ){
                    $entry['delete'] = true;
                }
                $milestones[] = $entry;
            }
            if ( !empty( $milestones ) ){
                $updates['milestones'] = [
                    'values' => $milestones
                ];
            }
        }

        // Update specified post record
        if ( empty( $params['post_id'] ) ) {
            // if ID is empty ("0", 0, or generally falsy), create a new post
            $updates['type'] = 'access';

            // Assign new item to parent post record.
            if ( isset( $params['parts']['post_id'] ) ){
                $updates['assigned_to'] = 'user-' . $params['parts']['post_id'];
            }

            $updated_post = DT_Posts::create_post( 'contacts', $updates, false, false );
        } else {
            $updated_post = DT_Posts::update_post( 'contacts', $params['post_id'], $updates, false, false );
        }
        if ( empty( $updated_post ) || is_wp_error( $updated_post ) ) {
            return [
                'id' => 0,
                'success' => false,
                'message' => 'Unable to update/create contact record details!'
            ];
        }

        // Add any available comments
        if ( isset( $params['comments'] ) && ! empty( $params['comments'] ) ) {
            $updated_comment = DT_Posts::add_post_comment( $updated_post['post_type'], $updated_post['ID'], $params['comments'], 'comment', [], false );
            if ( empty( $updated_comment ) || is_wp_error( $updated_comment ) ) {
                return [
                    'id' => $updated_post['ID'],
                    'success' => false,
                    'message' => 'Unable to add comment to contact record details!'
                ];
            }
        }

        // Finally, return successful response
        return [
            'id' => $updated_post['ID'],
            'success' => true,
            'message' => ''
        ];
    }

    public function endpoint_get( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['parts'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }
        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( ! is_user_logged_in() ){
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        // Fetch corresponding contacts post record
        $response = [];
        if ( $params['parts']['post_id'] > 0 ){
            $user_id = $params['parts']['post_id'];

            $contact_id = get_user_option( 'corresponds_to_contact', $user_id ) ?: [];

            $connections = p2p_get_connections( 'teams_to_contacts', [
                'from' => $contact_id,
            ]);

            $team_ids = array_map( function ( $connection ) {
                return $connection->p2p_to;
            }, $connections );

            $team_connections = p2p_get_connections( 'contacts_to_teams', [
                'from' => $team_ids,
            ]);

            $team_contacts = array_unique(array_map(function ($connection) {
                return $connection->p2p_to;
            }, $team_connections));

            $posts = [];
            $comments = [];

            if (is_array($team_contacts)) {
                foreach ($team_contacts as $contact_id) {
                    $post = DT_Posts::get_post('contacts', $contact_id, false);
                    if ($post) {
                        $posts[] = $post;
                        $comments[] = DT_Posts::get_post_comments( 'contacts', $contact_id, false, 'all', [ 'number' => $params['comment_count'] ] );
                    }
                }
            } else {
                $post = DT_Posts::get_post('contacts', $team_contacts, false);
                if ($post) {
                    $posts[] = $post;
                    $comments[] = DT_Posts::get_post_comments( 'contacts', $contact_id, false, 'all', [ 'number' => $params['comment_count'] ] );
                }
            }
        } else {
            $posts = [
                'ID' => 0,
                'post_type' => 'contacts'
            ];
        }

        if ( ! empty( $posts ) && ! is_wp_error( $posts ) ) {
            $response['success']  = true;
            $response['posts']     = $posts;
            $response['comments'] = $comments;
        } else {
            $response['success'] = false;
        }

        return $response;
    }

    public function get_post( WP_REST_Request $request ) {
        $params = $request->get_params();
        if ( ! isset( $params['post_id'], $params['parts'], $params['action'], $params['comment_count'], $params['sys_type'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        // Sanitize and fetch user/post id
        $params = dt_recursive_sanitize_array( $params );

        // Update logged-in user state if required accordingly, based on their sys_type
        if ( ! is_user_logged_in() ){
            $this->update_user_logged_in_state( $params['sys_type'], $params['parts']['post_id'] );
        }

        // Fetch corresponding contacts post record
        $response = [];
        if ( $params['post_id'] > 0 ){
            $post = DT_Posts::get_post( 'contacts', $params['post_id'], false );
        } else {
            $post = [
                'ID' => 0,
                'post_type' => 'contacts'
            ];
        }
        if ( ! empty( $post ) && ! is_wp_error( $post ) ) {
            $response['success']  = true;
            $response['post']     = $post;
            $response['comments'] = ( $post['ID'] > 0 ) ? DT_Posts::get_post_comments( 'contacts', $params['post_id'], false, 'all', [ 'number' => $params['comment_count'] ] ) : [];
        } else {
            $response['success'] = false;
        }

        return $response;
    }


    public function update_user_logged_in_state( $sys_type, $user_id ) {
        switch ( strtolower( trim( $sys_type ) ) ) {
            case 'post':
                wp_set_current_user( 0 );
                $current_user = wp_get_current_user();
                $current_user->add_cap( 'magic_link' );
                $current_user->display_name = sprintf( __( '%s Submission', 'disciple_tools' ), apply_filters( 'dt_magic_link_global_name', __( 'Magic Link', 'disciple_tools' ) ) );
                break;
            default: // wp_user
                wp_set_current_user( $user_id );
                break;

        }
    }
}
Disciple_Tools_Team_Module_Magic_Login_User_App::instance();
