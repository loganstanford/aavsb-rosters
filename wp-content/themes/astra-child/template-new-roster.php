<?php
/* Template Name: New Roster */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

add_filter( 'body_class', function( $classes ) {
    if ( is_page_template( 'template-new-roster.php' ) ) {
        $classes[] = 'new-roster-page';
    }
    return $classes;
} );

?>



<style>

</style>


<?php if ( astra_page_layout() == 'left-sidebar' ) : ?>
<?php get_sidebar(); ?>
<?php endif ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_roster'])) {
    // Handle the form submission here
    // Validate and sanitize inputs
    // Save the new roster and attendees

    // Redirect to a confirmation page or display a success message
}

?>
<button type="button" style="max-width: 180px;"
    onclick="window.location.href='<?php echo esc_url(get_permalink(get_page_by_path("manage-rosters"))); ?>';"
    class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Rosters</button>
<h1>New Roster</h1>
<form id="new-roster-form" method="post">
    <!-- Combined Step 1: Course Details -->
    <div id="step1">
        <div class="course-type">
            <label for="course_type">What course type is your roster for?</label>
            <select id="course_type" name="course_type" class="form-control" required>
                <option value="" disabled selected>-- Select Course Type --</option>
                <option value="full">Full Session</option>
                <option value="multi">Multi Session</option>
            </select>
        </div>
        <div class="provider-number">
            <label for="provider_number">Provider Number:</label>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">50-</span>
                </div>
                <input type="text" class="form-control" id="provider_number" name="provider_number"
                    aria-describedby="basic-addon1" required oninput="prependProviderNumber()">
            </div>
        </div>

        <div class="course-number">
            <label for="course_number">Course Number:</label>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon2">20-</span>
                </div>
                <input type="text" class="form-control" id="course_number" name="course_number"
                    aria-describedby="basic-addon2" required oninput="prependCourseNumber()">
            </div>
        </div>

        <button type="button" class="btn btn-primary" onclick="showStep(2);">Next</button>
    </div>



    <!-- Step 2: Add Attendees -->
    <div id="step2" style="display:none;">
        <div class="top-container">

            <!-- Display Course Details -->
            <div class="roster-details">
                <h3>Course Details</h3>
                <p><strong>Course Type:</strong> <span id="display_course_type"></span></p>
                <p><strong>Provider Number:</strong> <span id="display_provider_number"></span></p>
                <p><strong>Course Number:</strong> <span id="display_course_number"></span></p>
            </div>


            <div class="button-group top-buttons">
                <button type="button" onclick="startOver();" class="btn btn-secondary"><i class="fas fa-undo-alt"></i>
                    Start
                    Over</button>
                <button type="button" onclick="saveProgress();" class="btn btn-info"><i class="fas fa-save"></i> Save
                    Progress</button>
            </div>
        </div>

        <div class="table-container">
            <table id="attendees-table">
                <thead>
                    <tr>
                        <th>License Number</th>
                        <th>State</th>
                        <th>Date of Completion</th>
                        <th class="profession-header">Profession</th>
                        <th class="multi-field medical-hours-header">Medical Hours</th>
                        <th class="multi-field non-medical-hours-header">Non-Medical Hours</th>
                        <th id="actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Attendee rows will be added here dynamically -->
                </tbody>
            </table>
            <div class="button-group bottom-buttons">

                <button type="button" onclick="addAttendeeForm();" class="btn btn-success"><i class="fas fa-plus"></i>
                    Add Attendee</button>
                <button type="submit" id="submit-button" class="btn btn-primary" name="submit_roster">
                    <i class="fas fa-check" id="submit-icon"></i>
                    <div class="spinner" id="submit-spinner" style="display: none;"></div> Submit Roster
                </button>

            </div>
        </div>
    </div>


</form>


<?php if ( astra_page_layout() == 'right-sidebar' ) : ?>
<?php get_sidebar(); ?>
<?php endif ?>

<?php get_footer(); ?>