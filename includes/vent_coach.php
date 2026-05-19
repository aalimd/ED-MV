<?php
/**
 * Vent Coach — Clinical Rule Engine
 * ───────────────────────────────────
 * Pure, deterministic functions that take ventilator settings + ABG values
 * and return structured safety analysis with evidence-cited recommendations.
 *
 * Design principles:
 *   • No DB access here — pure functions, easy to unit test.
 *   • No exceptions thrown to callers — all errors handled gracefully.
 *   • All numeric thresholds documented inline with their evidence source.
 *   • Output structure is stable JSON-serialisable arrays.
 *
 * IMPORTANT — Clinical disclaimer:
 *   Outputs are educational guidance based on published evidence (ARDSNet,
 *   SCCM, ATS). They are NOT a substitute for clinical judgement. Each
 *   recommendation includes its source citation so the user can verify it.
 */

declare(strict_types=1);

/**
 * Calculate Predicted Body Weight (Devine formula, used by ARDSNet).
 *
 * @param string $sex 'male' | 'female'
 * @param float  $heightCm
 * @return float|null PBW in kg, or null if inputs are invalid
 */
function vc_calculate_pbw(string $sex, float $heightCm): ?float {
    if ($heightCm < 120 || $heightCm > 250) return null;
    $base = $sex === 'female' ? 45.5 : 50.0;
    $pbw = $base + 0.91 * ($heightCm - 152.4);
    return $pbw > 0 ? round($pbw, 1) : null;
}

/**
 * List of canonical clinical scenarios understood by the coach.
 * Keep the keys aligned with SCENARIOS in app/ventguide_raw.php.
 */
function vc_scenarios(): array {
    return [
        'healthy'     => ['name' => 'Healthy Lungs',          'emoji' => '💙', 'target_vt' => 8.0],
        'sepsis'      => ['name' => 'Sepsis / Septic Shock',  'emoji' => '🦠', 'target_vt' => 6.5],
        'asthma-copd' => ['name' => 'Asthma / COPD',          'emoji' => '🌬️', 'target_vt' => 7.0,  'obstructive' => true],
        'ards'        => ['name' => 'ARDS',                    'emoji' => '🫁', 'target_vt' => 6.0,  'protective'  => true],
        'cardiogenic' => ['name' => 'Cardiogenic Edema',      'emoji' => '❤️', 'target_vt' => 6.5],
        'head-injury' => ['name' => 'Head Injury / TBI',      'emoji' => '🧠', 'target_vt' => 7.0],
        'pregnancy'   => ['name' => 'Pregnancy',                'emoji' => '🤰', 'target_vt' => 7.0],
        'metabolic'   => ['name' => 'Metabolic Acidosis',     'emoji' => '🧪', 'target_vt' => 6.5],
    ];
}

/**
 * Pull a single float from an input array, returning null if missing/invalid.
 */
function vc_num(array $src, string $key, ?float $min = null, ?float $max = null): ?float {
    if (!isset($src[$key]) || $src[$key] === '' || $src[$key] === null) return null;
    $val = is_numeric($src[$key]) ? (float)$src[$key] : null;
    if ($val === null) return null;
    if ($min !== null && $val < $min) return null;
    if ($max !== null && $val > $max) return null;
    return $val;
}

/**
 * Compute derived ventilation parameters from raw inputs.
 *
 * @param array $vent  Ventilator settings (vt_ml, rr, peep, pplat, fio2_pct, ie_ratio_e)
 * @param array $abg   ABG values (ph, paco2, pao2, hco3, spo2)
 * @param float $pbw   Predicted Body Weight in kg
 * @return array       Derived metrics, with nulls where insufficient data
 */
function vc_compute_derived(array $vent, array $abg, ?float $pbw): array {
    $vt    = vc_num($vent, 'vt_ml',    100, 1500);
    $rr    = vc_num($vent, 'rr',         4, 60);
    $peep  = vc_num($vent, 'peep',       0, 25);
    $pplat = vc_num($vent, 'pplat',      0, 60);
    $fio2  = vc_num($vent, 'fio2_pct',  21, 100);
    $pao2  = vc_num($abg,  'pao2',      20, 700);
    $paco2 = vc_num($abg,  'paco2',     10, 150);
    $ph    = vc_num($abg,  'ph',       6.7, 8.0);
    $hco3  = vc_num($abg,  'hco3',       3, 60);
    $spo2  = vc_num($abg,  'spo2',      30, 100);

    $driving      = ($pplat !== null && $peep !== null && $pplat >= $peep) ? round($pplat - $peep, 1) : null;
    $vtPerKg      = ($vt !== null && $pbw && $pbw > 0) ? round($vt / $pbw, 2) : null;
    $minuteVent   = ($vt !== null && $rr !== null) ? round(($vt * $rr) / 1000.0, 2) : null;
    $pfRatio      = ($pao2 !== null && $fio2 !== null && $fio2 > 0) ? (int)round($pao2 / ($fio2 / 100.0)) : null;
    $compliance   = ($vt !== null && $driving !== null && $driving > 0) ? round($vt / $driving, 1) : null;
    $sfRatio      = ($spo2 !== null && $fio2 !== null && $fio2 > 0) ? (int)round($spo2 / ($fio2 / 100.0)) : null;

    return [
        'driving_pressure'  => $driving,    // cmH₂O
        'vt_per_kg'         => $vtPerKg,    // mL/kg PBW
        'minute_ventilation'=> $minuteVent, // L/min
        'pf_ratio'          => $pfRatio,    // mmHg
        'sf_ratio'          => $sfRatio,    // surrogate when no ABG
        'static_compliance' => $compliance, // mL/cmH₂O
    ];
}

/**
 * Interpret the ABG when available.
 *
 * @return array{ disorder:string|null, severity:string|null, summary:string|null }
 */
function vc_interpret_abg(array $abg): array {
    $ph    = vc_num($abg, 'ph',    6.7, 8.0);
    $paco2 = vc_num($abg, 'paco2', 10,  150);
    $hco3  = vc_num($abg, 'hco3',  3,   60);

    if ($ph === null) {
        return ['disorder' => null, 'severity' => null, 'summary' => null];
    }

    $disorder = 'normal';
    $severity = 'normal';
    if ($ph < 7.35) {
        $severity = $ph < 7.20 ? 'severe' : ($ph < 7.30 ? 'moderate' : 'mild');
        if ($paco2 !== null && $paco2 > 45)      $disorder = 'respiratory_acidosis';
        elseif ($hco3 !== null && $hco3 < 22)    $disorder = 'metabolic_acidosis';
        else                                      $disorder = 'acidosis_unspecified';
    } elseif ($ph > 7.45) {
        $severity = $ph > 7.60 ? 'severe' : ($ph > 7.50 ? 'moderate' : 'mild');
        if ($paco2 !== null && $paco2 < 35)      $disorder = 'respiratory_alkalosis';
        elseif ($hco3 !== null && $hco3 > 26)    $disorder = 'metabolic_alkalosis';
        else                                      $disorder = 'alkalosis_unspecified';
    }

    $summaryMap = [
        'normal'                 => 'pH within normal range (7.35–7.45)',
        'respiratory_acidosis'   => 'Respiratory acidosis — high PaCO₂ driving low pH',
        'respiratory_alkalosis'  => 'Respiratory alkalosis — low PaCO₂ driving high pH',
        'metabolic_acidosis'     => 'Metabolic acidosis — low HCO₃ driving low pH',
        'metabolic_alkalosis'    => 'Metabolic alkalosis — high HCO₃ driving high pH',
        'acidosis_unspecified'   => 'Acidosis — incomplete ABG to classify',
        'alkalosis_unspecified'  => 'Alkalosis — incomplete ABG to classify',
    ];
    return [
        'disorder' => $disorder,
        'severity' => $severity,
        'summary'  => $summaryMap[$disorder] ?? null,
    ];
}

/**
 * Internal — build a single alert record.
 */
function vc_alert(string $level, string $icon, string $title, string $detail, string $source): array {
    return [
        'level'  => $level,   // red | yellow | green | info
        'icon'   => $icon,
        'title'  => $title,
        'detail' => $detail,
        'source' => $source,
    ];
}

/**
 * Internal — build a single recommendation record.
 */
function vc_rec(int $priority, string $icon, string $action, string $rationale, string $source): array {
    return [
        'priority'  => $priority, // 1 = most urgent
        'icon'      => $icon,
        'action'    => $action,
        'rationale' => $rationale,
        'source'    => $source,
    ];
}

/**
 * Build the suggested target VT (mL) for the active scenario.
 */
function vc_target_vt_ml(string $scenarioKey, float $pbw): array {
    $sc = vc_scenarios()[$scenarioKey] ?? vc_scenarios()['healthy'];
    $target = (float)($sc['target_vt'] ?? 7.0);
    return [
        'low'    => (int)round(6.0 * $pbw),
        'target' => (int)round($target * $pbw),
        'high'   => (int)round(8.0 * $pbw),
    ];
}

/**
 * MAIN — Analyze a patient case and return structured guidance.
 *
 * @param array $input {
 *   scenario: string,
 *   pbw_kg: float|null,
 *   vent: { vt_ml, rr, peep, pplat, fio2_pct, ie_ratio_e },
 *   abg:  { ph, paco2, pao2, hco3, spo2 },
 *   target_paco2: float|null,
 * }
 * @return array {
 *   safety_level: 'green'|'yellow'|'red',
 *   derived: array,
 *   abg: array,
 *   alerts: array,
 *   recommendations: array,
 *   target_vt: array,
 *   completeness: int 0..100,
 *   missing: string[],
 * }
 */
function vc_analyze(array $input): array {
    $scenarios   = vc_scenarios();
    $scenarioKey = isset($input['scenario']) && isset($scenarios[$input['scenario']])
        ? (string)$input['scenario']
        : 'healthy';
    $scenario    = $scenarios[$scenarioKey];

    $pbw         = isset($input['pbw_kg']) && is_numeric($input['pbw_kg']) ? (float)$input['pbw_kg'] : null;
    if ($pbw !== null && ($pbw < 25 || $pbw > 150)) $pbw = null;

    $vent        = is_array($input['vent'] ?? null) ? $input['vent'] : [];
    $abg         = is_array($input['abg']  ?? null) ? $input['abg']  : [];
    $derived     = vc_compute_derived($vent, $abg, $pbw);
    $abgInterp   = vc_interpret_abg($abg);

    $alerts = [];
    $recs   = [];

    // ─── Track which inputs are present (for completeness scoring) ─────
    $expected = ['vt_ml','rr','peep','pplat','fio2_pct'];
    $present  = 0;
    $missing  = [];
    foreach ($expected as $k) {
        if (vc_num($vent, $k) !== null) $present++; else $missing[] = $k;
    }
    if ($pbw !== null) $present++;
    $totalExpected = count($expected) + 1;
    $completeness = (int)round(($present / $totalExpected) * 100);

    // ─── Plateau Pressure ─────────────────────────────────────────────
    $pplat = vc_num($vent, 'pplat');
    if ($pplat !== null) {
        if ($pplat > 30) {
            $alerts[] = vc_alert('red', '🚨', 'Plateau pressure too high',
                "Pplat {$pplat} cmH₂O exceeds the 30 cmH₂O ceiling — barotrauma risk.",
                'ARDSNet NEJM 2000');
            $recs[] = vc_rec(1, '⬇️', 'Reduce VT until Pplat ≤ 30 cmH₂O',
                'High Pplat is the strongest predictor of ventilator-induced lung injury.',
                'ARDSNet NEJM 2000');
        } elseif ($pplat >= 28) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Plateau pressure borderline',
                "Pplat {$pplat} cmH₂O — close to the 30 cmH₂O ceiling.",
                'ARDSNet NEJM 2000');
        }
    }

    // ─── Driving Pressure ─────────────────────────────────────────────
    $dp = $derived['driving_pressure'];
    if ($dp !== null) {
        if ($dp > 15) {
            $alerts[] = vc_alert('red', '🚨', 'Driving pressure too high',
                "ΔP {$dp} cmH₂O — mortality rises sharply when ΔP > 15.",
                'Amato NEJM 2015');
            $recs[] = vc_rec(1, '⬇️', 'Lower VT first; if still high, gently titrate PEEP',
                'Driving pressure (Pplat − PEEP) is a strong independent predictor of mortality.',
                'Amato NEJM 2015');
        } elseif ($dp >= 13) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Driving pressure borderline',
                "ΔP {$dp} cmH₂O — keep an eye on lung-protective ventilation.",
                'Amato NEJM 2015');
        }
    }

    // ─── Tidal volume per PBW ─────────────────────────────────────────
    $vtPerKg = $derived['vt_per_kg'];
    $vt      = vc_num($vent, 'vt_ml');
    if ($vtPerKg !== null && $pbw !== null && $vt !== null) {
        if ($vtPerKg > 8) {
            $alerts[] = vc_alert('red', '🚨', 'Tidal volume above safe range',
                "VT {$vt} mL = {$vtPerKg} mL/kg PBW (target 6–8).",
                'ARDSNet NEJM 2000');
            $recs[] = vc_rec(1, '⬇️',
                'Reduce VT toward ' . vc_target_vt_ml($scenarioKey, $pbw)['target'] . ' mL',
                'Volutrauma risk rises sharply above 8 mL/kg PBW.',
                'ARDSNet NEJM 2000');
        } elseif ($vtPerKg > 7) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Tidal volume at upper limit',
                "VT {$vt} mL = {$vtPerKg} mL/kg PBW — keep watch.",
                'ARDSNet NEJM 2000');
        } elseif ($vtPerKg < 5) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Tidal volume very low',
                "VT {$vt} mL = {$vtPerKg} mL/kg PBW — confirm adequate minute ventilation.",
                'ARDSNet NEJM 2000');
        }
    }

    // ─── pH / acid-base ───────────────────────────────────────────────
    $ph = vc_num($abg, 'ph', 6.7, 8.0);
    if ($ph !== null) {
        if ($ph < 7.20) {
            $alerts[] = vc_alert('red', '🚨', 'Severe acidaemia',
                "pH {$ph} — risk of haemodynamic collapse.",
                'SCCM/ATS');
            if (!empty($scenario['obstructive'])) {
                $recs[] = vc_rec(1, '🩺',
                    'In obstructive disease, look for auto-PEEP / breath stacking before chasing PaCO₂',
                    'Permissive hypercapnia is preferred — but pH < 7.20 needs cause-directed action.',
                    'Permissive Hypercapnia Review 2025');
            } else {
                $recs[] = vc_rec(2, '⬆️', 'Cautiously increase RR (avoid auto-PEEP) and/or address metabolic source',
                    'Severe acidaemia worsens cardiac contractility and vasopressor response.',
                    'SSC 2026');
            }
        } elseif ($ph > 7.55) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Significant alkalaemia',
                "pH {$ph} — reduce minute ventilation if respiratory in origin.",
                'SCCM/ATS');
        }
    }

    // ─── Oxygenation (PF or SF ratio) ─────────────────────────────────
    $pf = $derived['pf_ratio'];
    $sf = $derived['sf_ratio'];
    if ($pf !== null) {
        if ($pf < 100) {
            $alerts[] = vc_alert('red', '🚨', 'Severe ARDS-range oxygenation',
                "PaO₂/FiO₂ = {$pf} — severe ARDS range.",
                'Berlin Definition 2012');
            $recs[] = vc_rec(2, '🛏️', 'Consider prone positioning, neuromuscular blockade, and ARDSNet PEEP table',
                'Prone positioning improves mortality in PF < 150.',
                'PROSEVA NEJM 2013');
        } elseif ($pf < 200) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Moderate ARDS-range oxygenation',
                "PaO₂/FiO₂ = {$pf}.",
                'Berlin Definition 2012');
        }
    } elseif ($sf !== null && $sf < 235) {
        $alerts[] = vc_alert('yellow', '⚠️', 'Surrogate suggests poor oxygenation',
            "SpO₂/FiO₂ = {$sf} (PF < 200 likely).",
            'Rice CHEST 2007');
    }

    // ─── FiO₂ high without ABG ────────────────────────────────────────
    $fio2 = vc_num($vent, 'fio2_pct');
    if ($fio2 !== null && $fio2 > 60 && $pf === null) {
        $alerts[] = vc_alert('yellow', '⚠️', 'High FiO₂',
            "FiO₂ {$fio2}% — get an ABG to assess oxygenation precisely.",
            'BTS 2017');
    }

    // ─── PaCO₂ vs target — bedside RR formula ─────────────────────────
    $paco2 = vc_num($abg, 'paco2');
    $rr    = vc_num($vent, 'rr');
    $targetPaco2 = vc_num($input, 'target_paco2', 25, 80);
    if ($paco2 !== null && $rr !== null) {
        $target = $targetPaco2 ?? 40.0;
        // Permissive hypercapnia in obstructive disease — only chase if pH < 7.20.
        if (!empty($scenario['obstructive']) && ($ph === null || $ph >= 7.20)) {
            $alerts[] = vc_alert('info', 'ℹ️', 'Permissive hypercapnia is OK here',
                "Do not chase PaCO₂ = 40 in obstructive disease while pH ≥ 7.20.",
                'Permissive Hypercapnia Review 2025');
        } else {
            $delta = abs($paco2 - $target);
            if ($delta >= 5) {
                $newRR = (int)round($rr * ($paco2 / $target));
                $newRR = max(6, min(35, $newRR));
                if ($newRR !== (int)$rr) {
                    $recs[] = vc_rec(3, '⏱️',
                        "Adjust RR from {$rr} → {$newRR} to move PaCO₂ toward {$target}",
                        'Bedside formula: new RR = current RR × (PaCO₂ / target).',
                        'Bedside ABG correction');
                }
            }
        }
    }

    // ─── Auto-PEEP risk (obstructive scenarios) ───────────────────────
    if (!empty($scenario['obstructive']) && $rr !== null && $rr > 14) {
        $alerts[] = vc_alert('yellow', '⚠️', 'Auto-PEEP risk',
            "RR {$rr}/min in obstructive disease — risk of breath stacking.",
            'Obstructive ventilation strategy');
        $recs[] = vc_rec(2, '⏱️',
            'Drop RR to 8–12, lengthen expiratory time (I:E 1:4 or longer)',
            'Long expiratory time prevents dynamic hyperinflation.',
            'EMCrit / Rosen ED Airway');
    }

    // ─── PEEP sanity vs FiO₂ ──────────────────────────────────────────
    $peep = vc_num($vent, 'peep');
    if ($peep !== null && $fio2 !== null) {
        if ($fio2 >= 70 && $peep < 8) {
            $recs[] = vc_rec(3, '⬆️',
                "Consider raising PEEP (current {$peep}) before pushing FiO₂ {$fio2}% higher",
                'Low PEEP/High FiO₂ table — recruitment may reduce FiO₂ demand.',
                'ARDSNet PEEP table');
        }
        if (!empty($scenario['obstructive']) && $peep > 8) {
            $alerts[] = vc_alert('yellow', '⚠️', 'Externally applied PEEP may worsen air-trapping',
                "PEEP {$peep} in obstructive disease — verify it is below intrinsic PEEP.",
                'Obstructive ventilation strategy');
        }
    }

    // ─── Scenario-specific positive feedback ──────────────────────────
    if (empty($alerts) && $completeness >= 80) {
        $alerts[] = vc_alert('green', '✅', 'Within evidence-based safety targets',
            'All checked parameters are within lung-protective ranges.',
            'ARDSNet / SCCM / ATS');
    }

    // ─── Determine overall safety level ───────────────────────────────
    $hasRed    = false;
    $hasYellow = false;
    foreach ($alerts as $a) {
        if ($a['level'] === 'red')    $hasRed    = true;
        if ($a['level'] === 'yellow') $hasYellow = true;
    }
    $level = $hasRed ? 'red' : ($hasYellow ? 'yellow' : 'green');

    // ─── Sort recommendations by priority ────────────────────────────
    usort($recs, fn($a, $b) => $a['priority'] <=> $b['priority']);

    return [
        'safety_level'    => $level,
        'derived'         => $derived,
        'abg'             => $abgInterp,
        'alerts'          => $alerts,
        'recommendations' => $recs,
        'target_vt'       => $pbw !== null ? vc_target_vt_ml($scenarioKey, $pbw) : null,
        'completeness'    => $completeness,
        'missing'         => $missing,
        'scenario'        => [
            'key'   => $scenarioKey,
            'name'  => $scenario['name'],
            'emoji' => $scenario['emoji'],
        ],
    ];
}

/**
 * Sanitize a user-supplied case payload into the strict shape expected by vc_analyze().
 * Strings outside numeric ranges become null. Unknown scenarios fall back to 'healthy'.
 */
function vc_sanitize_input(array $raw): array {
    $scenarios = vc_scenarios();
    $scenario  = isset($raw['scenario']) && is_string($raw['scenario']) && isset($scenarios[$raw['scenario']])
        ? $raw['scenario']
        : 'healthy';

    $pbw = isset($raw['pbw_kg']) && is_numeric($raw['pbw_kg']) ? (float)$raw['pbw_kg'] : null;
    if ($pbw !== null && ($pbw < 25 || $pbw > 150)) $pbw = null;

    $vent = is_array($raw['vent'] ?? null) ? $raw['vent'] : [];
    $abg  = is_array($raw['abg']  ?? null) ? $raw['abg']  : [];

    return [
        'scenario'     => $scenario,
        'pbw_kg'       => $pbw,
        'target_paco2' => vc_num($raw, 'target_paco2', 25, 80),
        'vent' => [
            'vt_ml'    => vc_num($vent, 'vt_ml',    100, 1500),
            'rr'       => vc_num($vent, 'rr',         4,   60),
            'peep'     => vc_num($vent, 'peep',       0,   25),
            'pplat'    => vc_num($vent, 'pplat',      0,   60),
            'fio2_pct' => vc_num($vent, 'fio2_pct',  21,  100),
        ],
        'abg' => [
            'ph'    => vc_num($abg, 'ph',    6.7, 8.0),
            'paco2' => vc_num($abg, 'paco2', 10,  150),
            'pao2'  => vc_num($abg, 'pao2',  20,  700),
            'hco3'  => vc_num($abg, 'hco3',   3,   60),
            'spo2'  => vc_num($abg, 'spo2',  30,  100),
        ],
    ];
}
