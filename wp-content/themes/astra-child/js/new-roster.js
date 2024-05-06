// Ensure to call this function to initialize the form and attach the validation
document.addEventListener("DOMContentLoaded", function () {
  initializeForm();
});

function initializeForm() {
  const form = document.getElementById("new-roster-form");
  if (!form) return; // Early exit if form is not found

  form.addEventListener("submit", handleSubmit);

  document
    .getElementById("course_type")
    .addEventListener("change", updateFieldsBasedOnCourseType);

  updateFieldsBasedOnCourseType(); // Initialize fields on page load
}

function validateLicenseNumber(input, licenseNumber, boardCode) {
  if (!licenseNumber || !boardCode) {
    console.error("License number and State is required.");
    return Promise.reject("License number and State are required."); // Reject the promise immediately
  }

  // Disable input and add spinner
  input.disabled = true;
  let iconSpan = input.nextElementSibling || document.createElement("span");
  iconSpan.className = "fas fa-spinner fa-spin input-icon";
  iconSpan.setAttribute("data-toggle", "tooltip");
  iconSpan.setAttribute("title", ""); // Default empty title
  if (!input.nextElementSibling) {
    input.parentNode.insertBefore(iconSpan, input.nextSibling);
  }

  const formData = new FormData();
  formData.append("action", "validate_license_number");
  formData.append("license_number", licenseNumber);
  formData.append("board_code", boardCode);
  formData.append("validation_security", aavsbAjax.validation_nonce);

  return fetch(aavsbAjax.ajaxurl, {
    // Return the promise from fetch
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then((data) => {
      // Remove spinner and update with icon
      iconSpan.className = data.valid
        ? "fas fa-check-circle input-icon text-success"
        : "fas fa-times-circle input-icon text-danger";
      let message = data.valid
        ? ""
        : "This license was not found in our system. Double check that it was entered correctly. If the license number is correct, this person doesn't have an account with us and this CE will show as a rejection until they create an account.";
      input.setAttribute("title", message);
      jQuery(input).tooltip("dispose").tooltip(); // Reinitialize the tooltip

      input.classList.toggle("error", !data.valid);
      input.isValidated = data.valid; // Update the validated status on the input
      return data.valid; // Return the validation status for further processing
    })
    .catch((error) => {
      console.error("Error validating license number:", error);
      iconSpan.className = "fas fa-exclamation-circle text-warning";

      input.setAttribute("title", "Error during validation. Please retry.");
      jQuery(input).tooltip("dispose").tooltip(); // Reinitialize the tooltip

      input.disabled = false; // Re-enable the input for editing
      input.isValidated = false; // Indicate validation failure
      return false; // Return false as the validation failed
    })
    .finally(() => {});
}

function handleSubmit(e) {
  console.log("Form submitted");
  e.preventDefault();

  // Attempt to automatically save any unsaved changes
  const rows = document.querySelectorAll("#attendees-table tbody tr");
  let allSaved = true;

  // Toggle the spinner in the button
  const submitButton = document.getElementById("submit-button");
  const submitIcon = document.getElementById("submit-icon");
  const submitSpinner = document.getElementById("submit-spinner");

  submitButton.disabled = true; // Disable the button to prevent multiple submissions
  submitIcon.style.display = "none"; // Hide the check icon
  submitSpinner.style.display = "inline-block"; // Show the spinner

  rows.forEach((row) => {
    const saveButton = row.querySelector(".save-attendee");
    if (saveButton && saveButton.style.display !== "none") {
      // Attempt to save the row if it's not saved
      if (!saveAttendee(saveButton)) {
        allSaved = false; // Set flag to false if saving fails
      }
    }
  });

  if (!allSaved) {
    alert("Some rows could not be saved. Please correct the errors.");
    return; // Stop the submission if there are errors
  }

  // All rows are saved, proceed with form data submission
  const formData = new FormData(document.getElementById("new-roster-form"));
  formData.append("action", "submit_roster");
  formData.append("security", aavsbAjax.nonce);

  // Send the form data to the server
  fetch(aavsbAjax.ajaxurl, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((data) => handleResponse(data))
    .catch((error) => {
      // Reset button states if there's an error
      submitButton.disabled = false;
      submitIcon.style.display = "inline-block";
      submitSpinner.style.display = "none";
      console.error("Error:", error);
    });
}

function handleResponse(data) {
  const submitButton = document.getElementById("submit-button");
  const submitIcon = document.getElementById("submit-icon");
  const submitSpinner = document.getElementById("submit-spinner");

  if (data.success) {
    console.log("File URL:", data.data.file_url);
    console.log("Data:", data);
    console.log("Data message:", data.message); // Accessing message directly from data if there's no error

    const providerNumber = document.getElementById("provider_number").value;
    const courseNumber = document.getElementById("course_number").value;

    // Create a link element and trigger the download
    const link = document.createElement("a");
    link.href = data.data.file_url; // Ensure this is accessing the correct property
    link.download = `${providerNumber}_${courseNumber}_roster.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } else {
    console.log("Error:", data.message); // Accessing message directly from data if there's an error
    alert("An error occurred: " + data.message);
  }

  // Reset the button states
  submitButton.disabled = false;
  submitIcon.style.display = "inline-block";
  submitSpinner.style.display = "none";
}

function capitalizeFirstLetter(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

function showStep(step) {
  // Check if moving to step 2 requires validation
  if (step === 2) {
    updateFieldsBasedOnCourseType(); // Update fields based on course type
    const courseTypeInput = document.getElementById("course_type");
    const providerNumberInput = document.getElementById("provider_number");
    const courseNumberInput = document.getElementById("course_number");
    let valid = true;

    // Remove existing errors
    courseTypeInput.classList.remove("error");
    providerNumberInput.classList.remove("error");
    courseNumberInput.classList.remove("error");

    // Check if the course type input has value
    if (!courseTypeInput.value.trim()) {
      courseTypeInput.classList.add("error");
      valid = false;
    }

    // Check if the provider number input has value after "50-"
    if (!providerNumberInput.value.trim()) {
      providerNumberInput.classList.add("error");
      valid = false;
    }

    // Check if the course number input has value after "20-"
    if (!courseNumberInput.value.trim()) {
      courseNumberInput.classList.add("error");
      valid = false;
    }

    if (!valid) {
      return; // Stop the function if inputs are not valid
    }

    // Update display fields if inputs are valid
    document.getElementById("display_course_type").textContent =
      capitalizeFirstLetter(document.getElementById("course_type").value);
    document.getElementById("display_provider_number").textContent =
      "50-" + providerNumberInput.value;
    document.getElementById("display_course_number").textContent =
      "20-" + courseNumberInput.value;

    // Check if course type is 'full', then remove multi fields
    if (courseTypeInput.value === "full") {
      document.querySelectorAll(".multi-field").forEach((field) => {
        field.remove(); // Remove the field from the DOM
      });
    }
  }

  // Move to the specified step if all checks are passed
  for (let i = 1; i <= 2; i++) {
    document.getElementById("step" + i).style.display =
      i === step ? "block" : "none";
  }
}

let attendeeIndex = 0;

function addAttendeeForm() {
  const table = document
    .getElementById("attendees-table")
    .getElementsByTagName("tbody")[0];
  const lastRowIndex = table.rows.length - 1;

  if (lastRowIndex >= 0) {
    const lastRow = table.rows[lastRowIndex];
    const saveButton = lastRow.querySelector(".save-attendee");

    // Check if the last row is in editable state and try to save it
    if (saveButton && saveButton.style.display !== "none") {
      if (!saveAttendee(saveButton)) {
        // saveAttendee now returns a boolean indicating success
        console.error("Fix errors before adding a new attendee.");
        return; // Stop the function if there are errors
      }
    }
  }

  const newRow = table.insertRow();

  // Base HTML for a new row
  newRow.innerHTML = `
    <td><div class="input-icon-container"><input type="text" name="attendees[${attendeeIndex}][license_number]" required /></div></td>
    <td><select name="attendees[${attendeeIndex}][state]" required>${stateOptions}</select></td>
    <td><input type="date" name="attendees[${attendeeIndex}][date_of_completion]" required max="${getCurrentDate()}"/></td>
    <td><select name="attendees[${attendeeIndex}][profession]" required>
      <option value="Veterinarian">Vet</option>
      <option value="Veterinarian Technician">Vet Tech</option>
    </select></td>
    <td class="multi-field medical-hours"></td>
    <td class="multi-field non-medical-hours"></td>
    <td>
      <button type="button" class="btn btn-success save-attendee" onclick="saveAttendee(this);">Save</button>
      <button type="button" class="btn btn-info edit-attendee" onclick="editAttendee(this);" style="display: none;">Edit</button>
      <button type="button" class="btn btn-danger delete-attendee" onclick="deleteRow(event);">Delete</button>
    </td>
  `;

  // Update the multi fields based on the course type
  if (document.getElementById("course_type").value === "full") {
    document.querySelectorAll(".multi-field").forEach((field) => {
      field.style.display = "none"; // Remove display: none
    });
  } else {
    medicalHours = newRow.querySelector(".medical-hours");
    medicalHours.innerHTML = `<input type="number" name="attendees[${attendeeIndex}][medical_hours]" required min="0" max="99">`;

    nonMedicalHours = newRow.querySelector(".non-medical-hours");
    nonMedicalHours.innerHTML = `<input type="number" name="attendees[${attendeeIndex}][non_medical_hours]" required min="0" max="99">`;
  }

  // Attach event listeners to the delete buttons
  const deleteButtons = newRow.querySelector(".delete-attendee");
  if (deleteButtons) {
    deleteButtons.onclick = deleteRow;
  }

  // Carry over the values from the previous row if it exists
  if (lastRowIndex >= 0) {
    const lastRow = table.rows[lastRowIndex];
    const lastStateSelect = lastRow.querySelector(
      `select[name="attendees[${lastRowIndex}][state]"]`
    );
    const lastDateInput = lastRow.querySelector(
      `input[name="attendees[${lastRowIndex}][date_of_completion]"]`
    );
    const lastProfessionSelect = lastRow.querySelector(
      `select[name="attendees[${lastRowIndex}][profession]"]`
    );

    const newStateSelect = newRow.querySelector(
      `select[name="attendees[${attendeeIndex}][state]"]`
    );
    const newDateInput = newRow.querySelector(
      `input[name="attendees[${attendeeIndex}][date_of_completion]"]`
    );
    const newProfessionSelect = newRow.querySelector(
      `select[name="attendees[${attendeeIndex}][profession]"]`
    );

    // Set the values for the new row to be the same as the last, except for the license number
    if (newStateSelect && lastStateSelect)
      newStateSelect.value = lastStateSelect.value;
    if (newDateInput && lastDateInput) newDateInput.value = lastDateInput.value;
    if (newProfessionSelect && lastProfessionSelect)
      newProfessionSelect.value = lastProfessionSelect.value;
  }

  attendeeIndex++; // Increment for the next attendee
  //updateFieldsBasedOnCourseType(); // Update fields if course type is multi
}

function updateMultiFields(row, index) {
  const courseType = document.getElementById("course_type").value;
  const medicalFieldContainer = row.querySelector(".medical-field"); // Use querySelector to find the correct container
  const nonMedicalFieldContainer = row.querySelector(".non-medical-field"); // Use querySelector to find the correct container

  if (courseType === "multi") {
    medicalFieldContainer.innerHTML = `<input type="number" name="attendees[${index}][medical_hours]" required>`;
    nonMedicalFieldContainer.innerHTML = `<input type="number" name="attendees[${index}][non_medical_hours]" required>`;
  } else {
    medicalFieldContainer.innerHTML = "";
    nonMedicalFieldContainer.innerHTML = "";
  }

  // Disable the input fields for "Medical Hours" and "Non-Medical Hours" in previous rows
  const table = document.getElementById("attendees-table");
  for (let i = 1; i < table.rows.length - 1; i++) {
    const medicalInput = table.rows[i].querySelector(
      `input[name="attendees[${i - 1}][medical_hours]"]`
    );
    const nonMedicalInput = table.rows[i].querySelector(
      `input[name="attendees[${i - 1}][non_medical_hours]"]`
    );

    if (medicalInput) {
      medicalInput.disabled = true;
    }
    if (nonMedicalInput) {
      nonMedicalInput.disabled = true;
    }
  }
}

function deleteRow(e) {
  const row = e.target.closest("tr");
  row.parentNode.removeChild(row);
  attendeeIndex--; // Decrement the attendee index when deleting a row
}

function saveAttendee(button) {
  const row = button.closest("tr");
  let isValid = true;

  // Locate the license number input specifically
  const licenseInput = row.querySelector(
    "input[name^='attendees'][name$='[license_number]']"
  );
  const stateSelect = row.querySelector(
    "select[name^='attendees'][name$='[state]']"
  );

  validateLicenseNumber(
    licenseInput,
    licenseInput.value.trim(),
    stateSelect.value
  ).then((isValidated) => {
    console.log("License number validation status:", isValidated);
    if (!isValidated) {
      console.error(
        "License number validation failed. Check the number and state and try again."
      );
      return false; // Stop further execution if validation fails
    }
  });

  // Validate each input and select element
  Array.from(row.querySelectorAll("input, select")).forEach((input) => {
    if (
      input.required &&
      !input.value.trim() &&
      input.style.display !== "none"
    ) {
      input.classList.add("error"); // Add error class if the field is empty
      isValid = false;
      console.log("Field is empty or invalid:", input.name);
    } else {
      input.classList.remove("error"); // Remove error class if the field is filled
    }
  });

  if (!isValid) {
    console.error("Fix errors before saving.");
    return false; // Stop the function if there are errors
  }

  Array.from(row.querySelectorAll("input, select")).forEach((input) => {
    const existingHidden = row.querySelector(
      `input[type="hidden"][name="${input.name}"]`
    );
    if (existingHidden) {
      existingHidden.value = input.value; // Update existing hidden input
      existingHidden.disabled = false; // Enable existing hidden input
    } else {
      const hiddenInput = document.createElement("input");
      hiddenInput.type = "hidden";
      hiddenInput.name = input.name;
      hiddenInput.value = input.value;
      row.appendChild(hiddenInput); // Append new hidden input to the form
    }

    input.disabled = true; // Disable the visible input or select
    input.classList.add("read-only"); // Add a class to indicate the field is read-only
  });

  // Hide the 'Save' button and show the 'Edit' button
  row.querySelector(".save-attendee").style.display = "none";
  row.querySelector(".edit-attendee").style.display = "inline-block";

  return true; // Return true if the attendee was saved successfully
}

function editAttendee(button) {
  const row = button.closest("tr");
  Array.from(row.querySelectorAll("input, select")).forEach((input) => {
    input.readOnly = false; // Enable the input fields for editing
    input.disabled = false; // Ensure the fields are not disabled
    input.classList.remove("read-only"); // Remove read-only class when editing
    input.classList.remove("error"); // Remove any error highlighting when editing
  });

  // Hide edit button and show save button
  row.querySelector(".edit-attendee").style.display = "none";
  row.querySelector(".save-attendee").style.display = "inline-block"; // Adjust as per your button display preference
}

function updateFieldsBasedOnCourseType() {
  const courseType = document.getElementById("course_type").value;
  const medicalHoursHeader = document.querySelector("th.medical-hours-header");
  const nonMedicalHoursHeader = document.querySelector(
    "th.non-medical-hours-header"
  );

  // Determine display style based on course type
  const isMulti = courseType === "multi";
  medicalHoursHeader.style.display = isMulti ? "" : "none";
  nonMedicalHoursHeader.style.display = isMulti ? "" : "none";

  // Handle the fields for each attendee
  document.querySelectorAll(".multi-field").forEach((field) => {
    if (isMulti) {
      field.style.display = "table-cell"; // Use "table-cell" to override "none" explicitly for table elements
      // Ensure fields are active if previously made disabled
      if (field.querySelector("input")) {
        field.querySelector("input").disabled = false;
      }
    } else {
      field.style.display = "none"; // Hide fields for non-'multi' course type
      // Disable input to prevent form submission of these fields
      if (field.querySelector("input")) {
        field.querySelector("input").disabled = true;
      }
    }
  });
}

function startOver() {
  if (
    confirm(
      "Are you sure you want to start over? All unsaved changes will be lost."
    )
  ) {
    window.location.reload(); // Reload the page to start over
  }
}

function saveProgress() {
  const formData = new FormData(document.getElementById("new-roster-form"));
  formData.append("action", "save_roster_progress");

  fetch(aavsbAjax.ajaxurl, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Progress saved successfully!");
        // Optionally, redirect to the Manage Rosters page or refresh the current page
      } else {
        alert("An error occurred: " + data.data);
      }
    })
    .catch((error) => console.error("Error:", error));
}

// Function to get the current date in the format YYYY-MM-DD
function getCurrentDate() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

// Call this function on page load and when the course type changes
document
  .getElementById("course_type")
  .addEventListener("change", updateFieldsBasedOnCourseType);

// Consolidated DOMContentLoaded event listener
document.addEventListener("DOMContentLoaded", function () {
  initializeForm();
  updateFieldsBasedOnCourseType(); // Initialize fields based on course type

  // Attach event listeners for Start Over and Save Progress
  document
    .getElementById("start-over-btn")
    .addEventListener("click", startOver);
  document
    .getElementById("save-progress-btn")
    .addEventListener("click", saveProgress);
});
