<?php
/*
Plugin Name: Custom CRUD Plugin
Plugin URI: https://wpquickcare.com
Description: A plugin to demonstrate CRUD operations on a custom database table using OOP principles in WordPress.
Version: 1.0
Author: SH Sajal Chowdhury
Author URI: https://wpquickcare.com 
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Custom_CRUD_Plugin {
    private $table_name;

    // Constructor to initialize the plugin
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'custom_crud';

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('admin_menu', [$this, 'create_admin_menu']);
        add_action('admin_post_save_custom_data', [$this, 'save_custom_data']);
        add_action('admin_post_delete_custom_data', [$this, 'delete_custom_data']);
    }

    // Function to handle plugin activation
    public function activate() {
        $this->create_table();
    }

    // Function to handle plugin deactivation
    public function deactivate() {
        // Cleanup or other deactivation tasks could go here
    }

    // Function to create the custom database table
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Function to create an admin menu page
    public function create_admin_menu() {
        add_menu_page(
            'Custom CRUD',
            'Custom CRUD',
            'manage_options',
            'custom-crud-plugin',
            [$this, 'admin_page'],
            'dashicons-database'
        );
    }

    // Function to display the admin page
    public function admin_page() {
        global $wpdb;

        // Handle add/edit form submission
        if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
            $name = $record->name;
            $email = $record->email;
        } else {
            $id = 0;
            $name = '';
            $email = '';
        }

        // Display add/edit form
        echo '<div class="wrap">';
        echo '<h2>Custom CRUD Operations</h2>';
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="save_custom_data">';
        echo '<input type="hidden" name="id" value="' . esc_attr($id) . '">';
        echo '<table class="form-table">';
        echo '<tr><th>Name</th><td><input type="text" name="name" value="' . esc_attr($name) . '" class="regular-text"></td></tr>';
        echo '<tr><th>Email</th><td><input type="email" name="email" value="' . esc_attr($email) . '" class="regular-text"></td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" class="button-primary" value="Save Data"></p>';
        echo '</form>';

        // Display table with existing records
        $results = $wpdb->get_results("SELECT * FROM $this->table_name");
        echo '<h2>Existing Records</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->name) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>';
            echo '<a href="?page=custom-crud-plugin&action=edit&id=' . esc_attr($row->id) . '">Edit</a> | ';
            echo '<a href="' . wp_nonce_url(admin_url('admin-post.php?action=delete_custom_data&id=' . esc_attr($row->id)), 'delete_custom_data_' . esc_attr($row->id)) . '">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    // Function to handle form data saving (Create/Update)
    public function save_custom_data() {
        global $wpdb;

        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);

        if ($id > 0) {
            // Update existing record
            $wpdb->update(
                $this->table_name,
                ['name' => $name, 'email' => $email],
                ['id' => $id]
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $this->table_name,
                ['name' => $name, 'email' => $email]
            );
        }

        wp_redirect(admin_url('admin.php?page=custom-crud-plugin'));
        exit;
    }

    // Function to handle data deletion
    public function delete_custom_data() {
        global $wpdb;

        if (isset($_GET['id']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_custom_data_' . $_GET['id'])) {
            $id = intval($_GET['id']);
            $wpdb->delete($this->table_name, ['id' => $id]);
        }

        wp_redirect(admin_url('admin.php?page=custom-crud-plugin'));
        exit;
    }
}

// Instantiate the class to ensure the plugin's functionality is executed
new Custom_CRUD_Plugin();
