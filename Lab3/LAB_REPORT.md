# CSI 3140 Lab 3 Report: JavaScript Implementation

## Course Information
- **Course Code:** CSI 3140
- **Lab Number:** Lab 3
- **Team Members:**
  - David Gvozdyev - 300308910
  - Liam Geraghty - 300356748
  - Neelman Bhardwaj - 300389998
- **Date Submitted:** [Date]

---

## Website Overview

### Topic Description
Uranus Fitness is a community-focused fitness training website designed to provide a clear schedule, straightforward registration process, and a platform for users to build healthier habits. The website serves as a digital hub for gym members to view class schedules, register for membership, and access information about the fitness programs offered.

### Interaction Goals
1. **Home Page (index.html):** Engage visitors with a welcoming interactive modal that introduces the website and encourages exploration
2. **Registration Page (registration.html):** Validate user input in real-time and provide clear feedback to ensure data quality and improve user experience

---

## File Structure

### Directory Layout
```
Lab3/
├── index.html              # Home page
├── index.js                # Home page JavaScript
├── registration.html       # Registration/Contact page
├── registration.js         # Registration page JavaScript
├── about.html              # About page (no JavaScript changes)
├── about.js                # About page (empty)
├── schedule.html           # Schedule page (no JavaScript changes)
├── schedule.js             # Schedule page (empty)
├── styles.css              # Shared stylesheet for all pages
├── img/                    # Image assets folder
│   ├── logo.png
│   └── old_guypng.png
└── LAB_REPORT.md          # This report
```

### Submitted Files Description
- **index.html:** Main landing page with hero section and feature cards
- **index.js:** Implements welcome modal that displays on first visit
- **registration.html:** Form-based registration page with multiple input types
- **registration.js:** Handles client-side form validation with error messaging
- **styles.css:** Contains all styling including new modal and error message classes

---

## JavaScript File Organization

### index.js Structure
```javascript
// 1. DOM Content Loaded Event
// - Checks localStorage for modal display flag
// - Creates modal elements dynamically
// - Sets up event listeners

// 2. Modal Creation
// - Creates overlay and content container
// - Builds heading, paragraph, and button elements

// 3. Event Handling
// - Click handler on "Get Started" button
// - Dismisses modal and saves state to localStorage
```

### registration.js Structure
```javascript
// 1. Form Element References
// - Caches all form input elements by ID

// 2. Helper Functions
// - createErrorMessage(): Creates styled error div elements
// - clearErrors(): Removes all existing error messages
// - validateEmail(): Validates email format using regex
// - validateForm(): Runs all validation checks

// 3. Validation Rules
// - Individual field validation logic
// - Returns combined validation result

// 4. Event Listener
// - Form submit event handler
// - Prevents submission if validation fails
```

---

## DOM Elements Selected and Modified

### index.js

#### Elements Selected
- `document` - Root element for event listener
- `.hero-copy` - Section where modal is appended

#### Elements Created and Modified
| Element Type | Class/ID | Action |
|---|---|---|
| `<div>` | `.welcome-modal` | Created and appended to body |
| `<div>` | `.welcome-modal-content` | Created as child of modal |
| `<h2>` | (none) | Created with welcome message |
| `<p>` | (none) | Created with invitation text |
| `<button>` | `.welcome-modal-button` | Created with click handler |

### registration.js

#### Elements Selected
| Selector | Element | Purpose |
|---|---|---|
| `.registration-form` | `<form>` | Form submission target |
| `#full-name` | `<input type="text">` | Full name field |
| `#email` | `<input type="email">` | Email field |
| `#phone` | `<input type="tel">` | Phone number field |
| `#age` | `<select>` | Age range dropdown |
| `input[name="tier"]` | Multiple `<input type="radio">` | Membership tier options |
| `#contact-method` | `<select>` | Preferred contact method |
| `#start-date` | `<input type="date">` | Membership start date |
| `#about` | `<textarea>` | Goals/about section |

#### Elements Created and Modified
- Error message `<div>` elements with class `.form-error` are dynamically created and appended after each invalid field

---

## Event-Driven Features Implemented

### 1. Welcome Modal (index.html)
**Event Type:** `DOMContentLoaded`
- **Trigger:** Page load
- **Behavior:** 
  - Checks if user has seen modal before using `localStorage`
  - If first visit, creates and displays welcome modal overlay
  - Prevents body scroll while modal is visible (via CSS)
- **Interaction:** User clicks "Get Started" button to dismiss
- **State Persistence:** localStorage flag (`welcomeModalShown`) prevents modal from reappearing

### 2. Form Submission Validation (registration.html)
**Event Type:** `submit`
- **Trigger:** User clicks "Submit registration" button
- **Behavior:**
  - Executes comprehensive validation before submission
  - Clears previous error messages
  - Validates all form fields
  - Displays error messages for invalid fields
  - Prevents form submission if any field is invalid
- **Interaction:** User must correct errors and resubmit

---

## Dynamic Feature Using Array/Object



---

## Form Validation Rules

### Field-by-Field Validation (registration.html)

| Field | Type | Validation Rule | Error Message |
|---|---|---|---|
| Full Name | Text | Must not be empty | "Please enter your full name." |
| Email | Email | Must not be empty AND must match email format | "Please enter your email address." / "Please enter a valid email address." |
| Phone Number | Tel | Must not be empty | "Please enter your phone number." |
| Age Range | Select | Must have a selection (not default) | "Please select an age range." |
| Membership Plan | Radio | Must have at least one option selected | "Please select a membership plan." |
| Contact Method | Select | Must have a selection | "Please select a preferred contact method." |
| Start Date | Date | Must not be empty | "Please select a preferred start date." |
| Goals/About | Textarea | Must not be empty | "Please tell us about your goals." |

### Email Validation Regex
```javascript
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
```
This pattern ensures:
- At least one character before `@`
- Exactly one `@` symbol
- At least one character between `@` and `.`
- A domain with at least one character after `.`

### Validation Flow
1. User submits form
2. Event listener triggers on `submit` event
3. `validateForm()` function executes:
   - Clears all previous error messages
   - Iterates through each form field
   - Checks each field against its validation rule
   - Appends error message `<div>` if validation fails
   - Returns false if any field fails
4. If validation fails, `event.preventDefault()` stops form submission
5. User sees error messages and must correct fields
6. Upon successful validation, form submission proceeds

---

## Screenshots



---

## Testing and Debugging Process



---

## JavaScript and Dynamic Behavior Checklist



---

## Reflection


