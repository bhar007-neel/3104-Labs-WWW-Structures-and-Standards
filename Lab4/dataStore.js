const fs = require("fs");
const path = require("path");

const registrationsPath = path.join(__dirname, "data", "registrations.json");
const plansPath = path.join(__dirname, "data", "plans.json");

function readJson(filePath) {
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
}

let registrations = readJson(registrationsPath);
const plans = readJson(plansPath);

function saveRegistrations() {
    fs.writeFileSync(registrationsPath, JSON.stringify(registrations, null, 2));
}

function findRegistrationById(registrationId) {
    return registrations.find(function (registration) {
        return registration.id === registrationId;
    });
}

function findPlanById(planId) {
    return plans.find(function (plan) {
        return plan.id === planId;
    });
}

function findPlanByLevel(level) {
    return plans.find(function (plan) {
        return plan.level === level;
    });
}

function getNextRegistrationId() {
    return registrations.length > 0 ? Math.max.apply(null, registrations.map(function (registration) { return registration.id; })) + 1 : 1;
}

function getRegistrations() {
    return registrations;
}

function getPlans() {
    return plans;
}

function getRegistrationsByPlanId(planId) {
    return registrations.filter(function (registration) {
        return registration.planId === planId;
    });
}

module.exports = {
    getRegistrations: getRegistrations,
    getPlans: getPlans,
    findRegistrationById: findRegistrationById,
    findPlanById: findPlanById,
    findPlanByLevel: findPlanByLevel,
    getNextRegistrationId: getNextRegistrationId,
    getRegistrationsByPlanId: getRegistrationsByPlanId,
    saveRegistrations: saveRegistrations
};