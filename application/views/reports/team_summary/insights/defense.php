<?php
// defensive defaults
$overall_assessment = $overall_assessment ?? '';
$strength = is_array($strength) ? $strength : [];
$areas_for_improvement = is_array($areas_for_improvement) ? $areas_for_improvement : [];
$key_metrics = is_array($key_metrics) ? $key_metrics : [];
$performance_rating = $performance_rating ?? '';
$tactical_summary = $tactical_summary ?? '';
$key_observations = is_array($key_observations) ? $key_observations : [];
$coaching_priorities = is_array($coaching_priorities) ? $coaching_priorities : [];
$next_training_focus = $next_training_focus ?? '';

/** Basic HTML escape helper */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/**
 * Format shooting accuracy value:
 * - strips non-numeric chars
 * - parses float
 * - rounds UP to nearest whole number (ceil)
 * - appends '%' sign
 * Falls back to original safely-escaped string if parsing fails.
 */
function format_shooting_accuracy($raw) {
    $s = (string)$raw;
    $numstr = preg_replace('/[^0-9.]/', '', $s);
    if ($numstr === '' || !is_numeric($numstr)) {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
    $num = floatval($numstr);
    $rounded = (int) ceil($num);
    return $rounded . '%';
}

/**
 * Replace percentages and decimal numbers in a string by rounding UP to whole numbers.
 * - Converts "14.3%" => "15%"
 * - Converts "38.9" => "39"
 * - Leaves integers and non-numeric text alone.
 *
 * Note: Operates on string and returns modified string (not escaped).
 */
function round_up_numbers_in_text(string $text): string {
    if ($text === '' || $text === null) return $text;

    // 1) Percentages like "14.3%" or "14.3 %"
    $text = preg_replace_callback('/(\d+(?:\.\d+)?)\s*%/', function($m) {
        $n = floatval($m[1]);
        return ((int)ceil($n)) . '%';
    }, $text);

    // 2) Decimal numbers not adjacent to other digits (avoid touching things like IPs)
    // capture sequences like 38.9, 0.75, but not "2025-12-01" or "v1.2.3" (best-effort)
    $text = preg_replace_callback('/(?<=\D|^)(\d+\.\d+)(?=\D|$)/', function($m) {
        $n = floatval($m[1]);
        return (string)((int)ceil($n));
    }, $text);

    return $text;
}

/** Convenience: run rounding then escape for safe HTML output */
function process_and_escape($raw) {
    $s = (string)$raw;
    $s2 = round_up_numbers_in_text($s);
    return htmlspecialchars($s2, ENT_QUOTES, 'UTF-8');
}
?>

<div class="w-full flex flex-col gap-6 font-sans antialiased text-slate-200">
    <div class="w-full p-4">
        <p class="text-xl font-semibold text-white"><?= e($overall_assessment ?: ''); ?></p>
    </div>

    <div class="w-full flex flex-col lg:flex-row gap-6 items-start p-4">
        <div class="w-full lg:w-1/2 flex flex-col gap-4 p-4 rounded-2xl bg-gradient-to-b from-[#0f0f10] to-[#0b0b0b] border border-white/6 shadow-sm hover:shadow-md transition">
            <p class="text-sm text-white font-semibold">Strengths</p>
            <div class="w-full flex flex-col md:flex-row gap-4">
                <?php if (!empty($strength)): ?>
                    <?php foreach ($strength as $s): ?>
                        <span class="flex-1 px-3 py-2 bg-white/3 rounded-lg text-sm text-center text-slate-300 break-words">
                            <?= process_and_escape($s ?? ''); ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-sm text-slate-400">—</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex flex-col gap-4 p-4 rounded-2xl bg-gradient-to-b from-[#0f0f10] to-[#0b0b0b] border border-white/6 shadow-sm hover:shadow-md transition">
            <p class="text-sm text-white font-semibold">Areas for Improvement</p>
            <div class="w-full flex flex-col md:flex-row gap-4">
                <?php if (!empty($areas_for_improvement)): ?>
                    <?php foreach ($areas_for_improvement as $a): ?>
                        <span class="flex-1 px-3 py-2 bg-white/3 rounded-lg text-sm text-center text-slate-300 break-words">
                            <?= process_and_escape($a ?? ''); ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-sm text-slate-400">—</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="w-full flex flex-col gap-4 p-4">
        <p class="text-sm text-white font-semibold">Key Metrics</p>
        <div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
            <?php
            // nicely map common keys and provide fallback label/value pairs
            $metrics_map = [
                'tackles_attempted' => 'Tackles Attempted',
                'tackle_success_rate' => 'Tackle Success Rate',
                'interceptions' => 'Interceptions',
                'recoveries' => 'Recoveries',
                'clearances' => 'Clearances',
                'blocks' => 'Blocks',
                'saves' => 'Saves'
            ];
            foreach ($metrics_map as $k => $label):
                $val = $key_metrics[$k] ?? '';
            ?>
            <div class="w-full p-5 bg-[#121212] rounded-xl flex flex-col items-center gap-2 border border-white/5 shadow-sm hover:shadow-md transition transform hover:-translate-y-0.5">
                <?php if ($k === 'tackle_success_rate'): ?>
                    <span class="text-3xl text-white font-bold"><?= format_shooting_accuracy($val) ?></span>
                <?php else: ?>
                    <span class="text-3xl text-white font-bold"><?= e($val) ?></span>
                <?php endif; ?>
                <span class="text-sm text-slate-400 text-center"><?= e($label) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="w-full flex flex-col gap-4 p-4">
        <p class="text-sm text-white font-semibold">Coaching Assessment</p>
        <div class="w-full px-6 py-6 bg-gradient-to-b from-[#0f0f10] to-[#0b0b0b] rounded-2xl border border-white/6 shadow-sm">
            <div class="w-full flex flex-col divide-y divide-white/6">
                <div class="w-full flex flex-col gap-3 py-4">
                    <div class="flex flex-col md:flex-row md:items-start md:gap-4">
                        <p class="text-md text-white font-medium md:w-48">1. Performance Rating:</p>
                        <p class="text-sm text-slate-400 break-words"><?= process_and_escape($performance_rating) ?></p>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-3 py-4">
                    <div class="flex flex-col md:flex-row md:items-start md:gap-4">
                        <p class="text-md text-white font-medium md:w-48">2. Tactical Summary:</p>
                        <p class="text-sm text-slate-400 break-words"><?= process_and_escape($tactical_summary) ?></p>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-3 py-4">
                    <div class="flex flex-col md:flex-row md:gap-4">
                        <p class="text-md text-white font-medium md:w-48">3. Key Observations:</p>
                        <div class="text-sm text-slate-400 space-y-1">
                            <?php if (!empty($key_observations)): ?>
                                <?php foreach ($key_observations as $obs): ?>
                                    <p class="break-words">- <?= process_and_escape($obs) ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="break-words">-</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-3 py-4">
                    <div class="flex flex-col md:flex-row md:gap-4">
                        <p class="text-md text-white font-medium md:w-48">4. Coaching Priorities:</p>
                        <div class="text-sm text-slate-400 space-y-1">
                            <?php if (!empty($coaching_priorities)): ?>
                                <?php foreach ($coaching_priorities as $p): ?>
                                    <p class="break-words">- <?= process_and_escape($p) ?></p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="break-words">-</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="w-full flex flex-col gap-3 py-4">
                    <div class="flex flex-col md:flex-row md:items-start md:gap-4">
                        <p class="text-md text-white font-medium md:w-48">5. Next Training Focus:</p>
                        <p class="text-sm text-slate-400 break-words"><?= process_and_escape($next_training_focus) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
