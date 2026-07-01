# 11 — Sprint v2.0: Machine Learning Failure Prediction

**Target:** Prediksi kegagalan server/service sebelum terjadi menggunakan data historis
**Estimasi:** 4 minggu
**Dependensi:** v1.3 selesai (butuh data historis metric, log, service status)

---

## Week 1-2: Data Pipeline

### 1.1 Metric Aggregation
- [ ] `MetricsAggregator` — aggregate raw metrics ke window: 5m, 15m, 1h, 6h, 1d
- [ ] Aggregate table (Redis timeseries atau MySQL agg table):
  - `metric_aggregates`: server_id, type, window, avg, min, max, stddev, recorded_at
- [ ] `AggregateMetricsJob` — jalan setiap 5 menit

### 1.2 Feature Engineering

```php
class FeatureEngineering
{
    // Build feature vector per server per timestamp
    public function build(Server $server, Carbon $timestamp): array
    {
        return [
            // Metric features
            'cpu_avg_1h', 'cpu_max_1h', 'cpu_stddev_1h',
            'cpu_trend_6h', // slope of linear regression over 6h
            'ram_avg_1h', 'ram_max_1h', 'ram_trend_6h',
            'disk_usage_percent', 'disk_free_gb',
            'network_rx_kbps', 'network_tx_kbps',

            // Service features
            'service_restart_count_24h',
            'service_downtime_seconds_24h',
            'phpfpm_slow_requests_avg_1h',
            'phpfpm_max_children_hit_24h',
            'mysql_slow_queries_avg_1h',
            'redis_evicted_keys_24h',
            'queue_size_avg_1h',
            'queue_failed_jobs_24h',

            // Log features
            'error_count_1h', 'error_count_24h',
            'critical_count_1h', 'critical_count_24h',
            '502_count_1h', '502_count_24h',
            'nginx_error_rate_1h',

            // Temporal features
            'hour_of_day', 'day_of_week', 'is_weekend',
            'days_since_last_incident',
        ];
    }
}
```

### 1.3 Label Generation
- [ ] Label positif: server mengalami incident dalam 1 jam ke depan
  - Service down >30 detik
  - Alert critical triggered
  - 502 error terjadi
- [ ] Label negatif: server normal
- [ ] `LabelGenerator` — scan historical alerts + service_status_history untuk generate label

### 1.4 Training Dataset
- [ ] `DatasetBuilder` — join features + labels, export CSV/JSON
- [ ] Min data: 30 hari historical metric (v1.3 requirement)
- [ ] Dataset split: 80% train, 20% test

---

## Week 3: Model Training

### 3.1 Model Options (pilih satu, rekomendasi: Option B)

**Option A — Simple threshold (no ML library needed)**
- [ ] Linear regression per metric → slope → jika slope > threshold → warning
- [ ] Exponential moving average anomaly detection
- [ ] Pro: zero dependency, simple
- [ ] Con: kurang akurat untuk korelasi multi-faktor

**Option B — PHP-ML (php-ai/php-ml)**
- [ ] Random Forest classifier
- [ ] Input: 30+ features, output: probability of failure in 1h
- [ ] Training: `php-ai/php-ml` library
- [ ] Pro: handle non-linear correlation, reasonable accuracy
- [ ] Con: training di PHP, lambat untuk dataset besar

**Option C — Python microservice (highest accuracy, overkill)**
- [ ] Flask/FastAPI endpoint untuk inference
- [ ] Scikit-learn / XGBoost model
- [ ] Laravel call via HTTP
- [ ] Tidak di-cover dalam PRD ini

### 3.2 Model Training Command
- [ ] `php artisan monitor:train-model` — train model dari data historis
- [ ] Simpan model serialized ke `storage/app/ml/model.rf`
- [ ] Simpan metadata: accuracy, precision, recall, f1, trained_at, feature_count

### 3.3 Model Evaluation
- [ ] Confusion matrix
- [ ] Precision (false positive rate) — jangan terlalu banyak false alert
- [ ] Recall (false negative rate) — jangan lewatkan incident nyata
- [ ] Target: precision > 70%, recall > 60%

---

## Week 4: Inference & Integration

### 4.1 Prediction Job
- [ ] `PredictFailureJob` — jalan setiap 5 menit per server
- [ ] Build feature vector dari data terbaru
- [ ] Load model → predict probability
- [ ] Jika probability > threshold (default 0.7) → buat alert "Failure Predicted"
- [ ] Simpan prediction result ke `failure_predictions` table

### 4.2 Migration
- [ ] Migration: `failure_predictions`
  - id, server_id, predicted_at, target_timestamp (1h from now), probability, features (json), was_correct (bool, nullable, diisi retrospektif), actual_incident (bool, nullable)

### 4.3 Feedback Loop
- [ ] `EvaluatePredictionsJob` — jalan setiap 1 jam
- [ ] Cek prediction yang target_timestamp-nya sudah lewat
- [ ] Bandingkan dengan actual alert/incident → update was_correct
- [ ] Simpan accuracy metric → bisa trigger re-train jika accuracy turun

### 4.4 UI
- [ ] "Failure Prediction" card di dashboard
- [ ] Daftar server dengan risk score: "Server A: 85% risk of failure in next hour — CPU trend high + memory leak detected"
- [ ] Prediction history chart (probability over time)
- [ ] Model accuracy dashboard: precision, recall, f1, last trained

### 4.5 Scheduler Update
- [ ] `PredictFailureJob` setiap 5 menit (offset dari metrics collection)
- [ ] `EvaluatePredictionsJob` setiap 1 jam
- [ ] `AggregateMetricsJob` setiap 5 menit
- [ ] `TrainModelJob` manual trigger / mingguan

---

## Acceptance Criteria v2.0

- [ ] Feature vector terbentuk otomatis dari data metric + log + service history
- [ ] Dataset bisa di-export untuk training manual
- [ ] Model Random Forest bisa di-train via artisan command
- [ ] Prediction berjalan otomatis setiap 5 menit
- [ ] Alert "Failure Predicted" muncul dengan probability + faktor kontributor
- [ ] Feedback loop mengevaluasi akurasi prediksi retrospektif
- [ ] Dashboard menampilkan risk score per server
- [ ] Model bisa di-retrain periodik
- [ ] Semua test pass

---

## File Checklist v2.0

```
Models (1)     : FailurePrediction
Controllers (2): PredictionController, ModelController
Services (5)   : MetricsAggregator, FeatureEngineering, LabelGenerator,
                 DatasetBuilder, PredictionEngine
Jobs (4)       : AggregateMetricsJob, PredictFailureJob, EvaluatePredictionsJob, TrainModelJob
Migrations (2) : metric_aggregates, failure_predictions
Commands (1)   : TrainModel Artisan command
Tests (4)      : FeatureEngineeringTest, PredictionEngineTest, FeedbackLoopTest, ModelAccuracyTest
```
