function handleEditFormSubmission(event) {
  event.preventDefault();
  const formData = new FormData(document.getElementById("edit-roster-form"));
  formData.append("action", "update_roster");
  formData.append("security", aavsbAjax.nonce); // Ensure you have defined this nonce properly in your PHP

  // Toggle the spinner in the button
  const submitButton = document.getElementById("save-button");
  const submitIcon = document.getElementById("submit-icon");
  const submitSpinner = document.getElementById("submit-spinner");

  submitButton.disabled = true; // Disable the button to prevent multiple submissions
  submitIcon.style.display = "none"; // Hide the check icon
  submitSpinner.style.display = "inline-block"; // Show the spinner

  console.log("Submitting form data:", formData);
  for (const [key, value] of formData.entries()) {
    console.log(key, value);
  }

  fetch(aavsbAjax.ajaxurl, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Reset the button states
        submitButton.disabled = false;
        submitIcon.style.display = "inline-block";
        submitSpinner.style.display = "none";
        window.location.href = "/manage-rosters/";
      } else {
        alert("Failed to update roster: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error updating roster:", error);
    });
}

document
  .getElementById("edit-roster-form")
  .addEventListener("submit", handleEditFormSubmission);

// JavaScript to handle button click events
document.addEventListener("click", function (event) {
  if (event.target.classList.contains("export-roster")) {
    const rosterId = event.target.dataset.rosterId;
    exportRoster(rosterId);
  }
  if (event.target.classList.contains("edit-roster")) {
    const rosterId = event.target.dataset.rosterId;
    editRoster(rosterId);
  }
});
