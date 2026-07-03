(function () {
    'use strict';

    function settings() {
        return {
            baseUrl: document.getElementById('api-base').value.replace(/\/$/, ''),
            apiKey: document.getElementById('api-key').value.trim(),
            identifier: document.getElementById('identifier').value.trim()
        };
    }

    function headers(apiKey) {
        var result = { 'Content-Type': 'application/json' };
        if (apiKey) {
            result['X-API-Key'] = apiKey;
        }
        return result;
    }

    function request(path, options) {
        var config = settings();
        var url = config.baseUrl + path;
        var init = options || {};
        init.headers = Object.assign(headers(config.apiKey), init.headers || {});

        return fetch(url, init).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.success) {
                    throw new Error((payload.error && payload.error.message) || 'Request failed');
                }
                return payload.data;
            });
        });
    }

    function renderDashboard(data) {
        document.getElementById('dashboard-output').hidden = false;
        var metrics = document.getElementById('overview-metrics');
        var insights = data.insights || {};
        var readiness = insights.geo_readiness || {};

        metrics.innerHTML = [
            metric('Current score', readiness.current || '—'),
            metric('Average score', readiness.average || '—'),
            metric('Trend', readiness.trend || '—'),
            metric('Data points', insights.data_points || 0)
        ].join('');

        document.getElementById('history-output').textContent = JSON.stringify(data, null, 2);
    }

    function metric(label, value) {
        return '<div class="metric-card"><strong>' + value + '</strong><span>' + label + '</span></div>';
    }

    document.getElementById('dashboard-settings').addEventListener('submit', function (event) {
        event.preventDefault();
        var config = settings();
        request('/v1/dashboard?identifier=' + encodeURIComponent(config.identifier) + '&days=30')
            .then(renderDashboard)
            .catch(showError);
    });

    document.getElementById('track-now').addEventListener('click', function () {
        var config = settings();
        request('/v1/track', {
            method: 'POST',
            body: JSON.stringify({ identifier: config.identifier })
        }).then(function () {
            return request('/v1/dashboard?identifier=' + encodeURIComponent(config.identifier));
        }).then(renderDashboard).catch(showError);
    });

    document.getElementById('bulk-run').addEventListener('click', function () {
        var lines = document.getElementById('bulk-items').value.split('\n').filter(Boolean);
        var items = lines.map(function (line) { return JSON.parse(line); });

        request('/v1/bulk-analyze', {
            method: 'POST',
            body: JSON.stringify({ items: items })
        }).then(function (data) {
            document.getElementById('bulk-output').textContent = JSON.stringify(data, null, 2);
        }).catch(showError);
    });

    document.getElementById('compare-run').addEventListener('click', function () {
        var competitors = JSON.parse(document.getElementById('competitors-json').value || '[]');

        request('/v1/compare', {
            method: 'POST',
            body: JSON.stringify({
                primary_name: settings().identifier || 'Primary',
                primary_content: document.getElementById('primary-content').value,
                competitors: competitors
            })
        }).then(function (data) {
            document.getElementById('compare-output').textContent = JSON.stringify(data, null, 2);
        }).catch(showError);
    });

    function showError(error) {
        window.alert(error.message || 'Request failed');
    }
}());
