const express = require("express");
const store = require("../dataStore");

const router = express.Router();

router.get("/", function (req, res) {
    res.json(store.getPlans());
});

router.get("/:id", function (req, res) {
    const planId = Number(req.params.id);
    const plan = store.findPlanById(planId);

    if (!plan) {
        return res.status(404).json({ error: "Plan not found" });
    }

    res.json(plan);
});

module.exports = router;