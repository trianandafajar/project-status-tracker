<?php

namespace App\Listeners;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Events\MetricCollected;
use App\Models\Alert;
use App\Models\AlertRule;
use Carbon\Carbon;

class EvaluateAlertRules
{
    public function handle(MetricCollected $event): void
    {
        $rules = AlertRule::where('server_id', $event->server->id)
            ->where('metric_type', $event->metric->type->value)
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->isThresholdExceeded($event->metric->value, $rule->operator, $rule->threshold)) {
                if ($this->hasOpenAlert($rule)) {
                    continue;
                }

                Alert::create([
                    'server_id' => $event->server->id,
                    'alert_rule_id' => $rule->id,
                    'title' => $rule->name,
                    'message' => "{$rule->name}: {$event->metric->value}{$event->metric->unit} exceeds threshold {$rule->threshold}",
                    'severity' => $rule->severity,
                    'status' => AlertStatus::Open->value,
                    'triggered_at' => Carbon::now(),
                ]);
            }
        }
    }

    private function isThresholdExceeded(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '==' => $value == $threshold,
            '!=' => $value != $threshold,
            default => false,
        };
    }

    private function hasOpenAlert(AlertRule $rule): bool
    {
        return Alert::where('alert_rule_id', $rule->id)
            ->whereIn('status', [AlertStatus::Open->value, AlertStatus::Acknowledged->value])
            ->where('triggered_at', '>=', Carbon::now()->subMinutes($rule->cooldown_minutes ?: 5))
            ->exists();
    }
}
