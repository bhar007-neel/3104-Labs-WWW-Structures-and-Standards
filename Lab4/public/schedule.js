// Wait until the browser has finished building the HTML DOM.
document.addEventListener("DOMContentLoaded", function () {
  // Plans received from the API will be stored in this array.
  let plans = [];

  // Find the schedule table on the current page.
  const scheduleTable = document.querySelector(".schedule-table");

  // Stop safely if this script runs on a page without the schedule table.
  if (!scheduleTable) {
    return;
  }

  // Find the table body where plan rows will be inserted.
  const tableBody = scheduleTable.querySelector("tbody");

  // Find the parent panel that contains the schedule table.
  const tablePanel = scheduleTable.closest(".feature-panel");

  // Create a message that shows the API loading status.
  const statusMessage = document.createElement("p");
  statusMessage.className = "schedule-feedback";

  // aria-live allows screen readers to announce changing messages.
  statusMessage.setAttribute("aria-live", "polite");
  statusMessage.textContent = "Loading plans from the API...";

  // Create the filtering and sorting controls.
  const controls = document.createElement("div");
  controls.className = "schedule-controls";

  controls.innerHTML = `
    <div class="filter-buttons" aria-label="Filter schedule plans">
      <button
        type="button"
        class="button button-secondary filter-button active"
        data-filter="all"
        aria-pressed="true">
        All
      </button>

      <button
        type="button"
        class="button button-secondary filter-button"
        data-filter="beginner"
        aria-pressed="false">
        Beginner
      </button>

      <button
        type="button"
        class="button button-secondary filter-button"
        data-filter="intermediate"
        aria-pressed="false">
        Intermediate
      </button>

      <button
        type="button"
        class="button button-secondary filter-button"
        data-filter="advanced"
        aria-pressed="false">
        Advanced
      </button>
    </div>

    <div class="sort-control">
      <label for="plan-sort">Sort plans:</label>

      <select id="plan-sort">
        <option value="default">Default order</option>
        <option value="price-low">Price: low to high</option>
        <option value="price-high">Price: high to low</option>
        <option value="duration-short">
          Duration: shortest first
        </option>
      </select>
    </div>
  `;

  // Create a message showing how many plans are currently visible.
  const feedback = document.createElement("p");
  feedback.className = "schedule-feedback";
  feedback.setAttribute("aria-live", "polite");

  // Create an area that displays information about a selected plan.
  const detailsBox = document.createElement("div");
  detailsBox.className = "plan-details-box";
  detailsBox.setAttribute("aria-live", "polite");

  detailsBox.innerHTML =
    "<p>Select a plan detail button to see more information.</p>";

  // Find the existing wrapper around the table.
  const tableWrap = tablePanel.querySelector(".table-wrap");

  // Insert the newly created elements around the existing table.
  tablePanel.insertBefore(statusMessage, tableWrap);
  tablePanel.insertBefore(controls, tableWrap);
  tablePanel.insertBefore(feedback, tableWrap);
  tablePanel.appendChild(detailsBox);

  // Store the user's current filter and sorting selections.
  let currentFilter = "all";
  let currentSort = "default";

  /**
   * Converts a duration such as "5 weeks" into the number 5.
   * This allows durations to be sorted numerically.
   */
  function getDurationNumber(durationText) {
    return Number(durationText.split(" ")[0]);
  }

  /**
   * Returns the plan description or a fallback message.
   */
  function buildPlanDescription(plan) {
    return plan.description || "No description available.";
  }

  /**
   * Filters and sorts the plans based on the current selections.
   */
  function getVisiblePlans() {
    // Keep every plan when "all" is selected.
    // Otherwise, keep only plans matching the selected level.
    let visiblePlans = plans.filter(function (plan) {
      return (
        currentFilter === "all" ||
        plan.level === currentFilter
      );
    });

    // Sort price from lowest to highest.
    if (currentSort === "price-low") {
      visiblePlans.sort(function (a, b) {
        return a.price - b.price;
      });

    // Sort price from highest to lowest.
    } else if (currentSort === "price-high") {
      visiblePlans.sort(function (a, b) {
        return b.price - a.price;
      });

    // Sort duration from shortest to longest.
    } else if (currentSort === "duration-short") {
      visiblePlans.sort(function (a, b) {
        return (
          getDurationNumber(a.duration) -
          getDurationNumber(b.duration)
        );
      });
    }

    return visiblePlans;
  }

  /**
   * Builds the table rows from the filtered and sorted plans.
   */
  function renderPlans() {
    const visiblePlans = getVisiblePlans();

    // Remove any previously displayed rows.
    tableBody.innerHTML = "";

    // Create one table row for every visible plan.
    visiblePlans.forEach(function (plan) {
      const row = document.createElement("tr");

      // Store the plan level directly on the row.
      row.dataset.level = plan.level;

      // Fill the row with information received from the API.
      row.innerHTML = `
        <td>${plan.name}</td>
        <td>${plan.duration}</td>
        <td>${plan.style}</td>
        <td>$${plan.price}</td>

        <td>
          <button
            type="button"
            class="table-link details-button"
            data-plan="${plan.level}">
            View details
          </button>

          <a
            href="registration.html"
            class="table-link register-link"
            data-plan="${plan.level}">
            Register
          </a>
        </td>
      `;

      // Add the completed row to the table.
      tableBody.appendChild(row);
    });

    // Tell the user how many plans are currently shown.
    feedback.textContent =
      `Showing ${visiblePlans.length} plan(s).`;
  }

  /**
   * Displays an API error message.
   */
  function setErrorMessage(message) {
    statusMessage.textContent = message;

    statusMessage.classList.add(
      "schedule-feedback--error"
    );
  }

  /**
   * Removes the loading or error status message.
   */
  function clearStatusMessage() {
    statusMessage.textContent = "";

    statusMessage.classList.remove(
      "schedule-feedback--error"
    );
  }

  /**
   * Visually marks the selected filter button as active.
   */
  function updateActiveFilterButton(selectedButton) {
    const filterButtons =
      document.querySelectorAll(".filter-button");

    // Reset every filter button.
    filterButtons.forEach(function (button) {
      button.classList.remove("active");
      button.setAttribute("aria-pressed", "false");
    });

    // Activate only the selected button.
    selectedButton.classList.add("active");
    selectedButton.setAttribute("aria-pressed", "true");
  }

  /**
   * Handle clicks on any filter button.
   *
   * One listener is placed on the controls container instead of
   * placing a separate listener on every button.
   */
  controls.addEventListener("click", function (event) {
    // Find the filter button that was clicked.
    const selectedButton =
      event.target.closest(".filter-button");

    // Ignore clicks that did not come from a filter button.
    if (!selectedButton) {
      return;
    }

    // Read the filter value from data-filter.
    currentFilter = selectedButton.dataset.filter;

    updateActiveFilterButton(selectedButton);
    renderPlans();
  });

  /**
   * Handle changes to the sorting dropdown.
   */
  controls
    .querySelector("#plan-sort")
    .addEventListener("change", function (event) {
      // Save the selected sorting option.
      currentSort = event.target.value;

      // Rebuild the table using the new order.
      renderPlans();
    });

  /**
   * Handle clicks inside the table body.
   *
   * This is event delegation. It works even though the rows and
   * buttons are created dynamically after the page loads.
   */
  tableBody.addEventListener("click", function (event) {
    // Check whether the user clicked a details button.
    const detailsButton =
      event.target.closest(".details-button");

    // Check whether the user clicked a registration link.
    const registerLink =
      event.target.closest(".register-link");

    // Display more information when "View details" is clicked.
    if (detailsButton) {
      // Find the plan whose level matches data-plan.
      const selectedPlan = plans.find(function (plan) {
        return (
          plan.level === detailsButton.dataset.plan
        );
      });

      // Stop safely if the plan cannot be found.
      if (!selectedPlan) {
        return;
      }

      // Remove the selected style from all rows.
      const rows = tableBody.querySelectorAll("tr");

      rows.forEach(function (row) {
        row.classList.remove("selected-plan");
      });

      // Highlight the row containing the clicked button.
      detailsButton
        .closest("tr")
        .classList.add("selected-plan");

      // Display the selected plan's complete information.
      detailsBox.innerHTML = `
        <h3>${selectedPlan.name}</h3>

        <p>
          ${buildPlanDescription(selectedPlan)}
        </p>

        <p>
          <strong>Duration:</strong>
          ${selectedPlan.duration}
        </p>

        <p>
          <strong>Workout style:</strong>
          ${selectedPlan.style}
        </p>

        <p>
          <strong>Price:</strong>
          $${selectedPlan.price}
        </p>
      `;
    }

    /**
     * Save the selected plan before opening registration.html.
     *
     * The registration page reads this value and automatically
     * selects the matching membership tier.
     */
    if (registerLink) {
      localStorage.setItem(
        "selectedPlan",
        registerLink.dataset.plan
      );
    }
  });

  /**
   * Requests all plans from the Express REST API.
   */
  async function loadPlans() {
    try {
      // GET is the default HTTP method used by fetch().
      const response = await fetch("/api/plans");

      // fetch() does not automatically treat 404 or 500 as errors.
      if (!response.ok) {
        throw new Error("Unable to load plans");
      }

      // Convert the JSON response into a JavaScript value.
      const result = await response.json();

      // Use the result only when the API returns an array.
      plans = Array.isArray(result) ? result : [];

      // Remove the loading message and display the plans.
      clearStatusMessage();
      renderPlans();

    } catch (error) {
      // Remove any old rows after a failed request.
      tableBody.innerHTML = "";

      // Display a safe error message instead of raw technical details.
      setErrorMessage(
        "Unable to load plans from the API. Please refresh the page."
      );

      feedback.textContent = "Showing 0 plan(s).";
    }
  }

  // Start the API request after the page is ready.
  loadPlans();
});