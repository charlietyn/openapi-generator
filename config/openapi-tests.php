<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Test Templates
    |--------------------------------------------------------------------------
    |
    | Define which checks to run for each action type.
    | Each action can specify an array of check names that will be executed.
    |
    | Available checks are defined in the 'snippets' section below.
    |
    */

    'templates' => [
        // CRUD Templates
        'index' => [
            'checks' => [
                'status_200',
                'json_response',
                'array_data',
                'pagination_meta',
            ],
        ],

        'show' => [
            'checks' => [
                'status_200',
                'json_response',
                'has_data_object',
                'has_id_field',
            ],
        ],

        'store' => [
            'checks' => [
                'status_201',
                'json_response',
                'has_data_object',
                'has_id_field',
                'save_id_to_tracking', // Save to tracking variable
            ],
        ],

        'update' => [
            'checks' => [
                'status_200',
                'json_response',
                'has_data_object',
                'has_id_field',
            ],
        ],

        'destroy' => [
            'checks' => [
                'status_200_or_204',
                'json_response',
            ],
        ],

        // Custom Action Templates
        'create' => [
            'checks' => [
                'status_201',
                'json_response',
                'has_data_object',
                'has_id_field',
                'save_id_to_tracking',
            ],
        ],

        'list' => [
            'checks' => [
                'status_200',
                'json_response',
                'array_data',
            ],
        ],

        'delete' => [
            'checks' => [
                'status_200_or_204',
                'json_response',
            ],
        ],

        // Generic actions
        'unknown' => [
            'checks' => [
                'status_200',
                'json_response',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Snippets
    |--------------------------------------------------------------------------
    |
    | Define the actual test code for each check.
    | Each check has two variants: 'postman' and 'insomnia'
    |
    | Available placeholders:
    | {{tracking_var}} - Will be replaced with tracking variable name (e.g., 'last_user_id')
    | {{entity}}       - Will be replaced with entity name (e.g., 'User')
    |
    */

    'snippets' => [
        // Status Code Checks
        'status_200' => [
            'postman' => [
                'pm.test("✅ Status code is 200", function () {',
                '    pm.response.to.have.status(200);',
                '});',
            ],
            'insomnia' => [
                'const s = insomnia.response.status;',
                'insomnia.test("✅ Status code is 200", () => {',
                '    insomnia.expect(s).to.equal(200);',
                '});',
            ],
        ],

        'status_201' => [
            'postman' => [
                'pm.test("✅ Status code is 201", function () {',
                '    pm.response.to.have.status(201);',
                '});',
            ],
            'insomnia' => [
                'const s = insomnia.response.status;',
                'insomnia.test("✅ Status code is 201", () => {',
                '    insomnia.expect(s).to.equal(201);',
                '});',
            ],
        ],

        'status_200_or_204' => [
            'postman' => [
                'pm.test("✅ Status code is 200 or 204", function () {',
                '    pm.expect(pm.response.code).to.be.oneOf([200, 204]);',
                '});',
            ],
            'insomnia' => [
                'const s = insomnia.response.status;',
                'insomnia.test("✅ Status code is 200 or 204", () => {',
                '    insomnia.expect(s).to.be.oneOf([200, 204]);',
                '});',
            ],
        ],

        // Response Type Checks
        'json_response' => [
            'postman' => [
                'pm.test("✅ Response is JSON", function () {',
                '    pm.response.to.be.json;',
                '});',
            ],
            'insomnia' => [
                'if (s !== 204) {',
                '    const ct = ((insomnia.response.headers.find(h =>',
                '        h.key.toLowerCase() === "content-type") || {}).value) || "";',
                '    insomnia.test("✅ Response is JSON", () => {',
                '        insomnia.expect(ct).to.include("application/json");',
                '    });',
                '}',
            ],
        ],

        // Data Structure Checks
        'array_data' => [
            'postman' => [
                'pm.test("✅ Response has data array", function () {',
                '    const jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("data");',
                '    pm.expect(jsonData.data).to.be.an("array");',
                '});',
            ],
            'insomnia' => [
                'if (s === 200) {',
                '    const data = insomnia.response.json();',
                '    insomnia.test("✅ Response has data array", () => {',
                '        insomnia.expect(data).to.have.property("data");',
                '        insomnia.expect(data.data).to.be.an("array");',
                '    });',
                '}',
            ],
        ],

        'has_data_object' => [
            'postman' => [
                'pm.test("✅ Response has data object", function () {',
                '    const jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("data");',
                '    pm.expect(jsonData.data).to.be.an("object");',
                '});',
            ],
            'insomnia' => [
                'if (s === 200 || s === 201) {',
                '    const data = insomnia.response.json();',
                '    insomnia.test("✅ Response has data object", () => {',
                '        insomnia.expect(data).to.have.property("data");',
                '        insomnia.expect(data.data).to.be.an("object");',
                '    });',
                '}',
            ],
        ],

        'has_id_field' => [
            'postman' => [
                'pm.test("✅ Data has ID field", function () {',
                '    const jsonData = pm.response.json();',
                '    if (jsonData.data) {',
                '        pm.expect(jsonData.data).to.have.property("id");',
                '    }',
                '});',
            ],
            'insomnia' => [
                'if (s === 200 || s === 201) {',
                '    const data = insomnia.response.json();',
                '    if (data && data.data) {',
                '        insomnia.test("✅ Data has ID field", () => {',
                '            insomnia.expect(data.data).to.have.property("id");',
                '        });',
                '    }',
                '}',
            ],
        ],

        // Pagination Checks
        'pagination_meta' => [
            'postman' => [
                'pm.test("✅ Response has pagination meta", function () {',
                '    const jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("meta");',
                '    const meta = jsonData.meta;',
                '    pm.expect(meta).to.have.property("current_page");',
                '    pm.expect(meta).to.have.property("total");',
                '});',
            ],
            'insomnia' => [
                'if (s === 200) {',
                '    const data = insomnia.response.json();',
                '    insomnia.test("✅ Response has pagination meta", () => {',
                '        insomnia.expect(data).to.have.property("meta");',
                '        insomnia.expect(data.meta).to.have.property("current_page");',
                '        insomnia.expect(data.meta).to.have.property("total");',
                '    });',
                '}',
            ],
        ],

        // Tracking Variable Management
        'save_id_to_tracking' => [
            'postman' => [
                '// Save ID to tracking variable',
                'pm.test("✅ ID saved to {{tracking_var}}", function () {',
                '    const jsonData = pm.response.json();',
                '    const id = (jsonData && jsonData.data && jsonData.data.id) || (jsonData && jsonData.id);',
                '    if (id !== undefined) {',
                '        pm.environment.set("{{tracking_var}}", id);',
                '        console.log("💾 Saved ID to {{tracking_var}}:", id);',
                '    }',
                '});',
            ],
            'insomnia' => [
                '// Save ID to tracking variable',
                'if (s === 200 || s === 201) {',
                '    const data = insomnia.response.json();',
                '    const id = (data && data.data && data.data.id) || (data && data.id);',
                '    if (id !== undefined) {',
                '        insomnia.baseEnvironment.set("{{tracking_var}}", id);',
                '        insomnia.test("✅ ID saved to {{tracking_var}}", () => {',
                '            insomnia.expect(id).to.exist;',
                '        });',
                '        console.log("💾 Saved ID to {{tracking_var}}:", id);',
                '    }',
                '}',
            ],
        ],

        // Custom Response Checks
        'has_message' => [
            'postman' => [
                'pm.test("✅ Response has message", function () {',
                '    const jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("message");',
                '});',
            ],
            'insomnia' => [
                'if (s === 200 || s === 201) {',
                '    const data = insomnia.response.json();',
                '    insomnia.test("✅ Response has message", () => {',
                '        insomnia.expect(data).to.have.property("message");',
                '    });',
                '}',
            ],
        ],

        'has_token' => [
            'postman' => [
                'pm.test("✅ Response has token", function () {',
                '    const jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("token");',
                '    pm.environment.set("token", jsonData.token);',
                '});',
            ],
            'insomnia' => [
                'if (s === 200 || s === 201) {',
                '    const data = insomnia.response.json();',
                '    insomnia.test("✅ Response has token", () => {',
                '        insomnia.expect(data).to.have.property("token");',
                '    });',
                '    if (data && data.token) {',
                '        insomnia.baseEnvironment.set("token", data.token);',
                '        console.log("🔑 Token saved to environment");',
                '    }',
                '}',
            ],
        ],

        // Validation Error Checks
        'validation_errors' => [
            'postman' => [
                'pm.test("✅ Has validation errors", function () {',
                '    const jsonData = pm.response.json();',
                '    pm.expect(jsonData).to.have.property("errors");',
                '    pm.expect(jsonData.errors).to.be.an("object");',
                '});',
            ],
            'insomnia' => [
                'if (s === 422) {',
                '    const data = insomnia.response.json();',
                '    insomnia.test("✅ Has validation errors", () => {',
                '        insomnia.expect(data).to.have.property("errors");',
                '        insomnia.expect(data.errors).to.be.an("object");',
                '    });',
                '}',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Tests
    |--------------------------------------------------------------------------
    |
    | Define custom tests for specific endpoints.
    | Format: 'entity.action' => ['checks' => [...]]
    |
    | These override the default templates.
    |
    */

    'custom_tests' => [
        // Authentication endpoints
        'auth.login' => [
            'checks' => [
                'status_200',
                'json_response',
                'has_token',
                'has_data_object',
            ],
        ],

        'auth.register' => [
            'checks' => [
                'status_201',
                'json_response',
                'has_token',
                'has_data_object',
                'save_id_to_tracking',
            ],
        ],

        'auth.logout' => [
            'checks' => [
                'status_200',
                'json_response',
                'has_message',
            ],
        ],

        // API Apps - Rotate action
        'api-apps.rotate' => [
            'checks' => [
                'status_200',
                'json_response',
                'has_data_object',
            ],
        ],

        // User restore
        'users.restore' => [
            'checks' => [
                'status_200',
                'json_response',
                'has_data_object',
                'has_message',
            ],
        ],

        /**
         * Add your custom endpoint tests here
         *
         * Example:
         * 'posts.publish' => [
         *     'checks' => [
         *         'status_200',
         *         'json_response',
         *         'has_message',
         *     ],
         * ],
         */
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Test Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        // Enable detailed logging
        'verbose_logging' => env('OPENAPI_TESTS_VERBOSE', false),

        // Timeout for tests (ms)
        'timeout' => 5000,

        // Fail on first error
        'fail_fast' => false,

        // Include performance metrics
        'measure_performance' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Define acceptable response time thresholds
    |
    */

    'performance' => [
        'postman' => [
            'pm.test("⚡ Response time is acceptable", function () {',
            '    pm.expect(pm.response.responseTime).to.be.below(2000);',
            '});',
        ],
        'insomnia' => [
            '// Performance check',
            'const responseTime = Date.now() - insomnia.request.startTime;',
            'insomnia.test("⚡ Response time is acceptable", () => {',
            '    insomnia.expect(responseTime).to.be.below(2000);',
            '});',
        ],
    ],
];