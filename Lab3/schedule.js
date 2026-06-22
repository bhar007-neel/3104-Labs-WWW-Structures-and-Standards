document.addEventListener("DOMContentLoaded", function () {
  // Schedule data stored in an array of objects.
  // This satisfies the Lab 3 requirement for using a JavaScript data structure.
  const plans = [
    {
      name: "Beginner Plan",
      duration: "2 weeks",
      style: "Machine-assisted exercises",
      price: 49,
      level: "beginner",
      description:
        "Best for new members who want guided practice and safer pacing.",
    },
    {
      name: "Intermediate Plan",
      duration: "5 weeks",
      style: "Free weight exercises",
      price: 59,
      level: "intermediate",
      description:
        "Best for members who already know basic gym movements and want more structure.",
    },
    {
      name: "Advanced Plan",
      duration: "8 weeks",
      style: "Compound lifts + diet plans",
      price: 69,
      level: "advanced",
      description:
        "Best for experienced members who want heavier training and more detailed planning.",
    },
  ];

  const scheduleTable = document.querySelector(".schedule-table");

  // Stop the script safely if this file is loaded on a page without the schedule table.
  if (!scheduleTable) {
    return;
  }

  const tableBody = scheduleTable.querySelector("tbody");
  const tablePanel = scheduleTable.closest(".feature-panel");

  // Create filter and sorting controls dynamically.
  const controls = document.createElement("div");
  controls.className = "schedule-controls";
  controls.innerHTML = `
    <div class="filter-buttons" aria-label="Filter schedule plans">
      <button type="button" class="button button-secondary filter-button active" data-filter="all" aria-pressed="true">All</button>
      <button type="button" class="button button-secondary filter-button" data-filter="beginner" aria-pressed="false">Beginner</button>
      <button type="button" class="button button-secondary filter-button" data-filter="intermediate" aria-pressed="false">Intermediate</button>
      <button type="button" class="button button-secondary filter-button" data-filter="advanced" aria-pressed="false">Advanced</button>
    </div>

    <div class="sort-control">
      <label for="plan-sort">Sort plans:</label>
      <select id="plan-sort">
        <option value="default">Default order</option>
        <option value="price-low">Price: low to high</option>
        <option value="price-high">Price: high to low</option>
        <option value="duration-short">Duration: shortest first</option>
      </select>
    </div>
  `;

  // Message area for feedback after filtering/sorting.
  const feedback = document.createElement("p");
  feedback.className = "schedule-feedback";
  feedback.setAttribute("aria-live", "polite");

  // Details box updates when a user clicks "View details".
  const detailsBox = document.createElement("div");
  detailsBox.className = "plan-details-box";
  detailsBox.setAttribute("aria-live", "polite");
  detailsBox.innerHTML =
    "<p>Select a plan detail button to see more information.</p>";

  const tableWrap = tablePanel.querySelector(".table-wrap");
  tablePanel.insertBefore(controls, tableWrap);
  tablePanel.insertBefore(feedback, tableWrap);
  tablePanel.appendChild(detailsBox);

  let currentFilter = "all";
  let currentSort = "default";

  function getDurationNumber(durationText) {
    return Number(durationText.split(" ")[0]);
  }

  function getVisiblePlans() {
    let visiblePlans = plans.filter(function (plan) {
      return currentFilter === "all" || plan.level === currentFilter;
    });

    if (currentSort === "price-low") {
      visiblePlans.sort(function (a, b) {
        return a.price - b.price;
      });
    } else if (currentSort === "price-high") {
      visiblePlans.sort(function (a, b) {
        return b.price - a.price;
      });
    } else if (currentSort === "duration-short") {
      visiblePlans.sort(function (a, b) {
        return getDurationNumber(a.duration) - getDurationNumber(b.duration);
      });
    }

    return visiblePlans;
  }

  function renderPlans() {
    const visiblePlans = getVisiblePlans();
    tableBody.innerHTML = "";

    visiblePlans.forEach(function (plan) {
      const row = document.createElement("tr");
      row.dataset.level = plan.level;

      row.innerHTML = `
        <td>${plan.name}</td>
        <td>${plan.duration}</td>
        <td>${plan.style}</td>
        <td>$${plan.price}</td>
        <td>
          <button type="button" class="table-link details-button" data-plan="${plan.level}">
            View details
          </button>
          <a href="registration.html" class="table-link register-link" data-plan="${plan.level}">
            Register
          </a>
        </td>
      `;

      tableBody.appendChild(row);
    });

    feedback.textContent = `Showing ${visiblePlans.length} plan(s).`;
  }

  function updateActiveFilterButton(selectedButton) {
    const filterButtons = document.querySelectorAll(".filter-button");

    filterButtons.forEach(function (button) {
      button.classList.remove("active");
      button.setAttribute("aria-pressed", "false");
    });

    selectedButton.classList.add("active");
    selectedButton.setAttribute("aria-pressed", "true");
  }

  controls.addEventListener("click", function (event) {
    const selectedButton = event.target.closest(".filter-button");

    if (!selectedButton) {
      return;
    }

    currentFilter = selectedButton.dataset.filter;
    updateActiveFilterButton(selectedButton);
    renderPlans();
  });

  controls
    .querySelector("#plan-sort")
    .addEventListener("change", function (event) {
      currentSort = event.target.value;
      renderPlans();
    });

  tableBody.addEventListener("click", function (event) {
    const detailsButton = event.target.closest(".details-button");
    const registerLink = event.target.closest(".register-link");

    if (detailsButton) {
      const selectedPlan = plans.find(function (plan) {
        return plan.level === detailsButton.dataset.plan;
      });

      const rows = tableBody.querySelectorAll("tr");
      rows.forEach(function (row) {
        row.classList.remove("selected-plan");
      });

      detailsButton.closest("tr").classList.add("selected-plan");

      detailsBox.innerHTML = `
        <h3>${selectedPlan.name}</h3>
        <p>${selectedPlan.description}</p>
        <p><strong>Duration:</strong> ${selectedPlan.duration}</p>
        <p><strong>Workout style:</strong> ${selectedPlan.style}</p>
        <p><strong>Price:</strong> $${selectedPlan.price}</p>
      `;
    }

    // Store the selected plan before moving to registration.
    // This gives another useful dynamic behavior without breaking the normal link.
    if (registerLink) {
      localStorage.setItem("selectedPlan", registerLink.dataset.plan);
    }
  });

  renderPlans();
});
