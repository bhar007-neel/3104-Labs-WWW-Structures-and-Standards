# Uranus Fitness - Lab 4 Client-Server Application

This project is a CSI 3140 Lab 4 web application that demonstrates client-server interaction using:

- Node.js + Express.js backend
- REST API with JSON
- Front-end pages in HTML/CSS/JavaScript
- OpenAPI documentation with Swagger UI

## Features

- Serves static client pages from `public/`
- Provides API endpoints for training plans and registrations
- Supports CRUD-style registration operations
- Validates registration input on the server
- Shows API documentation at `/api-docs`
- Uses JSON files in `data/` as the local data source

## Tech Stack

- Node.js
- Express.js
- swagger-ui-express
- yamljs
- HTML5, CSS3, JavaScript (fetch API)

## Project Structure

```text
Lab4/
├── api-docs/
│   └── openapi.yaml
├── data/
│   ├── plans.json
│   └── registrations.json
├── public/
│   ├── about.html
│   ├── about.js
│   ├── index.html
│   ├── index.js
│   ├── registration.html
│   ├── registration.js
│   ├── schedule.html
│   ├── schedule.js
│   ├── script.js
│   ├── styles.css
│   └── img/
├── routes/
│   ├── plans.js
│   └── registrations.js
├── dataStore.js
├── server.js
├── package.json
├── LAB_REPORT.md
└── README.md
```

## Prerequisites

- Node.js 18+ (Node 20+ recommended)
- npm

## Installation

From the project root:

```bash
npm install
```

## Run the Application

```bash
npm start
```

Or:

```bash
node server.js
```

Server runs on:

- http://localhost:3000

## Front-End Pages

- Home: http://localhost:3000/index.html
- Schedule: http://localhost:3000/schedule.html
- Registration: http://localhost:3000/registration.html
- About: http://localhost:3000/about.html

## API Documentation

Swagger UI:

- http://localhost:3000/api-docs

OpenAPI source:

- `api-docs/openapi.yaml`

## REST API Endpoints

Base URL:

- `http://localhost:3000`

### Status

- `GET /api/status`

### Plans

- `GET /api/plans` - list all plans
- `GET /api/plans/:id` - get one plan by ID

### Registrations

- `GET /api/registrations` - list all registrations
- `GET /api/registrations/:id` - get one registration by ID
- `POST /api/registrations` - create a registration
- `PUT /api/registrations/:id` - replace a registration
- `PATCH /api/registrations/:id` - partially update a registration
- `DELETE /api/registrations/:id` - delete a registration

## Example Request (PowerShell)

Create a new registration:

```powershell
$body = @{
  fullName = "Jordan Lee"
  email = "jordan.lee@example.com"
  phone = "613-555-0144"
  ageRange = "18-25"
  tier = "beginner"
  contactMethod = "email"
  startDate = "2026-07-30"
  about = "Looking for a beginner program and an easy first month."
} | ConvertTo-Json

Invoke-RestMethod -Method Post -Uri "http://localhost:3000/api/registrations" -ContentType "application/json" -Body $body
```

## Validation Rules (Server-Side)

For registration creation/replacement, the API validates:

- `fullName` is a non-empty string
- `email` is a valid email format
- `phone` is a valid phone format
- `ageRange` is a non-empty string
- `tier` matches an available plan level (`beginner`, `intermediate`, `advanced`)
- `contactMethod` is a non-empty string
- `startDate` is a valid date
- `about` is a non-empty string

Typical error responses:

- `400 Bad Request` for invalid input
- `404 Not Found` for missing resources

## Notes

- Registration data is persisted in `data/registrations.json`.
- Plans are loaded from `data/plans.json`.
- If `npm start` fails with `Cannot find module 'express'`, run `npm install` first.
