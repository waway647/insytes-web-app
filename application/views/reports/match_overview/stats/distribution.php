<!-- type:distribution / distribution.php -->

<?php
// duels
$home_passes  = $home_metrics['distribution']['passes'] ?? null;
$away_passes  = $away_metrics['distribution']['passes'] ?? null;
$total_passes = $home_passes + $away_passes;
$home_passes_pct = $home_passes / $total_passes * 100;
// successful passes
$home_successful_passes  = $home_metrics['distribution']['successful_passes'] ?? null;
$away_successful_passes  = $away_metrics['distribution']['successful_passes'] ?? null;
$total_successful_passes  = $home_passes + $away_passes;
$home_successful_passes_pct  = $home_successful_passes / $total_successful_passes * 100;
// intercepted passes
$home_intercepted_passes  = $home_metrics['distribution']['intercepted_passes'] ?? null;
$away_intercepted_passes  = $away_metrics['distribution']['intercepted_passes'] ?? null;
$total_intercepted_passes  = $home_passes + $away_passes;
$home_intercepted_passes_pct  = $home_successful_passes / $total_successful_passes * 100;
// unsuccessful passes
$home_unsuccessful_passes = $home_metrics['distribution']['unsuccessful_passes'] ?? null;
$away_unsuccessful_passes = $away_metrics['distribution']['unsuccessful_passes'] ?? null;
$total_unsuccessful_passes = ($home_unsuccessful_passes ?? 0) + ($away_unsuccessful_passes ?? 0);
$home_unsuccessful_passes_pct = $total_unsuccessful_passes > 0 ? ($home_unsuccessful_passes / $total_unsuccessful_passes * 100) : null;

// short passes
$home_short_passes = $home_metrics['distribution']['short_passes'] ?? null;
$away_short_passes = $away_metrics['distribution']['short_passes'] ?? null;
$total_short_passes = ($home_short_passes ?? 0) + ($away_short_passes ?? 0);
$home_short_passes_pct = $total_short_passes > 0 ? ($home_short_passes / $total_short_passes * 100) : null;

// long passes
$home_long_passes = $home_metrics['distribution']['long_passes'] ?? null;
$away_long_passes = $away_metrics['distribution']['long_passes'] ?? null;
$total_long_passes = ($home_long_passes ?? 0) + ($away_long_passes ?? 0);
$home_long_passes_pct = $total_long_passes > 0 ? ($home_long_passes / $total_long_passes * 100) : null;

// crosses
$home_crosses = $home_metrics['distribution']['crosses'] ?? null;
$away_crosses = $away_metrics['distribution']['crosses'] ?? null;
$total_crosses = ($home_crosses ?? 0) + ($away_crosses ?? 0);
$home_crosses_pct = $total_crosses > 0 ? ($home_crosses / $total_crosses * 100) : null;

// through passes
$home_through_passes = $home_metrics['distribution']['through_passes'] ?? null;
$away_through_passes = $away_metrics['distribution']['through_passes'] ?? null;
$total_through_passes = ($home_through_passes ?? 0) + ($away_through_passes ?? 0);
$home_through_passes_pct = $total_through_passes > 0 ? ($home_through_passes / $total_through_passes * 100) : null;

// forward / lateral / back passes (percent relative to each metric's total)
$home_forward_passes = $home_metrics['distribution']['forward_passes'] ?? null;
$away_forward_passes = $away_metrics['distribution']['forward_passes'] ?? null;
$total_forward_passes = ($home_forward_passes ?? 0) + ($away_forward_passes ?? 0);
$home_forward_passes_pct = $total_forward_passes > 0 ? ($home_forward_passes / $total_forward_passes * 100) : null;

$home_lateral_passes = $home_metrics['distribution']['lateral_passes'] ?? null;
$away_lateral_passes = $away_metrics['distribution']['lateral_passes'] ?? null;
$total_lateral_passes = ($home_lateral_passes ?? 0) + ($away_lateral_passes ?? 0);
$home_lateral_passes_pct = $total_lateral_passes > 0 ? ($home_lateral_passes / $total_lateral_passes * 100) : null;

$home_back_passes = $home_metrics['distribution']['back_passes'] ?? null;
$away_back_passes = $away_metrics['distribution']['back_passes'] ?? null;
$total_back_passes = ($home_back_passes ?? 0) + ($away_back_passes ?? 0);
$home_back_passes_pct = $total_back_passes > 0 ? ($home_back_passes / $total_back_passes * 100) : null;

// passing accuracy (per-team percentages if available)
$home_passing_accuracy_pct = $home_metrics['distribution']['passing_accuracy_pct'] ?? null;
$away_passing_accuracy_pct = $away_metrics['distribution']['passing_accuracy_pct'] ?? null;

// assists & key passes (counts)
$home_assists = $home_metrics['distribution']['assists'] ?? null;
$away_assists = $away_metrics['distribution']['assists'] ?? null;

$home_key_passes = $home_metrics['distribution']['key_passes'] ?? null;
$away_key_passes = $away_metrics['distribution']['key_passes'] ?? null;
$total_key_passes = ($home_key_passes ?? 0) + ($away_key_passes ?? 0);
$home_key_passes_pct = $total_key_passes > 0 ? ($home_key_passes / $total_key_passes * 100) : null;
?>

<div class="w-full flex flex-col items-center px-20 py-8 gap-4">
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_passes !== null ? number_format($home_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_passes !== null ? number_format($away_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Successful passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_successful_passes !== null ? number_format($home_successful_passes, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_successful_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_successful_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_successful_passes !== null ? number_format($away_successful_passes, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Intercepted passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_intercepted_passes !== null ? number_format($home_intercepted_passes, 0) . '' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_intercepted_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_intercepted_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_intercepted_passes !== null ? number_format($away_intercepted_passes, 0) . '' : '—' ?>
            </span>
        </div>
    </div>

    <!-- unsuccessful passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Unsuccessful passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_unsuccessful_passes !== null ? number_format($home_unsuccessful_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_unsuccessful_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_unsuccessful_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_unsuccessful_passes !== null ? number_format($away_unsuccessful_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- short passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Short passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_short_passes !== null ? number_format($home_short_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_short_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_short_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_short_passes !== null ? number_format($away_short_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- long passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Long passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_long_passes !== null ? number_format($home_long_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_long_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_long_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_long_passes !== null ? number_format($away_long_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- crosses -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Crosses</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_crosses !== null ? number_format($home_crosses, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_crosses_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_crosses_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_crosses !== null ? number_format($away_crosses, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- through passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Through passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_through_passes !== null ? number_format($home_through_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_through_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_through_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_through_passes !== null ? number_format($away_through_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- forward passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Forward passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_forward_passes !== null ? number_format($home_forward_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_forward_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_forward_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_forward_passes !== null ? number_format($away_forward_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- lateral passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Lateral passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_lateral_passes !== null ? number_format($home_lateral_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_lateral_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_lateral_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_lateral_passes !== null ? number_format($away_lateral_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- back passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Back passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_back_passes !== null ? number_format($home_back_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_back_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_back_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_back_passes !== null ? number_format($away_back_passes, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- passing accuracy (per-team %) -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Passing accuracy</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_passing_accuracy_pct !== null ? number_format($home_passing_accuracy_pct, 0) . '%' : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_passing_accuracy_pct !== null && $away_passing_accuracy_pct !== null): ?>
                    <?php
                        // Build a comparative width where we map home% and away% to a shared 100 width.
                        // Avoid dividing by zero; map by comparing absolute values.
                        $sumAcc = $home_passing_accuracy_pct + $away_passing_accuracy_pct;
                        $homeAccWidth = $sumAcc > 0 ? ($home_passing_accuracy_pct / $sumAcc * 100) : 50;
                    ?>
                    <div style="height:100%; width:<?= min(max($homeAccWidth,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_passing_accuracy_pct !== null ? number_format($away_passing_accuracy_pct, 0) . '%' : '—' ?>
            </span>
        </div>
    </div>

    <!-- assists -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Assists</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_assists !== null ? number_format($home_assists, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php
                $total_assists = ($home_assists ?? 0) + ($away_assists ?? 0);
                $home_assists_pct = $total_assists > 0 ? ($home_assists / $total_assists * 100) : null;
                ?>
                <?php if ($home_assists_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_assists_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_assists !== null ? number_format($away_assists, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- key passes -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Key passes</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_key_passes !== null ? number_format($home_key_passes, 0) : '—' ?>
            </span>
            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_key_passes_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_key_passes_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>
            <span class="text-xl text-white font-semibold">
                <?= $away_key_passes !== null ? number_format($away_key_passes, 0) : '—' ?>
            </span>
        </div>
    </div>
</div>