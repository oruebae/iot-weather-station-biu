<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IoT Weather Station</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }

        .weather-card-bg {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }

        .bar-container {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 40px;
        }

        .bar {
            width: 6px;
            background-color: #E2E8F0;
            border-radius: 4px;
            transition: height 0.3s, background-color 0.3s;
        }

        .bar.active {
            background-color: #3B82F6;
        }

        .circle-chart {
            width: 70px;
            height: 70px;
        }

        .circle-bg {
            fill: none;
            stroke: #E2E8F0;
            stroke-width: 4;
        }

        .circle {
            fill: none;
            stroke-width: 3;
            stroke-linecap: round;
            transition: stroke-dasharray 1s ease;
        }
    </style>
</head>

<body class="bg-indigo-50 text-slate-600 min-h-screen p-4 md:p-8">

    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <div class="flex items-center gap-4">
                <div
                    class="h-12 w-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-200">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight" id="device-name">...</h1>
                    <div class="flex items-center gap-2">
                        <span id="status-dot" class="w-2 h-2 rounded-full bg-slate-300"></span>
                        <span id="status-text"
                            class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Desconectado</span>
                    </div>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="flex bg-white rounded-xl p-1 shadow-sm border border-slate-100">
                <input type="date" id="date-picker"
                    class="bg-transparent border-none text-sm text-slate-600 font-medium cursor-pointer focus:ring-0 px-3 py-2">
                <button id="btn-today"
                    class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-100 transition-colors">Hoy</button>
            </div>
        </header>

        <!-- Dynamic Content -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Left: Main Weather Card (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <div
                    class="glass-panel rounded-3xl p-8 shadow-xl shadow-indigo-100/50 relative overflow-hidden border-t-4 border-indigo-500">
                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Temperatura</h2>
                                <p class="text-xs text-slate-400 mt-1" id="card-context">Reciente</p>
                            </div>
                            <!-- Weather Icon -->
                            <div id="weather-icon"></div>
                        </div>

                        <div class="flex items-baseline mb-2">
                            <span class="text-7xl font-bold text-slate-800 tracking-tighter" id="val-temp">--</span>
                            <span class="text-3xl text-slate-400 font-medium ml-1">°C</span>
                        </div>
                        <p class="text-lg font-medium text-indigo-600 mb-6" id="weather-state">--</p>

                        <div class="grid grid-cols-3 gap-2 py-4 border-t border-slate-100">
                            <div class="text-center">
                                <p class="text-[0.65rem] uppercase text-slate-400 font-bold mb-1">Mín</p>
                                <p class="text-lg font-bold text-slate-700" id="val-min">--</p>
                            </div>
                            <div class="text-center border-l border-r border-slate-100">
                                <p class="text-[0.65rem] uppercase text-slate-400 font-bold mb-1">Máx</p>
                                <p class="text-lg font-bold text-slate-700" id="val-max">--</p>
                            </div>
                            <div class="text-center">
                                <p class="text-[0.65rem] uppercase text-slate-400 font-bold mb-1">Tendencia</p>
                                <p class="text-lg font-bold text-emerald-500" id="val-trend">--</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Metrics -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="glass-panel rounded-3xl p-5 shadow-sm weather-card-bg">
                        <div class="flex justify-between items-center mb-4"><span
                                class="text-xs font-bold text-slate-400 uppercase">Humedad</span></div>
                        <div class="flex items-end justify-between">
                            <div><span class="text-2xl font-bold text-slate-800" id="val-hum">--</span><span
                                    class="text-sm text-slate-400">%</span></div>
                            <div class="bar-container" id="hum-bars"></div>
                        </div>
                    </div>
                    <div class="glass-panel rounded-3xl p-5 shadow-sm weather-card-bg">
                        <div class="flex justify-between items-center mb-2"><span
                                class="text-xs font-bold text-slate-400 uppercase">Presión</span></div>
                        <div class="flex items-center justify-center py-1">
                            <div class="relative flex items-center justify-center">
                                <svg class="circle-chart" viewBox="0 0 36 36">
                                    <path class="circle-bg"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <path class="circle" id="press-circle" stroke="#10B981" stroke-dasharray="0, 100"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                </svg>
                                <div class="absolute text-center mt-1"><span
                                        class="block text-xs font-bold text-slate-800 leading-tight"
                                        id="val-press">--</span><span
                                        class="block text-[0.5rem] text-slate-400">hPa</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Charts (8 cols) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Chart -->
                <div class="glass-panel rounded-3xl p-8 shadow-sm bg-white h-96 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Evolución & Predicción</h3>
                        <div class="flex gap-4 text-xs font-medium">
                            <span class="flex items-center gap-1 text-orange-500"><span
                                    class="w-2 h-2 rounded-full bg-orange-500"></span> Real</span>
                            <span class="flex items-center gap-1 text-indigo-500"><span
                                    class="w-2 h-2 rounded-full bg-indigo-500"></span> EMA (A300)</span>
                        </div>
                    </div>
                    <div class="flex-1 w-full relative">
                        <canvas id="weatherChart"></canvas>
                    </div>
                </div>

                <!-- Alerts -->
                <div class="glass-panel rounded-3xl p-6 shadow-sm bg-white">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-4 flex items-center gap-2">
                        Alertas Recientes
                    </h3>
                    <div class="space-y-3 max-h-40 overflow-y-auto pr-2" id="alerts-container">
                        <p class="text-xs text-slate-400">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>

</html>