<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Class Disciple_Tools_Team_Module_Base
 * Load the core post type hooks into the Disciple.Tools system
 */
class Disciple_Tools_Team_Module_Base extends DT_Module_Base {

    /**
     * Define post type variables
     * @var string
     */
    public $post_type = 'teams';
    public $module = 'team';
    public $single_name = 'Team';
    public $plural_name = 'Teams';
    public static function post_type(){
        return 'teams';
    }

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }

        //setup post type
        add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 100 );
        add_filter( 'dt_set_roles_and_permissions', array( $this, 'dt_set_roles_and_permissions' ), 25, 1 ); //after contacts

        //setup tiles and fields
        add_filter( 'dt_custom_fields_settings', array( $this, 'dt_custom_fields_settings' ), 10, 2 );
        add_filter( 'dt_details_additional_tiles', array( $this, 'dt_details_additional_tiles' ), 10, 2 );
        add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), 99 );
        add_filter( 'dt_get_post_type_settings', array( $this, 'dt_get_post_type_settings' ), 20, 2 );

        add_filter( 'dt_post_create_fields', array( $this, 'dt_post_create_fields' ), 50, 2 );

        //list
        add_filter( 'dt_user_list_filters', array( $this, 'dt_user_list_filters' ), 100, 2 );
        add_filter( 'dt_filter_access_permissions', array( $this, 'dt_filter_access_permissions' ), 20, 2 );
        add_filter( 'dt_can_view_permission', array( $this, 'can_view_permission_filter' ), 10, 3 );
        add_filter( 'dt_can_update_permission', array( $this, 'dt_can_update_permission' ), 20, 3 );
        add_filter( 'dt_get_viewable_compact', array( $this, 'dt_filter_get_viewable_compact' ), 20, 4 );
    }

    public function after_setup_theme(){
        $this->single_name = __( 'Team', 'disciple-tools-team-module' );
        $this->plural_name = __( 'Teams', 'disciple-tools-team-module' );

        if ( class_exists( 'Disciple_Tools_Post_Type_Template' ) ) {
            new Disciple_Tools_Post_Type_Template( $this->post_type, $this->single_name, $this->plural_name );
        }
    }

      /**
     * Set the singular and plural translations for this post types settings
     * The add_filter is set onto a higher priority than the one in Disciple_tools_Post_Type_Template
     * so as to enable localisation changes. Otherwise the system translation passed in to the custom post type
     * will prevail.
     */
    public function dt_get_post_type_settings( $settings, $post_type ){
        if ( $post_type === $this->post_type ){
            $settings['label_singular'] = __( 'Team', 'disciple-tools-team-module' );
            $settings['label_plural'] = __( 'Teams', 'disciple-tools-team-module' );
        }
        return $settings;
    }

    /**
     * @todo define the permissions for the roles
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/roles-permissions.md#rolesd
     */
    public function dt_set_roles_and_permissions( $expected_roles ){
        $multiplier_permissions = Disciple_Tools_Roles::default_multiplier_caps(); // get the base multiplier permissions


        $base_team_member_permissions = wp_parse_args( array(
            'dt_list_users' => true,
            'access_specific_teams' => true, //give user permission to all posts their team(s) are assigned to
            'assign_any_contacts' => true, //assign contacts to others,
            'access_trainings' => true,
            'create_trainings' => true,
            'update_trainings' => true,
            'list_all_teams' => true,
        ), $multiplier_permissions );


        $general_all_teams_permissions = wp_parse_args( array(
            'dt_all_access_contacts' => true, //view and update all access contacts

            'view_any_groups' => true,
            'update_any_groups' => true,

            'view_any_trainings' => true,
            'update_any_trainings' => true,
        ), $base_team_member_permissions );

        $expected_roles['team_member'] = array(
            'label' => __( 'Team Member', 'disciple-tools-team-module' ),
            'description' => 'Interacts with Contacts, Groups, etc., for a given team',
            'permissions' => $base_team_member_permissions,
        );

        $expected_roles['team_collaborator'] = array(
            'label' => __( 'Team Collaborator', 'disciple-tools-team-module' ),
            'description' => 'Access to all Contacts, Groups, etc. for all teams',
            'permissions' => wp_parse_args( array(), $general_all_teams_permissions ),
            'order' => 20,
        );

        $expected_roles['team_leader'] = array(
            'label' => __( 'Team Leader', 'disciple-tools-team-module' ),
            'description' => 'Access to all Contacts, Groups, etc. for all teams and access to update their team',
            'permissions' => wp_parse_args( array(
                'access_teams' => true,
                'view_any_teams' => true,
                'update_my_teams' => true,
            ), $general_all_teams_permissions ),
            'order' => 20,
        );

        $expected_roles['teams_admin'] = array(
            'label' => __( 'Teams Admin', 'disciple-tools-team-module' ),
            'description' => 'Admin access to all teams',
            'permissions' => wp_parse_args( array(
                'view_project_metrics' => true,

                'access_teams' => true,
                'create_teams' => true,
                'view_any_teams' => true,
                'update_any_teams' => true,
            ), $general_all_teams_permissions ),
            'order' => 20,
        );


        // Only admins can view/update the teams post type
        if ( isset( $expected_roles['administrator'] ) ){
            $expected_roles['administrator']['permissions']['access_' . $this->post_type ] = true;
            $expected_roles['administrator']['permissions']['create_' . $this->post_type] = true;
            $expected_roles['administrator']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['administrator']['permissions']['update_any_'.$this->post_type ] = true;
        }
        if ( isset( $expected_roles['dt_admin'] ) ){
            $expected_roles['dt_admin']['permissions']['access_' . $this->post_type ] = true;
            $expected_roles['dt_admin']['permissions']['create_' . $this->post_type] = true;
            $expected_roles['dt_admin']['permissions']['view_any_'.$this->post_type ] = true;
            $expected_roles['dt_admin']['permissions']['update_any_'.$this->post_type ] = true;
        }

        return $expected_roles;
    }

    /**
     * Add custom fields
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/fields.md
     */
    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ) {
            // can this filter to just user contacts? (doesn't seem like it)
            $fields['members'] = array(
                'name' => __( 'Members', 'disciple-tools-team-module' ),
                'description' => __( 'Which contacts are members of this team.', 'disciple-tools-team-module' ),
                'type' => 'connection',
                'post_type' => 'contacts',
                'p2p_direction' => 'to',
                'p2p_key' => $this->post_type.'_to_contacts',
                'tile' => 'members',
                'icon' => plugin_dir_url( __FILE__ ) . '../assets/team.svg',
                'show_in_table' => 35,
            );
        } else {
            // add a teams field to all post types
            $fields['teams'] = array(
                'name' => __( 'Teams', 'disciple-tools-team-module' ),
                'description' => __( 'Which teams interact with and have access to this.', 'disciple-tools-team-module' ),
                'type' => 'connection',
                'post_type' => $this->post_type,
                'p2p_direction' => 'to',
                'p2p_key' => $post_type.'_to_'.$this->post_type,
                'tile' => 'status',
                'icon' => plugin_dir_url( __FILE__ ) . '../assets/team.svg',
                'show_in_table' => 17,
            );
        }

        // Connection to mark a contact/user as a member of a team
        if ( $post_type === 'contacts' ){
            $fields['member_' .$this->post_type] = array(
                'name' => __( 'Member of Teams', 'disciple-tools-team-module' ),
                'description' => '',
                'type' => 'connection',
                'post_type' => $this->post_type,
                'p2p_direction' => 'from',
                'p2p_key' => $this->post_type.'_to_contacts',
                'tile' => 'other',
                'icon' => plugin_dir_url( __FILE__ ) . '../assets/team.svg',
                'show_in_table' => 35,
            );
        }

        return $fields;
    }

    /**
     * Define tiles
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/field-and-tiles.md
     */
    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        if ( $post_type === $this->post_type ){
            $tiles['members'] = array( 'label' => __( 'Members', 'disciple-tools-team-module' ) );
            $tiles['other'] = array( 'label' => __( 'Other', 'disciple-tools-team-module' ) );
            $tiles['apps'] = array(
                'label' => __( 'Magic Links', 'disciple_tools' ),
                'description' => __( 'Magic Links available on this record.', 'disciple_tools' ),
            );
        }
        return $tiles;
    }

    public function dt_post_create_fields( $fields, $post_type ){
        if ( $post_type === 'contacts' && isset( $fields['assigned_to'] ) ) {
            // disable auto-assignment done by the access module
            unset( $fields['assigned_to'] );
        }
        return $fields;
    }

    private static function get_user_teams() {
        // get contact connected with current user
        $contact_id = get_user_option( 'corresponds_to_contact', get_current_user_id() ) ?? array();

        // get all teams this user is a member of
        $connections = p2p_get_connections( 'teams_to_contacts', array(
            'from' => $contact_id,
        ));

        $team_ids = array_map( function ( $connection ) {
            return $connection->p2p_to;
        }, $connections );

        return $team_ids;
    }

    //list page filters function

    /**
     * @todo adjust queries to support list counts
     * Documentation
     * @link https://github.com/DiscipleTools/Documentation/blob/master/Theme-Core/list-query.md
     */
    private static function get_my_teams_count( $post_type ){
        /**
         * @todo adjust query to return count for update needed
         */
        global $wpdb;
        $current_user = get_current_user_id();
        $results = $wpdb->get_var($wpdb->prepare( "
            SELECT count(p2p.p2p_to)
            FROM $wpdb->p2p p2p
            JOIN $wpdb->posts p ON (p.ID = p2p.p2p_to AND p.post_type = %s AND p.post_status = 'publish')
            JOIN $wpdb->p2p p2p2 ON (p2p.p2p_from = p2p2.p2p_to)
            JOIN $wpdb->postmeta pm ON (pm.post_id=p2p2.p2p_from AND meta_key='corresponds_to_user')
            WHERE p2p.p2p_type = CONCAT(%s, '_to_teams')
            AND pm.meta_value = %d
        ", $post_type, $post_type, $current_user ) );

        return $results;
    }

    //list page filters function
    private static function get_all_team_counts( $post_type ) {
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare( "
            SELECT p2p.p2p_from as team_id, team.post_title, count(p2p.p2p_to) as count
            FROM $wpdb->p2p p2p
            JOIN $wpdb->posts p ON (p.ID = p2p.p2p_to AND p.post_type = %s AND p.post_status = 'publish')
            JOIN $wpdb->posts team ON (team.ID = p2p.p2p_from)
            WHERE p2p.p2p_type = CONCAT(%s, '_to_teams')
            GROUP BY p2p.p2p_from, team.post_title #team
        ", $post_type, $post_type ), ARRAY_A );

        return $results;
    }


    //build list page filters
    public static function dt_user_list_filters( $filters, $post_type ){
        // Add filter to all post types besides teams, in order to filter by team
        if ( $post_type !== self::post_type() ) {
            // dt_write_log( "add teams filter to $post_type" );
            $tab = $post_type === 'contacts' ? 'default' : 'all';

            // Add Default filter tab if it doesn't exist yet
            if ( $post_type !== 'contacts' ) {
                $has_all_tab = array_search( 'all', array_column( $filters['tabs'], 'key' ) ) > -1;
                if ( !$has_all_tab ) {
                    $filters['tabs'][] = array(
                        'key' => 'all',
                        'label' => __( 'Default Filters', 'disciple_tools' ),
                        'order' => 10,
                    );
                    // add assigned to me filters
                    $filters['filters'][] = array(
                        'ID' => 'all',
                        'tab' => 'all',
                        'name' => _x( 'All', 'All records', 'disciple_tools' ),
                        'query' => array(
                            'sort' => '-post_date',
                        ),
                    );
                }
            }
            $user_team_ids = self::get_user_teams();

            $total_my = self::get_my_teams_count( $post_type );
            $counts = self::get_all_team_counts( $post_type );

            $team_counts = array();
            foreach ( $counts as $count ){
                $team_counts[$count['team_id']] = array(
                    'name' => $count['post_title'],
                    'count' => $count['count'],
                );
            }

            $settings = DT_Posts::get_post_settings( self::post_type() );
            // add assigned to team filters
            $filters['filters'][] = array(
                'ID' => 'my_team',
                'tab' => $tab,
                'name' => $settings['label_singular'],
                'query' => array(
                    'teams' => $user_team_ids,
                ),
                'count' => $total_my ?? null,
            );

            $can_view_single = current_user_can( 'access_specific_teams' ) && count( $user_team_ids ) === 1;
            $can_view_any = current_user_can( 'view_any_' . $post_type ) || current_user_can( 'dt_all_access_' . $post_type );
            if ( !$can_view_single || $can_view_any ) {

                foreach ( $team_counts as $team_id => $team_count ) {
                    $can_view_team = $can_view_any || array_search( $team_id, $user_team_ids ) > -1;

                    if ( $can_view_team ) {
                        $filters['filters'][] = array(
                            'ID' => 'team_' . $team_id,
                            'tab' => $tab,
                            'name' => $team_count['name'],
                            'query' => array(
                                'teams' => array( $team_id ),
                            ),
                            'count' => $team_count['count'],
                            'subfilter' => true,
                        );
                    }
                }
            }
        }
        return $filters;
    }

    // access permission
    public static function dt_filter_access_permissions( $permissions, $post_type ){
        if ( $post_type === self::post_type() ){
            if ( DT_Posts::can_view_all( $post_type ) ){
                $permissions = array();
            }

            // if has permission access_specific_teams and user.teams matches
        } else if ( !current_user_can( "view_any_$post_type" ) && current_user_can( 'access_specific_teams' ) ) {
            //give user permission to all posts their team(s) are assigned to
            $team_ids = self::get_user_teams();
            if ( !empty( $team_ids ) ){
                $permissions[] = array( 'teams' => $team_ids );
            }
        }
        return $permissions;
    }

    /**
     * Check if current user is in teams that can access given post
     * @param bool $has_permission
     * @param int $post_id
     * @return bool
     */
    public static function can_view_update_post( $has_permission, $post_id ) {
        if ( !$has_permission ) {
            $contact_id = get_user_option( 'corresponds_to_contact', get_current_user_id() ) ?? array();

            // Get all posts that the user's teams are assigned to
            global $wpdb;
            $accessible_post_ids = $wpdb->get_results($wpdb->prepare("
                SELECT user_team.p2p_from, team_posts.p2p_to
                FROM $wpdb->p2p as user_team
                JOIN $wpdb->p2p as team_posts on user_team.p2p_to=team_posts.p2p_from
                WHERE user_team.p2p_from = %d
            ", $contact_id));

            // Check if current post_id is in user's list of accessible posts
            foreach ( $accessible_post_ids as $p2p ) {
                if ( $p2p->p2p_to == $post_id ) {
                    $has_permission = true;
                    break;
                }
            }
        }

        return $has_permission;
    }

    // access permission
    public static function can_view_permission_filter( $has_permission, $post_id, $post_type ){
        if ( $post_type !== self::post_type() ) {
            if ( current_user_can( 'access_specific_teams' ) ) {
                //give user permission to all posts their team(s) are assigned to
                $has_permission = self::can_view_update_post( $has_permission, $post_id );
            }
        }
        return $has_permission;
    }
    public static function dt_can_update_permission( $has_permission, $post_id, $post_type ){
        if ( $post_type === self::post_type() ) {
            if ( current_user_can( 'update_my_teams' ) ) {
                $user_teams = self::get_user_teams();
                // dt_write_log( 'can_update: ' . json_encode( $post_id ) . ' | ' . json_encode( $user_teams ) );
                $has_permission = array_search( $post_id, $user_teams, false ) > -1;
            }
        } elseif ( current_user_can( 'access_specific_teams' ) ) {
            //give user permission to all posts their team(s) are assigned to
            $has_permission = self::can_view_update_post( $has_permission, $post_id );
        }
        return $has_permission;
    }

    public static function dt_filter_get_viewable_compact( $result, $post_type, $search_string, $args ) {
        // Allow all users to see
        if ( $post_type === 'teams' ) {
            $result = DT_Posts::list_posts( $post_type, array(), false );
            $compact = array();
            //filter out users if requested.
            foreach ( $result['posts'] as $post ) {
                if ( isset( $args['include-users'] ) && $args['include-users'] === 'false' && $post->corresponds_to_user >= 1 ) {
                    continue;
                }
                $compact[] = array(
                'ID' => $post['ID'],
                'name' => wp_specialchars_decode( $post['post_title'] ),
                );
            }
            return array(
            'total' => $result['total'],
            'raw' => $compact,
            'posts' => DT_Posts::capture_viewable_compact_post_record_status( $post_type, array_slice( $compact, 0, 50 ) ),
            );
        }
        return $result;
    }
    // scripts
    public function scripts(){
        if ( is_singular( $this->post_type ) && get_the_ID() && DT_Posts::can_view( $this->post_type, get_the_ID() ) ){
            $test = '';
            // @todo add enqueue scripts
        }
    }
}
