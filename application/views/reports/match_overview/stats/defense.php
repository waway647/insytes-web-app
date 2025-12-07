<!-- type:defense / defense.php -->

<?php
// ---------- defense metrics ----------
$home_tackles = $home_metrics['defense']['tackles'] ?? null;
$away_tackles = $away_metrics['defense']['tackles'] ?? null;
$total_tackles = ($home_tackles ?? 0) + ($away_tackles ?? 0);
$home_tackles_pct = $total_tackles > 0 ? ($home_tackles / $total_tackles * 100) : null;

$home_successful_tackles = $home_metrics['defense']['successful_tackles'] ?? null;
$away_successful_tackles = $away_metrics['defense']['successful_tackles'] ?? null;
$total_successful_tackles = ($home_successful_tackles ?? 0) + ($away_successful_tackles ?? 0);
$home_successful_tackles_pct = $total_successful_tackles > 0 ? ($home_successful_tackles / $total_successful_tackles * 100) : null;

// tackles success rate is already a pct value per-team (e.g. 71.43)
$home_tackles_success_rate_pct = $home_metrics['defense']['tackles_success_rate_pct'] ?? null;
$away_tackles_success_rate_pct = $away_metrics['defense']['tackles_success_rate_pct'] ?? null;

$home_clearances = $home_metrics['defense']['clearances'] ?? null;
$away_clearances = $away_metrics['defense']['clearances'] ?? null;
$total_clearances = ($home_clearances ?? 0) + ($away_clearances ?? 0);
$home_clearances_pct = $total_clearances > 0 ? ($home_clearances / $total_clearances * 100) : null;

$home_interceptions = $home_metrics['defense']['interceptions'] ?? null;
$away_interceptions = $away_metrics['defense']['interceptions'] ?? null;
$total_interceptions = ($home_interceptions ?? 0) + ($away_interceptions ?? 0);
$home_interceptions_pct = $total_interceptions > 0 ? ($home_interceptions / $total_interceptions * 100) : null;

$home_recoveries = $home_metrics['defense']['recoveries'] ?? null;
$away_recoveries = $away_metrics['defense']['recoveries'] ?? null;
$total_recoveries = ($home_recoveries ?? 0) + ($away_recoveries ?? 0);
$home_recoveries_pct = $total_recoveries > 0 ? ($home_recoveries / $total_recoveries * 100) : null;

$home_recoveries_att_third = $home_metrics['defense']['recoveries_attacking_third'] ?? null;
$away_recoveries_att_third = $away_metrics['defense']['recoveries_attacking_third'] ?? null;
$total_recoveries_att_third = ($home_recoveries_att_third ?? 0) + ($away_recoveries_att_third ?? 0);
$home_recoveries_att_third_pct = $total_recoveries_att_third > 0 ? ($home_recoveries_att_third / $total_recoveries_att_third * 100) : null;

// recoveries_attacking_third_pct is given as per-team percentage (e.g. 5.26)
$home_recoveries_att_third_pct_value = $home_metrics['defense']['recoveries_attacking_third_pct'] ?? null;
$away_recoveries_att_third_pct_value = $away_metrics['defense']['recoveries_attacking_third_pct'] ?? null;

$home_blocks = $home_metrics['defense']['blocks'] ?? null;
$away_blocks = $away_metrics['defense']['blocks'] ?? null;
$total_blocks = ($home_blocks ?? 0) + ($away_blocks ?? 0);
$home_blocks_pct = $total_blocks > 0 ? ($home_blocks / $total_blocks * 100) : null;

$home_saves = $home_metrics['defense']['saves'] ?? null;
$away_saves = $away_metrics['defense']['saves'] ?? null;
$total_saves = ($home_saves ?? 0) + ($away_saves ?? 0);
$home_saves_pct = $total_saves > 0 ? ($home_saves / $total_saves * 100) : null;
?>

<!-- Defense section -->
<div class="w-full flex flex-col items-center px-20 py-8 gap-4">
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Tackles</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_tackles !== null ? number_format($home_tackles, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_tackles_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_tackles_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_tackles !== null ? number_format($away_tackles, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Successful tackles</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_successful_tackles !== null ? number_format($home_successful_tackles, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_successful_tackles_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_successful_tackles_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_successful_tackles !== null ? number_format($away_successful_tackles, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- Tackles success rate (per-team percentage comparator) -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Tackles success rate</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_tackles_success_rate_pct !== null ? number_format($home_tackles_success_rate_pct, 0) . '%' : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_tackles_success_rate_pct !== null && $away_tackles_success_rate_pct !== null): ?>
                    <?php
                        $sumTackleRate = $home_tackles_success_rate_pct + $away_tackles_success_rate_pct;
                        $homeTackleRateWidth = $sumTackleRate > 0 ? ($home_tackles_success_rate_pct / $sumTackleRate * 100) : 50;
                    ?>
                    <div style="height:100%; width:<?= min(max($homeTackleRateWidth,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_tackles_success_rate_pct !== null ? number_format($away_tackles_success_rate_pct, 0) . '%' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Clearances</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_clearances !== null ? number_format($home_clearances, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_clearances_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_clearances_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_clearances !== null ? number_format($away_clearances, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Interceptions</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_interceptions !== null ? number_format($home_interceptions, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_interceptions_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_interceptions_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_interceptions !== null ? number_format($away_interceptions, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Recoveries</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_recoveries !== null ? number_format($home_recoveries, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_recoveries_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_recoveries_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_recoveries !== null ? number_format($away_recoveries, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Recoveries (attacking third)</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_recoveries_att_third !== null ? number_format($home_recoveries_att_third, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_recoveries_att_third_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_recoveries_att_third_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_recoveries_att_third !== null ? number_format($away_recoveries_att_third, 0) : '—' ?>
            </span>
        </div>
    </div>

    <!-- recoveries_attacking_third_pct (per-team percentage comparator) -->
    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Recoveries attacking third (%)</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_recoveries_att_third_pct_value !== null ? number_format($home_recoveries_att_third_pct_value, 0) . '%' : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_recoveries_att_third_pct_value !== null && $away_recoveries_att_third_pct_value !== null): ?>
                    <?php
                        $sumRecAtt = $home_recoveries_att_third_pct_value + $away_recoveries_att_third_pct_value;
                        $homeRecAttWidth = $sumRecAtt > 0 ? ($home_recoveries_att_third_pct_value / $sumRecAtt * 100) : 50;
                    ?>
                    <div style="height:100%; width:<?= min(max($homeRecAttWidth,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_recoveries_att_third_pct_value !== null ? number_format($away_recoveries_att_third_pct_value, 0) . '%' : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Blocks</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_blocks !== null ? number_format($home_blocks, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_blocks_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_blocks_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_blocks !== null ? number_format($away_blocks, 0) : '—' ?>
            </span>
        </div>
    </div>

    <div class="w-full flex flex-col items-center">
        <p class="text-sm text-[#B6BABD]">Saves</p>
        <div class="w-full flex justify-center items-center gap-5">
            <span class="text-xl text-white font-semibold">
                <?= $home_saves !== null ? number_format($home_saves, 0) : '—' ?>
            </span>

            <div id="percentage-bar" class="w-150 h-2.5 bg-[#2a2a2a] rounded-2xl">
                <?php if ($home_saves_pct !== null): ?>
                    <div style="height:100%; width:<?= min(max($home_saves_pct,0),100) ?>%; background:linear-gradient(90deg,#209435,#7ad47a); border-radius:12px;"></div>
                <?php endif; ?>
            </div>

            <span class="text-xl text-white font-semibold">
                <?= $away_saves !== null ? number_format($away_saves, 0) : '—' ?>
            </span>
        </div>
    </div>
</div>