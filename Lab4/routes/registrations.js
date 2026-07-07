const express = require("express");
const store = require("../dataStore");

const router = express.Router();

function isValidEmail(email) {
    return typeof email === "string" && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return typeof phone === "string" && /^\+?[\d\s\-().]{10,}$/.test(phone);
}

function isValidDate(dateText) {
    return typeof dateText === "string" && !Number.isNaN(Date.parse(dateText));
}

function validateRegistrationPayload(payload, options) {
    const errors = [];
    const requireAllFields = options && options.requireAllFields;

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "fullName")) {
        if (typeof payload.fullName !== "string" || payload.fullName.trim() === "") {
            errors.push("fullName is required and must be a non-empty string");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "email")) {
        if (!isValidEmail(payload.email)) {
            errors.push("email is required and must be a valid email address");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "phone")) {
        if (!isValidPhone(payload.phone)) {
            errors.push("phone is required and must be a valid phone number");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "ageRange")) {
        if (typeof payload.ageRange !== "string" || payload.ageRange.trim() === "") {
            errors.push("ageRange is required and must be a non-empty string");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "tier")) {
        if (typeof payload.tier !== "string" || payload.tier.trim() === "" || !store.findPlanByLevel(payload.tier.trim())) {
            errors.push("tier is required and must match one of the available plans");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "contactMethod")) {
        if (typeof payload.contactMethod !== "string" || payload.contactMethod.trim() === "") {
            errors.push("contactMethod is required and must be a non-empty string");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "startDate")) {
        if (!isValidDate(payload.startDate)) {
            errors.push("startDate is required and must be a valid date");
        }
    }

    if (requireAllFields || Object.prototype.hasOwnProperty.call(payload, "about")) {
        if (typeof payload.about !== "string" || payload.about.trim() === "") {
            errors.push("about is required and must be a non-empty string");
        }
    }

    return errors;
}

function buildRegistrationProfile(payload) {
    const matchedPlan = store.findPlanByLevel(payload.tier.trim());

    return {
        id: store.getNextRegistrationId(),
        fullName: payload.fullName.trim(),
        email: payload.email.trim().toLowerCase(),
        phone: payload.phone.trim(),
        ageRange: payload.ageRange.trim(),
        tier: payload.tier.trim(),
        planId: matchedPlan.id,
        contactMethod: payload.contactMethod.trim(),
        startDate: payload.startDate,
        about: payload.about.trim()
    };
}

router.get("/", function (req, res) {
    res.json(store.getRegistrations());
});

router.get("/:id", function (req, res) {
    const registrationId = Number(req.params.id);
    const registration = store.findRegistrationById(registrationId);

    if (!registration) {
        return res.status(404).json({ error: "Registration not found" });
    }

    res.json(registration);
});

router.post("/", function (req, res) {
    const errors = validateRegistrationPayload(req.body, { requireAllFields: true });

    if (errors.length > 0) {
        return res.status(400).json({ error: "Invalid registration data", details: errors });
    }

    const newRegistration = buildRegistrationProfile(req.body);

    store.getRegistrations().push(newRegistration);
    store.saveRegistrations();
    res.status(201).json(newRegistration);
});

router.put("/:id", function (req, res) {
    const registrationId = Number(req.params.id);
    const registration = store.findRegistrationById(registrationId);

    if (!registration) {
        return res.status(404).json({ error: "Registration not found" });
    }

    const errors = validateRegistrationPayload(req.body, { requireAllFields: true });

    if (errors.length > 0) {
        return res.status(400).json({ error: "Invalid registration data", details: errors });
    }

    const updatedRegistration = buildRegistrationProfile(req.body);

    registration.fullName = updatedRegistration.fullName;
    registration.email = updatedRegistration.email;
    registration.phone = updatedRegistration.phone;
    registration.ageRange = updatedRegistration.ageRange;
    registration.tier = updatedRegistration.tier;
    registration.planId = updatedRegistration.planId;
    registration.contactMethod = updatedRegistration.contactMethod;
    registration.startDate = updatedRegistration.startDate;
    registration.about = updatedRegistration.about;

    store.saveRegistrations();
    res.json(registration);
});

router.patch("/:id", function (req, res) {
    const registrationId = Number(req.params.id);
    const registration = store.findRegistrationById(registrationId);

    if (!registration) {
        return res.status(404).json({ error: "Registration not found" });
    }

    const errors = validateRegistrationPayload(req.body, { requireAllFields: false });

    if (errors.length > 0) {
        return res.status(400).json({ error: "Invalid registration data", details: errors });
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "fullName")) {
        registration.fullName = req.body.fullName.trim();
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "email")) {
        registration.email = req.body.email.trim().toLowerCase();
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "phone")) {
        registration.phone = req.body.phone.trim();
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "ageRange")) {
        registration.ageRange = req.body.ageRange.trim();
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "tier")) {
        registration.tier = req.body.tier.trim();
        const matchedPlan = store.findPlanByLevel(registration.tier);
        if (matchedPlan) {
            registration.planId = matchedPlan.id;
        }
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "contactMethod")) {
        registration.contactMethod = req.body.contactMethod.trim();
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "startDate")) {
        registration.startDate = req.body.startDate;
    }

    if (Object.prototype.hasOwnProperty.call(req.body, "about")) {
        registration.about = req.body.about.trim();
    }

    store.saveRegistrations();
    res.json(registration);
});

router.delete("/:id", function (req, res) {
    const registrationId = Number(req.params.id);
    const registrationIndex = store.getRegistrations().findIndex(function (registration) {
        return registration.id === registrationId;
    });

    if (registrationIndex === -1) {
        return res.status(404).json({ error: "Registration not found" });
    }

    const deletedRegistration = store.getRegistrations().splice(registrationIndex, 1)[0];
    store.saveRegistrations();
    res.json({ message: "Registration deleted", registration: deletedRegistration });
});

module.exports = router;