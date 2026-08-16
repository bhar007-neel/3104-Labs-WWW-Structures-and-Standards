<div align="center">

# 🌐 CSI 3140

## WWW Structures, Techniques & Standards

### From Semantic HTML → Responsive CSS → JavaScript → REST APIs → Secure Web Applications

<p>
  <strong>University of Ottawa · Spring/Summer 2026 · Group 55</strong>
</p>

<br>

![Labs Complete](https://img.shields.io/badge/Labs-5%2F5_Completed-2ea44f?style=for-the-badge\&logo=checkmarx\&logoColor=white)
![Last Commit](https://img.shields.io/github/last-commit/bhar007-neel/3104-Labs-WWW-Structures-and-Standards?style=for-the-badge\&logo=github)
![Contributors](https://img.shields.io/github/contributors/bhar007-neel/3104-Labs-WWW-Structures-and-Standards?style=for-the-badge\&logo=github)
![Repo Size](https://img.shields.io/github/repo-size/bhar007-neel/3104-Labs-WWW-Structures-and-Standards?style=for-the-badge)

<br>

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square\&logo=html5\&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square\&logo=css3\&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square\&logo=javascript\&logoColor=black)
![Node.js](https://img.shields.io/badge/Node.js-339933?style=flat-square\&logo=nodedotjs\&logoColor=white)
![Express](https://img.shields.io/badge/Express-000000?style=flat-square\&logo=express\&logoColor=white)
![REST](https://img.shields.io/badge/REST_API-005571?style=flat-square)
![Swagger](https://img.shields.io/badge/OpenAPI%20%2F%20Swagger-85EA2D?style=flat-square\&logo=swagger\&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-8.1-777BB4?style=flat-square\&logo=php\&logoColor=white)
![Moodle](https://img.shields.io/badge/Moodle-4.5-F98012?style=flat-square\&logo=moodle\&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=flat-square\&logo=docker\&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-11.4-003545?style=flat-square\&logo=mariadb\&logoColor=white)

<br>

<a href="#-project-overview"><kbd>Overview</kbd></a> <a href="#-course-journey"><kbd>Course Journey</kbd></a> <a href="#-visual-tour"><kbd>Visual Tour</kbd></a> <a href="#-lab-details"><kbd>Labs</kbd></a> <a href="#-architecture-evolution"><kbd>Architecture</kbd></a> <a href="#-quick-start"><kbd>Run It</kbd></a> <a href="#-contributors"><kbd>Contributors</kbd></a>

</div>

---

# 🚀 Project Overview

This repository contains the completed laboratory work for **CSI 3140 — WWW Structures, Techniques and Standards** at the **University of Ottawa**.

Rather than treating each laboratory as an isolated exercise, the work progressively builds a modern understanding of Web development.

The first four labs evolve **Uranus Fitness**, a fictional fitness platform, from a semantic multi-page HTML website into a responsive, interactive and full-stack Web application.

The final laboratory moves into **secure server-side Web development** through a Moodle plugin called **Secure Course Hub**, introducing authentication, authorization, ownership controls, AJAX/JSON communication, database access and Web security.

> **Five labs. One progression. From markup to full-stack security.**

---

# 🧭 Course Journey

```mermaid
flowchart LR
    A["🧱 Lab 1<br/>Semantic HTML5"] --> B["🎨 Lab 2<br/>Responsive CSS"]
    B --> C["⚡ Lab 3<br/>JavaScript + DOM"]
    C --> D["🌐 Lab 4<br/>REST + Express"]
    D --> E["🔐 Lab 5<br/>Moodle + Security"]

    style A fill:#e34f26,color:#fff
    style B fill:#1572b6,color:#fff
    style C fill:#f7df1e,color:#111
    style D fill:#339933,color:#fff
    style E fill:#f98012,color:#fff
```

<br>

|                                    Lab                                    | Focus                     | Major Concepts                                     | Status |
| :-----------------------------------------------------------------------: | ------------------------- | -------------------------------------------------- | :----: |
|                            [**Lab 1**](./Lab1)                            | Web Foundations           | HTML5, semantics, forms, accessibility, navigation |    ✅   |
|                            [**Lab 2**](./Lab2)                            | Responsive Design         | CSS3, Box Model, Flexbox, Grid, media queries      |    ✅   |
|                            [**Lab 3**](./Lab3)                            | Client-Side Programming   | JavaScript, DOM, events, validation, localStorage  |    ✅   |
|                            [**Lab 4**](./Lab4)                            | Client-Server Development | Node.js, Express, REST, JSON, Fetch API, OpenAPI   |    ✅   |
| [**Lab 5**](./Lab%205/CSI3140_Lab5_Working_Package/secure-course-hub-lab) | Secure Web Applications   | Moodle, PHP, RBAC, CSRF, XSS, AJAX, Docker         |    ✅   |

---

# 📸 Visual Tour

## Uranus Fitness

<p align="center">
  <img src="./Lab3/Screenshots/home_after.png" width="48%" alt="Uranus Fitness Home Page">
  <img src="./Lab3/Screenshots/schedule_after.png" width="48%" alt="Uranus Fitness Schedule Page">
</p>

<p align="center">
  <em>Responsive Uranus Fitness interface after HTML, CSS and JavaScript development.</em>
</p>

<br>

## Secure Course Hub

<p align="center">
  <img src="./Lab%205/CSI3140_Lab5_Working_Package/secure-course-hub-lab/screenshots/learner1_requests.png" width="70%" alt="Secure Course Hub Student Requests">
</p>

<p align="center">
  <em>Authenticated Moodle Secure Course Hub interface.</em>
</p>

---

# 🧪 Lab Details

<details>
<summary><strong>🧱 Lab 1 — Web Foundations & Semantic HTML5</strong></summary>

<br>

### Goal

Build the foundation of the Uranus Fitness website using standards-compliant and accessibility-aware HTML5.

### Pages

| Page                | Purpose                       |
| ------------------- | ----------------------------- |
| `index.html`        | Home and introduction         |
| `about.html`        | Club information              |
| `schedule.html`     | Membership plans and schedule |
| `registration.html` | Accessible registration form  |

### Concepts Demonstrated

* Semantic HTML5 structure
* `<header>`, `<nav>`, `<main>`, `<section>` and `<footer>`
* Relative navigation
* Internal and external links
* Images with meaningful `alt` attributes
* Lists and structured content
* Accessible tables
* Form labels and input controls
* HTML5 validation
* Logical heading hierarchy
* Keyboard-friendly navigation
* Accessibility-aware markup
* HTML validation

### Result

The project begins as a clean, accessible multi-page website with a strong semantic foundation.

<p align="right">
  <a href="./Lab1"><strong>Open Lab 1 →</strong></a>
</p>

</details>

---

<details>
<summary><strong>🎨 Lab 2 — CSS, Flexbox, Grid & Responsive Design</strong></summary>

<br>

### Goal

Transform the Lab 1 HTML foundation into a polished responsive interface.

### CSS Architecture

The shared stylesheet introduces reusable layout and component patterns for:

* Navigation
* Hero sections
* Feature cards
* Content panels
* Forms
* Tables
* Buttons
* Responsive layouts

### Layout Technologies

| Technology           | Usage                                          |
| -------------------- | ---------------------------------------------- |
| **CSS Box Model**    | Spacing, sizing, padding and borders           |
| **Flexbox**          | Navigation, buttons and horizontal alignment   |
| **CSS Grid**         | Hero layouts, cards and form fields            |
| **Media Queries**    | Mobile and tablet layouts                      |
| **Responsive Units** | `rem`, `%`, `fr`, `min()`, `calc()`, `clamp()` |

### Responsive Behaviour

The interface adapts across:

📱 Mobile
💻 Tablet
🖥️ Desktop

Multi-column layouts collapse into single-column layouts when screen space becomes limited.

Images remain responsive and tables are protected from breaking the page layout.

### Design Direction

* Light blue visual theme
* Deep blue primary interface colour
* Teal accents
* Reusable cards and panels
* Rounded components
* Consistent spacing
* Responsive typography

<p align="right">
  <a href="./Lab2"><strong>Open Lab 2 →</strong></a>
</p>

</details>

---

<details>
<summary><strong>⚡ Lab 3 — JavaScript, DOM & Event-Driven Interfaces</strong></summary>

<br>

### Goal

Turn the static responsive website into an interactive client-side application.

### Interactive Features

#### 🏠 Home Page

**Welcome Modal**

* Dynamically created through JavaScript
* Triggered on page load
* Dismissed through user interaction
* Uses `localStorage` to remember whether the visitor has already seen it

#### ℹ️ About Page

**Accordion FAQ**

* Expandable FAQ sections
* Click-driven interaction
* Uses `aria-expanded`
* CSS state changes
* Keyboard-friendly behaviour

#### 📅 Schedule Page

**Dynamic Membership Plans**

* Plans stored in an array of objects
* Filter by membership level
* Sort by price
* Sort by duration
* Dynamically render table rows
* Display selected plan details
* Update UI without page navigation

#### 📝 Registration Page

**JavaScript Form Validation**

* Required field checks
* Email validation
* Phone validation
* Inline error messages
* Invalid submissions blocked
* Success confirmation
* Dynamic DOM updates

### JavaScript Concepts

```text
DOM Selection
      ↓
Event Listeners
      ↓
Application State
      ↓
Validation / Filtering
      ↓
DOM Updates
      ↓
User Feedback
```

Additional implementation concepts include:

* `DOMContentLoaded`
* Event delegation
* `classList`
* `aria-current`
* `aria-pressed`
* `localStorage`
* Arrays and objects
* Reusable functions
* Regular expressions
* Dynamic element creation

<p align="right">
  <a href="./Lab3"><strong>Open Lab 3 →</strong></a>
</p>

</details>

---

<details>
<summary><strong>🌐 Lab 4 — Node.js, Express & REST APIs</strong></summary>

<br>

### Goal

Move Uranus Fitness from a browser-only application into a real **client-server architecture**.

### Full-Stack Architecture

```mermaid
flowchart LR
    A["Browser"] --> B["HTML / CSS / JavaScript"]
    B -->|"fetch()"| C["Express Server"]
    C --> D["REST Routes"]
    D --> E["Data Store"]
    E --> F["JSON Files"]
    D --> G["JSON Response"]
    G --> B
```

### Backend

Built using:

* Node.js
* Express.js
* REST architecture
* JSON request/response bodies
* Server-side validation
* HTTP status codes
* Modular API routes

### API

#### System

| Method | Endpoint      | Purpose           |
| ------ | ------------- | ----------------- |
| `GET`  | `/api/status` | API health/status |

#### Training Plans

| Method | Endpoint         | Purpose       |
| ------ | ---------------- | ------------- |
| `GET`  | `/api/plans`     | Get all plans |
| `GET`  | `/api/plans/:id` | Get one plan  |

#### Registrations

| Method   | Endpoint                 | Purpose              |
| -------- | ------------------------ | -------------------- |
| `GET`    | `/api/registrations`     | List registrations   |
| `GET`    | `/api/registrations/:id` | Get registration     |
| `POST`   | `/api/registrations`     | Create registration  |
| `PUT`    | `/api/registrations/:id` | Replace registration |
| `PATCH`  | `/api/registrations/:id` | Update registration  |
| `DELETE` | `/api/registrations/:id` | Delete registration  |

### API Documentation

The application includes:

* OpenAPI specification
* Swagger UI
* Documented routes
* Request schemas
* Response schemas
* Status codes

Swagger is available when the application is running at:

```text
http://localhost:3000/api-docs
```

### Client ↔ Server Communication

The frontend communicates with Express using the Fetch API.

```javascript
fetch("/api/plans")
    ↓
Express Route
    ↓
JSON Data
    ↓
Browser
    ↓
Dynamic DOM Update
```

### Server-Side Validation

Registration input is validated before being accepted.

Typical responses include:

```text
200 / 201 → Success
400       → Invalid request
404       → Resource not found
```

<p align="right">
  <a href="./Lab4"><strong>Open Lab 4 →</strong></a>
</p>

</details>

---

<details>
<summary><strong>🔐 Lab 5 — Secure Course Hub / Moodle Plugin</strong></summary>

<br>

### Goal

Build a secure Moodle local plugin where students can create course-help requests and authorized teachers can manage them.

This lab moves beyond ordinary frontend/backend development into:

> **Authentication + Authorization + Ownership + Web Security**

### Environment

![Moodle](https://img.shields.io/badge/Moodle-4.5.4-F98012?style=flat-square\&logo=moodle\&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1.32-777BB4?style=flat-square\&logo=php\&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-11.4-003545?style=flat-square\&logo=mariadb\&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Containerized-2496ED?style=flat-square\&logo=docker\&logoColor=white)

### Secure Course Hub Architecture

```mermaid
flowchart TD
    A["Browser"] --> B["Moodle Session"]
    B --> C["require_login()"]
    C --> D["Course Context"]
    D --> E["Capability Check"]
    E --> F["Ownership Check"]
    F --> G["Secure Course Hub"]
    G --> H["Request Service"]
    H --> I["Moodle DB API"]
    I --> J["MariaDB"]

    A -->|"fetch() + JSON"| K["ajax.php"]
    K --> L["Sesskey Validation"]
    L --> D
```

### Security Controls

| Threat / Requirement        | Protection                          |
| --------------------------- | ----------------------------------- |
| Unauthenticated access      | Moodle sessions + `require_login()` |
| Broken authorization        | Moodle capability checks            |
| Cross-user access           | Record ownership verification       |
| CSRF                        | Moodle `sesskey` validation         |
| XSS                         | Escaped PHP output + `textContent`  |
| SQL injection               | Moodle `$DB` API                    |
| Invalid status values       | Server-side whitelist               |
| Information leakage         | Safe error responses                |
| Excessive teacher responses | 500-character server limit          |
| Client manipulation         | Server-side validation              |

### Capabilities

```text
local/securecoursehub:viewown
local/securecoursehub:createrequest
local/securecoursehub:managecourserequests
```

### Roles

```mermaid
flowchart LR
    Student -->|"Create"| Request
    Student -->|"View own"| Request
    Student -->|"Edit own open"| Request
    Student -->|"Delete own open"| Request

    Teacher -->|"View course queue"| Request
    Teacher -->|"Update status"| Request
    Teacher -->|"Respond"| Request
    Teacher -->|"Manage authorized course"| Request
```

### AJAX / JSON

Teacher status updates are performed dynamically using:

```text
Browser
   ↓
fetch()
   ↓
JSON POST
   ↓
ajax.php
   ↓
Authentication
   ↓
Sesskey
   ↓
Capabilities
   ↓
Database
   ↓
JSON Response
   ↓
DOM Update
```

No page reload is required.

### Automated Security Testing

<div align="center">

![Tests](https://img.shields.io/badge/Automated_Tests-17%2F17_PASS-2ea44f?style=for-the-badge\&logo=checkmarx\&logoColor=white)
![Failures](https://img.shields.io/badge/Failures-0-success?style=for-the-badge)

</div>

The automated suite verifies:

* Unauthenticated access rejection
* Unauthorized API access rejection
* Valid request creation
* Required-field validation
* Maximum title length
* User data isolation
* Ownership enforcement
* Teacher-only operations
* Course request visibility
* Status updates
* 500-character teacher response limit
* Missing/forged sesskey rejection
* XSS payload rendering as text
* Safe 404 responses
* Status whitelist enforcement

### Security Evidence

<p align="center">
  <img src="./Lab%205/CSI3140_Lab5_Working_Package/secure-course-hub-lab/screenshots/csrf_forged_sesskey_rejected.png" width="47%" alt="CSRF Protection Evidence">
  <img src="./Lab%205/CSI3140_Lab5_Working_Package/secure-course-hub-lab/screenshots/learner1_XSS_probe.png" width="47%" alt="XSS Protection Evidence">
</p>

<p align="right">
  <a href="./Lab%205/CSI3140_Lab5_Working_Package/secure-course-hub-lab"><strong>Open Lab 5 →</strong></a>
</p>

</details>

---

# 🏗️ Architecture Evolution

The repository demonstrates how a Web application grows layer by layer.

```mermaid
flowchart TB

    subgraph L1["Lab 1"]
        HTML["Semantic HTML"]
    end

    subgraph L2["Lab 2"]
        CSS["Responsive CSS"]
    end

    subgraph L3["Lab 3"]
        JS["JavaScript / DOM"]
    end

    subgraph L4["Lab 4"]
        API["REST API"]
        EXPRESS["Node + Express"]
        JSON["JSON Data"]
    end

    subgraph L5["Lab 5"]
        MOODLE["Moodle / PHP"]
        SECURITY["Authentication + Authorization"]
        DATABASE["MariaDB"]
        DOCKER["Docker"]
    end

    HTML --> CSS
    CSS --> JS
    JS --> API
    API --> EXPRESS
    EXPRESS --> JSON
    JSON --> MOODLE
    MOODLE --> SECURITY
    SECURITY --> DATABASE
    DATABASE --> DOCKER
```

---

# 🧠 Skills Demonstrated

<table>
<tr>
<td width="33%" valign="top">

### 🎨 Frontend

* Semantic HTML5
* CSS3
* Responsive design
* Flexbox
* Grid
* Forms
* Accessibility
* DOM manipulation
* Event handling

</td>

<td width="33%" valign="top">

### ⚙️ Backend

* Node.js
* Express.js
* PHP
* REST API design
* JSON
* HTTP methods
* Status codes
* Server validation
* Data persistence

</td>

<td width="33%" valign="top">

### 🔐 Security & DevOps

* Authentication
* Authorization
* RBAC
* Ownership checks
* CSRF protection
* XSS mitigation
* Docker
* MariaDB
* Automated security tests

</td>
</tr>
</table>

---

# ▶️ Quick Start

## Clone the Repository

```bash
git clone https://github.com/bhar007-neel/3104-Labs-WWW-Structures-and-Standards.git

cd 3104-Labs-WWW-Structures-and-Standards
```

---

<details>
<summary><strong>Run Labs 1–3</strong></summary>

<br>

Labs 1–3 are primarily browser-based.

Open the desired lab folder and launch:

```text
index.html
```

For a better development experience, use a local development server such as VS Code Live Server.

</details>

---

<details>
<summary><strong>Run Lab 4</strong></summary>

<br>

```bash
cd Lab4

npm install

npm start
```

Application:

```text
http://localhost:3000
```

Swagger API documentation:

```text
http://localhost:3000/api-docs
```

</details>

---

<details>
<summary><strong>Run Lab 5</strong></summary>

<br>

Navigate to:

```bash
cd "Lab 5/CSI3140_Lab5_Working_Package/secure-course-hub-lab"
```

Start the environment:

```bash
docker compose up -d
```

Setup the Moodle plugin:

```bash
./setup.sh
```

Windows PowerShell alternative:

```powershell
powershell -ExecutionPolicy Bypass -File setup.ps1
```

Run the automated security tests:

```bash
./tests/run_security_tests.sh
```

Expected result:

```text
Automated tests: 17 passed, 0 failed, 17 total
```

</details>

---

# 📂 Repository Structure

```text
3104-Labs-WWW-Structures-and-Standards/
│
├── Lab1/
│   └── Semantic HTML5 + accessibility
│
├── Lab2/
│   └── Responsive CSS + Flexbox + Grid
│
├── Lab3/
│   └── JavaScript + DOM + validation
│
├── Lab4/
│   ├── public/
│   ├── routes/
│   ├── data/
│   ├── api-docs/
│   ├── server.js
│   └── package.json
│
├── Lab 5/
│   └── CSI3140_Lab5_Working_Package/
│       └── secure-course-hub-lab/
│           ├── plugin/
│           ├── tests/
│           ├── report/
│           ├── screenshots/
│           ├── docker-compose.yml
│           ├── setup.sh
│           └── package.sh
│
└── README.md
```

---

# 🧪 Testing & Validation

The work across the repository includes:

* HTML validation
* CSS validation
* Browser developer tools
* Desktop testing
* Tablet testing
* Mobile testing
* Keyboard navigation testing
* JavaScript console testing
* Form validation testing
* REST API testing
* Valid and invalid API requests
* HTTP error handling
* API documentation verification
* Automated authentication tests
* Authorization tests
* Ownership tests
* CSRF tests
* XSS tests

---

# 🔄 Development Progression

```text
HTML
  ↓
Semantic Structure
  ↓
Accessibility
  ↓
CSS
  ↓
Responsive Layout
  ↓
JavaScript
  ↓
Dynamic Interfaces
  ↓
HTTP / Fetch
  ↓
REST APIs
  ↓
Node.js / Express
  ↓
Server Validation
  ↓
Authentication
  ↓
Authorization
  ↓
Web Security
  ↓
Containerized Deployment
```

---

# 👥 Contributors

<table>
<tr>

<td align="center" width="33%">
<a href="https://github.com/bhar007-neel">
<img src="https://github.com/bhar007-neel.png?size=120" width="100px;" alt="Neelmani Bhardwaj"/>
<br />
<strong>Neelmani Bhardwaj</strong>
</a>
<br />
<a href="https://github.com/bhar007-neel">@bhar007-neel</a>
</td>

<td align="center" width="33%">
<a href="https://github.com/liamg2187">
<img src="https://github.com/liamg2187.png?size=120" width="100px;" alt="Liam Geraghty"/>
<br />
<strong>Liam Geraghty</strong>
</a>
<br />
<a href="https://github.com/liamg2187">@liamg2187</a>
</td>

<td align="center" width="33%">
<a href="https://github.com/DaKarfiG">
<img src="https://github.com/DaKarfiG.png?size=120" width="100px;" alt="David Gvozdyev"/>
<br />
<strong>David Gvozdyev</strong>
</a>
<br />
<a href="https://github.com/DaKarfiG">@DaKarfiG</a>
</td>

</tr>
</table>

---

# 🎓 Course

**CSI 3140 — WWW Structures, Techniques and Standards**

University of Ottawa
Faculty of Engineering
School of Electrical Engineering and Computer Science

**Term:** Spring/Summer 2026
**Group:** 55

---

<div align="center">

## ✅ 5 / 5 Labs Completed

### Semantic Web → Responsive UI → Interactive JavaScript → REST APIs → Secure Web Applications

<br>

![HTML](https://img.shields.io/badge/HTML-✓-E34F26?style=flat-square)
![CSS](https://img.shields.io/badge/CSS-✓-1572B6?style=flat-square)
![JavaScript](https://img.shields.io/badge/JavaScript-✓-F7DF1E?style=flat-square)
![REST](https://img.shields.io/badge/REST-✓-339933?style=flat-square)
![Security](https://img.shields.io/badge/Web_Security-✓-2ea44f?style=flat-square)

<br><br>

**Built through five progressive CSI 3140 laboratory projects.**

<br>

<a href="#-csi-3140">⬆ Back to top</a>

</div>

