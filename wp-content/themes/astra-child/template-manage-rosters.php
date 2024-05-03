<?php
/* Template Name: Manage Rosters */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header(); ?>

<?php if ( astra_page_layout() == 'left-sidebar' ) : ?>
<?php get_sidebar(); ?>
<?php endif ?>

<div id="primary" <?php astra_primary_class(); ?>>

    <?php astra_primary_content_top(); ?>

    <?php astra_content_page_loop(); ?>

    <!-- Custom Query to Display User Rosters -->
    <?php if ( is_user_logged_in() ): ?>
    <?php
            $current_user = wp_get_current_user();
            $args = array(
                'post_type'      => 'roster',  // Change to your custom post type name
                'posts_per_page' => -1
            );
            $rosters = new WP_Query($args);
            ?>
    <div class="header-items">
        <p><em>Admin view of all rosters</em></p>
        <button type="button" id="create-roster-button" class="btn btn-primary"
            onclick="window.location.href='<?php echo esc_url(get_permalink(get_page_by_path("new-roster"))); ?>';"><i
                class="fas fa-plus"></i> Create New Roster</button>
    </div>

    <?php
    
    if ($rosters->have_posts()) {
        echo '<table>';
        echo '<thead><tr><th>Provider Number</th><th>Course Number</th><th>Course Type</th><th>Date Created</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        while ($rosters->have_posts()) {
            $rosters->the_post();
            $provider_number = get_post_meta(get_the_ID(), 'provider_number', true);
            $course_number = get_post_meta(get_the_ID(), 'course_number', true);
            $course_type = get_post_meta(get_the_ID(), 'course_type', true);
            $courseTypeName = $course_type == 'full' ? "Full Session" : "Multi Session";
            $created_date = get_the_date('F j, Y g:i a', $post_id); // formats date as Month Day, Year
            $modified_date = get_the_modified_date('F j, Y g:i a', $post_id); // formats date as Month Day, Year
    
            echo '<tr>';
            echo '<td>' . esc_html("50-" . $provider_number) . '</td>';
            echo '<td>' . esc_html("20-" . $course_number) . '</td>';
            echo '<td>' . esc_html($courseTypeName)  . '</td>';
            echo '<td>'. esc_html($created_date) .'</td>';
            echo '<td class="action-buttons"><button type="button" class="btn btn-success export-roster" id="export-button-' . get_the_ID() . '" data-roster-id="' . get_the_ID() . '" onclick="exportRoster(this);"><i class="fas fa-file-export"></i> Export <span class="spinner-border spinner-border-sm" style="display:none;" id="spinner-' . get_the_ID() . '"></span></button>
            <button type="button" class="btn btn-info edit-roster" data-roster-id="' . get_the_ID() . '" onclick="editRoster(this);"><i class="fas fa-edit"></i> Edit</button></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo 'No rosters found.';
    }
    wp_reset_postdata();
    
    
        ?>
    <?php else: ?>
    <p>Please log in to manage your rosters.</p>
    <?php endif; ?>

    <?php astra_primary_content_bottom(); ?>

</div><!-- #primary -->

<script>
// Function to handle edit roster action
function editRoster(button) {
    var rosterId = button.getAttribute('data-roster-id');
    console.log('Editing roster with ID: ' + rosterId);
    var location = '<?php echo esc_url(get_permalink(get_page_by_path("edit-roster"))); ?>';
    location += '?roster_id=' + encodeURIComponent(rosterId);
    console.log('Redirecting to edit roster page...' + location);
    window.location.href = location;
}

// Function to trigger AJAX request for exporting roster
function exportRoster(button) {
    var rosterId = button.getAttribute('data-roster-id');
    const data = new FormData();
    data.append("action", "export_roster");
    data.append("roster_id", rosterId);
    data.append("security", aavsbAjax.nonce);

    // Get spinner and button elements
    const spinner = document.getElementById('spinner-' + rosterId);
    const exportButton = document.getElementById('export-button-' + rosterId);

    // Disable button and show spinner
    exportButton.disabled = true;
    spinner.style.display = 'inline-block';

    fetch(aavsbAjax.ajaxurl, {
            method: "POST",
            body: data,
            credentials: "same-origin",
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.location.href = data.data.file_url; // Redirect to the file URL for download
            } else {
                console.error("Failed to export roster: ", data.message);
            }
            // Re-enable button and hide spinner
            spinner.style.display = 'none';
            exportButton.disabled = false;

        })
        .catch((error) => {
            console.error("Error exporting roster:", error);
        });
}
</script>


<?php if ( astra_page_layout() == 'right-sidebar' ) : ?>
<?php get_sidebar(); ?>
<?php endif ?>

<?php get_footer(); ?>