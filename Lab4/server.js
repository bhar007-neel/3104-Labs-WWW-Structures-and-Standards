const express = require("express");
const app = express();
const PORT = 3000;
const registrationsRouter = require("./routes/registrations");
const plansRouter = require("./routes/plans");
const swaggerUi = require("swagger-ui-express");
const YAML = require("yamljs");

const openApiDocument = YAML.load("./api-docs/openapi.yaml");

// Core middleware for JSON payloads and static front-end files.
app.use(express.json());
app.use(express.static("public")); // when a browser requests a file, it will look in the public folder first

// Serve the API documentation through Swagger UI.
app.use("/api-docs", swaggerUi.serve, swaggerUi.setup(openApiDocument));

// Simple status endpoint used by the app and the documentation.
app.get("/api/status", function (req, res) {
    res.json({ message: "Lab 4 API is running" });
});

// Resource routers for plans and registrations.
app.use("/api/plans", plansRouter);
app.use("/api/registrations", registrationsRouter);

app.listen(PORT, function () {
    console.log(`Server running on http://localhost:${PORT}`);
});