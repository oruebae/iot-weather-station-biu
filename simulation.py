import random
import matplotlib.pyplot as plt

# 1. Configuration
ALPHA = 0.3
THRESHOLD_TEMP = 32.0
DATA_COUNT = 50

# 2. Generate Mock Data
data = []
base_temp = 25.0
current_temp = base_temp

print(f"--- Generating {DATA_COUNT} records ---")
for i in range(DATA_COUNT):
    # Random walk for realistic temp changes
    change = random.uniform(-0.5, 0.6) # Slightly trending up
    current_temp += change
    
    # Add random outlier occasionally
    if i == 40:
        current_temp = 35.0 # Spike to trigger alert
        
    data.append({
        'id': i,
        'temp': round(current_temp, 2),
        'hum': round(random.uniform(40, 60), 2)
    })

# 3. Calculate EMA
ema_values = []
prev_ema = None

for point in data:
    val = point['temp']
    if prev_ema is None:
        current_ema = val
    else:
        current_ema = (val * ALPHA) + (prev_ema * (1 - ALPHA))
    
    ema_values.append(round(current_ema, 2))
    prev_ema = current_ema

# 4. Validate Logic
print("\n--- Validation Results ---")
alerts_triggered = []
for point in data:
    if point['temp'] > THRESHOLD_TEMP:
        alerts_triggered.append(point)
        print(f"[ALERT] High Temp detected: {point['temp']}°C at index {point['id']} (Threshold: {THRESHOLD_TEMP}°C)")

if len(alerts_triggered) > 0:
    print(f"SUCCESS: Alert logic correctly identified {len(alerts_triggered)} breach(es).")
else:
    print("WARNING: No alerts triggered (check data generation range).")

# 5. Export Data for Charting (or plotting if env allows)
# We will create a simple plot using matplotlib
try:
    plt.figure(figsize=(10, 6))
    temps = [d['temp'] for d in data]
    indices = range(len(data))
    
    plt.plot(indices, temps, label='Actual Temp', marker='o', alpha=0.5)
    plt.plot(indices, ema_values, label=f'EMA (alpha={ALPHA})', color='orange', linewidth=2)
    
    # Mark alerts
    for alert in alerts_triggered:
        plt.axvline(x=alert['id'], color='red', linestyle='--', alpha=0.3)
        plt.text(alert['id'], alert['temp'] + 0.5, '!', color='red', fontweight='bold')

    plt.axhline(y=THRESHOLD_TEMP, color='r', linestyle=':', label='Threshold')
    
    plt.title('Simulation: Temperature vs EMA Forecast')
    plt.xlabel('Reading Index')
    plt.ylabel('Temperature (°C)')
    plt.legend()
    plt.grid(True, which='both', linestyle='--', linewidth=0.5)
    
    output_file = 'simulation_chart.png'
    plt.savefig(output_file)
    print(f"\nChart generated successfully: {output_file}")
    
except Exception as e:
    print(f"Could not generate chart: {e}")

# Output last 5 data points for verification
print("\n--- Last 5 Data Points (Actual vs EMA) ---")
for i in range(DATA_COUNT - 5, DATA_COUNT):
    print(f"Index {i}: Temp={data[i]['temp']} | EMA={ema_values[i]}")
