// js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // --- STATE ---
    let currentDateFilter = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD
    let currentDeviceId = null;

    // --- INIT UI ---
    document.getElementById('date-picker').value = currentDateFilter;

    // --- CHART INIT ---
    const ctx = document.getElementById('weatherChart').getContext('2d');
    let weatherChart = new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [] },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            elements: { point: { radius: 0, hitRadius: 10, hoverRadius: 5 } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                y: { grid: { color: '#f8fafc' } }
            }
        }
    });

    const updateDashboard = async () => {
        try {
            let url = `api/get-data.php?date=${currentDateFilter}`;
            if (currentDeviceId) {
                url += `&device_id=${currentDeviceId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();

            if (data.error) {
                console.error(data.error);
                return;
            }

            // 0. Update Device Selector (Initial Load)
            const selector = document.getElementById('device-selector');
            if (selector.options.length <= 1 && data.devices && data.devices.length > 0) {
                selector.innerHTML = '';
                data.devices.forEach(dev => {
                    const opt = document.createElement('option');
                    opt.value = dev.device_id;
                    opt.textContent = dev.name;
                    if (dev.device_id === data.device.id) opt.selected = true;
                    selector.appendChild(opt);
                });

                // Set current ID if not set
                if (!currentDeviceId) {
                    currentDeviceId = data.device.id; // API returns current device ID
                }
            }

            // 1. Header & Status
            const isOnline = data.device.is_online;
            document.getElementById('status-dot').className = `w-2 h-2 rounded-full ${isOnline ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'}`;
            document.getElementById('status-text').className = `text-xs font-semibold uppercase tracking-wider ${isOnline ? 'text-emerald-600' : 'text-red-500'}`;
            document.getElementById('status-text').textContent = isOnline ? 'En Línea' : 'Desconectado';

            // 2. Weather State & Icon
            if (data.state) {
                document.getElementById('weather-state').textContent = data.state.label;
                const iconMap = {
                    'sunny': '<svg class="w-16 h-16 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
                    'rain': '<svg class="w-16 h-16 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>',
                    'storm': '<svg class="w-16 h-16 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>',
                    'cloudy': '<svg class="w-16 h-16 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>',
                    'variable': '<svg class="w-16 h-16 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>'
                };
                document.getElementById('weather-icon').innerHTML = iconMap[data.state.code] || iconMap['variable'];
            }

            // 3. Current Metrics
            if (data.current) {
                document.getElementById('val-temp').textContent = data.current.temperature;
                document.getElementById('val-hum').textContent = Math.round(data.current.humidity);
                document.getElementById('val-press').textContent = Math.round(data.current.pressure);

                // Visuals
                const humContainer = document.getElementById('hum-bars');
                humContainer.innerHTML = '';
                [20, 40, 60, 80, 100].forEach((l, i) => {
                    const bar = document.createElement('div');
                    const isActive = data.current.humidity >= (l - 19);
                    bar.className = `bar ${isActive ? 'active' : ''}`;
                    bar.style.height = `${(i + 1) * 6 + 5}px`;
                    humContainer.appendChild(bar);
                });

                const pct = Math.min(100, Math.max(0, (data.current.pressure - 980) / (1040 - 980) * 100));
                document.getElementById('press-circle').setAttribute('stroke-dasharray', `${pct}, 100`);
            } else {
                document.getElementById('val-temp').textContent = '--';
            }

            // 4. Chart & History
            if (data.history && data.history.length > 0) {
                const temps = data.history.map(d => parseFloat(d.temperature));
                document.getElementById('val-min').innerText = Math.min(...temps).toFixed(1);
                document.getElementById('val-max').innerText = Math.max(...temps).toFixed(1);
                document.getElementById('card-context').innerText = (currentDateFilter === new Date().toLocaleDateString('en-CA')) ? 'Reciente' : currentDateFilter;

                // Trend
                if (data.ema_temperature && data.ema_temperature.length > 0) {
                    const lastEma = data.ema_temperature[data.ema_temperature.length - 1];
                    const diff = (parseFloat(data.current.temperature) - lastEma).toFixed(1);
                    document.getElementById('val-trend').innerText = (diff > 0 ? '+' : '') + diff + '°';
                }

                // Update Chart
                const labels = data.history.map(d => new Date(d.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
                weatherChart.data.labels = labels;
                weatherChart.data.datasets = [
                    {
                        label: 'Real',
                        data: temps,
                        borderColor: '#F97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'EMA',
                        data: data.ema_temperature,
                        borderColor: '#6366F1',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.4,
                        pointRadius: 0
                    }
                ];
                weatherChart.update();
            }

            // 5. Alerts
            const alertsContainer = document.getElementById('alerts-container');
            alertsContainer.innerHTML = '';
            if (data.alerts && data.alerts.length > 0) {
                data.alerts.forEach(alert => {
                    const el = document.createElement('div');
                    el.className = 'flex items-start gap-3 border-b border-slate-50 pb-3 last:border-0';
                    el.innerHTML = `
                         <div class="mt-1 w-2 h-2 rounded-full bg-red-500"></div>
                         <div>
                            <p class="text-xs font-bold text-slate-700 uppercase">${alert.alert_type} (${alert.condition_type})</p>
                            <p class="text-xs text-slate-400">${new Date(alert.triggered_at).toLocaleTimeString()} - Val: ${alert.threshold_value}</p>
                         </div>
                    `;
                    alertsContainer.appendChild(el);
                });
            } else {
                alertsContainer.innerHTML = '<p class="text-xs text-slate-400">Sin alertas recientes.</p>';
            }

            // 6. Activity
            const activityContainer = document.getElementById('activity-container');
            activityContainer.innerHTML = '';
            if (data.activity && data.activity.length > 0) {
                data.activity.forEach(log => {
                    const el = document.createElement('div');
                    el.className = 'flex items-center justify-between border-b border-slate-50 pb-2 last:border-0 text-sm';
                    el.innerHTML = `
                         <span class="text-slate-500">${new Date(log.created_at).toLocaleTimeString()}</span>
                         <span class="font-medium text-slate-700">${log.temperature}°C / ${log.humidity}%</span>
                    `;
                    activityContainer.appendChild(el);
                });
            } else {
                activityContainer.innerHTML = '<p class="text-xs text-slate-400">Sin actividad reciente.</p>';
            }

        } catch (e) { console.error(e); }
    };

    // --- EVENT LISTENERS ---
    document.getElementById('date-picker').addEventListener('change', (e) => {
        currentDateFilter = e.target.value;
        updateDashboard();
    });

    document.getElementById('btn-today').addEventListener('click', () => {
        currentDateFilter = new Date().toLocaleDateString('en-CA');
        document.getElementById('date-picker').value = currentDateFilter;
        updateDashboard();
    });

    document.getElementById('device-selector').addEventListener('change', (e) => {
        currentDeviceId = e.target.value;
        updateDashboard();
    });

    // Start
    updateDashboard();
    setInterval(updateDashboard, 30000);
});
