<?php
/*
 * Template Name: Edit Roster
 */
get_header();

$roster_id = isset($_GET['roster_id']) ? intval($_GET['roster_id']) : 0;
if (!$roster_id) {
    echo '<p>Roster not found.</p>';
} else {
    // Fetch roster details
    $provider_number = get_post_meta($roster_id, 'provider_number', true);
    $course_number = get_post_meta($roster_id, 'course_number', true);
    $course_type = get_post_meta($roster_id, 'course_type', true);

    // Fetch and decode attendees
    $attendees_json = get_post_meta($roster_id, 'attendees', true);
    $attendees = json_decode($attendees_json, true);  // Decode the JSON string into an array
    
    $rosterJsPath = get_stylesheet_directory_uri() . '/js/roster.js';
?>

<script src="<?php echo $rosterJsPath; ?>"></script>
<button type="button" style="max-width: 180px;"
    onclick="window.location.href='<?php echo esc_url(get_permalink(get_page_by_path("manage-rosters"))); ?>';"
    class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Rosters</button>

<h1>Edit Roster</h1>
<form id="edit-roster-form" method="post">
    <div class="top-container">
        <!-- Editable Course Details -->
        <div class="roster-details">
            <h3>Course Details</h3>
            <label for="course_type">Course Type:</label>
            <select id="course_type" name="course_type">
                <option value="full" <?php echo ($course_type == 'full') ? 'selected' : ''; ?>>Full Session</option>
                <option value="multi" <?php echo ($course_type == 'multi') ? 'selected' : ''; ?>>Multi Session</option>
            </select>
            </p>
            <div class="provider-number">
                <label for="provider_number">Provider Number:</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">50-</span>
                    </div>
                    <input type="text" class="form-control" id="provider_number" name="provider_number"
                        aria-describedby="basic-addon1" required oninput="prependProviderNumber()"
                        value="<?php echo esc_attr($provider_number); ?>">
                </div>
            </div>
            <div class="course-number">
                <label for="course_number">Course Number:</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon2">20-</span>
                    </div>
                    <input type="text" class="form-control" id="course_number" name="course_number"
                        aria-describedby="basic-addon2" required oninput="prependCourseNumber()"
                        value="<?php echo esc_attr($course_number); ?>">
                </div>
            </div>
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
                <?php if ($attendees): ?>
                <?php foreach ($attendees as $index => $attendee): ?>
                <tr>
                    <td><input type="text" name="attendees[<?php echo $index; ?>][license_number]"
                            value="<?php echo esc_attr($attendee['license_number']); ?>"></td>
                    <td>
                        <select name="attendees[<?php echo $index; ?>][state]">
                            <script>
                            document.write(stateOptions);
                            </script>
                        </select>
                        <script>
                        // Set the selected state for the current attendee
                        document.querySelector("select[name='attendees[<?php echo $index; ?>][state]']")
                            .value = "<?php echo esc_attr($attendee['state']); ?>";
                        </script>
                    </td>
                    <td>
                        <input type="date" name="attendees[<?php echo $index; ?>][date_of_completion]"
                            value="<?php echo esc_attr($attendee['date_of_completion']); ?>">
                    </td>
                    <td>
                        <select name="attendees[<?php echo $index; ?>][profession]">
                            <option value="Veterinarian"
                                <?php echo ($attendee['profession'] == 'Veterinarian') ? 'selected' : ''; ?>>Vet
                            </option>
                            <option value="Veterinarian Technician"
                                <?php echo ($attendee['profession'] == 'Veterinarian Technician') ? 'selected' : ''; ?>>
                                Vet Tech</option>
                        </select>
                    </td>
                    <td class="multi-field medical-hours-cell">
                        <?php if ($course_type === 'multi'): ?>
                        <input type="number" name="attendees[<?php echo $index; ?>][medical_hours]"
                            value="<?php echo esc_attr($attendee['medical_hours']); ?>">
                        <?php endif; ?>
                    </td>
                    <td class="multi-field non-medical-hours-cell">
                        <?php if ($course_type === 'multi'): ?>
                        <input type="number" name="attendees[<?php echo $index; ?>][non_medical_hours]"
                            value="<?php echo esc_attr($attendee['non_medical_hours']); ?>">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger" onclick="removeAttendee(this);">Remove</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>


        </table>
        <div class="button-group bottom-buttons">
            <button type="button" onclick="addAttendeeForm();" class="btn btn-success"><i class="fas fa-plus"></i> Add
                Attendee</button>
            <button type="submit" id="save-button" class="btn btn-primary"><i class="fas fa-check" id="submit-icon"></i>
                <div class="spinner" id="submit-spinner" style="display: none;"></div> Save Changes
            </button>
        </div>
    </div>
    <input type="hidden" name="post_id" value="<?php echo $roster_id; ?>">
</form>

<?php
}

get_footer();
?>

<script>
function removeAttendee(button) {
    const row = button.closest('tr');
    row.parentNode.removeChild(row);
}
document.addEventListener("DOMContentLoaded", function() {

    // Event listener for course type changes
    const courseTypeSelect = document.getElementById('course_type');
    courseTypeSelect.addEventListener('change', function() {
        updateAttendeeRows(this.value);
    });

    // Function to update attendee rows based on course type
    function updateAttendeeRows(courseType) {

        const table = document.getElementById('attendees-table');
        const rows = table.getElementsByTagName('tbody')[0].rows;


        for (let row of rows) {
            const medicalHoursCell = row.querySelector('.medical-hours-cell');
            const nonMedicalHoursCell = row.querySelector('.non-medical-hours-cell');

            if (courseType === 'multi') {
                document.querySelectorAll('.multi-field').forEach(function(el) {
                    el.style.display = 'table-cell';
                });
                // Add medical and non-medical hours if they do not exist
                if (!medicalHoursCell) {
                    const medicalCell = row.insertCell(4); // Insert before the last cell
                    medicalCell.className = 'medical-hours-cell';
                    medicalCell.innerHTML = '<input type="number" name="attendees[' + row.rowIndex +
                        '][medical_hours]" required>';
                }
                if (!nonMedicalHoursCell) {
                    const nonMedicalCell = row.insertCell(5); // Insert before the last cell
                    nonMedicalCell.className = 'non-medical-hours-cell';
                    nonMedicalCell.innerHTML = '<input type="number" name="attendees[' + row.rowIndex +
                        '][non_medical_hours]" required>';
                }
            } else {
                document.querySelectorAll('.multi-field').forEach(function(el) {
                    el.style.display = 'none';
                });
                // Remove medical and non-medical hours if they exist
                if (medicalHoursCell) medicalHoursCell.remove();
                if (nonMedicalHoursCell) nonMedicalHoursCell.remove();
            }
        }
    }

    // Initial call to set up rows correctly based on the initially selected course type
    updateAttendeeRows(courseTypeSelect.value);
});
</script>